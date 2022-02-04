<?php

namespace App\Controller;

use App\Entity\Media;
use App\Form\MediaFormType;
use App\Services\MediaService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;


class MediaController extends AbstractController
{
    /**
     * @var MediaService
     */
    private MediaService $mediaService;

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $em;

    /**
     * @var GenericController
     */
    private GenericController $genericController;


    public function __construct(MediaService $mediaService,EntityManagerInterface $em, GenericController $genericController)
    {
        /*$this->trickServices = $trickServices;*/
        $this->mediaService = $mediaService;
        $this->em=$em;
        $this->genericController=$genericController;
        /*$this->commentService=new CommentService($em);
        $this->mediaService=new MediaService($em);
        $this->commentController=new CommentsController($em);*/
    }

    /**
     * @param int $trickId
     * @param int $mediaType
     * @param Request $request
     * @param SluggerInterface $slugger
     * @return Response
     */
    public function create(int $trickId, int $mediaType, Request $request): Response
    {
        $media=new Media();
        $form = $this->createForm(MediaFormType::class, $media, array('type_of_media' => $mediaType));
        $form->handleRequest($request);
        $serviceReturn=$this->mediaService->createMedia($trickId,$mediaType,$form);
        return $this->genericController->controllerReturn($serviceReturn);
    }

    public function delete(int $mediaId, Request $request)
    {
        $mediaRepository = $this->getDoctrine()->getRepository(Media::class);
        $media = $mediaRepository->find($mediaId);
        $trickId = $media->getTrick()->getId();
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($media);
        $entityManager->flush();

        $this->addFlash('success', 'Vous avez supprimé un media');
        return $this->redirectToRoute('Trick.show', [
            'trickId' => $trickId]);
    }

    public function update(int $mediaId, Request $request, SluggerInterface $slugger)
    {
        $em = $this->getDoctrine()->getManager();
        $mediaToUpdate = $em->find(Media::class, $mediaId);
        $currentUrl = $mediaToUpdate->getUrl();
        $trickId = $mediaToUpdate->getTrick()->getId();
        $trickName = $mediaToUpdate->getTrick()->getName();
        $type = $mediaToUpdate->getMediaType()->getId();
        $was_main=$mediaToUpdate->getIsMain();
        $form = $this->createForm(MediaFormType::class, $mediaToUpdate, array('type_of_media' => $type));
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $url = $form->get('url')->getData();
            $changes_done=false;
            if ($type == 1) {
                if ($url) {
                    $fileCreation = $this->createNewFile($slugger, $url);
                    $e = $fileCreation['fileCreationError'];
                    $newFilename = $fileCreation['newFilename'];
                    if (!isset($e)) {
                        $mediaToUpdate->setUrl($newFilename);
                        $changes_done=true;
                    } else {
                        $this->addFlash("danger", "une erreur est survenue lors de l'enregistrement de l'image, description:" . $e);
                    }
                }
                $is_main = $form->get('isMain')->getData();
                //detect if user wants to change the is_main property
                if($is_main!=$was_main){
                    //identify the main media for the related trick
                    $mediaRepository = $this->getDoctrine()->getRepository(Media::class);
                    $initialMainMedia = $mediaRepository->findMainMediaWithTrickId($trickId);
                    if ($is_main == true) {
                        //set initial main media is_main value to false if updated media is not the same and requested to be main
                        if ($initialMainMedia != null and ($initialMainMedia->getId() != $mediaId)) {
                            $initialMainMedia->setIsMain(false);
                            $em->persist($initialMainMedia);
                            $em->flush();
                        }
                        $mediaToUpdate->setIsMain(true);
                    } else {
                        //change is_main property of media to false if it was true
                        if ($mediaToUpdate->getIsMain() == true) {
                            $mediaToUpdate->setIsMain(false);
                        }
                    }
                    $changes_done=true;
                }
            }
            if ($type == 2 ) {
                $urlConverted = $this->convertVideoUrl($url);
                $urlAccepted = $this->checkVideoUrl($urlConverted);
                if (($urlAccepted != true) || ($urlConverted == $currentUrl)) {
                    if ($urlAccepted != true) {
                        $this->addFlash("danger",
                            "Format d'url incorrect. Format accepté https://www.youtube.com/watch?v=xxxxx OU https://www.youtube.com/embed/xxxx");
                    }
                    if ($urlConverted == $currentUrl) {
                        $this->addFlash("warning",
                            "L'url entrée est identique à celle enregistrée, pas de modification effectuée");
                        return $this->redirectToRoute('Trick.show', [
                            'trickId' => $trickId]);
                    }

                    return $this->redirectToRoute('Media.create', [
                        'trickId' => $trickId,
                        'mediaType' => $type]);
                } else {
                    $changes_done=true;
                    $mediaToUpdate->setUrl($urlConverted);
                }

            }
            $em->persist($mediaToUpdate);
            $em->flush();
            if($changes_done){
                $this->addFlash("success",
                    "Modification enregistrée");
            }
            return $this->redirectToRoute('Trick.show', [
                'trickId' => $trickId]);
        }
        return $this->render('media/mediaCreation.html.twig', [
            'trickName' => $trickName,
            'mediaForm' => $form->createView(),
            'mediaType' => $type,
        ]);

    }

    public function checkVideoUrl(string $url): bool
    {

        if (str_contains($url, 'https://www.youtube.com/embed')) {
            return true;
        } else {
            return false;
        }
    }

    public function convertVideoUrl(string $url): string
    {
        if (str_contains($url, 'watch?v=')) {
            $newUrl = str_replace('watch?v=', 'embed/', $url);
            return $newUrl;
        } else {
            return $url;
        }
    }

    public function createNewFile(SluggerInterface $slugger, $file)
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        // this is needed to safely include the file name as part of the URL
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        // Move the file to the directory where images are stored
        try {
            $file->move(
                $this->getParameter('images_directory'),
                $newFilename
            );
            $fileCreationError = null;
        } catch (FileException $e) {
            // ... handle exception if something happens during file upload
            $this->addFlash("danger", "une erreur est survenue lors de l'enregistrement de l'image, description:" . $e);
            $newFilename = null;
            $fileCreationError = $e;
        }
        return ['newFilename' => $newFilename, 'fileCreationError' => $fileCreationError];
    }
}

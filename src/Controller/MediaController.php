<?php

namespace App\Controller;

use App\Entity\Group;
use App\Entity\Media;
use App\Entity\MediaType;
use App\Entity\Trick;
use App\Repository\MediaRepository;
use App\Form\MediaFormType;
use App\Form\TrickFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use function PHPUnit\Framework\isEmpty;


class MediaController extends AbstractController
{
    public function create(int $trickId, int $mediaType, Request $request, SluggerInterface $slugger): Response
    {
        $media = new Media();
        $form = $this->createForm(MediaFormType::class, $media, array('type_of_media' => $mediaType));
        $form->handleRequest($request);
        $entityManager = $this->getDoctrine()->getManager();
        $trick = $entityManager->find(Trick::class, $trickId);
        $trickName = $trick->getName();
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $file = $form->get('url')->getData();
                // this condition is needed because the 'url' field is not required
                // so the file must be processed only when a file is uploaded
                if ($file) {
                    //if the file is an image, we manage the uploading
                    if ($mediaType == 1) {
                        $fileCreation = $this->createNewFile($slugger, $file);
                        $e = $fileCreation['fileCreationError'];
                        $newFilename = $fileCreation['newFilename'];
                    }
                    //next condition is true if there was no problem during file upload or if the media is a video
                    if (!isset($e)) {
                        if ($mediaType == 1) {
                            $media->setUrl($newFilename);
                            $type = $entityManager->find(MediaType::class, $mediaType);
                            $media->setMediaType($type);
                            $media->setIsMain($form["isMain"]->getData());
                        } else if ($mediaType == 2) {
                            $urlConverted = $this->convertVideoUrl($file);
                            $urlAccepted = $this->checkVideoUrl($urlConverted);
                            $media->setIsMain(false);
                            if ($urlAccepted != true) {
                                $this->addFlash("danger",
                                    "Merci de choisir une video youtube et cliquer sur \"partager\" puis \"integrer\"  
                                    Utilisez ensuite l'url commencant par https://www.youtube.com/embed");
                                return $this->redirectToRoute('Media.create', [
                                    'trickId' => $trickId,
                                    'mediaType' => $mediaType]);
                            }
                            $media->setUrl($urlConverted);
                            $type = $entityManager->find(MediaType::class, $mediaType);
                            $media->setMediaType($type);
                        }
                        $media->setTrick($trick);
                        $entityManager->persist($media);
                        $entityManager->flush();
                        $this->addFlash("success", "Le média a été enregistré");
                        return $this->redirectToRoute('Trick.show', ['trickId' => $trickId]);
                    }

                } else {
                    $this->addFlash("danger", "Merci de choisir une image à uploader ou une url valide avant de valider");
                    return $this->redirectToRoute('Media.create', [
                        'trickId' => $trickId,
                        'mediaType' => $mediaType]);
                }

            }
        }
        return $this->render('media/mediaCreation.html.twig', [
            'controller_name' => 'MediaController',
            'trickName' => $trickName,
            'mediaForm' => $form->createView(),
            'mediaType' => $mediaType,
        ]);
    }

    public function delete(int $mediaId, Request $request)
    {
        $currentPage = $request->getUri();
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
            /*if ($url) {*/
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
            /*} else {
                return $this->redirectToRoute('Media.update', [
                    'trickId' => $trickId]);
            }*/
        }
        return $this->render('media/mediaCreation.html.twig', [
            /*'controller_name' => 'MediaController',*/
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
        /*$isFileCreated=false;
        $fileCreationError=null;*/
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

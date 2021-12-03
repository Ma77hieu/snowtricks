<?php

namespace App\Controller;

use App\Entity\Group;
use App\Entity\Media;
use App\Entity\Trick;
use App\Form\MediaFormType;
use App\Form\TrickFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use function PHPUnit\Framework\isEmpty;


class MediaController extends AbstractController
{
    public function create(int $trickId, int $mediaType,Request $request,SluggerInterface $slugger): Response
    {
        $media = new Media();
        $form = $this->createForm(MediaFormType::class, $media,array('type_of_media'=>$mediaType));
        $form->handleRequest($request);
        $entityManager = $this->getDoctrine()->getManager();
        $trick= $entityManager->find(Trick::class, $trickId);
        $trickName=$trick->getName();
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $file = $form->get('url')->getData();
                // this condition is needed because the 'url' field is not required
                // so the PDF file must be processed only when a file is uploaded
                if ($file) {
                    if ($mediaType==1){
                        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        // this is needed to safely include the file name as part of the URL
                        $safeFilename = $slugger->slug($originalFilename);
                        $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

                        // Move the file to the directory where images are stored
                        try {
                            $file->move(
                                $this->getParameter('images_directory'),
                                $newFilename
                            );
                        } catch (FileException $e) {
                            // ... handle exception if something happens during file upload
                            $this->addFlash("danger", "une erreur est survenue lors de l'enregistrement de l'image, description:".$e);
                        }
                    }
                    if (!isset($e)){
                        if($mediaType==1){
                            $media->setUrl($newFilename);
                            $media->setMediaType(1);
                        }
                        else if ($mediaType==2){
                            $media->setUrl($file);
                            $media->setMediaType(2);
                        }
                        $media->setIsMain($form["isMain"]->getData());
                        $media->setMediaType($form["mediaType"]->getData());
                        $media->setTrick($trick);
                        $entityManager->persist($media);
                        $entityManager->flush();
                        $this->addFlash("success", "L'image a été enregistrée");
                        return $this->redirectToRoute('Trick.show',['trickId'=>$trickId]);
                    }

                }
                else {
                    $this->addFlash("danger", "Merci de choisir une image à uploader ou une url valide avant de valider");
                    return $this->redirectToRoute('Media.create',['trickId'=>$trickId]);
                }

            }
        }
        return $this->render('media/mediaCreation.html.twig', [
            'controller_name' => 'MediaController',
            'trickName'=>$trickName,
            'mediaForm' => $form->createView(),
            'mediaType'=>$mediaType,
        ]);
    }

    public function delete(int $mediaId, Request $request){
        $currentPage=$request->getUri();
        $mediaRepository = $this->getDoctrine()->getRepository(Media::class);
        $media = $mediaRepository->find($mediaId);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($media);
        $entityManager->flush();

        $this->addFlash('success', 'Vous avez supprimé une image');
        return $this->redirectToRoute('index');
    }

    public function update(int $mediaId){
        $entityManager = $this->getDoctrine()->getManager();

    }
}

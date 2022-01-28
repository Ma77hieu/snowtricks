<?php

namespace App\Services;

use App\Controller\CommentsController;
use App\Entity\Media;
use App\Form\TrickFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Group;
use App\Entity\Trick;
use Symfony\Component\HttpFoundation\RedirectResponse;

class TrickServices extends AbstractController
{
    /**
     * Instanciation of trick service
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
        $this->mediaService=new MediaService($em);
    }

    /*public function __construct(TrickServices $trickServices,EntityManagerInterface $em)
    {
        $this->trickServices = $trickServices;
        $this->em=$em;
        $this->commentService=new CommentService($em);
        $this->mediaService=new MediaService($em);
        $this->commentController=new CommentsController($em);
    }*/

    public function createTrick($entityManager,$request):array
    {
        $trick = new Trick();
        $form = $this->createForm(TrickFormType::class, $trick);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $trickGroup = $entityManager->find(Group::class, $form["trickGroup"]->getData());
                //define the group to which the trick is linked
                $trick->setTrickGroup($trickGroup);
                //set the creation date to the current date type
                $trick->setCreationDate(new \DateTime());
                //save the trick into database
                $entityManager->persist($trick);
                $entityManager->flush();
                $serviceAnswer = ['returnType' => 'redirect',
                    'path' => 'index',
                    'flashType' => 'success',
                    'flashMessage' => 'Le trick a été créé',
                    'data' => []];
            } else {
                $serviceAnswer = ['returnType' => 'render',
                    'path' => 'tricks/trickCreation.html.twig',
                    'flashType' => 'danger',
                    'flashMessage' => 'Une erreur est survenue, voir le formulaire pour plus de détails',
                    'data' => ['trickForm' => $form->createView()]];
            }
            return $serviceAnswer;
        }
        $serviceAnswer = ['returnType' => 'render',
            'path' => 'tricks/trickCreation.html.twig',
            'flashType' => 'success',
            'flashMessage' => null,
            'data' => ['trickForm' => $form->createView()]];
        return $serviceAnswer;
    }

    /**
     * Deletion of a trick with the Id passed in parameter, return true if successfull, false if not
     * @param int $trickId
     * @return bool
     */
    public function deleteTrickFromId(int $trickId):bool
    {
        $trick = $this->em->find(Trick::class, $trickId);
        if ($trick != null) {
                $this->em->remove($trick);
                $this->em->flush();
            return true;
        } else {
            return false;
        }
    }

    /**
     * Displays the trick edition page in case of get, manage trick edition in case of post
     * @param int $trickId
     * @param $form
     * @param Trick $trick
     * @return array|RedirectResponse
     */
    public function handleTrickEditionForm(int $trickId, $form,Trick $trick)
    {
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $trick->setModificationDate(new \DateTime());
                $this->em->merge($trick);
                $this->em->flush();
                $flashType='success';
                $flashMsg='Trick mis à jour.';
                $path='Trick.edit';
                $data=['trickId'=>$trickId];
            } else {
                $errors = $form->getErrors();
                $this->addFlash('danger', "$errors");
                $flashType='danger';
                $flashMsg="$errors";
                $path='index';
                $data=[];
            }
            return ['returnType' => 'redirect',
                'path' => $path,
                'flashType' => $flashType,
                'flashMessage' => $flashMsg,
                'data' => $data];
        }
        $mediaRepository = $this->em->getRepository(Media::class);
        $medias = $mediaRepository->findBy(['trick' => $trickId]);
        $mainMedia = $this->mediaService->getMediaUrlAndId($medias);
        $mainMediaUrl = $mainMedia['mediaUrl'];
        $mainMediaId = $mainMedia['mediaId'];
        $groupRepository = $this->em->getRepository(Group::class);
        $groups = $groupRepository->findAll();

        $serviceReturn = ['returnType' => 'render',
            'path' => 'tricks/trickEdition.html.twig',
            'flashType' => 'success',
            'flashMessage' => "",
            'data' => [
                'trickForm' => $form->createView(),
                'medias' => $medias,
                'trick' => $trick,
                'groups' => $groups,
                'mainMediaUrl' => $mainMediaUrl,
                'mainMediaId' => $mainMediaId]];
        return $serviceReturn;
        /*return $this->render('tricks/trickEdition.html.twig', [
            'trickForm' => $form->createView(),
            'medias' => $medias,
            'trick' => $trick,
            'groups' => $groups,
            'mainMediaUrl' => $mainMediaUrl,
            'mainMediaId' => $mainMediaId]);*/
    }
}
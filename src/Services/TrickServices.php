<?php

namespace App\Services;

use App\Controller\CommentsController;
use Symfony\Component\HttpFoundation\Request;
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
     * @var CommentsController
     */
    private CommentsController $commentController;

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $em;

    /**
     * @var CommentService
     */
    private CommentService $commentService;

    /**
     * @var MediaService
     */
    private MediaService $mediaService;

    /**
     * Instanciation of trick service
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
        $this->mediaService=new MediaService($em);
        $this->commentController=new CommentsController($em);
        $this->commentService=new CommentService($em);
    }

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
    }

    /**
     * Used by the trick details page to display all the information about a trick
     * returns all the information required by the controllerReturn function of tricks controller
     * @param Request $request
     * @param $user
     * @param $form
     * @param $trickId
     * @return array
     */
    public function showTrickDetails(Request $request, $user, $form, $trickId):array
    {

        $commentManagement=$this->commentController->manageCommentForm($request,$user, $form, $trickId);
        if ($commentManagement['needFlash']){
            $this->addFlash($commentManagement['flashType'],$commentManagement['flashMessage']);
        }
        $mediaRepository = $this->getDoctrine()->getRepository(Media::class);
        $medias = $mediaRepository->findByTrickId($trickId);
        $mainMedia = $this->mediaService->getMediaUrlAndId($medias);
        $mainMediaUrl = $mainMedia['mediaUrl'];
        $mainMediaId = $mainMedia['mediaId'];
        $comments=$this->commentService->validatedComsForTrickId($trickId);
        $entityManager = $this->getDoctrine()->getManager();
        $trick = $entityManager->find(Trick::class, $trickId);
        $group = $entityManager->find(Group::class, $trick->getTrickGroup());
        $tags = [
            'date de creation' => $trick->getCreationDate()->format('Y-m-d H:i:s'),
            'groupe' => $group->getName(),
        ];
        if ($trick->getModificationDate()) {
            $trickModifDate = $trick->getModificationDate()->format('Y-m-d H:i:s');
            $tags['date de modification'] = $trickModifDate;
        }

        return ['returnType' => 'render',
            'path'=>'tricks/trickDetails.html.twig',
            'flashType' => 'success',
            'flashMessage' => "",
            'data'=>['commentForm' => $form->createView(),
            'medias' => $medias,
            'comments' => $comments,
            'trick' => $trick,
            'tags' => $tags,
            'mainMediaUrl' => $mainMediaUrl,
            'mainMediaId' => $mainMediaId]];
    }
}
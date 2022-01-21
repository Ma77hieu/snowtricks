<?php

namespace App\Controller;

use App\Services\CommentService;
use App\Services\MediaService;
use App\Services\TrickServices;
use App\Entity\Group;
use App\Entity\Media;
use App\Entity\Comment;
use App\Entity\Trick;
use App\Form\CommentFormType;
use App\Form\TrickFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class TrickController extends AbstractController
{
    /**
     * @var TrickServices
     */
    private TrickServices $trickServices;

    /**
     * @var CommentService
     */
    private CommentService $commentService;

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $em;

    /**
     * @var MediaService
     */
    private MediaService $mediaService;

    /**
     * @var \App\Controller\CommentsController
     */
    private CommentsController $commentController;

    /**
     * Instanciation of trick controller
     * @param TrickServices $trickServices
     */
    public function __construct(TrickServices $trickServices,EntityManagerInterface $em)
    {
        $this->trickServices = $trickServices;
        $this->em=$em;
        $this->commentService=new CommentService($em);
        $this->mediaService=new MediaService($em);
        $this->commentController=new CommentsController($em);
    }

    /**
     * Creates a new trick
     * @param Request $request
     * @return Response
     */
    public function create(Request $request): Response
    {
        $serviceReturn = $this->trickServices->createTrick($this->em, $request);
        return $this->controllerReturn($serviceReturn);
    }

    /**
     * @param int $trickId
     * @param Request $request
     * @return Response
     */
    public function show(int $trickId, Request $request): Response
    {
        $user = $this->getUser();
        $comment = new Comment();
        $form = $this->createForm(CommentFormType::class, $comment);
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

        return $this->render('tricks/trickDetails.html.twig', [
            'commentForm' => $form->createView(),
            'medias' => $medias,
            'comments' => $comments,
            'trick' => $trick,
            'tags' => $tags,
            'mainMediaUrl' => $mainMediaUrl,
            'mainMediaId' => $mainMediaId]);
    }

    /**
     * @param int $trickId
     * @param Request $request
     * @return Response
     */
    public function edit(TrickServices $trickService,MediaService $mediaService, int $trickId, Request $request): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $trick = $entityManager->find(Trick::class, $trickId);
        $form = $this->createForm(TrickFormType::class, $trick);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $entityManager = $this->getDoctrine()->getManager();
                $trick->setModificationDate(new \DateTime());
                $entityManager->merge($trick);
                $entityManager->flush();
                $this->addFlash('success', 'Trick mis à jour.');
                return $this->redirectToRoute('index');
            } else {
                $errors = $form->getErrors();
                $this->addFlash('danger', "$errors");
            }
        }
        $mediaRepository = $this->getDoctrine()->getRepository(Media::class);
        $medias = $mediaRepository->findBy(['trick' => $trickId]);
        $mainMedia = $mediaService->getMediaUrlAndId($medias);
        $mainMediaUrl = $mainMedia['mediaUrl'];
        $mainMediaId = $mainMedia['mediaId'];
        $groupRepository = $this->getDoctrine()->getRepository(Group::class);
        $groups = $groupRepository->findAll();

        /*var_dump($groups);die;*/
        return $this->render('tricks/trickEdition.html.twig', [
            'trickForm' => $form->createView(),
            'medias' => $medias,
            'trick' => $trick,
            'groups' => $groups,
            'mainMediaUrl' => $mainMediaUrl,
            'mainMediaId' => $mainMediaId]);
    }

    /**
     * deletes a trick
     * @param int $trickId
     * @return Response
     */
    public function delete(TrickServices $trickService,int $trickId):Response
    {
        $isDeletionOk=$trickService->deleteTrickFromId($trickId);
        if ($isDeletionOk==true){
            $this->addFlash('success', 'Vous avez supprimé un trick');
        }
        else {
            $this->addFlash('danger', 'Problème dans la suppression du trick');
        }
        return $this->redirectToRoute('index');
    }

    /**
     * @param $input
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function controllerReturn($input)
    {
        $returnType = $input['returnType'];
        $path = $input['path'];
        $flashType = $input['flashType'];
        $flashMessage = $input['flashMessage'];
        $data = $input['data'];
        if ($flashMessage) {
            $this->addFlash($flashType, $flashMessage);
        }
        if ($returnType === 'render') {
            return $this->render($path, $data);
        }
        if ($returnType === 'redirect') {
            return $this->redirectToRoute($path, $data);
        } else {
            $this->addFlash("danger", "une erreur interne est survenue, vous avez été redirigé vers la page d'accueil");
            return $this->redirectToRoute('index');
        }
    }
}

<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Form\CommentFormType;
use App\Services\CommentService;
use App\Services\CommentsServices;
use App\Services\MediaService;
use App\Services\TrickServices;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CommentsController extends AbstractController
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


    public function __construct(EntityManagerInterface $em, MediaService $mediaService)
    {
        $this->em = $em;
        $this->commentService = new CommentService($em);
        $this->mediaService = $mediaService;
    }

    /**
     * Display the unvalidated comments in the comments validation page
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $commentRepository = $this->getDoctrine()->getRepository(Comment::class);
        $comments = $commentRepository->findByValidationStatus('false');
        $returnDatas = ['returnType' => 'render',
            'path' => 'comments/commentsValidation.html.twig',
            'flashType' => 'success',
            'flashMessage' => null,
            'data' => ['unvalidatedComments' => $comments]];
        return $this->controllerReturn($returnDatas);
    }

    /**Function called when a trick detail page is displayed
     * calls the commentService to manage the form handling
     * @param Request $request
     * @param $user
     * @param $form
     * @param int $trickId
     * @return array|false[]
     */
    public function manageCommentForm(Request $request, $user, $form, int $trickId)
    {
        return $this->commentService->handleNewCommentForm($request, $user, $form, $trickId);
    }

    /**
     * Allows the admins to validate comments
     * if a comment is not validated, it is not displayed on a trick page
     * @param int $commentId
     * @return Response
     */
    public function validate(int $commentId): Response
    {
        $serviceReturn = $this->commentService->validateComments($commentId);
        return $this->controllerReturn($serviceReturn);
    }

    /** update an exising comment (set new modification date and new comment text )
     * @param int $commentId
     * @param Request $request
     * @return Response
     */
    public function update(int $commentId, request $request): Response
    {
        $commentToUpdate = $this->em->find(Comment::class, $commentId);
        $form = $this->createForm(CommentFormType::class, $commentToUpdate);
        $serviceReturn = $this->commentService->handleModificationForm($request, $form, $commentToUpdate, $commentId);
        return $this->controllerReturn($serviceReturn);
    }

    /**
     * Handles the deletion of a comment
     * @param int $commentId
     * @return Response
     */
    public function delete(int $commentId): Response
    {
        $serviceReturn = $this->commentService->deleteComment($commentId);
        return $this->controllerReturn($serviceReturn);
    }

    /**
     * Generic function used by the various funcitons of the controller to redirect or return the response
     * Also returns the datas to be sent to the templates and the flash message if one is needed
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

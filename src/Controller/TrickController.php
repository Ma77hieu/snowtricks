<?php

namespace App\Controller;

use App\Services\CommentService;
use App\Services\TrickServices;
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
     * @var CommentsController
     */
    private CommentsController $commentController;

    /**
     * Instanciation of trick controller
     * @param TrickServices $trickServices
     */
    public function __construct(
        TrickServices $trickServices,
        EntityManagerInterface $em,
        CommentsController $commentController
    ) {
        $this->trickServices = $trickServices;
        $this->em = $em;
        $this->commentService = new CommentService($em);
        $this->commentController = $commentController;
    }

    /**
     * Creates a new trick
     * @param Request $request
     * @return Response
     */
    public function create(Request $request): Response
    {
        $trick = new Trick();
        $form = $this->createForm(TrickFormType::class, $trick);
        $form->handleRequest($request);
        $serviceReturn = $this->trickServices->createTrick($this->em, $trick, $form);
        return $this->controllerReturn($serviceReturn);
    }

    /**
     * Manage the display of a trick details page
     * @param int $trickId
     * @param Request $request
     * @return Response
     */
    public function show(int $trickId, Request $request): Response
    {
        $user = $this->getUser();
        $comment = new Comment();
        $form = $this->createForm(CommentFormType::class, $comment);
        $serviceReturn = $this->trickServices->showTrickDetails($request, $user, $form, $trickId);
        return $this->controllerReturn($serviceReturn);
    }

    /**
     * Manage the display of a trickEdition page
     * @param int $trickId
     * @param Request $request
     * @return Response
     */
    public function edit(int $trickId, Request $request): Response
    {
        $trick = $this->em->find(Trick::class, $trickId);
        $form = $this->createForm(TrickFormType::class, $trick);
        $form->handleRequest($request);
        $serviceReturn = $this->trickServices->handleTrickEditionForm($trickId, $form, $trick);
        return $this->controllerReturn($serviceReturn);
    }

    /**
     * deletes a trick
     * @param int $trickId
     * @return Response
     */
    public function delete(TrickServices $trickService, int $trickId): Response
    {
        $isDeletionOk = $trickService->deleteTrickFromId($trickId);
        if ($isDeletionOk == true) {
            $this->addFlash('success', 'Vous avez supprimé un trick');
        } else {
            $this->addFlash('danger', 'Problème dans la suppression du trick');
        }
        return $this->redirectToRoute('index');
    }

    /**
     * Generic function used by the various functions of the controller to redirect or return the response
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

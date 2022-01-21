<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Trick;
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


    public function __construct(EntityManagerInterface $em)
    {
        $this->em=$em;
        $this->commentService=new CommentService($em);
        $this->mediaService=new MediaService($em);
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
    public function manageCommentForm(Request $request,$user,$form,int $trickId)
    {
        return $this->commentService->handleCommentForm($request,$user,$form,$trickId);
    }

    public function createComment()
    {
        
    }

    public function validate(int $commentId): Response
    {
        /*$commentsService= new CommentsServices();*/
        $commentRepository = $this->getDoctrine()->getRepository(Comment::class);
        $commentToValidate = $commentRepository->find($commentId);
        $commentToValidate->setIsValidated(true);
        $em = $this->getDoctrine()->getManager();
        $em->persist($commentToValidate);
        $em->flush();
        $relatedTrick = $commentToValidate->getTrick()->getName();
        $unvalidatedComments = $commentRepository->findByValidationStatus('false');
        $serviceReturn = ['returnType' => 'render',
            'path' => 'comments/commentsValidation.html.twig',
            'flashType' => 'success',
            'flashMessage' => "le commentaire Id $commentId a été validé pour le trick $relatedTrick",
            'data' => ['unvalidatedComments' => $unvalidatedComments]];
        /*$serviceReturn=$commentsService->validateComments($commentRepository, $commentId);*/
        return $this->controllerReturn($serviceReturn);
    }

    /**
     * @param int $commentId
     * @param Request $request
     * @return Response
     */
    public function update(int $commentId, request $request): Response
    {
        $em = $this->getDoctrine()->getManager();
        $commentToUpdate = $em->find(Comment::class, $commentId);
        $form = $this->createForm(CommentFormType::class, $commentToUpdate);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $updatedText = $form->get('commentText')->getData();
                $commentToUpdate->setCommentText($updatedText);
                $commentToUpdate->setModificationDate(new \DateTime());
                $em->persist($commentToUpdate);
                $em->flush();
                $relatedTrickId = $commentToUpdate->getTrick()->getId();
                $serviceReturn = ['returnType' => 'redirect',
                    'path' => 'Trick.show',
                    'flashType' => 'success',
                    'flashMessage' => "le commentaire Id $commentId a été modifié pour le trick $relatedTrickId",
                    'data' => ['trickId'=>$relatedTrickId]];
            }
            else{
                $serviceReturn = ['returnType' => 'redirect',
                    'path' => 'Comment.update',
                    'flashType' => 'danger',
                    'flashMessage' => "Un problème est survenu avec la modification du commentaire, merci de reessayer",
                    'data' => ['commentId'=>$commentId]];
            }
            return $this->controllerReturn($serviceReturn);
        }
        $serviceReturn = ['returnType' => 'render',
            'path' => 'comments/commentEdition.html.twig',
            'flashType' => 'danger',
            'flashMessage' => null,
            'data' => ['commentId'=>$commentId,
                'commentEditionForm'=>$form->createView()]];
        return $this->controllerReturn($serviceReturn);
    }

    public function delete(int $commentId): Response
    {
        /*$commentsService= new CommentsServices();*/
        $commentRepository = $this->getDoctrine()->getRepository(Comment::class);
        $commentToDelete = $commentRepository->find($commentId);
        $em = $this->getDoctrine()->getManager();
        $relatedTrick = $commentToDelete->getTrick()->getName();
        $em->remove($commentToDelete);
        $em->flush();
        $unvalidatedComments = $commentRepository->findByValidationStatus('false');
        $serviceReturn = ['returnType' => 'render',
            'path' => 'comments/commentsValidation.html.twig',
            'flashType' => 'success',
            'flashMessage' => "le commentaire Id $commentId en lien avec le trick $relatedTrick a été supprimé",
            'data' => ['unvalidatedComments' => $unvalidatedComments]];
        /*$serviceReturn=$commentsService->validateComments($commentRepository, $commentId);*/
        return $this->controllerReturn($serviceReturn);
    }

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

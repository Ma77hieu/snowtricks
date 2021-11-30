<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Services\CommentsServices;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CommentsController extends AbstractController
{


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

    public function validate(int $commentId): Response
    {
        /*$commentsService= new CommentsServices();*/
        $commentRepository = $this->getDoctrine()->getRepository(Comment::class);
        $commentToValidate=$commentRepository->find($commentId);
        $commentToValidate->setIsValidated(true);
        $em = $this->getDoctrine()->getManager();
        $em->persist($commentToValidate);
        $em->flush();
        $relatedTrick=$commentToValidate->getTrick()->getName();
        $unvalidatedComments = $commentRepository->findByValidationStatus('false');
        $serviceReturn = ['returnType' => 'render',
            'path' => 'comments/commentsValidation.html.twig',
            'flashType' => 'success',
            'flashMessage' => "le commentaire Id $commentId a été validé pour le trick $relatedTrick",
            'data' => ['unvalidatedComments'=>$unvalidatedComments]];
        /*$serviceReturn=$commentsService->validateComments($commentRepository, $commentId);*/
        return $this->controllerReturn($serviceReturn);
    }

    public function delete(int $commentId): Response
    {
        /*$commentsService= new CommentsServices();*/
        $commentRepository = $this->getDoctrine()->getRepository(Comment::class);
        $commentToDelete=$commentRepository->find($commentId);
        $em = $this->getDoctrine()->getManager();
        $relatedTrick=$commentToDelete->getTrick()->getName();
        $em->remove($commentToDelete);
        $em->flush();
        $unvalidatedComments = $commentRepository->findByValidationStatus('false');
        $serviceReturn = ['returnType' => 'render',
            'path' => 'comments/commentsValidation.html.twig',
            'flashType' => 'success',
            'flashMessage' => "le commentaire Id $commentId en lien avec le trick $relatedTrick a été supprimé",
            'data' => ['unvalidatedComments'=>$unvalidatedComments]];
        /*$serviceReturn=$commentsService->validateComments($commentRepository, $commentId);*/
        return $this->controllerReturn($serviceReturn);
    }

    public function controllerReturn($input)
    {
        $returnType=$input['returnType'];
        $path=$input['path'];
        $flashType=$input['flashType'];
        $flashMessage=$input['flashMessage'];
        $data=$input['data'];
        if ($flashMessage){
            $this->addFlash($flashType, $flashMessage);
        }
        if ($returnType==='render'){
            return $this->render($path,$data);
        }
        if ($returnType==='redirect'){
            return $this->redirectToRoute($path,$data);
        }
        else{
            $this->addFlash("danger", "une erreur interne est survenue, vous avez été redirigé vers la page d'accueil");
            return $this->redirectToRoute('index');
        }
    }
}

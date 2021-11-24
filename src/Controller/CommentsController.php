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
        $commentsService= new CommentsServices();
        $commentRepository = $this->getDoctrine()->getRepository(Comment::class);
        $serviceReturn=$commentsService->validateComments($commentRepository);
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

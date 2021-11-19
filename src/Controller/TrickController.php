<?php

namespace App\Controller;

use App\Services\TrickServices;
use Symfony\Component\Security\Core\Security;
use App\Entity\Group;
use App\Entity\Media;
use App\Entity\User;
use App\Entity\Comment;
use App\Entity\Trick;
use App\Form\CommentFormType;
use App\Form\TrickFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;




class TrickController extends AbstractController
{

    public function create(Request $request): Response
    {
        $trick = new Trick();
        $form = $this->createForm(TrickFormType::class, $trick);
        $form->handleRequest($request);
        $entityManager = $this->getDoctrine()->getManager();
        $trickService= new TrickServices();
        $serviceReturn=$trickService->createTrick($entityManager,$trick,$form);
        return $this->controllerReturn($serviceReturn);
    }

    public function show(int $trickId, Request $request): Response
    {
        $comment = new Comment();
        $entityManager = $this->getDoctrine()->getManager();
        $trick = $entityManager->find(Trick::class,$trickId);
        $group = $entityManager->find(Group::class, $trick->getTrickGroup());
        $form = $this->createForm(CommentFormType::class,$comment);
        $form->handleRequest($request);

        if ($form->isSubmitted())
        {
            /*            echo ("form valide?");
                        var_dump($form->isValid());*/
            if ($form->isValid())
            {
                $user = $this->getUser();

                $comment->setCreationDate(new \DateTime());
                $comment->setTrick($trick);
                $comment->setAuthor($user);
                $entityManager->persist($comment);
                $entityManager->flush();
                $this->addFlash('success', 'Votre commentaire a été ajouté');/*
                return $this->render('tricks/trickDetails.html.twig');*/
            }
            else
            {
                $errors = $form->getErrors();
                $this->addFlash('danger', "$errors");
            }
        }
        $mediaRepository = $this->getDoctrine()->getRepository(Media::class);
        $medias = $mediaRepository->findAll(['trickId' => $trickId]);
        $commentRepository = $this->getDoctrine()->getRepository(Comment::class);
        $comments = $commentRepository->findAll(['trick' => $trickId]);
        $tags=[
            'date de creation'=>$trick->getCreationDate()->format('Y-m-d H:i:s'),
            'groupe'=>$group->getName(),
        ];
        if ($trick->getModificationDate()) {
            $trickModifDate = $trick->getModificationDate()->format('Y-m-d H:i:s');
            $tags['date de modification']=$trickModifDate;
        }

        return $this->render('tricks/trickDetails.html.twig',[
            'commentForm' => $form->createView(),
            'medias' => $medias,
            'comments' => $comments,
            'trick' => $trick,
            'tags'=>$tags]);
    }

    public function edit(int $trickId, Request $request): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $trick = $entityManager->find(Trick::class,$trickId);
        $form = $this->createForm(TrickFormType::class, $trick);
        $form->handleRequest($request);

        if ($form->isSubmitted())
        {
            /*            echo ("form valide?");
                        var_dump($form->isValid());*/
            if ($form->isValid())
            {
                /*$entityManager = $this->getDoctrine()->getManager();*/
                $entityManager->merge($trick);
                $entityManager->flush();
                $this->addFlash('success', 'Trick mis à jour.');
                return $this->redirectToRoute('index');
            }
            else
            {
                $errors = $form->getErrors();
                $this->addFlash('danger', "$errors");
            }
        }
        $repository = $this->getDoctrine()->getRepository(Media::class);
        $medias = $repository->findAll(['trickId' => $trickId]);

        return $this->render('tricks/trickEdition.html.twig',[
            'trickForm' => $form->createView(),
            'medias' => $medias,
            'trick' => $trick]);
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

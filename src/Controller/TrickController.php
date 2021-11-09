<?php

namespace App\Controller;

use App\Entity\Group;
use App\Entity\Media;
use App\Entity\User;
use App\Entity\Comment;
use App\Entity\Trick;
use App\Form\CommentFormType;
use App\Form\TrickFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\PropertyRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TrickController extends AbstractController
{
    public function show(int $trickId, Request $request): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $trick = $entityManager->find(Trick::class,$trickId);
        $group = $entityManager->find(Group::class, $trick->getTrickGroup());
        $form = $this->createForm(CommentFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted())
        {
            /*            echo ("form valide?");
                        var_dump($form->isValid());*/
            if ($form->isValid())
            {
                /*$entityManager = $this->getDoctrine()->getManager();*/
                $comment = new Comment();
                $userId=$request->getUser()->getId();
                $comment->setCreationDate(new \DateTime());
                $comment->setTrick($trickId);
                $comment->setAuthor($userId);
                $entityManager->persist($comment);
                $entityManager->flush();
                $this->addFlash('success', 'Votre commentaire a été ajouté');
                return $this->render('tricks/trickDetails.html.twig');
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

    public function create(Request $request): Response
    {
        $trick = new Trick();
        $form = $this->createForm(TrickFormType::class, $trick);
        $form->handleRequest($request);
/*        echo ("form soumis?");
        var_dump($form->isSubmitted());*/


        if ($form->isSubmitted())
        {
/*            echo ("form valide?");
            var_dump($form->isValid());*/
            if ($form->isValid())
            {

                $entityManager = $this->getDoctrine()->getManager();
                $trickGroup = $entityManager->find(Group::class, $form["trickGroup"]->getData());
                //define the group to which the trick is linked
                $trick->setTrickGroup($trickGroup);
                //set the creation date to the current date type
                $trick->setCreationDate(new \DateTime());
                //save the trick into database
                $entityManager2 = $this->getDoctrine()->getManager();
                $entityManager2->persist($trick);
                $entityManager2->flush();
                $this->addFlash('success', 'Trick créé.');
                return $this->redirectToRoute('index');
            }
                else
                {
                    $errors = $form->getErrors();
                    $this->addFlash('danger', "$errors");
                }
        }
        return $this->render('tricks/trickCreation.html.twig',[
            'trickForm' => $form->createView()]);
    }
}

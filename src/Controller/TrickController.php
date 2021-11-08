<?php

namespace App\Controller;

use App\Entity\Group;
use App\Entity\Media;
use App\Entity\Trick;
use App\Form\TrickFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\PropertyRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TrickController extends AbstractController
{
    public function show(): Response
    {
        $tricks=1;
        $user_logged_in=null;
        $medias=[1,1,1,1,1];
        $videos=1;//tests purpose
        $images=null;//tests purpose
        $tags=[1,1,1];//tests purpose
        $comments=[1,1,1];//tests purpose
        $edition_mode=false;
        return $this->render('tricks/trickDetails.html.twig',[
            'tricks'=>$tricks,
            'medias'=>$medias,
            'videos'=>$videos,
            'images'=>$images,
            'tags'=>$tags,
            'comments'=>$comments,
            'user_logged_in'=>$user_logged_in,
            'edition_mode'=>$edition_mode]);
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
        /*$tricks=1;
        $user_logged_in=null;
        $medias=[1,1,1,1,1];
        $videos=1;//tests purpose
        $images=null;//tests purpose
        $tags=[1,1,1];//tests purpose
        $comments=[1,1,1];//tests purpose
        $edition_mode=true;
        $description="tecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores e aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?";
        $groupes=['Groupe A','Groupe B','Groupe C'];
        return $this->render('tricks/trickEdition.html.twig',[
            'tricks'=>$tricks,
            'medias'=>$medias,
            'videos'=>$videos,
            'images'=>$images,
            'tags'=>$tags,
            'comments'=>$comments,
            'user_logged_in'=>$user_logged_in,
            'edition_mode'=>$edition_mode,
            'description'=>$description,
            'groups'=>$groupes]);
    }*/

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

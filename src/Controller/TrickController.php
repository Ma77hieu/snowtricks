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
    /**
     * @var TrickServices
     */
    private TrickServices $trickServices;

    /**
     * @param TrickServices $trickServices
     */
    public function __construct(TrickServices $trickServices)
    {
        $this->trickServices = $trickServices;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function create(Request $request): Response
    {
        $trick = new Trick();
        $form = $this->createForm(TrickFormType::class, $trick);
        $form->handleRequest($request);
        $entityManager = $this->getDoctrine()->getManager();
        $serviceReturn=$this->trickServices->createTrick($entityManager,$trick,$form);
        /*$trickService= new TrickServices();
        $serviceReturn=$trickService->createTrick($request);*/
        return $this->controllerReturn($serviceReturn);
    }

    /**
     * @param int $trickId
     * @param Request $request
     * @return Response
     */
    public function show(int $trickId, Request $request): Response
    {
        $comment = new Comment();
        $entityManager = $this->getDoctrine()->getManager();
        $trick = $entityManager->find(Trick::class,$trickId);
        $group = $entityManager->find(Group::class, $trick->getTrickGroup());
        $form = $this->createForm(CommentFormType::class,$comment);
        $form->handleRequest($request);
        $trickService= new TrickServices();
        /*$serviceReturn=$trickService->showTrick($entityManager,$trick,$group,$form,$trickId,$comment);
        return $this->controllerReturn($serviceReturn);*/
        if ($form->isSubmitted())
        {

            if ($form->isValid())
            {
                $user = $this->getUser();

                $comment->setCreationDate(new \DateTime());
                $comment->setTrick($trick);
                $comment->setAuthor($user);
                $comment->setIsValidated(false);
                $entityManager->persist($comment);
                $entityManager->flush();
                $this->addFlash('success', 'Votre commentaire a été soumis, il est en attente de validation de notre équipe.');

            }
            else
            {
                $errors = $form->getErrors();
                $this->addFlash('danger', "$errors");
            }
        }
        $mediaRepository = $this->getDoctrine()->getRepository(Media::class);
        $medias = $mediaRepository->findByTrickId($trickId);
        $mainMediaUrl=null;
        $mainMediaId=null;
        foreach ($medias as $media){
            if($media->getIsMain()){
                $mainMediaUrl=$media->getUrl();
                $mainMediaId=$media->getId();
            }
        }
        $commentRepository = $this->getDoctrine()->getRepository(Comment::class);
        $comments = $commentRepository->findByTrickId($trickId);
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
            'tags'=>$tags,
            'mainMediaUrl'=>$mainMediaUrl,
            'mainMediaId'=>$mainMediaId]);
    }

    /**
     * @param int $trickId
     * @param Request $request
     * @return Response
     */
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
                $entityManager = $this->getDoctrine()->getManager();
                $trick->setModificationDate(new \DateTime());
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
        $mediaRepository = $this->getDoctrine()->getRepository(Media::class);
        $medias = $mediaRepository->findBy(['trick' => $trickId]);
        $groupRepository = $this->getDoctrine()->getRepository(Group::class);
        $groups = $groupRepository->findAll();

        /*var_dump($groups);die;*/
        return $this->render('tricks/trickEdition.html.twig',[
            'trickForm' => $form->createView(),
            'medias' => $medias,
            'trick' => $trick,
            'groups' => $groups]);
    }

    /**
     * @param $input
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
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

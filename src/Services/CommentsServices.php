<?php

namespace App\Services;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
use App\Repository\MediaRepository;
use Doctrine\Persistence\ManagerRegistry;


class CommentsServices extends AbstractController
{
    public function validateComments($commentRepository):array
    {
        /*$comment=new Comment();*/

        $comments = $commentRepository->findByValidationStatus('false');
        /*if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $trickGroup = $entityManager->find(Comment::class, $form["trickGroup"]->getData());
                //define the group to which the trick is linked
                $trick->setTrickGroup($trickGroup);
                //set the creation date to the current date type
                $trick->setCreationDate(new \DateTime());
                //save the trick into database
                $entityManager->persist($trick);
                $entityManager->flush();
                $serviceAnswer = ['returnType' => 'redirect',
                    'path' => 'index',
                    'flashType' => 'success',
                    'flashMessage' => 'Le trick a été créé',
                    'data' => []];
            } else {
                $serviceAnswer = ['returnType' => 'render',
                    'path' => 'tricks/trickCreation.html.twig',
                    'flashType' => 'danger',
                    'flashMessage' => 'Une erreur est survenue, voir le formulaire pour plus de détails',
                    'data' => ['trickForm' => $form->createView()]];
            }
            return $serviceAnswer;
        }*/
        $serviceAnswer = ['returnType' => 'render',
            'path' => 'comments/commentsValidation.html.twig',
            'flashType' => 'success',
            'flashMessage' => null,
            'data' => ['unvalidatedComments' => $comments]];
        return $serviceAnswer;
    }

    /*public function showTrick($entityManager, $trick, $group, $form, $trickId, $comment): array
    {

        if ($form->isSubmitted()) {

            if ($form->isValid()) {
                $user = $this->getUser();

                $comment->setCreationDate(new \DateTime());
                $comment->setTrick($trick);
                $comment->setAuthor($user);
                $entityManager->persist($comment);
                $entityManager->flush();
                $this->addFlash('success', 'Votre commentaire a été ajouté');
            } else {
                $errors = $form->getErrors();
                $this->addFlash('danger', "$errors");
            }
        }
        $mediaRepository = $this->getDoctrine()->getRepository(Media::class);
        $medias = $mediaRepository->findByTrickId($trickId);
        $commentRepository = $this->getDoctrine()->getRepository(Comment::class);
        $comments = $commentRepository->findByTrickId($trickId);
        $tags = [
            'date de creation' => $trick->getCreationDate()->format('Y-m-d H:i:s'),
            'groupe' => $group->getName(),
        ];
        if ($trick->getModificationDate()) {
            $trickModifDate = $trick->getModificationDate()->format('Y-m-d H:i:s');
            $tags['date de modification'] = $trickModifDate;
        }

        $returnData= [
            'commentForm' => $form->createView(),
            'medias' => $medias,
            'comments' => $comments,
            'trick' => $trick,
            'tags' => $tags,];

        $serviceAnswer = ['returnType' => 'render',
            'path' => 'tricks/trickCreation.html.twig',
            'flashType' => 'success',
            'flashMessage' => null,
            'data' => $returnData];

        return $serviceAnswer;
    }*/
}
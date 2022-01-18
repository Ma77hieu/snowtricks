<?php

namespace App\Services;

use App\Entity\Comment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Group;
use App\Entity\Trick;
use Symfony\Component\HttpFoundation\Request;


class CommentService
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $em;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Save a new comment in the database
     * @param int $trickId the ID of the trick for which the comment needs to be created
     * @param Request $request
     */
    public function saveNewComment(int $trickId,  User $user, $commentText)
    {
        $comment = new Comment();
        $trick = $this->em->find(Trick::class, $trickId);
        $comment->setCommentText($commentText);
        $comment->setCreationDate(new \DateTime());
        $comment->setTrick($trick);
        $comment->setAuthor($user);
        $comment->setIsValidated(false);
        $this->em->persist($comment);
        $this->em->flush();
        return $comment->getId();
    }

    public function validateComments($commentRepository, $commentId): array
    {
        /*$comment=new Comment();*/
        $em = $this->em->getDoctrine()->getManager();
        $commentToValidate = $commentRepository->find($commentId);
        $commentToValidate->setIsValidated(true);
        $em->persist($commentToValidate);
        $em->flush();
        $relatedTrick = $commentToValidate->getTrick()->getName();
        $unvalidatedComments = $commentRepository->findByValidationStatus('false');
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
            'flashMessage' => "le commentaire Id $commentId a été validé pour le trick $relatedTrick",
            'data' => ['unvalidatedComments' => $unvalidatedComments]];
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
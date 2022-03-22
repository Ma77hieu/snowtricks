<?php

namespace App\Services;

use App\Entity\Comment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Trick;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\AsciiSlugger;

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
    public function saveNewComment(int $trickId, User $user, $commentText)
    {
        $comment = new Comment();
        $trick = $this->em->find(Trick::class, $trickId);
        $comment->setCommentText($commentText);
        $comment->setCreationDate(new \DateTime());
        //also set the modification date, this way we can get the latest comments based on the modification date
        $comment->setModificationDate(new \DateTime());
        $comment->setTrick($trick);
        $comment->setAuthor($user);
        $comment->setIsValidated(false);
        $this->em->persist($comment);
        $this->em->flush();
        return $comment->getId();
    }

    /**
     * Handles a new comment form submission (issued from the trick details page)
     * @param Request $request
     * @param $user
     * @param $form
     * @param int $trickId
     * @return array|false[]
     */
    public function handleNewCommentForm(Request $request, $user, $form, int $trickId)
    {
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $commentText = $form->get('commentText')->getData();
                $newCommentId = $this->saveNewComment($trickId, $user, $commentText);
                if (gettype($newCommentId) == 'integer') {
                    $flashType = 'success';
                    $flashMessage = 'Votre commentaire a été soumis, 
                    il est en attente de validation de notre équipe.';
                } else {
                    $flashType = 'danger';
                    $flashMessage = 'Problème lors de la soumission de votre commentaire, 
                    veuillez réessayer';
                }
            } else {
                $errors = $form->getErrors();
                $flashType = 'danger';
                $flashMessage = "$errors";
            }
            return ['needFlash' => true,'flashType' => $flashType, 'flashMessage' => $flashMessage];
        } else {
            return ['needFlash' => false];
        }
    }

    /**
     * handle the display and submission of the modification comment form
     * returns all the information required by the controllerReturn function of comments controller
     * @param Request $request
     * @param $form
     * @param int $commentToUpdate
     * @param int $commentId
     * @return mixed
     */
    public function handleModificationForm(Request $request, $form, Comment $commentToUpdate, int $commentId)
    {
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $updatedText = $form->get('commentText')->getData();
                $commentToUpdate->setCommentText($updatedText);
                $commentToUpdate->setModificationDate(new \DateTime());
                $this->em->persist($commentToUpdate);
                $this->em->flush();
                $relatedTrickId = $commentToUpdate->getTrick()->getId();
                $slugger = new AsciiSlugger();
                $slug = $slugger->slug($commentToUpdate->getTrick()->getName());
                $serviceReturn = ['returnType' => 'redirect',
                    'path' => 'Trick.show',
                    'flashType' => 'success',
                    'flashMessage' => "le commentaire Id $commentId a été modifié pour le trick $relatedTrickId",
                    'data' => ['trickId' => $relatedTrickId,'slug'=>$slug]];
            } else {
                $serviceReturn = ['returnType' => 'redirect',
                    'path' => 'Comment.update',
                    'flashType' => 'danger',
                    'flashMessage' => "Un problème est survenu avec la modification du commentaire, merci de reessayer",
                    'data' => ['commentId' => $commentId]];
            }
            return $serviceReturn;
        }
        $serviceReturn = ['returnType' => 'render',
            'path' => 'comments/commentEdition.html.twig',
            'flashType' => 'danger',
            'flashMessage' => null,
            'data' => ['commentId' => $commentId,
                'commentEditionForm' => $form->createView()]];
        return $serviceReturn;
    }


    /**
     * Set the isValidated property to true for a specific comments
     * returns all the information required by the controllerReturn function of comments controller
     * @param $commentId
     * @return array
     */
    public function validateComments($commentId): array
    {
        $commentRepository = $this->em->getRepository(Comment::class);
        $commentToValidate = $commentRepository->find($commentId);
        $commentToValidate->setIsValidated(true);
        $this->em->persist($commentToValidate);
        $this->em->flush();
        $relatedTrick = $commentToValidate->getTrick()->getName();
        $unvalidatedComments = $commentRepository->findByValidationStatus('false');
        $serviceAnswer = ['returnType' => 'render',
            'path' => 'comments/commentsValidation.html.twig',
            'flashType' => 'success',
            'flashMessage' => "le commentaire Id $commentId a été validé pour le trick $relatedTrick",
            'data' => ['unvalidatedComments' => $unvalidatedComments]];
        return $serviceAnswer;
    }

    /**
     * returns the list of validated comments for a specific trickId
     * @param int $trickId
     * @return mixed
     */
    public function validatedComsForTrickId(int $trickId)
    {
        $commentRepository = $this->em->getRepository(Comment::class);
        $comments = $commentRepository->findOkComsTrickId($trickId);
        return $comments;
    }

    /**
     * delete from the database the comment corresponding to the parameter commentId
     * returns all the information required by the controllerReturn function of comments controller
     * @param int $commentId
     * @return array
     */
    public function deleteComment(int $commentId): array
    {
        $commentRepository = $this->em->getRepository(Comment::class);
        $commentToDelete = $commentRepository->find($commentId);
        $relatedTrickName = $commentToDelete->getTrick()->getName();
        $trickId = $commentToDelete->getTrick()->getId();
        $this->em->remove($commentToDelete);
        $this->em->flush();
        $serviceReturn = ['returnType' => 'redirect',
            'path' => 'Trick.show',
            'flashType' => 'success',
            'flashMessage' => "le commentaire Id $commentId en lien avec le trick $relatedTrickName a été supprimé",
            'data' => ['trickId' => $trickId]];
        return $serviceReturn;
    }
}

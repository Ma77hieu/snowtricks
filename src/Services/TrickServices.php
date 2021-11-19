<?php

namespace App\Services;

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
use App\Repository\PropertyRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TrickServices
{
    public function createTrick($entityManager, $trick, $form)
    {
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $trickGroup = $entityManager->find(Group::class, $form["trickGroup"]->getData());
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
        }
        $serviceAnswer = ['returnType' => 'render',
            'path' => 'tricks/trickCreation.html.twig',
            'flashType' => 'success',
            'flashMessage' => null,
            'data' => ['trickForm' => $form->createView()]];
        return $serviceAnswer;
    }
}
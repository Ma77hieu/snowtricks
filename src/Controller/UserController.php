<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use App\Repository\PropertyRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserController extends AbstractController
{
    public function create(): Response
    {
        return $this->render('user/register.html.twig', [
        ]);
    }

    public function login(): Response
    {
        return $this->render('user/login.html.twig', [
        ]);
    }

    public function logout(): Response
    {
        return $this->render('generic/base.html.twig', [
        ]);
    }

    public function forgot(): Response
    {
        return $this->render('user/forgotPassword.html.twig', [
        ]);
    }

    public function reset(): Response
    {
        return $this->render('user/resetPassword.html.twig', [
        ]);
    }
}

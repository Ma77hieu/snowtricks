<?php

namespace App\Controller;

use App\Entity\User;
use App\Services\UserService;
use App\Form\ResetPwdFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserController extends AbstractController
{
    /**
     * @var UserService
     */
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

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

    /**
     * can either serve the forgot paswword form for get request or send the reset pwd email if post
     * @param Request $request
     * @return Response
     */
    public function forgot(Request $request): Response
    {
        $form = $this->createForm(ResetPwdFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $username = $form->get('username')->getData();
            $userForgot = $this->getDoctrine()->getRepository(User::class)->findOneBy(['username' => $username]);
            if (isset($userForgot)) {
                $token = $this->userService->createResetToken($userForgot);
                $this->userService->persistUser($userForgot);
                $this->userService->sendResetPwdMail($userForgot, $token);
                if ($this->userService->emailError == '') {
                    $this->addFlash("success", "Un email pour réinitialiser votre mot de passe vous a été envoyé");
                    return $this->redirectToRoute('index');
                } else {
                    $this->addFlash("danger", "Problème lors de l'envoi de l'email, merci de rééssayer");
                    return $this->render('user/forgotPassword.html.twig', [
                        'resetPwdForm' => $form->createView(),
                    ]);
                }
            } else {
                $this->addFlash("danger", "Il n'existe pas d'utilisateur avec ce nom d'utilisateur");
                return $this->render('user/forgotPassword.html.twig', [
                    'resetPwdForm' => $form->createView(),
                ]);
            }
        }
        return $this->render('user/forgotPassword.html.twig', [
            'resetPwdForm' => $form->createView(),
        ]);
    }
}

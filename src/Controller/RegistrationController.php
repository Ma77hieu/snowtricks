<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\NewPwdFormType;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use App\Services\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    /**
     * @var UserService
     */
    private UserService $userService;

    /**
     * @var EmailVerifier
     */
    private EmailVerifier $emailVerifier;

    /**
     * @var UserPasswordHasherInterface
     */
    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(
        EmailVerifier $emailVerifier,
        UserService $userService,
        UserPasswordHasherInterface $userPasswordHasher
    ){
        $this->emailVerifier = $emailVerifier;
        $this->userService = $userService;
        $this->userPasswordHasher = $userPasswordHasher;
    }

    /**
     * Used to register a new user
     * @param Request $request
     * @param UserPasswordHasherInterface $userPwdHasherInt
     * @return Response
     */
    public function register(UserPasswordHasherInterface $userPwdHasherInt, Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $this->userService->saveHashedPassword($userPwdHasherInt, $user, $plainPassword);
            $this->userService->persistUser($user);
            $this->userService->sendConfirmationMail($user);
            $this->addFlash(
                'warning',
                'Un email vous a été envoyé pour finaliser la création de votre compte, il expirera dans une heure.'
            );
            return $this->redirectToRoute('index');
        } else {
            return $this->render('user/register.html.twig', [
                'registrationForm' => $form->createView(),
            ]);
        }
    }

    /**
     * Road called by a click on the link of the verification email sent when a user signup
     * @param Request $request
     * @return Response
     */
    public function verifyUserEmail(Request $request): Response
    {

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $this->getUser());
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('danger', $exception->getReason());

            return $this->redirectToRoute('User.register');
        }

        $this->addFlash('success', 'Votre adresse mail a été vérifiée.');

        return $this->redirectToRoute('index');
    }

    /**
     * Road called by a click on the link of the reset pwd email sent when a user
     * follows the reset password procedure
     * @param Request $request
     * @return Response
     */
    public function resetPwdFromMail(Request $request): Response
    {
        $token = $request->query->get('token');
        $form = $this->createForm(NewPwdFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $username = $form->get('username')->getData();
            $userReset = $this->getDoctrine()->getRepository(User::class)->findOneBy(['username' => $username]);
            $isValidToken = $this->userService->verifyTokenValidity($userReset, $token);
            if ($isValidToken) {
                $plainPassword = $form->get('plainPassword')->getData();
                $this->userService->saveHashedPassword($this->userPasswordHasher, $userReset, $plainPassword);
                $userReset->setIsVerified(true);
                $userReset->setResetPwdToken('');
                $this->userService->persistUser($userReset);
                $this->addFlash('success', 'Votre changement de mot de passe a été effectué.');
                return $this->redirectToRoute('index');
            } else {
                $this->addFlash(
                    'danger',
                    'Problème avec la réinitialisation, veuillez recommencer la procédure de renouvellement.');
                return $this->redirectToRoute('User.forgot');
            }
        }
        return $this->render('user/resetPassword.html.twig', [
            'newPwdForm' => $form->createView(),
        ]);
    }
}

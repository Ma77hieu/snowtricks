<?php

namespace App\Services;

use App\Entity\User;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Security\EmailVerifier;
use Symfony\Component\Mailer\MailerInterface;

class UserService
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $em;

    /**
     * @var MailerInterface
     */
    private MailerInterface $mailerInterface;

    /**
     * @var EmailVerifier
     */
    private EmailVerifier $emailVerifier;

    /**
     * @var string
     */
    public string $emailError = '';

    public function __construct(
        EntityManagerInterface $em,
        EmailVerifier $emailVerifier,
        MailerInterface $mailerInterface
    ){
        $this->em = $em;
        $this->emailVerifier = $emailVerifier;
        $this->mailerInterface = $mailerInterface;
    }

    /**
     * associate the new password (hashed) to the password attribute of the user entity
     * WARNING: user entity needs ot be persisted after this function is executed
     * @param UserPasswordHasherInterface $userPwdHasherInt
     * @param User $user
     * @param string $plainPassword
     */
    public function saveHashedPassword(
        UserPasswordHasherInterface $userPwdHasherInt,
        User $user,
        string $plainPassword
    ) {
        // encode the plain password
        $user->setPassword(
            $userPwdHasherInt->hashPassword(
                $user,
                $plainPassword
            )
        );
    }

    /**
     * persists a user in te database with a standard user role
     * @param User $user
     */
    public function persistUser(User $user)
    {
        $user->setRoles(["ROLE_USER"]);
        $this->em->persist($user);
        $this->em->flush();
    }


    /**
     * Sends an email with a verification token to the user when he
     * signs up or reset her/his password
     * @param User $user
     */
    public function sendConfirmationMail(User $user)
    {
        $template = 'registration/confirmation_email.html.twig';
        $mailLinkRoute = 'User.verify.email';
        $mailSubject = 'Merci de confirmer votre Email';
        $this->emailVerifier->sendEmailConfirmation(
            $mailLinkRoute,
            $user,
            (new TemplatedEmail())
                ->from(new Address('testmateo42@gmail.com', 'Snowtricks mail bot'))
                ->to($user->getEmail())
                ->subject($mailSubject)
                ->htmlTemplate($template)
        );
    }

    /**
     * Sends the reset pwd email to the user. There is a link inside this mail to
     * the resetPwd url with the reset token passed as a parameter
     *
     * @param User $user
     * @param string $token
     * @throws TransportExceptionInterface
     */
    public function sendResetPwdMail(User $user, string $token)
    {
        $urlTokenParameter = '?token=' . $token;
        $template = 'registration/resetPwd_confirmation_email.html.twig';
        $mailSubject = "Génération d'un nouveau mot de passe snowtricks";
        $context = ['urlTokenParameter' => $urlTokenParameter];

        $email = (new TemplatedEmail())
            ->from(new Address('testmateo42@gmail.com', 'Snowtricks mail bot'))
            ->to($user->getEmail())
            ->subject($mailSubject)
            ->htmlTemplate($template)
            ->context($context);

        try {
            $this->mailerInterface->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->emailError = $e;
        }
    }

    /**
     * @param User $user
     * @return string
     * @throws \Exception
     */
    public function createResetToken(User $user): string
    {
        $user->setIsVerified(false);
        $length = User::RESET_PWD_TOKEN_LENGTH;
        $token = bin2hex(random_bytes($length));
        $now = new \DateTime('now');
        $expiration = $now->add(new DateInterval(User::TIME_BEFORE_EXPIRATION));
        $user->setResetPwdToken($token);
        $user->setTokenExpirationDate($expiration);

        return $token;
    }

    /**
     * Returns true or false base on the validaty of the reset password token of the user
     * checks the content of the token and the expiration
     * @param User $user
     * @param string $token
     * @return bool
     */
    public function verifyTokenValidity(User $user, string $token): bool
    {
        $now = new \DateTime();
        $tokenInDb = $user->getResetPwdToken();
        if ($token == $tokenInDb && $now < $user->getTokenExpirationDate()) {
            return true;
        }
        return false;
    }
}

<?php
// src/Controller/PasswordResetController.php
namespace App\Controller;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordResetController extends AbstractController
{
    #[Route('/api/request-password-reset', name: 'request_password_reset', methods: ['POST'])]
    public function requestPasswordReset(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, MailerInterface $mailer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['email'])) {
            return $this->json(['message' => 'Invalid data'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = $userRepository->findOneBy(['email' => $data['email']]);

        if (!$user) {
            return $this->json(['message' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $token = new PasswordResetToken($user);
        $entityManager->persist($token);
        $entityManager->flush();

        // Send email with reset link
        $email = (new Email())
            ->from('youness@example.com')
            ->to($user->getEmail())
            ->subject('Your password reset request')
            ->html('<p>To reset your password, please click the following link: <a href="http://localhost:8000/reset-password-page/' . $token->getToken() . '">Reset Password</a></p>');

        $mailer->send($email);

        return $this->json(['message' => 'Password reset email sent'], JsonResponse::HTTP_OK);
    }

    #[Route('/api/reset-password', name: 'reset_password', methods: ['POST'])]
    public function resetPassword(Request $request, PasswordResetTokenRepository $tokenRepository, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['token']) || empty($data['password'])) {
            return $this->json(['message' => 'Invalid data'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $token = $tokenRepository->findOneBy(['token' => $data['token']]);

        if (!$token || $token->isExpired()) {
            return $this->json(['message' => 'Invalid or expired token'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = $token->getUser();
        $user->setPassword($passwordHasher->hashPassword($user, $data['password']));

        $entityManager->persist($user);
        $entityManager->remove($token);
        $entityManager->flush();

        return $this->json(['message' => 'Password reset successfully'], JsonResponse::HTTP_OK);
    }

    #[Route('/reset-password-page/{token}', name:"reset-password-page", methods:['Get'])]
    public function resetPasswordPage($token)
    {
        return $this->render('resetPasswordPage.html.twig', [
            // 'token' => $token,
            'token' => htmlspecialchars($token),
        ]);
    }

}
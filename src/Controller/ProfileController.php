<?php

namespace App\Controller;

use App\Entity\User;
use App\Horaro\DTO\DeletePasswordDto;
use App\Horaro\DTO\ProfileUpdateDto;
use App\Horaro\DTO\UpdatePasswordDto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ProfileController extends BaseController
{
    #[Route('/-/profile', name: 'app_profile', methods: ['GET'], priority: 1)]
    public function index(): Response
    {
        $user = $this->getCurrentUser();

        return $this->renderForm($user);
    }

    #[IsCsrfTokenValid('horaro', tokenKey: '_csrf_token')]
    #[Route('/-/profile', name: 'app_profile_update', methods: ['PUT'], priority: 1)]
    public function updateProfile(#[MapRequestPayload] ProfileUpdateDto $updateDto): Response
    {
        $user = $this->getCurrentUser();

        $user->setDisplayName($updateDto->getDisplayName());
        $user->setLanguage($updateDto->getLanguage());
        $user->setGravatarHash($updateDto->getGravatar());

        $this->entityManager->flush();

        $this->addSuccessMsg('Your profile has been updated.');

        return $this->redirect('/-/profile');
    }

    #[IsCsrfTokenValid('horaro', tokenKey: '_csrf_token')]
    #[Route('/-/profile/password', name: 'app_profile_update_password', methods: ['PUT'], priority: 1)]
    public function updatePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        #[MapRequestPayload] UpdatePasswordDto $updateDto,
    ): Response {
        $user = $this->getCurrentUser();

        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $updateDto->getPassword()
        );

        $user->setPassword($hashedPassword);

        $this->entityManager->flush();

        $this->createFreshSession($request, 'Your password has been changed.');

        return $this->redirect('/-/profile');
    }

    #[Route('/-/profile/oauth', name: 'app_profile_oauth', methods: ['GET'], priority: 1)]
    public function oauth(): Response {
        $user = $this->getCurrentUser();

        if ($user->getTwitchOAuth() === null || $user->getPassword() === null) {
            return $this->redirect('/-/profile');
        }

        return $this->renderOAuthForm($user);
    }

    #[IsCsrfTokenValid('horaro', tokenKey: '_csrf_token')]
    #[Route('/-/profile/oauth', name: 'app_profile_oauth_unconnect', methods: ['DELETE'], priority: 1)]
    public function disconnectOauth(Request $request) {
        $user = $this->getCurrentUser();

        if ($user->getTwitchOAuth() === null) {
            $this->addErrorMsg('Your account is not linked with any Twitch account.');
            return $this->redirect('/-/profile/oauth');
        }

        if ($user->getPassword() === null) {
            $this->addErrorMsg('You cannot remove the only access to your account.');
            return $this->redirect('/-/profile/oauth');
        }

        // update profile

        $user->setTwitchOAuth(null);
        $this->entityManager->flush();
        $this->createFreshSession($request, 'Your account is no longer connected to any Twitch account.');

        return $this->redirect('/-/profile');
    }

    #[IsCsrfTokenValid('horaro', tokenKey: '_csrf_token')]
    #[Route('/-/profile/password', name: 'app_profile_unconnect_password', methods: ['DELETE'], priority: 1)]
    public function removePassword(
        Request $request,
        #[MapRequestPayload] DeletePasswordDto $deleteDto,
    ): Response {
        $user = $this->getCurrentUser();

        if ($user->getPassword() === null) {
            $this->addErrorMsg('You already have no password.');
            return $this->redirect('/-/profile');
        }

        if ($user->getTwitchOAuth() === null) {
            $this->addErrorMsg('You can only remove your password if your account is linked to Twitch.');
            return $this->redirect('/-/profile');
        }

        $user->setPassword(null);
        $this->entityManager->flush();
        $this->createFreshSession($request, 'Your password has been removed. Login via Twitch from now on.');

        return $this->redirect('/-/profile');
    }

    protected function renderForm(User $user, ?array $result = null): ?Response
    {
        return $this->render('profile/form.twig', [
            'result' => $result,
            'user' => $user,
            'languages' => $this->getLanguages(),
        ]);
    }

    protected function renderOAuthForm(User $user, ?array $result = null): Response
    {
        return $this->render('profile/oauth.twig', [
            'result' => $result,
            'user' => $user,
        ]);
    }



    protected function createFreshSession(Request $request, string $successMsg): void {
        $session = $request->getSession();

        $session->migrate();

        // done

        $this->addSuccessMsg($successMsg);
    }
}

<?php

namespace App\Controller;

use App\Entity\User;
use App\Horaro\DTO\ProfileUpdateDto;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class ProfileController extends BaseController
{
    #[Route('/-/profile', name: 'app_profile', methods: ['GET'], priority: 1)]
    public function index(): Response
    {
        $user = $this->getCurrentUser();

        return $this->renderForm($user);
    }

    #[Route('/-/profile', name: 'app_profile_update', methods: ['POST'], priority: 1)]
    public function updateProfile(#[MapRequestPayload] ProfileUpdateDto $updateDto): Response {
        $user      = $this->getCurrentUser();

        $user->setDisplayName($updateDto->getDisplayName());
        $user->setLanguage($updateDto->getLanguage());
        $user->setGravatarHash($updateDto->getGravatar());

        $this->entityManager->flush();

        $this->addSuccessMsg('Your profile has been updated.');

        return $this->redirect('/-/profile');
    }

    protected function renderForm(User $user, ?array $result = null): ?Response {
        return $this->render('profile/form.twig', [
            'result'    => $result,
            'user'      => $user,
            'languages' => $this->getLanguages(),
        ]);
    }

    protected function renderOAuthForm(User $user, ?array $result = null): Response {
        return $this->render('profile/oauth.twig', [
            'result' => $result,
            'user'   => $user,
        ]);
    }
}

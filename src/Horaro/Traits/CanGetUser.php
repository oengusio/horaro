<?php

namespace App\Horaro\Traits;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

trait CanGetUser
{
    public function getUserFromToken(TokenInterface $token): ?User {
        $user = $token->getUser();

        if ($user instanceof User) {
            return $user;
        }

        return null;
    }

    public function getUserFromAdminRequest(): ?User
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return null;
        }

        if (!$request->attributes->has('__horaro_resolved_user')) {
            $user = $this->entityManager
                ->getRepository(User::class)
                ->findOneBy(['id' => $request->attributes->get('user')]);

            $request->attributes->set('__horaro_resolved_user', $user);
        }

        return $request->attributes->get('__horaro_resolved_user');
    }
}

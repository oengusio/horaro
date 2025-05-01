<?php

namespace App\Horaro\Traits;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

trait CanGetUser
{
    public function getUser(): ?User {
        $securityUser = $this->security->getUser();

        if ($securityUser instanceof User) {
            return $securityUser;
        }

        return null;
    }

    public function getUserFromToken(TokenInterface $token): ?User {
        $user = $token->getUser();

        if ($user instanceof User) {
            return $user;
        }

        return null;
    }
}

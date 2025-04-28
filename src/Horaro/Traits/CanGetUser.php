<?php

namespace App\Horaro\Traits;

use App\Entity\User;

trait CanGetUser
{
    public function getUser(): ?User {
        $securityUser = $this->security->getUser();

        if ($securityUser instanceof User) {
            return $securityUser;
        }

        return null;
    }
}

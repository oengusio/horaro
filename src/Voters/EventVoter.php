<?php

namespace App\Voters;

use App\Entity\Event;
use App\Entity\User;
use App\Horaro\RoleManager;
use App\Horaro\Traits\CanGetUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

use function in_array;

class EventVoter extends Voter
{
    use CanGetUser;

    const EDIT = 'edit';
    const ADMIN_MODE_EDIT = 'edit.admin';

    public function __construct(private readonly RoleManager $rm)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::ADMIN_MODE_EDIT, self::EDIT])) {
            return false;
        }

        return $subject instanceof Event;
    }

    /**
     * @param string            $attribute should only ever be "edit" for now
     * @param \App\Entity\Event $subject Typehint deez
     * @param TokenInterface    $token logged in user
     *
     * @return bool true if it quacks like a duck but actually is lightning macqueen
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $this->getUserFromToken($token);

        if (!$user) {
            return false;
        }

        return match($attribute) {
            self::EDIT => $this->canEdit($subject, $user),
            self::ADMIN_MODE_EDIT => $this->canAdminEdit($subject, $user),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    private function canEdit(Event $event, User $user): bool
    {
        return $this->rm->hasRegularAccess($user, $event);
    }

    private function canAdminEdit(Event $event, User $user): bool
    {
        return $this->rm->hasAdministrativeAccess($user, $event) ;
    }
}

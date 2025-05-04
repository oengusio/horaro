<?php

namespace App\Horaro;


use App\Entity\Event;
use App\Entity\Schedule;
use App\Entity\ScheduleColumn;
use App\Entity\ScheduleItem;
use App\Entity\User;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

use function array_search;

class RoleManager
{

    /**
     * @var string[]
     */
    private readonly array $roles;

    public function __construct(ContainerBagInterface $params)
    {
        $this->roles = $params->get('horaro.roles');
    }

    public function getWeight(string $role): int {
        $weight = array_search($role, $this->roles);

        if ($weight === false) {
            throw new \InvalidArgumentException('Unknown role "'.$role.'" given.');
        }

        return $weight;
    }

    public function isIncluded(string $role, string $inThisRole): bool {
        return $this->getWeight($role) <= $this->getWeight($inThisRole);
    }

    public function userHasRole(string $role, User $user): bool {
        return $this->isIncluded($role, $user->getRole());
    }

    public function userIsSuperior(User $user, User $to): bool {
        return $this->getWeight($to->getRole()) < $this->getWeight($user->getRole());
    }

    public function userIsColleague(User $user, User $to): bool {
        return $to->getRole() === $user->getRole();
    }

    public function userIsOp(User $user): bool {
        return $this->userHasRole('ROLE_OP', $user);
    }

    public function userIsAdmin(User $user): bool {
        return $this->userHasRole('ROLE_ADMIN', $user);
    }

    public function canEditUser(User $editor, User $toBeEdited): bool {
        if ($editor->getId() === $toBeEdited->getId()) {
            return true;
        }

        return !$this->userIsSuperior($toBeEdited, $editor) && !$this->userIsColleague($toBeEdited, $editor);
    }

    public function canEditEvent(User $editor, Event $event): bool {
        return $this->canEditUser($editor, $event->getUser());
    }

    public function canEditSchedule(User $editor, Schedule $schedule): bool {
        return $this->canEditEvent($editor, $schedule->getEvent());
    }

    public function hasRegularAccess(User $user, mixed $resource): bool {
        $owner = $this->getUserFromResource($resource);

        return $owner && $owner->getId() === $user->getId();
    }

    public function hasAdministrativeAccess(User $user, mixed $resource): bool {
        $owner = $this->getUserFromResource($resource);

        return $owner && $this->isIncluded($owner->getRole(), $user->getRole());
    }

    protected function getUserFromResource(User|Schedule|ScheduleColumn|ScheduleItem|Event $resource): ?User {
        if ($resource instanceof User) {
            return $resource;
        }

        if ($resource instanceof ScheduleItem) {
            return $this->getUserFromResource($resource->getSchedule());
        }

        if ($resource instanceof ScheduleColumn) {
            return $this->getUserFromResource($resource->getSchedule());
        }

        if ($resource instanceof Schedule) {
            return $this->getUserFromResource($resource->getEvent());
        }

        if ($resource instanceof Event) {
            return $this->getUserFromResource($resource->getOwner());
        }

        return null;
    }
}

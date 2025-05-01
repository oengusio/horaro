<?php

namespace App\Horaro;


use App\Entity\Event;
use App\Entity\Schedule;
use App\Entity\ScheduleColumn;
use App\Entity\ScheduleItem;
use App\Entity\User;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class RoleManager
{

    /**
     * @var string[]
     */
    private readonly array $roles;

    public function __construct(ContainerBagInterface $params)
    {
        $this->roles = $params->get('horaro.roles');

//        dd($this->roles);
    }


    protected function getUserFromResource(User|Schedule|ScheduleColumn|ScheduleItem|Event $resource): User {
        return match(get_class($resource)) {
            User::class => $resource,
            ScheduleItem::class => $this->getUserFromResource($resource->getSchedule()),
            ScheduleColumn::class => $this->getUserFromResource($resource->getSchedule()),
            Schedule::class => $this->getUserFromResource($resource->getEvent()),
            Event::class => $this->getUserFromResource($resource->getOwner()),
        };
    }
}

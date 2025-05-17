<?php

namespace App\Horaro\DTO;

use App\Entity\Event;
use Symfony\Component\Validator\Constraints as Assert;

class EventDescriptionUpdateDto
{
    #[Assert\Length(
        max: 16*1024,
        maxMessage: 'The description cannot be longer than 16k characters.',
    )]
    private string $description = '';

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public static function fromEvent(Event $event): static
    {
        $dto = new static();

        $dto->description = $event->getDescription();

        return $dto;
    }

}

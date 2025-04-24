<?php

namespace App\Entity;

use App\Repository\ScheduleItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'schedule_items')]
#[ORM\Entity(repositoryClass: ScheduleItemRepository::class)]
class ScheduleItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $position = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $length = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Schedule $schedule = null;

    /**
     * calculated scheduled date; this is not synchronized with the database,
     * but meant to be set by the ScheduleItemIterator.
     */
    private ?\DateTimeInterface $scheduled = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $extra = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getLength(): ?\DateTimeInterface
    {
        return $this->length;
    }

    public function setLength(\DateTimeInterface $length): static
    {
        $this->length = $length;

        return $this;
    }

    public function getSchedule(): ?Schedule
    {
        return $this->schedule;
    }

    public function setSchedule(?Schedule $schedule): static
    {
        $this->schedule = $schedule;

        return $this;
    }

    public function getScheduled(): ?\DateTimeInterface
    {
        return $this->scheduled;
    }

    public function setScheduled(\DateTimeInterface $scheduled): static
    {
        $this->scheduled = $scheduled;

        return $this;
    }

    public function getExtra(): ?string
    {
        return $this->extra;
    }

    public function setExtra(?string $extra): static
    {
        $this->extra = $extra;

        return $this;
    }

    // Custom functions

    public function getISODuration(): string {
        $iso = preg_replace('/(?<=[THMS])0+[HMS]/', '$1', $this->length->format('\P\TG\Hi\Ms\S'));

        if ($iso === 'PT') {
            $iso = 'PT0S';
        }

        return $iso;
    }

    public function getDateInterval(): \DateInterval
    {
        return new \DateInterval($this->getISODuration());
    }
}

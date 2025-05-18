<?php

namespace App\Entity;

use App\Horaro\Library\ReadableTime;
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

    public function getPosition(): int
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

    public function getExtra(): array {
        return json_decode($this->extra, true);
    }

    public function setExtra(array $extra): static {
        foreach ($extra as $key => $value) {
            if (mb_strlen(trim($value)) === 0) {
                unset($extra[$key]);
            }
        }

        ksort($extra);
        $this->extra = json_encode($extra);

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

    public function getWidth($columns) {
        $len   = 0;
        $extra = $this->getExtra();

        foreach ($columns as $idx => $column) {
            if (isset($extra[$column->getId()]) && mb_strlen(trim($extra[$column->getId()])) > 0) {
                $len = $idx;
            }
        }

        return $len;
    }

    /*public function getScheduled(\DateTimeZone $timezone = null) {
        $scheduled = clone $this->scheduled;

        if ($timezone) {
            $scheduled->setTimezone($timezone);
        }

        return $scheduled;
    }*/

    /**
     * Get scheduled
     *
     * @return \DateTime
     */
    public function getScheduledEnd(?\DateTimeZone $timezone = null): \DateTimeInterface {
        if ($this->scheduled === null) {
            throw new \LogicException('Can only determine the scheduled end if the schedule start has been set.');
        }

        $scheduled = clone $this->scheduled;
        $scheduled->add($this->getDateInterval());

        if ($timezone) {
            $scheduled->setTimezone($timezone);
        }

        return $scheduled;
    }

    public function getLengthInSeconds(): int {
        $parts = explode(':', $this->getLength()->format('H:i:s'));

        return $parts[0] * 3600 + $parts[1] * 60 + $parts[2];
    }

    public function getOptions(?ScheduleColumn $optionsCol = null): ?array
    {
        if ($optionsCol === null) {
            $optionsCol = $this->getSchedule()->getOptionsColumn();

            if ($optionsCol === null) {
                return null;
            }
        }

        $colID   = $optionsCol->getID();
        $extra   = $this->getExtra();
        $options = null;

        if (isset($extra[$colID])) {
            $decoded = @json_decode($extra[$colID], false, 5);

            if (json_last_error() === JSON_ERROR_NONE && $decoded instanceof \stdClass) {
                $options = (array) $decoded;
            }
        }

        return $options;
    }

    public function getSetupTime(?ScheduleColumn $optionsCol = null): ?\DateInterval {
        $options = $this->getOptions($optionsCol);

        if (!empty($options['setup'])) {
            try {
                $parser = new ReadableTime();
                $parsed = $parser->parse(trim($options['setup']));

                if ($parsed) {
                    return ReadableTime::dateTimeToDateInterval($parsed);
                }
            }
            catch (\InvalidArgumentException $e) {
                // ignore bad user input
            }
        }

        return null;
    }

    public function setLengthInSeconds($seconds): static {
        return $this->setLength(\DateTime::createFromFormat('U', $seconds));
    }
}

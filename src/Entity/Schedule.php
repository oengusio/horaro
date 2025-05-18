<?php

namespace App\Entity;

use AllowDynamicProperties;
use App\Horaro\Library\ReadableTime;
use App\Horaro\Library\ScheduleItemIterator;
use App\Repository\ScheduleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[AllowDynamicProperties]
#[ORM\Table(name: 'schedules')]
#[ORM\Entity(repositoryClass: ScheduleRepository::class)]
class Schedule
{
    const OPTION_COLUMN_NAME = '[[options]]';

    const COLUMN_SCHEDULED = 'col-scheduled';
    const COLUMN_ESTIMATE  = 'col-estimate';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(length: 255)]
    private ?string $timezone = null;

    #[ORM\Column(type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $start = null;

    #[ORM\Column(length: 255)]
    private ?string $website = null;

    #[ORM\Column(length: 255)]
    private ?string $twitter = null;

    #[ORM\Column(length: 255)]
    private ?string $twitch = null;

    #[ORM\Column(length: 255)]
    private ?string $theme = null;

    #[ORM\Column(length: 255)]
    private ?string $secret = null;

    #[ORM\Column(length: 255)]
    private ?string $hidden_secret = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, length: 255, nullable: true)]
    private ?\DateTimeInterface $setup_time = null;

    #[ORM\Column]
    private ?int $max_items = null;

    #[ORM\Column(type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $updated_at = null;

    #[ORM\Column(length: 255)]
    private ?string $extra = null;

    /**
     * @var Collection<int, ScheduleItem>
     */
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[ORM\OneToMany(targetEntity: ScheduleItem::class, mappedBy: 'schedule', orphanRemoval: true)]
    private Collection $items;

    /**
     * @var Collection<int, ScheduleColumn>
     */
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[ORM\OneToMany(targetEntity: ScheduleColumn::class, mappedBy: 'schedule', orphanRemoval: true)]
    private Collection $columns;

    #[ORM\ManyToOne(inversedBy: 'schedules')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Event $event = null;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->columns = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getStart(): ?\DateTimeInterface
    {
        return $this->start;
    }

    public function setStart(\DateTimeInterface $start): static
    {
        $this->start = $start;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(string $website): static
    {
        $this->website = $website;

        return $this;
    }

    public function getTwitter(): ?string
    {
        return $this->twitter;
    }

    public function setTwitter(string $twitter): static
    {
        $this->twitter = $twitter;

        return $this;
    }

    public function getTwitch(): ?string
    {
        return $this->twitch;
    }

    public function setTwitch(string $twitch): static
    {
        $this->twitch = $twitch;

        return $this;
    }

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(string $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    public function getSecret(): ?string
    {
        return $this->secret;
    }

    public function setSecret(string $secret): static
    {
        $this->secret = $secret;

        return $this;
    }

    public function getHiddenSecret(): ?string
    {
        return $this->hidden_secret;
    }

    public function setHiddenSecret(string $hidden_secret): static
    {
        $this->hidden_secret = $hidden_secret;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getSetupTime(): ?\DateTimeInterface
    {
        return $this->setup_time;
    }

    public function setSetupTime(?\DateTimeInterface $setup_time): static
    {
        $this->setup_time = $setup_time;

        return $this;
    }

    public function getMaxItems(): ?int
    {
        return $this->max_items;
    }

    public function setMaxItems(int $max_items): static
    {
        $this->max_items = $max_items;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeInterface $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getExtra(): array
    {
        return $this->extra === null ? [] : json_decode($this->extra, true);
    }

    public function setExtra(array $extra): static
    {
        ksort($extra);
        $this->extra = json_encode($extra);

        return $this;
    }

    /**
     * @return Collection<int, ScheduleItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(ScheduleItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setSchedule($this);
        }

        return $this;
    }

    public function removeItem(ScheduleItem $item): static
    {
        if ($this->items->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getSchedule() === $this) {
                $item->setSchedule(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ScheduleColumn>
     */
    public function getColumns(): Collection
    {
        return $this->columns;
    }

    public function addColumn(ScheduleColumn $column): static
    {
        if (!$this->columns->contains($column)) {
            $this->columns->add($column);
            $column->setSchedule($this);
        }

        return $this;
    }

    public function removeColumn(ScheduleColumn $column): static
    {
        if ($this->columns->removeElement($column)) {
            // set the owning side to null (unless already changed)
            if ($column->getSchedule() === $this) {
                $column->setSchedule(null);
            }
        }

        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;

        return $this;
    }

    // Extension functions

    /**
     * Get timezone as a DateTimeZone instance
     *
     * @return \DateTimeZone
     */
    public function getTimezoneInstance(): \DateTimeZone
    {
        return new \DateTimeZone($this->getTimezone());
    }

    /**
     * Get start time with the proper local timezone
     *
     * The timezone will be fixed to the UTC offset of the starting date and time.
     * This is done to prevent issues when a schedule uses a timezone that changes
     * DST during it. In that case, PHP would switch the timezone offset internally,
     * e.g.:
     *     2015-03-08 01:13:00 - 05:00
     *   +            02:45:00
     *   = 2015-03-08 03:58:00 - 04:00
     *
     * I would consider this a bug in PHP's DateTime::add() implementation. To
     * avoid this, we take away the DST effect by fixing the offset right now.
     *
     * @return \DateTime
     */
    public function getLocalStart(): \DateTime
    {
        $tmpFrmt = 'Y-m-d H:i:s';
        $start   = $this->getStart()->format($tmpFrmt);
        $tz      = $this->getTimezoneInstance();

        // and now the PHP dance to get the UTC offset of $tz as "[+-]HH:MM"
        $offset   = $tz->getOffset(new \DateTime($start));
        $negative = $offset < 0;

        $offset  = abs($offset);
        $hours   = floor($offset / 3600);
        $minutes = floor(($offset - $hours*3600) / 60);
        $offset  = sprintf('%s%02d:%02d', $negative ? '-' : '+', $hours, $minutes);

        return \DateTime::createFromFormat($tmpFrmt.'P', $start.$offset); // "inject" proper timezone
    }

    /**
     * Get start time in UTC timezone
     *
     * @return \DateTime
     */
    public function getUTCStart(): \DateTime
    {
        $local = $this->getLocalStart();
        $local->setTimezone(new \DateTimeZone('UTC'));

        return $local;
    }

    /**
     * Get end time with the proper local timezone
     *
     * @return \DateTime
     */
    public function getLocalEnd(): \DateTime
    {
        $t = $this->getLocalStart();

        foreach ($this->getItems() as $item) {
            $t->add($item->getDateInterval());
        }

        return $t;
    }

    public function getVisibleColumns(): Collection
    {
        $filtered = $this->getColumns()->filter(function(ScheduleColumn $col) {
            return !$col->isHidden();
        })->getValues();

        // Return a new collection to reset the index keys :/
        return new ArrayCollection($filtered);
    }

    public function getMaxItemWidth($columns) {
        $max = 0;

        foreach ($this->getItems() as $item) {
            $max = max($max, $item->getWidth($columns));
        }

        return $max;
    }

    public function needsSeconds(): bool
    {
        $iterator = new ScheduleItemIterator($this);

        foreach ($iterator as $item) {
            if ($item->getScheduled()->format('s') !== '00') {
                return true;
            }
        }

        return false;
    }

    public function getOptionsColumn() {
        $columns = $this->getColumns();

        foreach ($columns as $col) {
            if ($col->getName() === self::OPTION_COLUMN_NAME) {
                return $col;
            }
        }

        return null;
    }

    public function getSetupTimeDateInterval(): \DateInterval
    {
        return ReadableTime::dateTimeToDateInterval($this->getSetupTime());
    }

    /*public function getUpdatedAt() {
        $tmpFrmt = 'Y-m-d H:i:s';

        return \DateTime::createFromFormat($tmpFrmt, $this->updated_at->format($tmpFrmt), new \DateTimeZone('UTC')); // "inject" proper timezone
    }*/

    public function getLocalUpdatedAt(): \DateTimeInterface
    {
        $local = $this->getUpdatedAt();
        $local->setTimezone($this->getTimezoneInstance());

        return $local;
    }

    public function getScheduledItems(): ScheduleItemIterator
    {
        return new ScheduleItemIterator($this);
    }

    public function isPublic(): bool {
        return !$this->getSecret() && $this->getEvent()->isPublic();
    }

    public function getSetupTimeInSeconds(): float|int
    {
        return ReadableTime::dateTimeToSeconds($this->getSetupTime());
    }

    /**
     * Get setup time as ISO duration
     *
     * @return string
     */
    public function getSetupTimeISODuration(): string
    {
        return ReadableTime::dateTimeToISODuration($this->getSetupTime());
    }

    public function touch(): Schedule {
        return $this->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
    }

    public function getLink(): string {
        $event = $this->getEvent();
        $url   = '/'.$event->getSlug().'/'.$this->getSlug();

        // for convenience reasons, create links that have access to the whole event if possible
        if ($event->getSecret()) {
            $url .= '?key='.$event->getSecret();
        } else if ($this->getSecret()) {
            $url .= '?key='.$this->getSecret();
        }

        return $url;
    }

    // called from twig as "schedule.text('col-scheduled')"
    public function getText($key): ?string {
        $extra = $this->getExtra();

        return isset($extra['texts'][$key]) ? $extra['texts'][$key] : null;
    }
}

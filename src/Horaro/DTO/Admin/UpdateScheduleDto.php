<?php

namespace App\Horaro\DTO\Admin;

use App\Entity\Event;
use App\Entity\Schedule;
use App\Validator as HoraroAssert;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateScheduleDto
{
    #[Assert\NotBlank]
    private string $name;

    #[Assert\Length(min: 2)]
    #[Assert\Regex(pattern: '/^[a-z0-9-]{2,}$/')]
    #[Assert\Regex(
        pattern: '/^-+$/',
        message: 'The slug cannot be all dashes only.',
        match: false,
    )]
    #[HoraroAssert\CustomSlugRules(
        entity: Schedule::class,
        parent: Event::class,
        paramSuffix: '',
        idNeedsDecoding: false,
    )]
    private string $slug;

    #[Assert\Timezone]
    private string $timezone;

    #[Assert\NotBlank]
    #[Assert\Date]
    #[Assert\GreaterThan('2000-12-31')]
    #[Assert\LessThan('now +2 years', message: "Start date should be less than {{ compared_value }}")]
    private string $start_date;

    #[Assert\NotBlank]
    #[Assert\Time(withSeconds: false)]
    private string $start_time;

    #[Assert\Url(requireTld: true)]
    private ?string $website;

    #[Assert\Regex(pattern: '/^@?([a-zA-Z0-9-_]+)$/')]
    private ?string $twitter;

    #[Assert\Regex(pattern: '/^[a-zA-Z0-9_-]+$/')]
    private ?string $twitch;

    #[Assert\NotBlank]
    #[HoraroAssert\Theme]
    private string $theme;

    #[Assert\Length(max: 20)]
    #[Assert\Regex(pattern: '/^[a-zA-Z0-9_-]+$/')]
    private ?string $secret;

    #[Assert\LessThanOrEqual(999)]
    #[Assert\GreaterThan(0)] // TODO: min item count === current item count
    private int $max_items;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name ?? '';
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): void
    {
        $this->slug = $slug ?? '';
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): void
    {
        $this->website = $website;
    }

    public function getTwitter(): ?string
    {
        return $this->twitter;
    }

    public function setTwitter(?string $twitter): void
    {
        $this->twitter = $twitter;
    }

    public function getTwitch(): ?string
    {
        return $this->twitch;
    }

    public function setTwitch(?string $twitch): void
    {
        $this->twitch = $twitch;
    }

    public function getTheme(): string
    {
        return $this->theme;
    }

    public function setTheme(?string $theme): void
    {
        $this->theme = $theme ?? '';
    }

    public function getSecret(): ?string
    {
        return $this->secret;
    }

    public function setSecret(?string $secret): void
    {
        $this->secret = $secret;
    }

    public function getMaxItems(): int
    {
        return $this->max_items;
    }

    public function setMaxItems(int $max_items): void
    {
        $this->max_items = $max_items;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): void
    {
        $this->timezone = $timezone ?? '';
    }

    public function getStartDate(): string
    {
        return $this->start_date;
    }

    public function setStartDate(?string $start_date): void
    {
        $this->start_date = $start_date ?? '';
    }

    public function getStartTime(): string
    {
        return $this->start_time;
    }

    public function setStartTime(?string $start_time): void
    {
        $this->start_time = $start_time ?? '';
    }

    public static function fromSchedule(Schedule $schedule): self {
        $dto = new self();

        $dto->name = $schedule->getName();
        $dto->slug = $schedule->getSlug();
        $dto->timezone = $schedule->getTimezone();
        $dto->start_date = $schedule->getStart()->format('Y-m-d');
        $dto->start_time = $schedule->getStart()->format('H:i');
        $dto->website = $schedule->getWebsite();
        $dto->twitter = $schedule->getTwitter();
        $dto->twitch = $schedule->getTwitch();
        $dto->theme = $schedule->getTheme();
        $dto->secret = $schedule->getSecret();
        $dto->max_items = $schedule->getMaxItems();

        return $dto;
    }
}

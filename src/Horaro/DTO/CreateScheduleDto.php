<?php

namespace App\Horaro\DTO;

use App\Entity\Event;
use App\Entity\Schedule;
use App\Validator as HoraroAssert;
use Symfony\Component\Validator\Constraints as Assert;

class CreateScheduleDto
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
    #[HoraroAssert\CustomSlugRules(entity: Schedule::class, parent: Event::class)]
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
    private string $website;

    #[Assert\Regex(pattern: '/^@?([a-zA-Z0-9-_]+)$/')]
    private string $twitter;

    #[Assert\Regex(pattern: '/^[a-zA-Z0-9_-]+$/')]
    private string $twitch;

    #[Assert\NotBlank]
    #[HoraroAssert\Theme]
    private string $theme;

    #[Assert\Length(max: 20)]
    #[Assert\Regex(pattern: '/^[a-zA-Z0-9_-]+$/')]
    private string $secret;

    #[Assert\Length(max: 20)]
    #[Assert\Regex(pattern: '/^[a-zA-Z0-9_-]+$/')]
    private string $hidden_secret;

    #[Assert\NotNull]
    #[HoraroAssert\ReadableTime]
    private string $setup_time;

    private ?\DateTimeInterface $parsedSetupTime = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): void
    {
        $this->timezone = $timezone;
    }

    public function getStartDate(): string
    {
        return $this->start_date;
    }

    public function setStartDate(string $start_date): void
    {
        $this->start_date = $start_date;
    }

    public function getStartTime(): string
    {
        return $this->start_time;
    }

    public function setStartTime(string $start_time): void
    {
        $this->start_time = $start_time;
    }

    public function getWebsite(): string
    {
        return $this->website;
    }

    public function setWebsite(string $website): void
    {
        $this->website = $website;
    }

    public function getTwitter(): string
    {
        return $this->twitter;
    }

    public function setTwitter(string $twitter): void
    {
        $this->twitter = $twitter;
    }

    public function getTwitch(): string
    {
        return $this->twitch;
    }

    public function setTwitch(string $twitch): void
    {
        $this->twitch = $twitch;
    }

    public function getTheme(): string
    {
        return $this->theme;
    }

    public function setTheme(string $theme): void
    {
        $this->theme = $theme;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function setSecret(string $secret): void
    {
        $this->secret = $secret;
    }

    public function getHiddenSecret(): string
    {
        return $this->hidden_secret;
    }

    public function setHiddenSecret(string $hidden_secret): void
    {
        $this->hidden_secret = $hidden_secret;
    }

    public function getSetupTime(): string
    {
        return $this->setup_time;
    }

    public function setSetupTime(string $setup_time): void
    {
        $this->setup_time = $setup_time;
    }

    public function getParsedSetupTime(): ?\DateTimeInterface
    {
        return $this->parsedSetupTime;
    }

    public function setParsedSetupTime(\DateTimeInterface $parsedSetupTime): void
    {
        $this->parsedSetupTime = $parsedSetupTime;
    }
}

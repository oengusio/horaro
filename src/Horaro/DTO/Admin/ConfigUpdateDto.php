<?php

namespace App\Horaro\DTO\Admin;

use Symfony\Component\Validator\Constraints as Assert;
use App\Validator as HoraroAssert;

class ConfigUpdateDto
{
    #[Assert\GreaterThanOrEqual(
        value: 6,
        message: 'Setting the bcrypt cost factor to something this low is stupid.',
    )]
    #[Assert\LessThanOrEqual(
        value: 15,
        message: 'bcrypt cost factors this high will lead to Denial of Service even during regular usage.',
    )]
    private int $bcrypt_cost;

    #[Assert\GreaterThanOrEqual(
        value: 600,
        message: 'Values lower than 600 (10 minutes) will seriously affect usability.',
    )]
    private int $cookie_lifetime;

    #[Assert\NotBlank]
    #[Assert\Regex('/^[a-z0-9_-]+$/i')]
    private string $csrf_token_name;

    #[Assert\NotBlank]
    #[HoraroAssert\Theme]
    private string $default_event_theme;

    #[Assert\Choice(
        choices: ['en_us', 'de_de'], // TODO: pull this from the config
        message: 'Unknown language chosen.',
    )]
    private string $default_language;

    #[Assert\GreaterThan(0)]
    #[Assert\LessThanOrEqual(999)]
    private int $max_events;

    #[Assert\GreaterThan(0)]
    #[Assert\LessThanOrEqual(999)]
    private int $max_schedule_items;

    #[Assert\GreaterThan(0)]
    #[Assert\LessThanOrEqual(999)]
    private int $max_schedules;

    #[Assert\GreaterThan(-1)] // yes, I want to allow 0, trust me on this
    private int $max_users;

    #[Assert\Url]
    private string $sentry_dsn;

    public function getBcryptCost(): int
    {
        return $this->bcrypt_cost;
    }

    public function setBcryptCost(int $bcrypt_cost): void
    {
        $this->bcrypt_cost = $bcrypt_cost;
    }

    public function getCookieLifetime(): int
    {
        return $this->cookie_lifetime;
    }

    public function setCookieLifetime(int $cookie_lifetime): void
    {
        $this->cookie_lifetime = $cookie_lifetime;
    }

    public function getCsrfTokenName(): string
    {
        return $this->csrf_token_name;
    }

    public function setCsrfTokenName(string $csrf_token_name): void
    {
        $this->csrf_token_name = $csrf_token_name;
    }

    public function getDefaultEventTheme(): string
    {
        return $this->default_event_theme;
    }

    public function setDefaultEventTheme(string $default_event_theme): void
    {
        $this->default_event_theme = $default_event_theme;
    }

    public function getDefaultLanguage(): string
    {
        return $this->default_language;
    }

    public function setDefaultLanguage(string $default_language): void
    {
        $this->default_language = $default_language;
    }

    public function getMaxEvents(): int
    {
        return $this->max_events;
    }

    public function setMaxEvents(int $max_events): void
    {
        $this->max_events = $max_events;
    }

    public function getMaxScheduleItems(): int
    {
        return $this->max_schedule_items;
    }

    public function setMaxScheduleItems(int $max_schedule_items): void
    {
        $this->max_schedule_items = $max_schedule_items;
    }

    public function getMaxSchedules(): int
    {
        return $this->max_schedules;
    }

    public function setMaxSchedules(int $max_schedules): void
    {
        $this->max_schedules = $max_schedules;
    }

    public function getMaxUsers(): int
    {
        return $this->max_users;
    }

    public function setMaxUsers(int $max_users): void
    {
        $this->max_users = $max_users;
    }

    public function getSentryDsn(): string
    {
        return $this->sentry_dsn;
    }

    public function setSentryDsn(string $sentry_dsn): void
    {
        $this->sentry_dsn = $sentry_dsn;
    }
}

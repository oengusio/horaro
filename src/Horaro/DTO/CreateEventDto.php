<?php

namespace App\Horaro\DTO;

use App\Entity\Event;
use App\Validator as HoraroAssert;
use Symfony\Component\Validator\Constraints as Assert;

class CreateEventDto
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
    #[HoraroAssert\CustomSlugRules(entity: Event::class)]
    private string $slug;

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

}

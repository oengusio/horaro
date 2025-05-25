<?php

namespace App\Horaro\DTO\Admin;

use App\Entity\User;
use App\Validator as HoraroAssert;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateUserDto
{

    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[HoraroAssert\Admin\Login] // TODO: check if username is taken if not self
    #[Assert\Regex(pattern: '/^[a-zA-Z0-9_-]+$/u')]
    private string $login;

    #[Assert\NotBlank]
    private string $display_name;

    #[Assert\NotBlank]
    #[Assert\Choice(
        choices: ['en_us', 'de_de'],
        message: 'Choose a valid Language.',
    )] // TODO: make these dynamic
    private string $language;

    #[HoraroAssert\GravatarHash]
    private ?string $gravatar;

    #[Assert\LessThan(999)]
    #[Assert\GreaterThan(0)] // TODO: min events count === current event count
    private int $max_events;

    #[Assert\NotBlank]
    #[HoraroAssert\Admin\RoleAllowed]
    private string $role;

    public function getLogin(): string
    {
        return $this->login;
    }

    public function setLogin(?string $login): void
    {
        $this->login = $login ?? '';
    }

    public function getDisplayName(): string
    {
        return $this->display_name;
    }

    public function setDisplayName(?string $display_name): void
    {
        $this->display_name = $display_name ?? '';
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    public function getGravatar(): ?string
    {
        return $this->gravatar;
    }

    public function setGravatar(?string $gravatar): void
    {
        $this->gravatar = $gravatar;
    }

    public function getMaxEvents(): int
    {
        return $this->max_events;
    }

    public function setMaxEvents(int $max_events): void
    {
        $this->max_events = $max_events;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): void
    {
        $this->role = $role;
    }

    public static function fromUser(User $user): self
    {
        $dto = new self();

        $dto->login = $user->getLogin();
        $dto->display_name = $user->getDisplayName();
        $dto->language = $user->getLanguage();
        $dto->gravatar = $user->getGravatarHash();
        $dto->max_events = $user->getMaxEvents();
        $dto->role = $user->getRole();

        return $dto;
    }
}

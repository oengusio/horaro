<?php

namespace App\Horaro\DTO;

use App\Entity\User;
use App\Validator as HoraroAssert;
use Symfony\Component\Validator\Constraints as Assert;

class ProfileUpdateDto
{

    #[Assert\NotBlank]
    private string $display_name;

    #[Assert\Choice(
        choices: ['en_us', 'de_de'], // TODO: pull this from the config
        message: 'Choose a valid genre.',
    )]
    #[Assert\NotBlank]
    private string $language;

    #[HoraroAssert\GravatarHash]
    private string $gravatar;

    public function getDisplayName(): string
    {
        return $this->display_name;
    }

    public function setDisplayName(string $display_name): void
    {
        $this->display_name = $display_name;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    public function getGravatar(): string
    {
        return $this->gravatar;
    }

    public function setGravatar(string $gravatar): void
    {
        $this->gravatar = $gravatar;
    }

    public static function fromUser(User $user): self
    {
        $dto = new self();

        $dto->display_name = $user->getDisplayName();
        $dto->language = $user->getLanguage();
        $dto->gravatar = $user->getGravatarHash() ?? '';

        return $dto;
    }
}

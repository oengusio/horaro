<?php

namespace App\Entity;

use App\Repository\ConfigRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConfigRepository::class)]
class Config
{
    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private ?string $keyname = null;

    #[ORM\Column(length: 255)]
    private ?string $value = null;

    /**
     * @param string|null $keyname
     * @param string|null $value
     */
    public function __construct(?string $keyname, mixed $value)
    {
        $this->keyname = $keyname;
        $this->setValue($value);
    }

    public function getKeyname(): string
    {
        return $this->keyname;
    }

    public function setKeyname(string $keyname): static
    {
        $this->keyname = $keyname;

        return $this;
    }

    public function getValue(): mixed
    {
        return json_decode($this->value, true);
    }

    public function setValue(mixed $value): static
    {
        $this->value = json_encode($value, JSON_UNESCAPED_SLASHES);;

        return $this;
    }
}

<?php

namespace App\Horaro\Library;

use App\Repository\ConfigRepository;

class RuntimeConfiguration
{
    public function __construct(
        protected readonly ConfigRepository $repository
    )
    {
    }

    public function get(string $key, mixed $defaultValue = null): mixed
    {
        return null;
    }
}

<?php

namespace App\Horaro\Service;

use League\Fractal\Manager;
use League\Fractal\Serializer\DataArraySerializer;

class FractalService
{
    private readonly Manager $manager;

    public function __construct()
    {
        $this->manager = new Manager();
        $this->manager->setSerializer(new DataArraySerializer());

        if (isset($_GET['embed'])) {
            $this->manager->parseIncludes($_GET['embed']);
            $this->manager->setRecursionLimit(2);
        }
    }

    /**
     * @return Manager
     */
    public function getManager(): Manager
    {
        return $this->manager;
    }
}

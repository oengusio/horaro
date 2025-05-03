<?php

namespace App\Horaro\Ex;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ScheduleColumnNotFoundException extends NotFoundHttpException
{
    public function __construct(?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct('Schedule column not found', $previous, $code, $headers);
    }
}

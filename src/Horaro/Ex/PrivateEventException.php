<?php

namespace App\Horaro\Ex;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PrivateEventException extends AccessDeniedHttpException
{
    public function __construct(?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct('This event is private', $previous, $code, $headers);
    }
}

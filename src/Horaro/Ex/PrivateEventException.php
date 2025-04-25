<?php

namespace App\Horaro\Ex;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PrivateEventException extends AccessDeniedHttpException

{
    public function __construct(?\Throwable $previous = null, array $headers = [], int $code = 0)
    {
        parent::__construct('This event is private', $previous, $headers, $code);
    }
}

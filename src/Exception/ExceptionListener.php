<?php

namespace App\Exception;

use App\Horaro\Ex\EventNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class ExceptionListener
{

    public function onKernelException(ExceptionEvent $event) {
        $exception = $event->getThrowable();

        if ($exception instanceof EventNotFoundException) {
            $event->setResponse(new Response('test'));
        }
    }
}

<?php

namespace App;

use Symfony\Component\HttpFoundation\JsonResponse;

class JsonRedirectResponse extends JsonResponse
{
    public function __construct($url, $status = 302, $headers = array()) {
        parent::__construct([
            'links' => [
                ['rel' => 'redirect', 'uri' => $url]
            ]
        ], $status, $headers);

        $this->headers->set('Location', $url);
    }
}

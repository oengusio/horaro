<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

abstract class BaseController extends AbstractController
{
    // TODO: tmp, implement config properly
    private $cacheTLS = [
        'schedule' => 1,
        'event' => 10,
        'homepage' => 10,
        'calendar' => 60,
        'other' => 60,
    ];

    public function getCurrentUser()
    {
        return null;// $this->app['user'];
    }

    protected function exceedsMaxUsers()
    {
        return false; // $this->getRepository('User')->countUsers() >= $this->app['config']['max_users'];
    }

    protected function setCachingHeader(Response $response, $resourceType, \DateTime $lastModified = null)
    {
        if ($lastModified) {
            $response->setLastModified($lastModified);
        }

        // $times = $this->app['config']['cache_ttls'];
        $user = null; // $this->app['user'];
        $ttl = $this->cacheTLS[$resourceType];

        if ($user) {
            $response->setPrivate();
        } else if ($ttl > 0) {
            $response->setTtl($ttl * 60);
            $response->headers->set('X-Accel-Expires', $ttl * 60); // nginx will not honor s-maxage set by setTtl() above
        }

        return $response;
    }
}

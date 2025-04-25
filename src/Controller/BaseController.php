<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ConfigRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseController extends AbstractController
{
    public function __construct(
        protected readonly ConfigRepository $config,
        protected readonly Security $security,
        protected readonly EntityManagerInterface $entityManager,
        // Can use RequestStack to get the current request
    )
    {
    }

    public function getCurrentUser(): ?User
    {
        $curUser = $this->security->getUser();

        if (!($curUser instanceof User)) {
            throw new \RuntimeException('User is not a user???');
        }

        return $curUser;
    }

    protected function exceedsMaxUsers(): bool
    {
        return $this->entityManager->getRepository(User::class)->count() >= $this->config->getByKey('max_users', 0);
    }

    protected function setCachingHeader(Response $response, $resourceType, ?\DateTime $lastModified = null)
    {
        if ($lastModified) {
            $response->setLastModified($lastModified);
        }

        $times = $this->getParameter('cache_ttls');
        $user = $this->getCurrentUser();
        $ttl = $times[$resourceType];

        if ($user) {
            $response->setPrivate();
        } else if ($ttl > 0) {
            $response->setTtl($ttl * 60);
            $response->headers->set('X-Accel-Expires', $ttl * 60); // nginx will not honor s-maxage set by setTtl() above
        }

        return $response;
    }
}

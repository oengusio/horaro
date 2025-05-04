<?php

namespace App\Controller\Admin;

use App\Horaro\RoleManager;
use App\Horaro\Service\ObscurityCodecService;
use App\Repository\ConfigRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class BaseController extends \App\Controller\BaseController
{
    public function __construct(
        private readonly RoleManager $roleManager,
        ConfigRepository $config,
        Security $security,
        EntityManagerInterface $entityManager,
        ObscurityCodecService $obscurityCodec,
    )
    {
        parent::__construct($config, $security, $entityManager, $obscurityCodec);
    }

    protected function hasResourceAccess($resource): bool
    {
        return $this->roleManager->hasAdministrativeAccess($this->getCurrentUser(), $resource);
    }


}

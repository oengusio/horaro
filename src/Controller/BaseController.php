<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Schedule;
use App\Entity\ScheduleColumn;
use App\Entity\ScheduleItem;
use App\Entity\User;
use App\Horaro\Service\ObscurityCodecService;
use App\Repository\ConfigRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;

use function is_null;

abstract class BaseController extends AbstractController
{
    public function __construct(
        protected readonly ConfigRepository $config,
        protected readonly Security $security,
        protected readonly EntityManagerInterface $entityManager,
        protected readonly ObscurityCodecService $obscurityCodec,
        // Can use RequestStack to get the current request
    )
    {
    }

    protected function getLanguages() {
        return $this->getParameter('horaro.languages');
    }

    protected function addFlashMsg(string $type, string $message): void {
        $this->addFlash($type, $message);
    }

    protected function addSuccessMsg(string $message): void {
        $this->addFlashMsg('success', $message);
    }

    protected function addErrorMsg(string $message): void {
        $this->addFlashMsg('error', $message);
    }

    public function getCurrentUser(): ?User
    {
        $curUser = $this->security->getUser();

        if (!is_null($curUser) && !($curUser instanceof User)) {
            throw new \RuntimeException('User is not a user???');
        }

        return $curUser;
    }

    protected function encodeID(int $id, ?string $entityType = null): string {
        return $this->obscurityCodec->encode($id, $entityType);
    }

    protected function decodeID(string $hash, ?string $entityType = null): ?int {
        return $this->obscurityCodec->decode($hash, $entityType);
    }

    protected function exceedsMaxUsers(): bool
    {
        return $this->entityManager->getRepository(User::class)->count() >= $this->config->getByKey('max_users', 0)->getValue();
    }

    protected function exceedsMaxEvents(User $u): bool {
        return $this->entityManager->getRepository(Event::class)->count(['user' => $u]) >= $u->getMaxEvents();
    }

    protected function exceedsMaxSchedules(Event $e): bool {
        return $this->entityManager->getRepository(Schedule::class)->count(['event' => $e]) >= $e->getMaxSchedules();
    }

    protected function exceedsMaxScheduleItems(Schedule $s): bool {
        return $this->entityManager->getRepository(ScheduleItem::class)->count(['schedule' => $s]) >= $s->getMaxItems();
    }

    protected function exceedsMaxScheduleColumns(Schedule $s): bool {
        return $this->entityManager->getRepository(ScheduleColumn::class)->countVisible($s) >= 10;
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

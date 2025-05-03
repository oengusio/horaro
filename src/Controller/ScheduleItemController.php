<?php

namespace App\Controller;

use App\Entity\Schedule;
use App\Entity\ScheduleItem;
use App\Horaro\DTO\CreateScheduleItemDto;
use App\Horaro\DTO\ScheduleItemMoveDto;
use App\Horaro\DTO\UpdateScheduleItemDto;
use App\Horaro\Library\ObscurityCodec;
use App\Horaro\Service\ObscurityCodecService;
use App\Repository\ConfigRepository;
use App\Repository\ScheduleItemRepository;
use App\Repository\ScheduleRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ScheduleItemController extends BaseController
{
    public function __construct(
        private readonly ScheduleRepository     $scheduleRepository,
        private readonly ScheduleItemRepository $scheduleItemRepository,
        ConfigRepository                        $config,
        Security                                $security,
        EntityManagerInterface                  $entityManager,
        ObscurityCodecService                   $obscurityCodec,
    )
    {
        parent::__construct($config, $security, $entityManager, $obscurityCodec);
    }

    #[IsGranted('edit', 'schedule')]
    #[Route('/-/schedules/{schedule_e}/items', name: 'app_schedule_item_new', methods: ['POST'])]
    public function create(
        #[ValueResolver('schedule_e')] Schedule    $schedule,
        #[MapRequestPayload] CreateScheduleItemDto $createDto,
    ): Response
    {
        if ($this->exceedsMaxScheduleItems($schedule)) {
            throw new BadRequestHttpException('You cannot create more rows in this schedule.');
        }

        $schedRepo = $this->scheduleRepository;
        $itemRepo = $this->scheduleItemRepository;

        $item = $this->entityManager->wrapInTransaction(static function (EntityManager $em) use ($schedule, $createDto, $schedRepo, $itemRepo) {
            $schedRepo->transientLock($schedule);

            $last = $itemRepo->findOneBy(
                ['schedule' => $schedule],
                ['position' => 'DESC']
            );
            $max = $last ? $last->getPosition() : 0;

            $item = new ScheduleItem();
            $item->setSchedule($schedule);
            $item->setLengthInSeconds($createDto->getLength());
            $item->setPosition($max + 1);
            $item->setExtra($createDto->getColumns());

            $schedule->touch();

            $em->persist($schedule);
            $em->persist($item);

            return $item;
        });

        return $this->respondWithItem($item, 201);
    }

    #[IsGranted('edit', 'schedule')]
    #[Route('/-/schedules/{schedule_e}/items/move', name: 'app_schedule_item_move', methods: ['POST'])]
    public function moveItem(
        #[ValueResolver('schedule_e')] Schedule  $schedule,
        #[MapRequestPayload] ScheduleItemMoveDto $dto,
    ): Response
    {
        $schedRepo = $this->scheduleRepository;
        $itemRepo = $this->scheduleItemRepository;

        $itemId = $this->decodeID($dto->getItem(), ObscurityCodec::SCHEDULE_ITEM);

        /** @var ScheduleItem $item */
        $item = $this->entityManager->wrapInTransaction(static function (EntityManager $em) use ($schedule, $dto, $schedRepo, $itemRepo, $itemId) {
            $schedRepo->transientLock($schedule);

            $scheduleItem = $itemRepo->findOneBy([
                'id' => $itemId,
                'schedule' => $schedule,
            ]);

            if (!$scheduleItem) {
                throw new NotFoundHttpException('No such schedule item in this schedule');
            }

            $curPos = $scheduleItem->getPosition();

            if ($curPos < 1) {
                throw new BadRequestHttpException('This item is already at position 0. This should never happen.');
            }

            $target = $dto->getPosition();

            if ($target === $curPos) {
                throw new ConflictHttpException('This would be a NOP.');
            }

            $last = $itemRepo->findOneBy(
                ['schedule' => $schedule],
                ['position' => 'DESC']
            );
            $max = $last ? $last->getPosition() : 0;

            if ($target > $max) {
                throw new BadRequestHttpException('Target position ('.$target.') is greater than the last position ('.$max.').');
            }

            // prepare chunk move
            $up = $target < $curPos;
            $relation = $up ? '+' : '-';
            [$a, $b] = $up ? [$target, $curPos] : [$curPos, $target];

            // move items between old and new position
            $itemRepo->movePosition($schedule, $a, $b, $relation);

            $schedule->touch();
            $scheduleItem->setPosition($target);

            return $scheduleItem;
        });

        return $this->json([
            'data' => [
                'id' => $this->encodeID($item->getId(), ObscurityCodec::SCHEDULE_ITEM),
                'pos' => $item->getPosition(),
            ],
        ]);
    }

    #[IsGranted('edit', 'schedule')]
    #[Route('/-/schedules/{schedule_e}/items/{schedule_item_e}', name: 'app_schedule_item_update', methods: ['PATCH'])]
    public function update(
        #[ValueResolver('schedule_e')] Schedule          $schedule,
        #[ValueResolver('schedule_item_e')] ScheduleItem $scheduleItem,
        #[MapRequestPayload] UpdateScheduleItemDto       $dto,
    ): Response
    {
        $newLength = $dto->getLength();

        if ($newLength) {
            $scheduleItem->setLengthInSeconds($newLength);
        }

        $newCols = $dto->getColumns();

        if ($newCols) {
            $extra = $scheduleItem->getExtra();

            foreach ($dto->getColumns() as $columnId => $newVal) {
                $extra[$columnId] = $newVal;
            }

            $scheduleItem->setExtra($extra);
        }

        $schedule->touch();

        $this->entityManager->flush();

        return $this->respondWithItem($scheduleItem, 200);
    }

    // TODO: move & delete

    protected function respondWithItem(ScheduleItem $item, int $status): Response
    {
        $extraData = [];

        foreach ($item->getExtra() as $colID => $value) {
            $extraData[$this->encodeID($colID, ObscurityCodec::SCHEDULE_COLUMN)] = $value;
        }

        return $this->json([
            'data' => [
                'id' => $this->encodeID($item->getId(), ObscurityCodec::SCHEDULE_ITEM),
                'pos' => $item->getPosition(),
                'length' => $item->getLengthInSeconds(),
                'columns' => $extraData,
            ],
        ], $status);
    }
}

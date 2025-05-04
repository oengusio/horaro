<?php

namespace App\Controller\Api\Version1;

use App\Controller\Api\BaseController;
use App\Entity\Schedule;
use App\Horaro\Ex\ScheduleNotFoundException;
use App\Horaro\Service\FractalService;
use App\Horaro\Service\ObscurityCodecService;
use App\Horaro\Transformer\Version1\IndexTransformer;
use App\Horaro\Transformer\Version1\ScheduleTickerTransformer;
use App\Horaro\Transformer\Version1\ScheduleTransformer;
use App\Repository\ConfigRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

class ScheduleControllerController extends BaseController
{
    public function __construct(
        FractalService $fractal, RequestStack $requestStack, ConfigRepository $config, Security $security, EntityManagerInterface $entityManager, ObscurityCodecService $obscurityCodec)
    {
        parent::__construct($fractal, $requestStack, $config, $security, $entityManager, $obscurityCodec);
    }

    #[Route('/-/api/v1/schedules/{schedule_e}', name: 'app_api_v1_schedule_view', methods: ['GET'])]
    public function viewSchedule(
        Request                                 $request,
        #[ValueResolver('schedule_e')] Schedule $schedule,
        #[MapQueryParameter] ?string            $hiddenkey = null,
    ): JsonResponse
    {
        if (!$schedule->isPublic()) {
            throw new ScheduleNotFoundException();
        }

        $hiddenSecret = $schedule->getHiddenSecret();
        $includeHiddenColumns = $hiddenSecret === null;

        if (!$includeHiddenColumns) {
            $includeHiddenColumns = $hiddenkey === $hiddenSecret;
        }

        $transformer = new ScheduleTransformer($this->requestStack, $this->obscurityCodec, $includeHiddenColumns);
        $response = $this->respondWithItem($schedule, $transformer);

        // TODO: what the fuck??
        if ($response->setLastModified($schedule->getUpdatedAt())->isNotModified($request)) {
            return $response;
        }

        return $response;
    }

    #[Route('/-/api/v1/schedules/{schedule_e}/ticker', name: 'app_api_v1_schedule_ticker', methods: ['GET'])]
    public function viewTicker(
        #[ValueResolver('schedule_e')] Schedule $schedule,
        #[MapQueryParameter] ?string            $hiddenkey = null,
    ): JsonResponse {
        if (!$schedule->isPublic()) {
            throw new ScheduleNotFoundException();
        }

        // determine the currently active item
        $now    = new \DateTime('now');
        $prev   = null;
        $active = null;
        $next   = null;

        foreach ($schedule->getScheduledItems() as $item) {
            $scheduled = $item->getScheduled();

            if ($scheduled <= $now) {
                $prev   = $active;
                $active = $item;

                // getting $next is more involved because we cannot access scheduled items by index
            }
            elseif ($next === null) {
                $next = $item;
            }
        }

        // check if the schedule is over already
        if ($active && $active->getScheduledEnd() <= $now) {
            $prev   = $active;
            $active = null;
        }

        // check if hidden columns can be shown
        $hiddenSecret         = $schedule->getHiddenSecret();
        $includeHiddenColumns = $hiddenSecret === null;

        if (!$includeHiddenColumns) {
            $includeHiddenColumns = $hiddenkey === $hiddenSecret;
        }

        $toTransform = [
            'schedule' => $schedule,
            'prev'     => $prev,
            'active'   => $active,
            'next'     => $next,
        ];

        $transformer = new ScheduleTickerTransformer($this->requestStack, $this->obscurityCodec, $includeHiddenColumns);
        return $this->respondWithItem($toTransform, $transformer);
    }
}

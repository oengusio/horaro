<?php

namespace App\Controller\Api\Version1;

use App\Controller\Api\BaseController;
use App\Entity\Event;
use App\Entity\Schedule;
use App\Horaro\Ex\EventNotFoundException;
use App\Horaro\Ex\ScheduleNotFoundException;
use App\Horaro\Library\ObscurityCodec;
use App\Horaro\Pager\OffsetLimitPager;
use App\Horaro\Transformer\Version1\EventTransformer;
use App\Horaro\Transformer\Version1\ScheduleTransformer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EventController extends BaseController
{
    #[Route('/-/api/v1/events', name: 'app_api_v1_event_list', methods: ['GET'])]
    public function listPublicEvents(Request $request)
    {
        // determine current page
        $pager = new OffsetLimitPager($request);
        $offset = $pager->getOffset();
        $size = $pager->getPageSize();

        // determine direction
        $allowed = ['name' => 'e.name'];
        $orderBy = $pager->getOrder(array_keys($allowed), 'name');
        $direction = $pager->getDirection('ASC');
        $orderBy = $allowed[$orderBy];

        // prepare query builder
        $queryBuilder = $this->entityManager->getRepository(Event::class)
                                            ->createQueryBuilder('e')
                                            ->where('e.secret IS NULL')
                                            ->orderBy($orderBy, $direction)
                                            ->setFirstResult($offset)
                                            ->setMaxResults($size);

        // filter by name
        $name = trim($request->query->get('name'));

        if (mb_strlen($name) > 0) {
            $queryBuilder
                ->andWhere('e.name LIKE :name')
                ->setParameter('name', '%'.addcslashes($name, '%_').'%');
        }

        // find events
        $events = $queryBuilder->getQuery()->getResult();

        $transformer = new EventTransformer($this->requestStack, $this->obscurityCodec);

        return $this->respondWithCollection($events, $transformer, $pager);
    }

    // We love custom lookups xD
    // Might refactor this in the future
    // But we all know there is nothing as permanent as a temporary solution
    #[Route('/-/api/v1/events/{eventid}', name: 'app_api_v1_event_view', methods: ['GET'])]
    public function viewEvent(Request $request): Response {
        [$event, $bySlug] = $this->resolveEvent($request);

        if ($bySlug) {
            $id = $this->encodeID($event->getID(), ObscurityCodec::EVENT);

            return $this->redirect('/-/api/v1/events/'.$id);
        }

        $transformer = new EventTransformer($this->requestStack, $this->obscurityCodec);

        return $this->respondWithItem($event, $transformer);
    }

    #[Route('/-/api/v1/events/{eventid}/schedules', name: 'app_api_v1_event_schedules', methods: ['GET'])]
    public function listEventSchedules(Request $request): Response {
        [$event, $bySlug] = $this->resolveEvent($request);

        if ($bySlug) {
            $id = $this->encodeID($event->getID(), ObscurityCodec::EVENT);

            return $this->redirect('/-/api/v1/events/'.$id.'/schedules');
        }

        $schedules = [];

        foreach ($event->getSchedules() as $schedule) {
            if ($schedule->isPublic()) {
                $schedules[] = $schedule;
            }
        }

        $transformer = new ScheduleTransformer($this->requestStack, $this->obscurityCodec, false);
        return $this->respondWithCollection($schedules, $transformer);
    }

    #[Route('/-/api/v1/events/{eventid}/schedules/{scheduleid}', name: 'app_api_v1_event_schedules_view', methods: ['GET'])]
    public function viewSchedule(Request $request): Response {
        [$event]    = $this->resolveEvent($request);
        [$schedule] = $this->resolveSchedule($event, $request);

        $scheduleID = $this->obscurityCodec->encode($schedule->getID(), ObscurityCodec::SCHEDULE);

        return $this->redirect('/-/api/v1/schedules/'.$scheduleID);
    }

    #[Route('/-/api/v1/events/{eventid}/schedules/{scheduleid}/ticker', name: 'app_api_v1_event_schedules_view_ticker', methods: ['GET'])]
    public function viewScheduleTicker(Request $request): Response {
        [$event]    = $this->resolveEvent($request);
        [$schedule] = $this->resolveSchedule($event, $request);

        $scheduleID = $this->obscurityCodec->encode($schedule->getID(), ObscurityCodec::SCHEDULE);

        return $this->redirect('/-/api/v1/schedules/'.$scheduleID.'/ticker');
    }

    private function resolveEvent(Request $request): array
    {
        $event = null;
        $bySlug = false;
        $eventID = $request->attributes->get('eventid');
        $codec = $this->obscurityCodec;
        $repo = $this->entityManager->getRepository(Event::class);
        $id = $codec->decode($eventID, ObscurityCodec::EVENT);

        if ($id === null) {
            $bySlug = true;
            $event = $repo->findOneBy(['slug' => $eventID]);
        } else {
            $event = $repo->find($id);
        }

        if (!$event || !$event->isPublic()) {
            throw new EventNotFoundException();
        }

        return [$event, $bySlug];
    }

    private function resolveSchedule(Event $event, Request $request): array
    {
        $schedule = null;
        $bySlug = false;
        $scheduleID = $request->attributes->get('scheduleid');
        $codec = $this->obscurityCodec;
        $repo = $this->entityManager->getRepository(Schedule::class);
        $id = $codec->decode($scheduleID, ObscurityCodec::SCHEDULE);

        if ($id === null) {
            $bySlug = true;
            $schedule = $repo->findOneBy([
                'event' => $event,
                'slug' => $scheduleID,
            ]);
        } else {
            $schedule = $repo->findOneBy([
                'event' => $event,
                'id' => $id,
            ]);
        }

        if (!$schedule || !$schedule->isPublic()) {
            throw new ScheduleNotFoundException();
        }

        return [$schedule, $bySlug];
    }
}

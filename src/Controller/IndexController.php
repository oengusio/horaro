<?php

namespace App\Controller;

use App\Repository\ConfigRepository;
use App\Repository\EventRepository;
use App\Repository\ScheduleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class IndexController extends BaseController
{
    public function __construct(
        protected readonly ConfigRepository $configRepository,
        protected readonly EventRepository $eventRepository,
        protected readonly ScheduleRepository $scheduleRepository,
        protected readonly EntityManagerInterface $entityManager,
    ) {}

    #[Route('/', name: 'app_welcome', methods: ['GET'])]
    public function welcome(Request $request): Response
    {
        // find schedules that are currently happening
        $schedules = $this->scheduleRepository->findCurrentlyRunning();
        $live = [];

        // group by event
        foreach ($schedules as $schedule) {
            $event = $schedule->getEvent();
            $eventID = $event->getId();

            $live[$eventID]['event'] = $event;
            $live[$eventID]['schedules'][] = $schedule;
        }

        // find upcoming event schedules (blatenly ignoring that the starting times
        // in the database are not in UTC).
        $schedules = $this->scheduleRepository->findUpcoming(365);
        $upcoming = [];

        // group by event
        foreach ($schedules as $schedule) {
            $event = $schedule->getEvent();
            $eventID = $event->getId();

            $upcoming[$eventID]['event'] = $event;
            $upcoming[$eventID]['schedules'][] = $schedule;
        }

        // find featured, old events
        $ids = $this->configRepository->getByKey('featured_events', [])->getValue();
        $featured  = $this->eventRepository->findByIds($ids);

        // remove featured events that are already included in the live/upcoming lists
        foreach ($featured as $idx => $event) {
            $eventID = $event->getId();

            if (isset($live[$eventID]) || isset($upcoming[$eventID]) || !$event->isPublic()) {
                unset($featured[$idx]);
            }
        }

        // if someone is logged in, find their recent activity
        $user   = $this->getCurrentUser();
        $recent = [];

        if ($user) {
            // TODO
            $recent = []; //$scheduleRepo->findRecentlyUpdated($user, 7);
        }

        $html = $this->render('index/welcome.twig', [
            'noRegister' => $this->exceedsMaxUsers(),
            'live'       => array_slice($live, 0, 5),
            'upcoming'   => array_slice($upcoming, 0, 5),
            'featured'   => array_slice($featured, 0, 5),
            'recent'     => $recent,
        ]);

        return $this->setCachingHeader($html, 'homepage');
    }
}

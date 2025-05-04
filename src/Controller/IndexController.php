<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\User;
use App\Horaro\DTO\RegisterDto;
use App\Horaro\Service\ObscurityCodecService;
use App\Repository\ConfigRepository;
use App\Repository\EventRepository;
use App\Repository\ScheduleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Solution10\Calendar\Calendar;
use Solution10\Calendar\Resolution\MonthResolution;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;

final class IndexController extends BaseController
{
    public function __construct(
        protected readonly ConfigRepository $configRepository,
        protected readonly EventRepository $eventRepository,
        protected readonly ScheduleRepository $scheduleRepository,
        private readonly FormLoginAuthenticator $authenticator,
        EntityManagerInterface $entityManager,
        ConfigRepository $config,
        Security $security,
        ObscurityCodecService $obscurityCodec,
    )
    {
        parent::__construct($config, $security, $entityManager, $obscurityCodec);
    }

    #[Route('/', name: 'app_welcome', methods: ['GET'])]
    public function welcome(): Response
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
            $recent = $this->scheduleRepository->findRecentlyUpdated($user, 7);
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

    // TODO: prevent users with role ghost from logging in
    #[Route('/-/login', name: 'app_login', methods: ['GET', 'POST'], priority: 1)]
    public function loginForm(AuthenticationUtils $authenticationUtils): Response {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $response = $this->render('index/login.twig', [
            'result' => null, // TODO: remove/re-implement this
            'error' => $error,
            'error_message' => $error,
            'last_login' => $lastUsername,
        ]);

        return $this->setCachingHeader($response, 'other');
    }

    #[Route('/-/register', name: 'app_register_form', methods: ['GET'], priority: 1)]
    public function registerForm(): Response {
        if ($this->exceedsMaxUsers()) {
            return $this->redirect('/');
        }

        $html = $this->render('index/register.twig', ['result' => null]);

        return $this->setCachingHeader($html, 'other');
    }

    #[Route('/-/register', name: 'app_register_submit', methods: ['POST'], priority: 1)]
    public function registerAction(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        UserAuthenticatorInterface $authenticatorManager,
        #[MapRequestPayload] RegisterDto $dto,
    ): Response
    {
        if ($this->exceedsMaxUsers()) {
            return $this->redirect('/');
        }

        $maxEvents = $this->config->getByKey('max_events', 10)->getValue();

        $user = new User();
        $user->setLogin($dto->getLogin());

        $passwordHash = $passwordHasher->hashPassword(
            $user,
            $dto->getPassword(),
        );

        $user->setPassword($passwordHash);
        $user->setDisplayName($dto->getDisplayName());
        $user->setRole($this->getParameter('horaro.default_role'));
        $user->setMaxEvents($maxEvents);
        $user->setLanguage('en_us');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $request->getSession()->start();
        $request->getSession()->migrate();

        // auth, not sure if RememberMeBadge works, keep testing
        $authenticatorManager->authenticateUser($user, $this->authenticator, $request, [new RememberMeBadge()]);

        $this->addSuccessMsg('Welcome to Horaro, your account has been successfully created.');

        return $this->redirect('/-/home');
    }

    #[Route('/-/contact', name: 'app_contact', methods: ['GET'], priority: 1)]
    public function contact(): Response {
        return $this->setCachingHeader($this->render('index/contact.twig'), 'other');
    }

    #[Route('/-/licenses', name: 'app_licenses', methods: ['GET'], priority: 1)]
    public function licenses(): Response {

        return $this->setCachingHeader($this->render('index/licenses.twig'), 'other');
    }

    // TODO: this does a lot of database queries, see if I can possibly reduce them
    #[Route('/-/calendar/{year<\d+>}/{month<\d+>}', name: 'app_calendar', methods: ['GET'], priority: 1)]
    public function calendar(int $year, int $month): Response
    {
        $minYear = 2000;
        $maxYear = intval(date('Y')) + 10;

        if ($year < $minYear || $year > $maxYear) {
            throw new NotFoundHttpException('Invalid year given.');
        }

        if ($month < 1 || $month > 12) {
            throw new NotFoundHttpException('Invalid month given.');
        }

        $firstDay = new \DateTime(sprintf('%04d-%02d-01', $year, $month));
        $calendar = new Calendar($firstDay);
        $calendar->setResolution(new MonthResolution());

        $viewData = $calendar->viewData();
        $month    = $viewData['contents'][0];

        $calStart = $firstDay->format('Y-m-d');
        $calEnd   = $month->lastDay()->format('Y-m-d');

        // find date range shown in the actual calendar (which shows overflowing dates)

        $firstWeek = null;
        $lastWeek  = null;

        foreach ($month->weeks() as $week) {
            if ($firstWeek === null) {
                $firstWeek = $week;
            }

            $lastWeek = $week;
        }

        $calViewStart = $firstWeek->weekStart()->format('Y-m-d');
        $calViewEnd   = $lastWeek->weekEnd()->format('Y-m-d');

        // define range for the database query
        // Since the database doesn't know the end, we must manually make sure that schedules that
        // start before $calStart but end after $claStart are included. To do so, we set the search
        // beginning date one month back. Should work well enough.

        $queryBegin = clone $firstDay;
        $queryEnd   = new \DateTime($calEnd.' 23:59:59');

        $queryBegin->modify('-1 month');

        $schedules    = $this->scheduleRepository->findPublic($queryBegin, $queryEnd);

        // collect schedule ranges, grouped by event
        // collapse schedules of the same event with the same date range

        $ranges = []; // {eventID: {'from-to': <schedule|event>, ...}, eventID: ...}

        foreach ($schedules as $schedule) {
            $event   = $schedule->getEvent();
            $start   = $schedule->getLocalStart()->format('Y-m-d');
            $end     = $schedule->getLocalEnd()->format('Y-m-d');
            $eventID = $event->getID();
            $range   = "$start-$end";

            // now that we know the start and end, we can filter out schedules that are not relevant
            // to this calendar view
            if ($end < $calStart || $start > $calEnd) {
                continue;
            }

            if (!isset($ranges[$eventID][$range])) {
                $ranges[$eventID][$range] = [$schedule, $schedule];
            }
            else {
                $ranges[$eventID][$range] = [$schedule, $event];
            }
        }

        // collect remaining schedules
        // also, count how often we see each event in this calendar view

        $calElements = [];
        $eventCounts = [];

        foreach ($ranges as $eventID => $dateranges) {
            foreach ($dateranges as $x) {
                $calElements[] = $x;
                $eventID       = $x[0]->getEvent()->getID();

                if (!isset($eventCounts[$eventID])) {
                    $eventCounts[$eventID] = 0;
                }

                $eventCounts[$eventID]++;
            }
        }

        // collect raw schedule data, grouped by day

        $data       = []; // {YYYY-MM-DD: {scheduleID: info, scheduleID: info, ...}}
        $lengths    = []; // {scheduleID: numofdays, scheduleID: numofdays, ...}
        $calStartTS = strtotime($calViewStart);

        foreach ($calElements as $calElement) {
            [$schedule, $linkTo] = $calElement;

            $id     = $schedule->getID();
            $event  = $schedule->getEvent();
            $start  = strtotime($schedule->getLocalStart()->format('Y-m-d'));
            $end    = strtotime($schedule->getLocalEnd()->format('Y-m-d'));
            $cursor = $start;
            $len    = 0;

            if ($linkTo instanceof Event) {
                $title = $linkTo->getName();
                $url   = '/'.$linkTo->getSlug();
            }
            elseif ($eventCounts[$event->getID()] < 2) {
                $title = $event->getName();
                $url   = '/'.$event->getSlug().'/'.$schedule->getSlug();
            }
            else {
                $title = $event->getName(). ' ('.$schedule->getName().')';
                $url   = '/'.$event->getSlug().'/'.$schedule->getSlug();
            }

            // walk through the scheduler date range, day by day, and add one element
            // per day to $data

            while ($cursor <= $end) {
                $date  = date('Y-m-d', $cursor);
                $state = 'progress';

                if ($start === $end) {
                    $state = 'single';
                }
                elseif ($cursor === $start) {
                    $state = 'begin';
                }
                elseif ($cursor === $end) {
                    $state = 'end';
                }

                $data[$date][$id] = [
                    'id'        => $id,
                    'state'     => $state,
                    'group'     => $event->getID(),
                    'title'     => $title,
                    'url'       => $url,
                    'continued' => in_array($state, ['progress', 'end']) && $cursor === $calStartTS,
                ];

                $cursor = strtotime('+1 day', $cursor);
                $len   += 1;
            }

            $lengths[$id] = $len;
        }

        // sort by date
        ksort($data);

        // remove dates prior/after the selected month

        foreach (array_keys($data) as $day) {
            if ($day < $calViewStart || $day > $calViewEnd) {
                unset($data[$day]);
            }
        }

        // build up the stacks. in the calendar, each day has a stack of rows, up to $height many.

        $stacks    = [];
        $height    = 5;
        $yesterday = [];

        foreach ($data as $day => $things) {
            $stack = [];

            // sort things of this day descending by their length
            uasort($things, function($a, $b) use ($lengths) {
                return $lengths[$b['id']] - $lengths[$a['id']];
            });

            // continue things from yesterday
            foreach ($yesterday as $pos => $value) {
                $thingID = $value['id'];

                if (isset($things[$thingID])) {
                    $stack[$pos] = $things[$thingID];
                    unset($things[$thingID]);
                }
            }

            // add remaining things to the free slots
            for ($i = 0; $i < $height && count($things) > 0; ++$i) {
                if (!isset($stack[$i])) {
                    reset($things);

                    $thingID   = key($things);
                    $stack[$i] = $things[$thingID];

                    unset($things[$thingID]);
                }
            }

            // we will make sure that $stack is properly sorted later when we insert the filler elements

            $stacks[$day] = $stack;
            $yesterday    = $stack;
        }

        // Now we can assign colors to each bar in the calendar. For this we choose of a predefined
        // list of colors. There are two priorities: If two things that belong together appear in the
        // calendar (think to schedules of the same event), they should share the same color. Also,
        // each calendar day should only unique colors. The first requirement has priority.

        $colors          = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
        $colorsNeverUsed = $colors; // never used on this page yet
        $colorsPerGroup  = [];
        $yesterday       = [];

        shuffle($colorsNeverUsed);

        foreach ($stacks as $day => &$stack) {
            $colorsAvailable = $colors;

            shuffle($colorsAvailable);

            foreach ($stack as $i => $element) {
                $previous = isset($yesterday[$i]) ? $yesterday[$i] : null;
                $group    = $element['group'];

                // if we continue a line from the previous day, we MUST use the same color
                if ($previous && $previous['id'] === $element['id']) {
                    $color = $previous['color'];
                }

                // otherwise, see if we already have a color for this group
                elseif (isset($colorsPerGroup[$group])) {
                    $color = $colorsPerGroup[$group];
                }

                // choose a new color
                else {
                    if (!empty($colorsNeverUsed)) {
                        $color = array_pop($colorsNeverUsed);
                    }
                    else {
                        $color = array_pop($colorsAvailable);
                    }
                }

                // remove this color from the list of colors available for today
                $idx = array_search($color, $colorsAvailable);
                if ($idx !== false) unset($colorsAvailable[$idx]);

                $idx = array_search($color, $colorsNeverUsed);
                if ($idx !== false) unset($colorsNeverUsed[$idx]);

                $stack[$i]['color'] = $color;
            }

            $yesterday = $stack;
        }

        // fill in gaps with filler elements

        foreach ($stacks as $day => &$stack) {
            $fill = false;

            for ($i = $height - 1; $i >= 0; --$i) {
                if (isset($stack[$i])) {
                    $fill = true;
                }
                elseif ($fill) {
                    $stack[$i] = 'fill';
                }
            }

            ksort($stack);
        }

        $prevMonth = clone $month->firstDay();
        $nextMonth = clone $month->firstDay();
        $prevYear  = clone $month->firstDay();
        $nextYear  = clone $month->firstDay();

        $prevMonth->modify('-1 month');
        $nextMonth->modify('+1 month');
        $prevYear->modify('-1 year');
        $nextYear->modify('+1 year');

        $response = $this->render('index/calendar.twig', [
            'stacks'    => $stacks,
            'month'     => $month,
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
            'prevYear'  => $prevYear,
            'nextYear'  => $nextYear,
            'minYear'   => $minYear,
            'maxYear'   => $maxYear,
        ]);

        return $this->setCachingHeader($response, 'event');
    }
}

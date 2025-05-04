<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Schedule;
use App\Horaro\Ex\PrivateEventException;
use App\Horaro\Ex\ScheduleNotFoundException;
use App\Horaro\Service\MarkdownService;
use App\Horaro\Service\ObscurityCodecService;
use App\Horaro\Service\ScheduleTransformerService;
use App\Repository\ConfigRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class FrontendController extends BaseController
{
    public function __construct(
        private readonly ScheduleTransformerService $transformerService,
        private readonly MarkdownService $markdownService,
        ConfigRepository $config,
        Security $security,
        EntityManagerInterface $entityManager,
        ObscurityCodecService $obscurityCodec,
    )
    {
        parent::__construct($config, $security, $entityManager, $obscurityCodec);
    }

    #[Route('/{eventSlug}/{scheduleSlug}/ical-feed', name: 'app_frontend_event_schedule_ical', methods: ['GET'])]
    public function icalFaqAction(
        #[ValueResolver('eventSlug')] Event       $event, // for later reference #[MapEntity(mapping: ['eventSlug' => 'slug'])]
        #[ValueResolver('scheduleSlug')] Schedule $schedule,
        #[MapQueryParameter] ?string              $key = null,
    ): Response
    {
        if (!$this->handleScheduleAccess($event, $schedule, $key)) {
            return new Response();
        }

        $isPrivate = $this->isPrivatePage($event);
        $response = $this->render('frontend/schedule/ical.twig', [
            'event' => $event,
            'schedule' => $schedule,
            'key' => $key,
            'schedules' => $this->getAllowedSchedules($event, $key),
            'isPrivate' => $isPrivate,
        ]);

        return $this->setCachingHeader($response, 'other');
    }

    #[Route('/{eventSlug}/{scheduleSlug}.{format}', name: 'app_frontend_event_schedule_export', methods: ['GET'], condition: "params['format'] matches '/(jsonp?|xml|csv|ical)/'")]
    public function scheduleExport(
        Request                                   $request,
        #[ValueResolver('eventSlug')] Event       $event,
        #[ValueResolver('scheduleSlug')] Schedule $schedule,
        string                                    $format,
        #[MapQueryParameter] ?string              $key = null,
        #[MapQueryParameter] ?string              $hiddenkey = null,
    ): Response
    {
        $formats = ['json', 'jsonp', 'xml', 'csv', 'ical'];

        if (!in_array($format, $formats, true)) {
            throw new BadRequestHttpException('Invalid format "'.$format.'" given.');
        }

        if (!$this->handleScheduleAccess($event, $schedule, $key)) {
            return new Response();
        }

        // auto-switch to JSONP if there is a callback parameter
        if ($format === 'json' && $request->query->has('callback')) {
            $format = 'jsonp';
        }

        $hiddenSecret = $schedule->getHiddenSecret();
        $includeHiddenColumns = $hiddenSecret === null;

        if (!$includeHiddenColumns) {
            $includeHiddenColumns = $hiddenkey === $hiddenSecret;
        }

        $transformer = $this->transformerService->getTransformer($format);

        try {
            $data = $transformer->transform($schedule, true, $includeHiddenColumns);
        } catch (\InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        $filename = sprintf('%s-%s.%s', $event->getSlug(), $schedule->getSlug(), $transformer->getFileExtension());
        $headers = ['Content-Type' => $transformer->getContentType()];

        if ($request->query->get('named')) {
            $headers['Content-Disposition'] = 'filename="'.$filename.'"';
        }

        $response = new Response($data, 200, $headers);

        return $this->setScheduleCachingHeader($schedule, $response);
    }

    #[Route('/{eventSlug}/{scheduleSlug}', name: 'app_frontend_event_schedule', methods: ['GET'])]
    public function schedule(
        #[ValueResolver('eventSlug')] Event       $event,
        #[ValueResolver('scheduleSlug')] Schedule $schedule,
        #[MapQueryParameter] ?string              $key = null,
    ): Response
    {
        if (!$this->handleScheduleAccess($event, $schedule, $key)) {
            return new Response();
        }

        $description = $schedule->getDescription();

        if ($description) {
            $description = $this->markdownService->convert($description);
        }

        $response = $this->render('frontend/schedule/schedule.twig', [
            'event' => $event,
            'schedule' => $schedule,
            'key' => $key,
            'schedules' => $this->getAllowedSchedules($event, $key),
            'isPrivate' => $this->isPrivatePage($event),
            'description' => $description,
        ]);

        return $this->setScheduleCachingHeader($schedule, $response);
    }

    #[Route('/{eventSlug}', name: 'app_frontend_event_home', methods: ['GET'])]
    public function event(
        #[ValueResolver('eventSlug')] Event $event,
        #[MapQueryParameter] ?string        $key = null,
    ): Response
    {
        // the event page is accessible if you have the event key or a key for one of the schedules
        if (!$this->hasGoodEventKey($event, $key) && !$this->hasGoodSchedulesKey($event, $key)) {
            throw new PrivateEventException();
        }

        $description = $event->getDescription();

        if ($description) {
            $description = $this->markdownService->convert($description);
        }

        $isPrivate = $this->isPrivatePage($event);

        $resp = $this->render('frontend/event/event.twig', [
            'event' => $event,
            'key' => $key,
            'schedules' => $this->getAllowedSchedules($event, $key),
            'description' => $description,
            'isPrivate' => $isPrivate,
        ]);

        if (!$isPrivate) {
            $resp = $this->setCachingHeader($resp, 'event');
        }

        return $resp;
    }

    protected function hasGoodEventKey(Event $event, ?string $key): bool
    {
        return $this->hasGoodKey($event->getSecret(), $key);
    }

    protected function hasGoodSchedulesKey(Event $event, ?string $key): bool
    {
        foreach ($event->getSchedules() as $schedule) {
            if (strlen($schedule->getSecret()) > 0 && $this->hasGoodScheduleKey($schedule, $key)) {
                return true;
            }
        }

        return false;
    }

    protected function hasGoodScheduleKey(Schedule $schedule, ?string $key): bool
    {
        return $this->hasGoodKey($schedule->getSecret(), $key);
    }

    private function hasGoodKey(?string $secret, ?string $key): bool
    {
        return strlen($secret) === 0 || $key === $secret;
    }

    /**
     * Check if the current page is something private.
     *
     * Basically everytime a key is involved, a page is private. This means that
     * a public schedule in a public event can be private, if a key for another,
     * private schedule is given (because in this case the dropdown menu in the
     * navigation is different).
     *
     * @param Event $event
     *
     * @return boolean
     */
    protected function isPrivatePage(Event $event): bool
    {
        $isPrivate = strlen($event->getSecret()) > 0;

        foreach ($event->getSchedules() as $schedule) {
            $isPrivate |= strlen($schedule->getSecret()) > 0;
        }

        return $isPrivate;
    }

    protected function handleScheduleAccess(Event $event, Schedule $schedule, $key): bool
    {
        $needsEventKey = strlen($event->getSecret()) > 0;
        $needsScheduleKey = strlen($schedule->getSecret()) > 0;
        $validEventKey = $needsEventKey && $this->hasGoodEventKey($event, $key);
        $validScheduleKey = $needsScheduleKey && $this->hasGoodScheduleKey($schedule, $key);

        $eventAccess = !$needsEventKey || $validEventKey;
        $scheduleAccess = !$needsScheduleKey || $validScheduleKey || $validEventKey;

        if (!$scheduleAccess) {
            if ($eventAccess) {
                throw new ScheduleNotFoundException();
            } else {
                throw new PrivateEventException();
            }
        }

        return true;
    }

    protected function getAllowedSchedules(Event $event, ?string $key): array
    {
        $schedules = [];
        $validEventKey = strlen($event->getSecret()) > 0 && $this->hasGoodEventKey($event, $key);

        foreach ($event->getSchedules() as $s) {
            if ($validEventKey || $this->hasGoodScheduleKey($s, $key)) {
                $schedules[] = $s;
            }
        }

        return $schedules;
    }

    protected function setScheduleCachingHeader(Schedule $schedule, Response $response): Response
    {
        if ($this->isPrivatePage($schedule->getEvent())) {
            return $response;
        } else {
            return parent::setCachingHeader($response, 'schedule', $schedule->getUpdatedAt());
        }
    }
}

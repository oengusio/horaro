<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Schedule;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class FrontendController extends BaseController
{
    #[Route('/{eventSlug}', name: 'app_frontend_event_home')]
    public function event(
        #[MapEntity(mapping: ['eventSlug' => 'slug'])] Event $event,
        #[MapQueryParameter] ?string $key = null,
    ): Response
    {
        // the event page is accessible if you have the event key or a key for one of the schedules
        if (!$this->hasGoodEventKey($event, $key) && !$this->hasGoodSchedulesKey($event, $key)) {
            throw new AccessDeniedHttpException('This event is private.');
        }

        $description = $event->getDescription();

        // TODO: implement markdown parsing
        /*if ($description) {
            $description = $this->convertMarkdown($description);
        }*/

        $isPrivate = $this->isPrivatePage($event);

        $resp = $this->render('frontend/event/event.twig', [
            'event'       => $event,
            'key'         => $key,
            'schedules'   => $this->getAllowedSchedules($event, $key),
            'description' => $description,
            'isPrivate'   => $isPrivate,
        ]);

        if (!$isPrivate) {
            $resp = $this->setCachingHeader($resp, 'event');
        }

        return $resp;
    }

    protected function hasGoodEventKey(Event $event, $key) {
        return $this->hasGoodKey($event->getSecret(), $key);
    }

    protected function hasGoodSchedulesKey(Event $event, $key) {
        foreach ($event->getSchedules() as $schedule) {
            if (strlen($schedule->getSecret()) > 0 && $this->hasGoodScheduleKey($schedule, $key)) {
                return true;
            }
        }

        return false;
    }

    protected function hasGoodScheduleKey(Schedule $schedule, $key) {
        return $this->hasGoodKey($schedule->getSecret(), $key);
    }

    private function hasGoodKey($secret, $key) {
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
     * @param  Event   $event
     * @return boolean
     */
    protected function isPrivatePage(Event $event) {
        $isPrivate = strlen($event->getSecret()) > 0;

        foreach ($event->getSchedules() as $schedule) {
            $isPrivate |= strlen($schedule->getSecret()) > 0;
        }

        return $isPrivate;
    }

    protected function getAllowedSchedules(Event $event, $key) {
        $schedules     = [];
        $validEventKey = strlen($event->getSecret()) > 0 && $this->hasGoodEventKey($event, $key);

        foreach ($event->getSchedules() as $s) {
            if ($validEventKey || $this->hasGoodScheduleKey($s, $key)) {
                $schedules[] = $s;
            }
        }

        return $schedules;
    }
}

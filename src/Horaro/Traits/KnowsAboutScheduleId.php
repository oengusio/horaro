<?php

namespace App\Horaro\Traits;

use App\Horaro\Library\ObscurityCodec;

trait KnowsAboutScheduleId
{
    public function getDecodedScheduleId(): int {
        $paramId = $this->requestStack->getCurrentRequest()->attributes->get('schedule_e');

        if (!$paramId) {
            throw new \RuntimeException('schedule_e not in request');
        }

        return $this->obscurityCodec->decode($paramId, ObscurityCodec::SCHEDULE);
    }

}

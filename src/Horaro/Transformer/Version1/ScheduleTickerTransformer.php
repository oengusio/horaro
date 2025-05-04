<?php

namespace App\Horaro\Transformer\Version1;

use App\Horaro\Library\ObscurityCodec;
use App\Horaro\Library\Transformer\Schedule\JsonTransformer;
use App\Horaro\Service\ObscurityCodecService;
use App\Horaro\Transformer\BaseTransformer;
use Symfony\Component\HttpFoundation\RequestStack;

class ScheduleTickerTransformer extends BaseTransformer
{
    public function __construct(
        RequestStack          $requestStack,
        ObscurityCodecService $obscurityCodec,
        private readonly bool $includeHiddenColumns = false,
    )
    {
        parent::__construct($requestStack, $obscurityCodec);
    }

    public function transform(array $ticker): array
    {
        $schedule = $ticker['schedule'];
        $transformer = new JsonTransformer($this->obscurityCodec, $this->requestStack);
        $data = $transformer->transformTicker($schedule, $ticker, $this->includeHiddenColumns);

        // replace "url" with an absolute "link"
        $data['schedule']['link'] = $this->base().$data['schedule']['url'];
        unset($data['schedule']['url']);

        // add additional API links
        $eventID = $this->encodeID($schedule->getEvent()->getID(), ObscurityCodec::EVENT);
        $data['links'] = [
            ['rel' => 'self', 'uri' => $this->url('/v1/schedules/'.$data['schedule']['id']).'/ticker'],
            ['rel' => 'schedule', 'uri' => $this->url('/v1/schedules/'.$data['schedule']['id'])],
            ['rel' => 'event', 'uri' => $this->url('/v1/events/'.$eventID)],
        ];

        return $data;
    }
}

<?php

namespace App\Horaro\Transformer\Version1;

use App\Entity\Schedule;
use App\Horaro\Library\ObscurityCodec;
use App\Horaro\Library\Transformer\Schedule\JsonTransformer;
use App\Horaro\Service\ObscurityCodecService;
use App\Horaro\Transformer\BaseTransformer;
use Symfony\Component\HttpFoundation\RequestStack;

class ScheduleTransformer extends BaseTransformer
{
    public function __construct(
        RequestStack          $requestStack,
        ObscurityCodecService $obscurityCodec,
        private readonly bool $includeHiddenColumns = false,
    )
    {
        parent::__construct($requestStack, $obscurityCodec);
    }

    public function transform(Schedule $schedule): array
    {
        $transformer = new JsonTransformer($this->obscurityCodec, $this->requestStack);
        $transformed = json_decode($transformer->transform($schedule, false, $this->includeHiddenColumns), true);

        $data = $transformed['schedule'];

        // remove private data (do not use transform()'s $public parameter because it removes the IDs as well)
        unset($data['theme'], $data['secret']);

        // embedding the event is handled by Fractal
        unset($data['event']);

        // replace "url" with an absolute "link"
        $data['link'] = $this->base().$data['url'];
        unset($data['url']);

        // "re-sort"
        $i = $data['items'];
        $c = $data['columns'];

        unset($data['items'], $data['columns']);

        $data['columns'] = $c;
        $data['items'] = $i;

        // add additional API links
        $eventID = $this->encodeID($schedule->getEvent()->getID(), ObscurityCodec::EVENT);
        $data['links'] = [
            ['rel' => 'self', 'uri' => $this->url('/v1/schedules/'.$data['id'])],
            ['rel' => 'event', 'uri' => $this->url('/v1/events/'.$eventID)],
            ['rel' => 'ticker', 'uri' => $this->url('/v1/schedules/'.$data['id']).'/ticker'],
        ];

        return $data;
    }
}

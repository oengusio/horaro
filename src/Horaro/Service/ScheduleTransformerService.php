<?php

namespace App\Horaro\Service;

use App\Horaro\Library\Transformer\Schedule\BaseTransformer;
use App\Horaro\Library\Transformer\Schedule\JsonTransformer;

class ScheduleTransformerService
{
    /**
     * @var BaseTransformer[]
     */
    private readonly array $transformers;

    public function __construct(private readonly ObscurityCodecService $obscurityCodecService)
    {
        $this->transformers = [
            'json' => JsonTransformer::class,
//            'jsonp' => null,
//            'csv' => null,
//            'ical' => null,
//            'xml' => null,
        ];
    }

    public function getTransformer(string $format): BaseTransformer
    {
//        dd($format, $this->transformers);

        if (!array_key_exists($format, $this->transformers)) {
            throw new \InvalidArgumentException('Unknown transformer for '.$format);
        }

        return new $this->transformers[$format]($this->obscurityCodecService);
    }
}

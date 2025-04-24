<?php

namespace App\Horaro\Service;

use App\Horaro\Library\Transformer\Schedule\BaseTransformer;
use App\Horaro\Library\Transformer\Schedule\CsvTransformer;
use App\Horaro\Library\Transformer\Schedule\JsonTransformer;
use App\Horaro\Library\Transformer\Schedule\XmlTransformer;

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
            'csv' => CsvTransformer::class,
//            'ical' => null,
            'xml' => XmlTransformer::class,
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

<?php

namespace App\Horaro\Transformer\Version1;

use App\Horaro\Transformer\BaseTransformer;

class IndexTransformer extends BaseTransformer
{
    public function transform(): array
    {
        return [
            'links' => [
                ['rel' => 'events', 'uri' => $this->url('/v1/events')],
            ],
        ];
    }
}

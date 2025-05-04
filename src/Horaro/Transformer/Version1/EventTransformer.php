<?php

namespace App\Horaro\Transformer\Version1;

use App\Entity\Event;
use App\Horaro\Library\ObscurityCodec;
use App\Horaro\Transformer\BaseTransformer;

class EventTransformer extends BaseTransformer
{
    public function transform(Event $event): array {
        $id        = $event->getID();
        $encodedID = $this->encodeID($id, ObscurityCodec::EVENT);
        $owner     = $event->getOwner();
        $links     = [
            ['rel' => 'self',      'uri' => $this->url('/v1/events/'.$encodedID)],
            ['rel' => 'schedules', 'uri' => $this->url('/v1/events/'.$encodedID.'/schedules')],
        ];

        return [
            'id'          => $encodedID,
            'name'        => $event->getName(),
            'slug'        => $event->getSlug(),
            'link'        => $this->base().'/'.$event->getSlug(),
            'description' => $event->getDescription(),
            'owner'       => $owner->getName(),
            'website'     => $event->getWebsite(),
            'twitter'     => $event->getTwitter(),
            'twitch'      => $event->getTwitch(),
            'links'       => $links,
        ];
    }
}

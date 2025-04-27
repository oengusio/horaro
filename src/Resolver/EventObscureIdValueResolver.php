<?php

namespace App\Resolver;

use App\Horaro\Ex\EventNotFoundException;
use App\Horaro\Service\ObscurityCodecService;
use App\Repository\EventRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

#[AsTargetedValueResolver('event_e')]
readonly class EventObscureIdValueResolver implements ValueResolverInterface
{
    public function __construct(
        private EventRepository $repository,
        private ObscurityCodecService $obscurityCodec,
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function resolve(Request $request, ArgumentMetadata $argument): array
    {
        [$options] = $argument->getAttributes(ValueResolver::class, ArgumentMetadata::IS_INSTANCEOF);
        $eventId = $request->get($options->resolver);

        $decoded = $this->obscurityCodec->decode($eventId, 'event');

        $foundEvent = $this->repository->findOneBy(['id' => $decoded]);

        if (!$foundEvent) {
            throw new EventNotFoundException();
        }

        return [$foundEvent];
    }
}

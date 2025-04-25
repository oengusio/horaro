<?php

namespace App\Resolver;

use App\Horaro\Ex\EventNotFoundException;
use App\Repository\EventRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

#[AsTargetedValueResolver('eventSlug')]
readonly class EventSlugValueResolver implements ValueResolverInterface
{
    public function __construct(private EventRepository $repository)
    {
    }

    /**
     * @inheritDoc
     */
    public function resolve(Request $request, ArgumentMetadata $argument): array
    {
        [$options] = $argument->getAttributes(ValueResolver::class, ArgumentMetadata::IS_INSTANCEOF);
        $eventSlug = $request->get($options->resolver);

        if ($eventSlug === '-' || $eventSlug === 'assets') {
            throw new EventNotFoundException();
        }

        $foundEvent = $this->repository->findOneBy(['slug' => $eventSlug]);

        if (!$foundEvent) {
            throw new EventNotFoundException();
        }

        return [$foundEvent];
    }
}

<?php

namespace App\Resolver;

use App\Horaro\Ex\ScheduleNotFoundException;
use App\Repository\ScheduleRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

#[AsTargetedValueResolver('scheduleSlug')]
readonly class ScheduleSlugValueResolver implements ValueResolverInterface
{
    public function __construct(private ScheduleRepository $repository)
    {
    }

    /**
     * @inheritDoc
     */
    public function resolve(Request $request, ArgumentMetadata $argument): array
    {
        // Safety to make sure we have our event slug in the url :)
        if (!$request->attributes->has('eventSlug')) {
            throw new \RuntimeException('Schedule slug resolver must be pared with eventSlug resolver & parameter');
        }

        [$options] = $argument->getAttributes(ValueResolver::class, ArgumentMetadata::IS_INSTANCEOF);

        $eventSlug = $request->attributes->get('eventSlug');
        $scheduleSlug = $request->get($options->resolver);

        $foundSchedule = $this->repository->findBySlug($eventSlug, $scheduleSlug);

        if (!$foundSchedule) {
            throw new ScheduleNotFoundException();
        }

        return [$foundSchedule];
    }
}

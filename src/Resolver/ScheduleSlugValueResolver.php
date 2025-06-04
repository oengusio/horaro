<?php

namespace App\Resolver;

use App\Horaro\Ex\ScheduleNotFoundException;
use App\Repository\ScheduleRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

use function mb_strtolower;
use function preg_replace;

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
        $origSlug = $scheduleSlug;
        $scheduleSlug = mb_strtolower($scheduleSlug);

        // strip bad trailing characters from badly detected links on other sites
        $scheduleSlug = preg_replace('/^(.*?)[^a-z0-9-].*/i', '$1', $scheduleSlug);

        $foundSchedule = $this->repository->findBySlug($eventSlug, $scheduleSlug);

        if (!$foundSchedule) {
            throw new ScheduleNotFoundException();
        }

        // TODO: redirect to the correct version to avoid duplicate content
        /*if ($origSlug !== $scheduleSlug || $foundSchedule->getEvent()->getSlug() !== $eventSlug) {
            throw new RedirectionException(
                new RedirectResponse($foundSchedule->getLink(), 301)
            );
        }*/

        return [$foundSchedule];
    }
}

<?php

namespace App\Resolver;

use App\Horaro\Ex\EventNotFoundException;
use App\Repository\EventRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function mb_strtolower;
use function preg_replace;

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
        $eventSlug = mb_strtolower($eventSlug);

        if ($eventSlug === '-' || $eventSlug === 'assets') {
            throw new NotFoundHttpException('Page not found');
        }

        // strip bad trailing characters from badly detected links on other sites
        $eventSlug = preg_replace('/[^a-z0-9]+$/i', '', $eventSlug);

        $foundEvent = $this->repository->findOneBy(['slug' => $eventSlug]);

        if (!$foundEvent) {
            throw new EventNotFoundException();
        }

        return [$foundEvent];
    }
}

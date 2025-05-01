<?php

namespace App\Resolver;

use App\Horaro\Ex\ScheduleNotFoundException;
use App\Horaro\Service\ObscurityCodecService;
use App\Repository\ScheduleRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

#[AsTargetedValueResolver('schedule_e')]
readonly class ScheduleObscureIdValueResolver implements ValueResolverInterface
{
    public function __construct(
        private ScheduleRepository $repository,
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
        $scheduleId = $request->get($options->resolver);

        $decoded = $this->obscurityCodec->decode($scheduleId, 'schedule');

        $foundSchedule = $this->repository->findOneBy(['id' => $decoded]);

        if (!$foundSchedule) {
            throw new ScheduleNotFoundException();
        }

        return [$foundSchedule];
    }
}

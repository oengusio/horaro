<?php

namespace App\Resolver;

use App\Horaro\Ex\ScheduleItemNotFoundException;
use App\Horaro\Library\ObscurityCodec;
use App\Horaro\Service\ObscurityCodecService;
use App\Repository\ScheduleItemRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

#[AsTargetedValueResolver('schedule_item_e')]
readonly class ScheduleItemObscureIdValueResolver implements ValueResolverInterface
{
    public function __construct(
        private ScheduleItemRepository $repository,
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
        $scheduleItemId = $request->get($options->resolver);

        $decoded = $this->obscurityCodec->decode($scheduleItemId, ObscurityCodec::SCHEDULE_ITEM);

        $foundSchedule = $this->repository->findOneBy(['id' => $decoded]);

        if (!$foundSchedule) {
            throw new ScheduleItemNotFoundException();
        }

        return [$foundSchedule];
    }
}

<?php

namespace App\Resolver;

use App\Horaro\Ex\ScheduleItemNotFoundException;
use App\Horaro\Library\ObscurityCodec;
use App\Horaro\Service\ObscurityCodecService;
use App\Horaro\Traits\KnowsAboutScheduleId;
use App\Repository\ScheduleItemRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

#[AsTargetedValueResolver('schedule_item_e')]
readonly class ScheduleItemObscureIdValueResolver implements ValueResolverInterface
{
    use KnowsAboutScheduleId;

    public function __construct(
        private ScheduleItemRepository $repository,
        private ObscurityCodecService  $obscurityCodec,
        private RequestStack           $requestStack,
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

        $foundScheduleItem = $this->repository->findOneBy([
            'id' => $decoded,
            'schedule' => $this->getDecodedScheduleId(),
        ]);

        if (!$foundScheduleItem) {
            throw new ScheduleItemNotFoundException();
        }

        return [$foundScheduleItem];
    }
}

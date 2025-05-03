<?php

namespace App\Resolver;

use App\Horaro\Ex\ScheduleColumnNotFoundException;
use App\Horaro\Library\ObscurityCodec;
use App\Horaro\Service\ObscurityCodecService;
use App\Horaro\Traits\KnowsAboutScheduleId;
use App\Repository\ScheduleColumnRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

#[AsTargetedValueResolver('schedule_column_e')]
readonly class ScheduleColumnObscureIdValueResolver implements ValueResolverInterface
{
    use KnowsAboutScheduleId;

    public function __construct(
        private ScheduleColumnRepository $repository,
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
        $scheduleColumn = $request->get($options->resolver);

        $decoded = $this->obscurityCodec->decode($scheduleColumn, ObscurityCodec::SCHEDULE_COLUMN);

        $foundScheduleColumn = $this->repository->findOneBy([
            'id' => $decoded,
            'schedule' => $this->getDecodedScheduleId(),
        ]);

        if (!$foundScheduleColumn) {
            throw new ScheduleColumnNotFoundException();
        }

        return [$foundScheduleColumn];
    }
}

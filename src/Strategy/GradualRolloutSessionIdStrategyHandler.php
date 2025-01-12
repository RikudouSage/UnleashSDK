<?php

namespace Rikudou\Unleash\Strategy;

use JetBrains\PhpStorm\Deprecated;
use Rikudou\Unleash\Configuration\Context;
use Rikudou\Unleash\DTO\DefaultStrategy;
use Rikudou\Unleash\DTO\Strategy;
use Rikudou\Unleash\Enum\Stickiness;

#[Deprecated(reason: 'The strategy has been deprecated, please use Gradual Rollout (flexibleRollout)')]
final class GradualRolloutSessionIdStrategyHandler extends AbstractStrategyHandler
{
    public function __construct(private GradualRolloutStrategyHandler $rolloutStrategyHandler)
    {
    }

    public function isEnabled(Strategy $strategy, Context $context): bool
    {
        $transformedStrategy = new DefaultStrategy(
            $this->getStrategyName(),
            [
                'stickiness' => Stickiness::SESSION_ID,
                'groupId' => $strategy->getParameters()['groupId'],
                'rollout' => $strategy->getParameters()['percentage'],
            ]
        );

        return $this->rolloutStrategyHandler->isEnabled($transformedStrategy, $context);
    }

    public function getStrategyName(): string
    {
        return 'gradualRolloutSessionId';
    }
}

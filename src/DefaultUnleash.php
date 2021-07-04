<?php

namespace Rikudou\Unleash;

use Rikudou\Unleash\Client\RegistrationService;
use Rikudou\Unleash\Configuration\UnleashContext;
use Rikudou\Unleash\DTO\Strategy;
use Rikudou\Unleash\DTO\Variant;
use Rikudou\Unleash\Metrics\MetricsHandler;
use Rikudou\Unleash\Repository\UnleashRepository;
use Rikudou\Unleash\Strategy\StrategyHandler;
use Rikudou\Unleash\Variant\VariantHandler;

final class DefaultUnleash implements Unleash
{
    /**
     * @var mixed[]
     */
    private $strategyHandlers;
    /**
     * @var \Rikudou\Unleash\Repository\UnleashRepository
     */
    private $repository;
    /**
     * @var \Rikudou\Unleash\Client\RegistrationService
     */
    private $registrationService;
    /**
     * @var \Rikudou\Unleash\Metrics\MetricsHandler
     */
    private $metricsHandler;
    /**
     * @var \Rikudou\Unleash\Variant\VariantHandler
     */
    private $variantHandler;
    /**
     * @param iterable<StrategyHandler> $strategyHandlers
     *
     * @internal
     */
    public function __construct(iterable $strategyHandlers, UnleashRepository $repository, RegistrationService $registrationService, bool $autoregister, MetricsHandler $metricsHandler, VariantHandler $variantHandler)
    {
        $this->strategyHandlers = $strategyHandlers;
        $this->repository = $repository;
        $this->registrationService = $registrationService;
        $this->metricsHandler = $metricsHandler;
        $this->variantHandler = $variantHandler;
        if ($autoregister) {
            $this->register();
        }
    }
    public function isEnabled(string $featureName, UnleashContext $context = null, bool $default = false): bool
    {
        if ($context === null) {
            $context = new UnleashContext();
        }

        $feature = $this->repository->findFeature($featureName);
        if ($feature === null) {
            return $default;
        }

        if (!$feature->isEnabled()) {
            $this->metricsHandler->handleMetrics($feature, false);

            return false;
        }

        $strategies = $feature->getStrategies();
        if (!is_countable($strategies)) {
            // @codeCoverageIgnoreStart
            $strategies = iterator_to_array($strategies);
            // @codeCoverageIgnoreEnd
        }
        if (!count($strategies)) {
            $this->metricsHandler->handleMetrics($feature, true);

            return true;
        }

        foreach ($strategies as $strategy) {
            $handlers = $this->findStrategyHandlers($strategy);
            if (!count($handlers)) {
                continue;
            }
            foreach ($handlers as $handler) {
                if ($handler->isEnabled($strategy, $context)) {
                    $this->metricsHandler->handleMetrics($feature, true);

                    return true;
                }
            }
        }

        $this->metricsHandler->handleMetrics($feature, false);

        return false;
    }

    public function getVariant(string $featureName, ?UnleashContext $context = null, ?Variant $fallbackVariant = null): Variant
    {
        $fallbackVariant = $fallbackVariant ?? $this->variantHandler->getDefaultVariant();
        $context = $context ?? new UnleashContext();

        $feature = $this->repository->findFeature($featureName);
        if ($feature === null || !$feature->isEnabled() || !count($feature->getVariants())) {
            return $fallbackVariant;
        }

        return $this->variantHandler->selectVariant($feature, $context) ?? $fallbackVariant;
    }

    public function register(): bool
    {
        return $this->registrationService->register($this->strategyHandlers);
    }

    /**
     * @return array<StrategyHandler>
     */
    private function findStrategyHandlers(Strategy $strategy): array
    {
        $handlers = [];
        foreach ($this->strategyHandlers as $strategyHandler) {
            if ($strategyHandler->supports($strategy)) {
                $handlers[] = $strategyHandler;
            }
        }

        return $handlers;
    }
}

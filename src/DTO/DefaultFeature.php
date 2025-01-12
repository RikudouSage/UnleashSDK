<?php

namespace Rikudou\Unleash\DTO;

final class DefaultFeature implements Feature
{
    /**
     * @param iterable<Strategy> $strategies
     * @param array<Variant>     $variants
     */
    public function __construct(
        private string $name,
        private bool $enabled,
        private iterable $strategies,
        private array $variants = [],
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return iterable<Strategy>
     */
    public function getStrategies(): iterable
    {
        return $this->strategies;
    }

    /**
     * @return array<Variant>
     */
    public function getVariants(): array
    {
        return $this->variants;
    }
}

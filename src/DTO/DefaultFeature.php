<?php

namespace Rikudou\Unleash\DTO;

final class DefaultFeature implements Feature
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var bool
     */
    private $enabled;
    /**
     * @var mixed[]
     */
    private $strategies;
    /**
     * @param iterable<Strategy> $strategies
     */
    public function __construct(string $name, bool $enabled, iterable $strategies)
    {
        $this->name = $name;
        $this->enabled = $enabled;
        $this->strategies = $strategies;
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
}

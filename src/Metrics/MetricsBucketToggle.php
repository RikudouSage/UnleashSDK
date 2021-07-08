<?php

namespace Rikudou\Unleash\Metrics;

use Rikudou\Unleash\DTO\Feature;
use Rikudou\Unleash\DTO\Variant;

/**
 * @internal
 */
final class MetricsBucketToggle
{
    /**
     * @var \Rikudou\Unleash\DTO\Feature
     */
    private $feature;
    /**
     * @var bool
     */
    private $success;
    /**
     * @var \Rikudou\Unleash\DTO\Variant|null
     */
    private $variant;
    public function __construct(Feature $feature, bool $success, ?Variant $variant = null)
    {
        $this->feature = $feature;
        $this->success = $success;
        $this->variant = $variant;
    }
    public function getFeature(): Feature
    {
        return $this->feature;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getVariant(): ?Variant
    {
        return $this->variant;
    }
}

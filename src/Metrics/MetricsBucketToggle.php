<?php

namespace Rikudou\Unleash\Metrics;

use Rikudou\Unleash\DTO\Feature;

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
    public function __construct(Feature $feature, bool $success)
    {
        $this->feature = $feature;
        $this->success = $success;
    }
    public function getFeature(): Feature
    {
        return $this->feature;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }
}

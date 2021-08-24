<?php

namespace Rikudou\Unleash\DTO;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\ExpectedValues;
use Rikudou\Unleash\Enum\Stickiness;

final class DefaultVariant implements Variant
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
     * @var int
     */
    private $weight = 0;
    /**
     * @var string
     */
    private $stickiness = Stickiness::DEFAULT;
    /**
     * @var \Rikudou\Unleash\DTO\VariantPayload|null
     */
    private $payload;
    /**
     * @var \Rikudou\Unleash\DTO\VariantOverride[]
     */
    private $overrides;
    /**
     * @param array<VariantOverride> $overrides
     */
    public function __construct(
        string $name,
        bool $enabled,
        int $weight = 0,
        #[\JetBrains\PhpStorm\ExpectedValues(valuesFromClass: \Rikudou\Unleash\Enum\Stickiness::class)]
        string $stickiness = Stickiness::DEFAULT,
        ?VariantPayload $payload = null,
        ?array $overrides = null
    )
    {
        $this->name = $name;
        $this->enabled = $enabled;
        $this->weight = $weight;
        $this->stickiness = $stickiness;
        $this->payload = $payload;
        $this->overrides = $overrides;
    }
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getPayload(): ?VariantPayload
    {
        return $this->payload;
    }

    /**
     * @phpstan-return array<string|bool|array>
     */
    #[ArrayShape(['name' => 'string', 'enabled' => 'bool', 'payload' => 'mixed'])]
    public function jsonSerialize(): array
    {
        $result = [
            'name' => $this->name,
            'enabled' => $this->enabled,
        ];
        if ($this->payload !== null) {
            $result['payload'] = $this->payload->jsonSerialize();
        }

        return $result;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return array<VariantOverride>
     */
    public function getOverrides(): array
    {
        return $this->overrides ?? [];
    }

    #[ExpectedValues(valuesFromClass: Stickiness::class)]
    public function getStickiness(): string
    {
        return $this->stickiness;
    }
}

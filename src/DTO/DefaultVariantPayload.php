<?php

/** @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection */

namespace Rikudou\Unleash\DTO;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\Pure;
use JsonException;
use LogicException;
use Rikudou\Unleash\Enum\VariantPayloadType;

final class DefaultVariantPayload implements VariantPayload
{
    public function __construct(
        #[ExpectedValues(valuesFromClass: VariantPayloadType::class)]
        private string $type,
        private string $value,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    #[ExpectedValues(valuesFromClass: VariantPayloadType::class)]
    public function getType(): string
    {
        return $this->type;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @throws JsonException
     *
     * @return array<mixed>
     */
    public function fromJson(): array
    {
        if ($this->type !== VariantPayloadType::JSON) {
            throw new LogicException(
                sprintf(
                    "Only payloads of type '%s' can be converted from json, this payload has type '%s'",
                    VariantPayloadType::JSON,
                    $this->type,
                )
            );
        }

        return json_decode($this->value, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string>
     */
    #[Pure]
    #[ArrayShape(['type' => 'string', 'value' => 'string'])]
    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type,
            'value' => $this->getValue(),
        ];
    }
}

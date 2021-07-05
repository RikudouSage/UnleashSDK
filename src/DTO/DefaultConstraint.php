<?php

namespace Rikudou\Unleash\DTO;

use JetBrains\PhpStorm\ExpectedValues;
use Rikudou\Unleash\Enum\ConstraintOperator;

final class DefaultConstraint implements Constraint
{
    /**
     * @var string
     */
    private $contextName;
    /**
     * @var string
     */
    private $operator;
    /**
     * @var mixed[]
     */
    private $values;
    /**
     * @param array<string> $values
     */
    public function __construct(string $contextName, string $operator, array $values)
    {
        $this->contextName = $contextName;
        $this->operator = $operator;
        $this->values = $values;
    }
    public function getContextName(): string
    {
        return $this->contextName;
    }

    #[ExpectedValues(valuesFromClass: ConstraintOperator::class)]
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @return array<string>
     */
    public function getValues(): array
    {
        return $this->values;
    }
}

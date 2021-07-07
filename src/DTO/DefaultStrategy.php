<?php

namespace Rikudou\Unleash\DTO;

final class DefaultStrategy implements Strategy
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var mixed[]
     */
    private $parameters = [];
    /**
     * @var mixed[]
     */
    private $constraints = [];
    /**
     * @param array<string,string> $parameters
     * @param array<Constraint>    $constraints
     */
    public function __construct(string $name, array $parameters = [], array $constraints = [])
    {
        $this->name = $name;
        $this->parameters = $parameters;
        $this->constraints = $constraints;
    }
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, string>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return array<Constraint>
     */
    public function getConstraints(): array
    {
        return $this->constraints;
    }
}

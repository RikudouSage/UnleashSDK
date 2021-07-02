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
    private $parameters;
    /**
     * @param array<string,string> $parameters
     */
    public function __construct(string $name, array $parameters)
    {
        $this->name = $name;
        $this->parameters = $parameters;
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
}

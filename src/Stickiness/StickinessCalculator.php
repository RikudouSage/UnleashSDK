<?php

namespace Rikudou\Unleash\Stickiness;

interface StickinessCalculator
{
    public function calculate(string $id, string $groupId, int $normalizer = 100): int;
}

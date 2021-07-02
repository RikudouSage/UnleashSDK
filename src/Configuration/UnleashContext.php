<?php

namespace Rikudou\Unleash\Configuration;

use Rikudou\Unleash\Exception\InvalidValueException;

final class UnleashContext
{
    /**
     * @var array<string,string>
     */
    private $customContext = [];
    /**
     * @var string|null
     */
    private $currentUserId;
    /**
     * @var string|null
     */
    private $ipAddress;
    /**
     * @var string|null
     */
    private $sessionId;
    public function __construct(?string $currentUserId = null, ?string $ipAddress = null, ?string $sessionId = null)
    {
        $this->currentUserId = $currentUserId;
        $this->ipAddress = $ipAddress;
        $this->sessionId = $sessionId;
    }

    public function getCurrentUserId(): ?string
    {
        return $this->currentUserId;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress ?? $_SERVER['REMOTE_ADDR'];
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId ?? (session_id() ?: null);
    }

    public function getCustomProperty(string $name): string
    {
        if (!array_key_exists($name, $this->customContext)) {
            throw new InvalidValueException("The custom context value '{$name}' does not exist");
        }

        return $this->customContext[$name];
    }

    /**
     * @return $this
     */
    public function setCustomProperty(string $name, string $value)
    {
        $this->customContext[$name] = $value;

        return $this;
    }

    public function hasCustomProperty(string $name): bool
    {
        return array_key_exists($name, $this->customContext);
    }

    /**
     * @return $this
     */
    public function removeCustomProperty(string $name, bool $silent = true)
    {
        if (!$this->hasCustomProperty($name) && !$silent) {
            throw new InvalidValueException("The custom context value '{$name}' does not exist");
        }

        unset($this->customContext[$name]);

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function setCurrentUserId(?string $currentUserId): UnleashContext
    {
        $this->currentUserId = $currentUserId;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function setIpAddress(?string $ipAddress): UnleashContext
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function setSessionId(?string $sessionId): UnleashContext
    {
        $this->sessionId = $sessionId;

        return $this;
    }
}

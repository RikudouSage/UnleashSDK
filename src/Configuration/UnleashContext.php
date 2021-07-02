<?php

namespace Rikudou\Unleash\Configuration;

final class UnleashContext
{
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

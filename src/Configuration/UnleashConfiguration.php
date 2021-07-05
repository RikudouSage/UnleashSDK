<?php

namespace Rikudou\Unleash\Configuration;

use Psr\SimpleCache\CacheInterface;

final class UnleashConfiguration
{
    /**
     * @var \Psr\SimpleCache\CacheInterface|null
     */
    private $cache;

    /**
     * @var int
     */
    private $ttl = 30;

    /**
     * @var int
     */
    private $metricsInterval = 30000;

    /**
     * @var bool
     */
    private $metricsEnabled = true;
    /**
     * @var string
     */
    private $url;
    /**
     * @var string
     */
    private $appName;
    /**
     * @var string
     */
    private $instanceId;

    public function __construct(string $url, string $appName, string $instanceId)
    {
        $this->url = $url;
        $this->appName = $appName;
        $this->instanceId = $instanceId;
    }

    public function getCache(): ?CacheInterface
    {
        return $this->cache;
    }

    public function getUrl(): string
    {
        $url = $this->url;
        if (substr($url, -1) !== '/') {
            $url .= '/';
        }

        return $url;
    }

    public function getAppName(): string
    {
        return $this->appName;
    }

    public function getInstanceId(): string
    {
        return $this->instanceId;
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }

    /**
     * @return $this
     */
    public function setCache(?CacheInterface $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * @return $this
     */
    public function setTtl(int $ttl)
    {
        $this->ttl = $ttl;

        return $this;
    }

    public function getMetricsInterval(): int
    {
        return $this->metricsInterval;
    }

    public function isMetricsEnabled(): bool
    {
        return $this->metricsEnabled;
    }

    /**
     * @return $this
     */
    public function setUrl(string $url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return $this
     */
    public function setAppName(string $appName)
    {
        $this->appName = $appName;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return $this
     */
    public function setInstanceId(string $instanceId)
    {
        $this->instanceId = $instanceId;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return $this
     */
    public function setMetricsInterval(int $metricsInterval)
    {
        $this->metricsInterval = $metricsInterval;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return $this
     */
    public function setMetricsEnabled(bool $metricsEnabled)
    {
        $this->metricsEnabled = $metricsEnabled;

        return $this;
    }
}

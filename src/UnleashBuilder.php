<?php

namespace Rikudou\Unleash;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use JetBrains\PhpStorm\Immutable;
use JetBrains\PhpStorm\Pure;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use Rikudou\Unleash\Configuration\UnleashConfiguration;
use Rikudou\Unleash\Exception\InvalidValueException;
use Rikudou\Unleash\Repository\DefaultUnleashRepository;
use Rikudou\Unleash\Stickiness\MurmurHashCalculator;
use Rikudou\Unleash\Strategy\DefaultStrategyHandler;
use Rikudou\Unleash\Strategy\GradualRolloutRandomStrategyHandler;
use Rikudou\Unleash\Strategy\GradualRolloutSessionIdStrategyHandler;
use Rikudou\Unleash\Strategy\GradualRolloutStrategyHandler;
use Rikudou\Unleash\Strategy\GradualRolloutUserIdStrategyHandler;
use Rikudou\Unleash\Strategy\IpAddressStrategyHandler;
use Rikudou\Unleash\Strategy\StrategyHandler;
use Rikudou\Unleash\Strategy\UserIdStrategyHandler;

#[Immutable]
final class UnleashBuilder
{
    /**
     * @var string|null
     */
    private $appUrl;

    /**
     * @var string|null
     */
    private $instanceId;

    /**
     * @var string|null
     */
    private $appName;

    /**
     * @var \Psr\Http\Client\ClientInterface|null
     */
    private $httpClient;

    /**
     * @var \Psr\Http\Message\RequestFactoryInterface|null
     */
    private $requestFactory;

    /**
     * @var \Psr\SimpleCache\CacheInterface|null
     */
    private $cache;

    /**
     * @var int|null
     */
    private $cacheTtl;

    /**
     * @var array<string,string>
     */
    private $headers = [];

    /**
     * @var array<StrategyHandler>|null
     */
    private $strategies;

    /**
     * @return $this
     */
    #[Pure]
    public static function create()
    {
        return new self();
    }

    /**
     * @return $this
     */
    #[Pure]
    public function withAppUrl(string $appUrl)
    {
        return $this->with('appUrl', $appUrl);
    }

    /**
     * @return $this
     */
    #[Pure]
    public function withInstanceId(string $instanceId)
    {
        return $this->with('instanceId', $instanceId);
    }

    /**
     * @return $this
     */
    #[Pure]
    public function withAppName(string $appName)
    {
        return $this->with('appName', $appName);
    }

    /**
     * @return $this
     */
    #[Pure]
    public function withHttpClient(ClientInterface $client)
    {
        return $this->with('httpClient', $client);
    }

    /**
     * @return $this
     */
    #[Pure]
    public function withRequestFactory(RequestFactoryInterface $requestFactory)
    {
        return $this->with('requestFactory', $requestFactory);
    }

    /**
     * @return $this
     */
    #[Pure]
    public function withStrategies(StrategyHandler ...$strategies)
    {
        return $this->with('strategies', $strategies);
    }

    /**
     * @return $this
     */
    #[Pure]
    public function withCacheHandler(?CacheInterface $cache, ?int $timeToLive = null)
    {
        $result = $this->with('cache', $cache);
        if ($timeToLive !== null) {
            $result = $result->withCacheTimeToLive($timeToLive);
        }

        return $result;
    }

    /**
     * @return $this
     */
    #[Pure]
    public function withCacheTimeToLive(int $timeToLive)
    {
        return $this->with('cacheTtl', $timeToLive);
    }

    /**
     * @return $this
     */
    #[Pure]
    public function withHeader(string $header, string $value)
    {
        return $this->with('headers', array_merge($this->headers, [$header => $value]));
    }

    /**
     * @param array<string, string> $headers
     * @return $this
     */
    #[Pure]
    public function withHeaders(array $headers)
    {
        return $this->with('headers', $headers);
    }

    public function build(): Unleash
    {
        if ($this->appUrl === null) {
            throw new InvalidValueException("App url must be set, please use 'withAppUrl()' method");
        }
        if ($this->instanceId === null) {
            throw new InvalidValueException("Instance ID must be set, please use 'withInstanceId()' method");
        }
        if ($this->appName === null) {
            throw new InvalidValueException("App name must be set, please use 'withAppName()' method");
        }

        $configuration = new UnleashConfiguration($this->appUrl, $this->appName, $this->instanceId);
        $configuration
            ->setCache($this->cache)
            ->setTtl($this->cacheTtl ?? $configuration->getTtl());

        $httpClient = $this->httpClient;
        if ($httpClient === null) {
            if (class_exists(Client::class)) {
                $httpClient = new Client();
            } else {
                throw new InvalidValueException(
                    "No http client provided and Guzzle is not installed, please use 'withHttpClient()' method"
                );
            }
        }
        assert($httpClient instanceof ClientInterface);

        $requestFactory = $this->requestFactory;
        if ($requestFactory === null) {
            if (class_exists(HttpFactory::class)) {
                $requestFactory = new HttpFactory();
            } else {
                throw new InvalidValueException(
                    "No request factory provided and Guzzle is not installed, please use 'withRequestFactory()' method"
                );
            }
        }
        assert($requestFactory instanceof RequestFactoryInterface);

        $repository = new DefaultUnleashRepository($httpClient, $requestFactory, $configuration, $this->headers);

        $strategies = $this->strategies;
        if ($strategies === null || !count($strategies)) {
            $rolloutStrategyHandler = new GradualRolloutStrategyHandler(new MurmurHashCalculator());
            $strategies = [
                new DefaultStrategyHandler(),
                new IpAddressStrategyHandler(),
                new UserIdStrategyHandler(),
                $rolloutStrategyHandler,
                new GradualRolloutUserIdStrategyHandler($rolloutStrategyHandler),
                new GradualRolloutSessionIdStrategyHandler($rolloutStrategyHandler),
                new GradualRolloutRandomStrategyHandler($rolloutStrategyHandler),
            ];
        }

        return new DefaultUnleash($strategies, $repository);
    }

    /**
     * @param mixed $value
     * @return $this
     */
    private function with(string $property, $value)
    {
        $copy = clone $this;
        $copy->{$property} = $value;

        return $copy;
    }
}

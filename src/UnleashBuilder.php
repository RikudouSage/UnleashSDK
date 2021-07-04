<?php

namespace Rikudou\Unleash;

use JetBrains\PhpStorm\Immutable;
use JetBrains\PhpStorm\Pure;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use Rikudou\Unleash\Client\DefaultRegistrationService;
use Rikudou\Unleash\Client\RegistrationService;
use Rikudou\Unleash\Configuration\UnleashConfiguration;
use Rikudou\Unleash\Exception\InvalidValueException;
use Rikudou\Unleash\Helper\DefaultHttpImplementationLocator;
use Rikudou\Unleash\Metrics\DefaultMetricsHandler;
use Rikudou\Unleash\Metrics\DefaultMetricsSender;
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
use Rikudou\Unleash\Variant\DefaultVariantHandler;

#[Immutable]
final class UnleashBuilder
{
    /**
     * @var \Rikudou\Unleash\Helper\DefaultHttpImplementationLocator
     */
    private $defaultHttpImplementationLocator;

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
     * @var \Rikudou\Unleash\Client\RegistrationService|null
     */
    private $registrationService;

    /**
     * @var bool
     */
    private $autoregister = true;

    /**
     * @var bool|null
     */
    private $metricsEnabled;

    /**
     * @var int|null
     */
    private $metricsInterval;

    /**
     * @var array<string,string>
     */
    private $headers = [];

    /**
     * @var array<StrategyHandler>|null
     */
    private $strategies;

    #[Pure]
    public function __construct()
    {
        $this->defaultHttpImplementationLocator = new DefaultHttpImplementationLocator();
    }

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
    public static function createForGitlab()
    {
        return self::create()
            ->withMetricsEnabled(false)
            ->withAutomaticRegistrationEnabled(false);
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
    public function withGitlabEnvironment(string $environment)
    {
        return $this->withAppName($environment);
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

    /**
     * @return $this
     */
    #[Pure]
    public function withRegistrationService(RegistrationService $registrationService)
    {
        return $this->with('registrationService', $registrationService);
    }

    /**
     * @return $this
     */
    #[Pure]
    public function withAutomaticRegistrationEnabled(bool $enabled)
    {
        return $this->with('autoregister', $enabled);
    }

    /**
     * @return $this
     */
    #[Pure]
    public function withMetricsEnabled(bool $enabled)
    {
        return $this->with('metricsEnabled', $enabled);
    }

    /**
     * @return $this
     */
    #[Pure]
    public function withMetricsInterval(int $milliseconds)
    {
        return $this->with('metricsInterval', $milliseconds);
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
            throw new InvalidValueException(
                "App name must be set, please use 'withAppName()' or 'withGitlabEnvironment()' method"
            );
        }

        $configuration = new UnleashConfiguration($this->appUrl, $this->appName, $this->instanceId);
        $configuration
            ->setCache($this->cache)
            ->setTtl($this->cacheTtl ?? $configuration->getTtl())
            ->setMetricsEnabled($this->metricsEnabled ?? $configuration->isMetricsEnabled())
            ->setMetricsInterval($this->metricsInterval ?? $configuration->getMetricsInterval())
        ;

        $httpClient = $this->httpClient;
        if ($httpClient === null) {
            $httpClient = $this->defaultHttpImplementationLocator->findHttpClient();
            if ($httpClient === null) {
                throw new InvalidValueException(
                    sprintf(
                        "No http client provided, please use 'withHttpClient()' method or install one of officially supported clients: '%s'",
                        implode("', '", $this->defaultHttpImplementationLocator->getHttpClientPackages())
                    )
                );
            }
        }
        assert($httpClient instanceof ClientInterface);

        $requestFactory = $this->requestFactory;
        if ($requestFactory === null) {
            $requestFactory = $this->defaultHttpImplementationLocator->findRequestFactory();
            if ($requestFactory === null) {
                throw new InvalidValueException(
                    sprintf(
                        "No request factory provided, please use 'withHttpClient()' method or install one of officially supported clients: '%s'",
                        implode("', '", $this->defaultHttpImplementationLocator->getRequestFactoryPackages())
                    )
                );
            }
        }
        assert($requestFactory instanceof RequestFactoryInterface);

        $repository = new DefaultUnleashRepository($httpClient, $requestFactory, $configuration, $this->headers);

        $hashCalculator = new MurmurHashCalculator();
        $strategies = $this->strategies;
        if ($strategies === null || !count($strategies)) {
            $rolloutStrategyHandler = new GradualRolloutStrategyHandler($hashCalculator);
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

        $registrationService = $this->registrationService;
        if ($registrationService === null) {
            $registrationService = new DefaultRegistrationService($httpClient, $requestFactory, $configuration, $this->headers);
        }

        return new DefaultUnleash($strategies, $repository, $registrationService, $this->autoregister, new DefaultMetricsHandler(
            new DefaultMetricsSender(
                $httpClient,
                $requestFactory,
                $configuration,
                $this->headers
            ),
            $configuration
        ), new DefaultVariantHandler($hashCalculator));
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

<?php

namespace Rikudou\Unleash;

use JetBrains\PhpStorm\Immutable;
use JetBrains\PhpStorm\Pure;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use Rikudou\Unleash\Client\DefaultRegistrationService;
use Rikudou\Unleash\Client\RegistrationService;
use Rikudou\Unleash\Configuration\Context;
use Rikudou\Unleash\Configuration\UnleashConfiguration;
use Rikudou\Unleash\Exception\InvalidValueException;
use Rikudou\Unleash\Helper\DefaultImplementationLocator;
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
    private DefaultImplementationLocator $defaultImplementationLocator;

    private ?string $appUrl = null;

    private ?string $instanceId = null;

    private ?string $appName = null;

    private ?ClientInterface $httpClient = null;

    private ?RequestFactoryInterface $requestFactory = null;

    private ?CacheInterface $cache = null;

    private ?int $cacheTtl = null;

    private ?RegistrationService $registrationService = null;

    private bool $autoregister = true;

    private ?bool $metricsEnabled = null;

    private ?int $metricsInterval = null;

    private ?Context $defaultContext = null;

    /**
     * @var array<string,string>
     */
    private array $headers = [];

    /**
     * @var array<StrategyHandler>
     */
    private array $strategies;

    #[Pure]
    public function __construct()
    {
        $this->defaultImplementationLocator = new DefaultImplementationLocator();

        $rolloutStrategyHandler = new GradualRolloutStrategyHandler(new MurmurHashCalculator());
        $this->strategies = [
            new DefaultStrategyHandler(),
            new IpAddressStrategyHandler(),
            new UserIdStrategyHandler(),
            $rolloutStrategyHandler,
            new GradualRolloutUserIdStrategyHandler($rolloutStrategyHandler),
            new GradualRolloutSessionIdStrategyHandler($rolloutStrategyHandler),
            new GradualRolloutRandomStrategyHandler($rolloutStrategyHandler),
        ];
    }

    #[Pure]
    public static function create(): self
    {
        return new self();
    }

    #[Pure]
    public static function createForGitlab(): self
    {
        return self::create()
            ->withMetricsEnabled(false)
            ->withAutomaticRegistrationEnabled(false);
    }

    #[Pure]
    public function withAppUrl(string $appUrl): self
    {
        return $this->with('appUrl', $appUrl);
    }

    #[Pure]
    public function withInstanceId(string $instanceId): self
    {
        return $this->with('instanceId', $instanceId);
    }

    #[Pure]
    public function withAppName(string $appName): self
    {
        return $this->with('appName', $appName);
    }

    #[Pure]
    public function withGitlabEnvironment(string $environment): self
    {
        return $this->withAppName($environment);
    }

    #[Pure]
    public function withHttpClient(ClientInterface $client): self
    {
        return $this->with('httpClient', $client);
    }

    #[Pure]
    public function withRequestFactory(RequestFactoryInterface $requestFactory): self
    {
        return $this->with('requestFactory', $requestFactory);
    }

    #[Pure]
    public function withStrategies(StrategyHandler ...$strategies): self
    {
        return $this->with('strategies', $strategies);
    }

    #[Pure]
    public function withStrategy(StrategyHandler $strategy): self
    {
        return $this->withStrategies(...array_merge($this->strategies, [$strategy]));
    }

    #[Pure]
    public function withCacheHandler(?CacheInterface $cache, ?int $timeToLive = null): self
    {
        $result = $this->with('cache', $cache);
        if ($timeToLive !== null) {
            $result = $result->withCacheTimeToLive($timeToLive);
        }

        return $result;
    }

    #[Pure]
    public function withCacheTimeToLive(int $timeToLive): self
    {
        return $this->with('cacheTtl', $timeToLive);
    }

    #[Pure]
    public function withHeader(string $header, string $value): self
    {
        return $this->with('headers', array_merge($this->headers, [$header => $value]));
    }

    /**
     * @param array<string, string> $headers
     */
    #[Pure]
    public function withHeaders(array $headers): self
    {
        return $this->with('headers', $headers);
    }

    #[Pure]
    public function withRegistrationService(RegistrationService $registrationService): self
    {
        return $this->with('registrationService', $registrationService);
    }

    #[Pure]
    public function withAutomaticRegistrationEnabled(bool $enabled): self
    {
        return $this->with('autoregister', $enabled);
    }

    #[Pure]
    public function withMetricsEnabled(bool $enabled): self
    {
        return $this->with('metricsEnabled', $enabled);
    }

    #[Pure]
    public function withMetricsInterval(int $milliseconds): self
    {
        return $this->with('metricsInterval', $milliseconds);
    }

    #[Pure]
    public function withDefaultContext(?Context $context): self
    {
        return $this->with('defaultContext', $context);
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

        $cache = $this->cache;
        if ($cache === null) {
            $cache = $this->defaultImplementationLocator->findCache();
            if ($cache === null) {
                throw new InvalidValueException(
                    sprintf(
                        "No cache implementation provided, please use 'withCacheHandler()' method or install one of officially supported clients: '%s'",
                        implode("', '", $this->defaultImplementationLocator->getCachePackages())
                    )
                );
            }
        }
        assert($cache instanceof CacheInterface);

        $configuration = new UnleashConfiguration($this->appUrl, $this->appName, $this->instanceId);
        $configuration
            ->setCache($cache)
            ->setTtl($this->cacheTtl ?? $configuration->getTtl())
            ->setMetricsEnabled($this->metricsEnabled ?? $configuration->isMetricsEnabled())
            ->setMetricsInterval($this->metricsInterval ?? $configuration->getMetricsInterval())
            ->setHeaders($this->headers)
            ->setAutoRegistrationEnabled($this->autoregister)
            ->setDefaultContext($this->defaultContext)
        ;

        $httpClient = $this->httpClient;
        if ($httpClient === null) {
            $httpClient = $this->defaultImplementationLocator->findHttpClient();
            if ($httpClient === null) {
                throw new InvalidValueException(
                    sprintf(
                        "No http client provided, please use 'withHttpClient()' method or install one of officially supported clients: '%s'",
                        implode("', '", $this->defaultImplementationLocator->getHttpClientPackages())
                    )
                );
            }
        }
        assert($httpClient instanceof ClientInterface);

        $requestFactory = $this->requestFactory;
        if ($requestFactory === null) {
            $requestFactory = $this->defaultImplementationLocator->findRequestFactory();
            if ($requestFactory === null) {
                throw new InvalidValueException(
                    sprintf(
                        "No request factory provided, please use 'withHttpClient()' method or install one of officially supported clients: '%s'",
                        implode("', '", $this->defaultImplementationLocator->getRequestFactoryPackages())
                    )
                );
            }
        }
        assert($requestFactory instanceof RequestFactoryInterface);

        $repository = new DefaultUnleashRepository($httpClient, $requestFactory, $configuration);

        $hashCalculator = new MurmurHashCalculator();

        $registrationService = $this->registrationService;
        if ($registrationService === null) {
            $registrationService = new DefaultRegistrationService($httpClient, $requestFactory, $configuration);
        }

        return new DefaultUnleash(
            $this->strategies,
            $repository,
            $registrationService,
            $configuration,
            new DefaultMetricsHandler(
                new DefaultMetricsSender(
                    $httpClient,
                    $requestFactory,
                    $configuration,
                ),
                $configuration
            ),
            new DefaultVariantHandler($hashCalculator),
        );
    }

    private function with(string $property, mixed $value): self
    {
        $copy = clone $this;
        $copy->{$property} = $value;

        return $copy;
    }
}

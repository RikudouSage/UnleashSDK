<?php

namespace Rikudou\Unleash\Client;

use DateTimeImmutable;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Rikudou\Unleash\Configuration\UnleashConfiguration;
use Rikudou\Unleash\Enum\CacheKey;
use Rikudou\Unleash\Helper\StringStream;
use Rikudou\Unleash\Strategy\StrategyHandler;
use Rikudou\Unleash\Unleash;

final class DefaultRegistrationService implements RegistrationService
{
    public function __construct(
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private UnleashConfiguration $configuration,
    ) {
    }

    /**
     * @param iterable<StrategyHandler> $strategyHandlers
     *
     * @throws JsonException
     * @throws ClientExceptionInterface
     */
    public function register(iterable $strategyHandlers): bool
    {
        if ($this->hasValidCacheRegistration()) {
            return true;
        }
        if (!is_array($strategyHandlers)) {
            // @codeCoverageIgnoreStart
            $strategyHandlers = iterator_to_array($strategyHandlers);
            // @codeCoverageIgnoreEnd
        }
        $request = $this->requestFactory
            ->createRequest('POST', $this->configuration->getUrl() . 'client/register')
            ->withHeader('Content-Type', 'application/json')
            ->withBody(new StringStream(json_encode([
                'appName' => $this->configuration->getAppName(),
                'instanceId' => $this->configuration->getInstanceId(),
                'sdkVersion' => 'rikudou-unleash-sdk:' . Unleash::SDK_VERSION,
                'strategies' => array_map(function (StrategyHandler $strategyHandler): string {
                    return $strategyHandler->getStrategyName();
                }, $strategyHandlers),
                'started' => (new DateTimeImmutable())->format('c'),
                'interval' => $this->configuration->getMetricsInterval(),
            ], JSON_THROW_ON_ERROR)));
        foreach ($this->configuration->getHeaders() as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        $response = $this->httpClient->sendRequest($request);

        $result = $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
        $this->storeCache($result);

        return $result;
    }

    private function hasValidCacheRegistration(): bool
    {
        $cache = $this->configuration->getCache();
        if (!$cache->has(CacheKey::REGISTRATION)) {
            return false;
        }

        return $cache->get(CacheKey::REGISTRATION);
    }

    private function storeCache(bool $result): void
    {
        $this->configuration->getCache()->set(CacheKey::REGISTRATION, $result, $this->configuration->getTtl());
    }
}

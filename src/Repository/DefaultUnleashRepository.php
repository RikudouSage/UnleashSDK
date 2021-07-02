<?php

namespace Rikudou\Unleash\Repository;

use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Rikudou\Unleash\Configuration\UnleashConfiguration;
use Rikudou\Unleash\DTO\DefaultFeature;
use Rikudou\Unleash\DTO\DefaultStrategy;
use Rikudou\Unleash\DTO\Feature;

final class DefaultUnleashRepository implements UnleashRepository
{
    private const CACHE_KEY_FEATURES = 'rikudou.unleash.feature.list';
    /**
     * @var \Psr\Http\Client\ClientInterface
     */
    private $httpClient;
    /**
     * @var \Psr\Http\Message\RequestFactoryInterface
     */
    private $requestFactory;
    /**
     * @var \Rikudou\Unleash\Configuration\UnleashConfiguration
     */
    private $configuration;
    public function __construct(ClientInterface $httpClient, RequestFactoryInterface $requestFactory, UnleashConfiguration $configuration)
    {
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->configuration = $configuration;
    }

    /**
     * @throws ClientExceptionInterface
     * @throws InvalidArgumentException
     * @throws JsonException
     */
    public function findFeature(string $featureName): ?Feature
    {
        $features = $this->getFeatures();
        assert(is_array($features));

        return $features[$featureName] ?? null;
    }

    /**
     * @throws InvalidArgumentException
     * @throws ClientExceptionInterface
     * @throws JsonException
     *
     * @return iterable<Feature>
     */
    public function getFeatures(): iterable
    {
        if (!$features = $this->getCachedFeatures()) {
            $request = $this->requestFactory
                ->createRequest('GET', $this->configuration->getUrl() . 'client/features')
                ->withHeader('UNLEASH-APPNAME', $this->configuration->getAppName())
                ->withHeader('UNLEASH-INSTANCEID', $this->configuration->getInstanceId());
            $response = $this->httpClient->sendRequest($request);
            $features = $this->parseFeatures($response->getBody()->getContents());
            $this->setCache($features);
        }

        return $features;
    }

    /**
     * @throws InvalidArgumentException
     *
     * @return array<Feature>|null
     */
    private function getCachedFeatures(): ?array
    {
        $cache = $this->configuration->getCache();
        if ($cache === null) {
            return null;
        }

        if (!$cache->has(self::CACHE_KEY_FEATURES)) {
            return null;
        }

        return $cache->get(self::CACHE_KEY_FEATURES, []);
    }

    /**
     * @param array<Feature> $features
     *
     * @throws InvalidArgumentException
     */
    private function setCache(array $features): void
    {
        $cache = $this->configuration->getCache();
        if ($cache === null) {
            return;
        }

        $cache->set(self::CACHE_KEY_FEATURES, $features, $this->configuration->getTtl());
    }

    /**
     * @throws JsonException
     *
     * @return array<Feature>
     */
    private function parseFeatures(string $rawBody): array
    {
        $features = [];
        $body = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        foreach ($body['features'] as $feature) {
            $strategies = [];
            foreach ($feature['strategies'] as $strategy) {
                $strategies[] = new DefaultStrategy($strategy['name'], $strategy['parameters'] ?? []);
            }
            $features[$feature['name']] = new DefaultFeature($feature['name'], $feature['enabled'], $strategies);
        }

        return $features;
    }
}

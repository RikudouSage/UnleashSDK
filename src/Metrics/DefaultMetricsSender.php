<?php

namespace Rikudou\Unleash\Metrics;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Rikudou\Unleash\Configuration\UnleashConfiguration;
use Rikudou\Unleash\Helper\StringStream;

final class DefaultMetricsSender implements MetricsSender
{
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
    /**
     * @var mixed[]
     */
    private $headers = [];
    /**
     * @param array<string,string> $headers
     */
    public function __construct(ClientInterface $httpClient, RequestFactoryInterface $requestFactory, UnleashConfiguration $configuration, array $headers = [])
    {
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->configuration = $configuration;
        $this->headers = $headers;
    }
    public function sendMetrics(MetricsBucket $bucket): void
    {
        if (!$this->configuration->isMetricsEnabled()) {
            return;
        }

        $request = $this->requestFactory
            ->createRequest('POST', $this->configuration->getUrl() . 'client/metrics')
            ->withHeader('Content-Type', 'application/json')
            ->withBody(new StringStream(json_encode([
                'appName' => $this->configuration->getAppName(),
                'instanceId' => $this->configuration->getInstanceId(),
                'bucket' => $bucket->jsonSerialize(),
            ], JSON_THROW_ON_ERROR)));
        foreach ($this->headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        $this->httpClient->sendRequest($request);
    }
}

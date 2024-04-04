<?php

namespace Efabrica\HttpClient\Retry;

use Efabrica\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Response\AsyncContext;
use Symfony\Component\HttpClient\Retry\RetryStrategyInterface;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * @internal
 */
class CustomRetryStrategy implements ClientRetryStrategy, RetryStrategyInterface
{
    public function __construct(private readonly RetryStrategyInterface $strategy, private readonly int $maxRetries = 3)
    {
    }

    public function addDecorator(HttpClient $client, array|string|null $baseUrl = null): void
    {
        $retryClient = new RetryableHttpClient($client->getClient(), $this, $this->maxRetries, $client->getLogger());
        if (is_array($baseUrl)) {
            $retryClient = $retryClient->withOptions(['base_uri' => $baseUrl]);
        }
        $client->setClient($retryClient);
    }

    public function shouldRetry(
        AsyncContext $context,
        ?string $responseContent,
        ?TransportExceptionInterface $exception
    ): ?bool {
        return $this->strategy->shouldRetry($context, $responseContent, $exception);
    }

    public function getDelay(
        AsyncContext $context,
        ?string $responseContent,
        ?TransportExceptionInterface $exception
    ): int {
        return $this->strategy->getDelay($context, $responseContent, $exception);
    }
}

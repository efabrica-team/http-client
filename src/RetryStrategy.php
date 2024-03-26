<?php

namespace Efabrica\HttpClient;

use Symfony\Component\HttpClient\Response\AsyncContext;
use Symfony\Component\HttpClient\Retry\GenericRetryStrategy;
use Symfony\Component\HttpClient\Retry\RetryStrategyInterface;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class RetryStrategy implements RetryStrategyInterface
{
    private RetryStrategyInterface $strategy;

    /**
     * @param int $maxRetries The maximum number of times to retry
     * @param array $statusCodes List of HTTP status codes that trigger a retry
     * @param int $delayMs Amount of time to delay (or the initial value when multiplier is used)
     * @param float $multiplier Multiplier to apply to the delay each time a retry occurs
     * @param int $maxDelayMs Maximum delay to allow (0 means no maximum)
     * @param float $jitter Probability of randomness int delay (0 = none, 1 = 100% random)
     */
    public function __construct(
        private readonly int $maxRetries = 3,
        array $statusCodes = GenericRetryStrategy::DEFAULT_RETRY_STATUS_CODES,
        int $delayMs = 1000,
        float $multiplier = 2.0,
        int $maxDelayMs = 0,
        float $jitter = 0.1
    ) {
        $this->strategy = new GenericRetryStrategy(
            $statusCodes, $delayMs, $multiplier, $maxDelayMs, $jitter
        );
    }

    public static function custom(RetryStrategyInterface $strategy): self {
        $instance = new self();
        $instance->strategy = $strategy;
        return $instance;
    }

    public static function none(): self
    {
        return new self(0);
    }

    /**
     * @internal
     */
    public function addDecorator(HttpClient $client, array|string|null $baseUrl = null): void
    {
        $retryClient = new RetryableHttpClient($client->getClient(), $this, $this->maxRetries, $client->getLogger());
        if (is_array($baseUrl)) {
            $retryClient = $retryClient->withOptions(['base_uri' => $baseUrl]);
        }
        $client->setClient($retryClient);
    }

    /**
     * @internal
     */
    public function shouldRetry(
        AsyncContext $context,
        ?string $responseContent,
        ?TransportExceptionInterface $exception
    ): ?bool {
        return $this->strategy->shouldRetry($context, $responseContent, $exception);
    }

    /**
     * @internal
     */
    public function getDelay(
        AsyncContext $context,
        ?string $responseContent,
        ?TransportExceptionInterface $exception
    ): int {
        return $this->strategy->getDelay($context, $responseContent, $exception);
    }

}

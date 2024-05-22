<?php
declare(strict_types=1);

namespace Efabrica\HttpClient\Retry;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\DecoratorTrait;
use Symfony\Component\HttpClient\Retry\RetryStrategyInterface;
use Symfony\Component\HttpClient\RetryableHttpClient as SfRetryableHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Service\ResetInterface;

final class RetryableHttpClient implements HttpClientInterface, ResetInterface, LoggerAwareInterface
{
    use DecoratorTrait;

    private HttpClientInterface $rawClient;

    public function __construct(
        private HttpClientInterface $client,
        private readonly ?RetryStrategyInterface $strategy = null,
        private readonly int $maxRetries = 0,
        private ?LoggerInterface $logger = null,
        array|string|null $baseUri = null
    ) {
        $this->rawClient = $client;

        if ($strategy !== null || is_array($baseUri) || $maxRetries > 0) {
            $this->client = new SfRetryableHttpClient($client, $strategy, $maxRetries, $logger);

            if (is_array($baseUri)) {
                $this->client = $this->client->withOptions(['base_uri' => $baseUri]);
            }
        }
    }

    public function withOptions(array $options): static
    {
        $strategy = $options['retry_strategy'] ?? $this->strategy;
        unset($options['retry_strategy']);

        $maxRetries = $options['max_retries'] ?? $this->maxRetries;
        unset($options['max_retries']);

        $baseUri = $options['base_uri'] ?? null;
        if (is_array($baseUri)) {
            unset($options['base_uri']);
        }

        $newClient = $this->rawClient->withOptions($options);
        return new self($newClient, $strategy, $maxRetries, $this->logger, $baseUri);
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
        if ($this->client instanceof LoggerAwareInterface) {
            $this->client->setLogger($logger);
        }
    }
}

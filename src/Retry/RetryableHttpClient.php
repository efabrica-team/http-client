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

/**
 * This client wraps the RetryableHttpClient from Symfony and adds the ability to change the retry strategy with options.
 */
final class RetryableHttpClient implements HttpClientInterface, ResetInterface, LoggerAwareInterface
{
    use DecoratorTrait;

    private HttpClientInterface $rawClient;

    /**
     * @param string|array<string[]|string>|null $baseUri
     *       - If a single URI is provided:
     *          It represents the base URI for resolving relative URLs.
     *       - If an array of URIs is provided:
     *          Each URI in the array represents a base URI. The client will try these URIs in order,
     *          using the next URI if the previous one fails.
     *       - If a nested array is provided:
     *          Each inner array represents a set of base URIs to choose from for retries.
     *          The client will choose a random URI from each inner array for each retry attempt,
     *          allowing for a randomized approach to handling retries and load distribution among nodes.
     *       You can combine nesting and non-nesting to create a mix of these strategies.
     */
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

    /**
     * @param array<string,mixed> $options
     */
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

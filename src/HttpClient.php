<?php

namespace Efabrica\HttpClient;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient as SymfonyHttpClient;
use Symfony\Component\HttpClient\TraceableHttpClient;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

class HttpClient implements HttpClientInterface, LoggerAwareInterface
{
    private HttpClientInterface $client;

    private TraceableHttpClient $traceClient;

    private Stopwatch $stopwatch;

    public function __construct(HttpClientInterface $client)
    {
        $this->stopwatch = new Stopwatch(true);
        $this->client = $this->traceClient = new TraceableHttpClient($client, $this->stopwatch);
    }

    public static function create(HttpOptions $options, int $maxHostConnections = 6, int $maxPendingPushes = 50): self
    {
        return new self(SymfonyHttpClient::create($options->toArray(), $maxHostConnections, $maxPendingPushes));
    }

    public function request(string $method, string $url, HttpOptions | array $options = []): ResponseInterface
    {
        return $this->client->request($method, $url, $options instanceof HttpOptions ? $options->toArray() : $options);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function get(string $url, array $urlQuery = [], array $headers = [], ?float $maxDuration = null): ResponseInterface
    {
        return $this->request('GET', $url, new HttpOptions(urlQuery: $urlQuery, headers: $headers));
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function post(
        string $url,
        array $urlQuery = [],
        array $json = [],
        array $formData = [],
        array $headers = [],
        ?float $maxDuration = null,
        array $userData = []
    ): ResponseInterface {
        return $this->request('POST', $url,
            new HttpOptions($urlQuery, $json, $formData, $headers, maxDuration: $maxDuration, userData: $userData)
        );
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function put(
        string $url,
        array $urlQuery = [],
        array $json = [],
        array $formData = [],
        array $headers = [],
        ?float $maxDuration = null,
        array $userData = []
    ): ResponseInterface {
        return $this->request('PUT', $url,
            new HttpOptions($urlQuery, $json, $formData, $headers, maxDuration: $maxDuration, userData: $userData)
        );
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function patch(
        string $url,
        array $urlQuery = [],
        array $json = [],
        array $formData = [],
        array $headers = [],
        ?float $maxDuration = null,
        array $userData = []
    ): ResponseInterface {
        return $this->request('PATCH', $url,
            new HttpOptions($urlQuery, $json, $formData, $headers, maxDuration: $maxDuration, userData: $userData)
        );
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function delete(
        string $url,
        array $urlQuery = [],
        array $json = [],
        array $formData = [],
        array $headers = [],
        ?float $maxDuration = null,
        array $userData = []
    ): ResponseInterface {
        return $this->request('DELETE', $url,
            new HttpOptions($urlQuery, $json, $formData, $headers, maxDuration: $maxDuration, userData: $userData)
        );
    }

    public function stream(iterable | ResponseInterface $responses, ?float $timeout = null): ResponseStreamInterface
    {
        return $this->client->stream($responses, $timeout);
    }

    public function withOptions(array | HttpOptions $options): static
    {
        $new = clone $this;
        $new->client = $this->client->withOptions($options instanceof HttpOptions ? $options->toArray() : $options);
        return $new;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->traceClient->setLogger($logger);
    }

    public function getStopwatch(): Stopwatch
    {
        return $this->stopwatch;
    }

    /**
     * You can use this to add more decorators, combined with getClient()
     */
    public function setClient(HttpClientInterface $client): self
    {
        $this->client = $client;
        return $this;
    }

    public function getClient(): HttpClientInterface
    {
        return $this->client;
    }
}

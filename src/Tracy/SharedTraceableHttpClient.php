<?php

namespace Efabrica\HttpClient\Tracy;

use stdClass;
use Symfony\Component\HttpClient\TraceableHttpClient;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;
use Tracy\Debugger;

/**
 * This client accumulates all requests made by it into a static variable that can be later used for debugging.
 */
final class SharedTraceableHttpClient implements HttpClientInterface
{
    /**
     * @var TracedRequest[]
     */
    private static array $requests = [];

    private ?TraceableHttpClient $streamClient = null;

    public static ?bool $defaultBuffer = null;

    public function __construct(private HttpClientInterface $client)
    {
        self::$defaultBuffer ??= class_exists(Debugger::class) && Debugger::isEnabled();
    }

    /**
     * @param array<string, mixed> $options
     * @see HttpClientInterface::OPTIONS_DEFAULTS for options
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        if (false === ($options['extra']['trace'] ?? true)) {
            return $this->client->request($method, $url, $options);
        }
        if (($options['extra']['trace_content'] ?? self::$defaultBuffer) === true) {
            $options['buffer'] = true;
        }

        $stopwatch = new Stopwatch();
        $client = new TraceableHttpClient($this->client, $stopwatch);

        $ref = new stdClass();
        $options['extra']['__ref'] = $ref;
        $response = $client->request($method, $url, $options);
        $ref->response = $response; // avoid early destruction of the response

        self::$requests[] = new TracedRequest($client, $stopwatch, $response);

        return $response;
    }

    public function stream(iterable|ResponseInterface $responses, float $timeout = null): ResponseStreamInterface
    {
        if ($this->client instanceof TraceableHttpClient) {
            return $this->client->stream($responses, $timeout);
        }

        $this->streamClient ??= new TraceableHttpClient($this->client);
        return $this->streamClient->stream($responses, $timeout);
    }

    /**
     * @param array<string, mixed> $options
     * @see HttpClientInterface::OPTIONS_DEFAULTS for options
     */
    public function withOptions(array $options): static
    {
        $clone = clone $this;
        $clone->client = $this->client->withOptions($options);
        $clone->streamClient = null;

        return $clone;
    }

    /**
     * @return TracedRequest[]
     */
    public static function getTracedRequests(): array
    {
        return self::$requests;
    }
}

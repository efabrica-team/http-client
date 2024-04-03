<?php

namespace Efabrica\HttpClient\Tracy;

use Generator;
use stdClass;
use Symfony\Component\HttpClient\TraceableHttpClient;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

class SharedTraceableHttpClient implements HttpClientInterface
{
    /**
     * @var Stopwatch[]
     */
    private static array $stopwatches = [];

    /**
     * @var TraceableHttpClient[]
     */
    public static array $clients = [];

    private ?TraceableHttpClient $streamClient = null;

    public function __construct(private HttpClientInterface $client)
    {
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        if (false === ($options['extra']['trace'] ?? true)) {
            return $this->client->request($method, $url, $options);
        }

        self::$stopwatches[] = $stopwatch = new Stopwatch();
        self::$clients[] = $client = new TraceableHttpClient($this->client, $stopwatch);

        $ref = new stdClass();
        $options['extra']['__ref'] = $ref;
        $response = $client->request($method, $url, $options);
        $ref->response = $response; // avoid early destruction of the response

        return $response;
    }

    public function stream(iterable|ResponseInterface $responses, float $timeout = null): ResponseStreamInterface
    {
        $this->streamClient ??= new TraceableHttpClient($this->client);
        return $this->streamClient->stream($responses, $timeout);
    }

    public function withOptions(array $options): static
    {
        $clone = clone $this;
        $clone->client = $this->client->withOptions($options);
        $clone->streamClient = null;

        return $clone;
    }

    /**
     * @return Generator<TracedRequest>
     */
    public static function getTracedRequests(): Generator
    {
        foreach (self::$clients as $client) {
            foreach ($client->getTracedRequests() as $request) {
                yield new TracedRequest($request, $client);
            }
        }
    }

    /**
     * @return Generator<StopwatchEvent>
     */
    public static function getEvents(): Generator
    {
        foreach (self::$stopwatches as $sw) {
            foreach ($sw->getSections() as $section) {
                foreach ($section->getEvents() as $event) {
                    yield $event;
                }
            }
        }
    }
}

<?php

namespace Efabrica\HttpClient\Tracy;

use Symfony\Component\HttpClient\TraceableHttpClient;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Throwable;

/**
 * This class represents a single request made by the SharedTraceableHttpClient.
 */
final class TracedRequest
{
    public function __construct(
        private readonly TraceableHttpClient $client,
        private readonly Stopwatch $stopwatch,
        private readonly ResponseInterface $response
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return current($this->client->getTracedRequests());
    }

    public function getMethod(): string
    {
        return $this->getData()['method'];
    }

    public function getUrl(): string
    {
        $url = $this->getData()['url'];
        if ($this->getOptions()['base_uri'] ?? false) {
            $url = rtrim($this->getOptions()['base_uri'], '/') . '/' . ltrim($url, '/');
        }
        return $url;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->getData()['options'];
    }

    /**
     * @return array<string, mixed>
     */
    public function getInfo(): array
    {
        return $this->getData()['info'];
    }

    public function getContent(): mixed
    {
        $content = $this->getData()['content'] ?? null;
        try {
            if ($this->getHeaders() === []) {
                return $content;
            }
            $content ??= trim($this->response->getContent(false));
            if (is_string($content) && ($content[0] === '{' || $content[0] === '[')) {
                $content = json_decode($content, true) ?: $content;
            }
        } catch (Throwable $e) {
            if ($e instanceof TransportExceptionInterface && str_contains($e->getMessage(), 'buffering is disabled')) {
                return null;
            }
            $content = get_class($e) . ': ' . $e->getMessage() . "\n\n" . $e->getTraceAsString();
        }
        return $content;
    }

    public function getClient(): HttpClientInterface
    {
        return $this->client;
    }

    public function getStatus(): string
    {
        return current($this->getHeaders()) ?: 'Headers not received';
    }

    public function getEvent(): ?StopwatchEvent
    {
        foreach ($this->stopwatch->getSections() as $section) {
            foreach ($section->getEvents() as $event) {
                return $event;
            }
        }
        return null;
    }

    public function getHeaders(): mixed
    {
        $headers = $this->getData()['info']['response_headers'] ?? [];
        return is_array($headers) ? $headers : [];
    }
}

<?php

namespace Efabrica\HttpClient\Tracy;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class TracedRequest
{
    public function __construct(private readonly array $data, private readonly HttpClientInterface $client)
    {
    }

    public function getMethod(): string
    {
        return $this->data['method'];
    }

    public function getUrl(): string
    {
        $url = $this->data['url'];
        if ($this->getOptions()['base_uri'] ?? false) {
            $url = rtrim($this->getOptions()['base_uri'], '/') . '/' . ltrim($url, '/');
        }
        return $url;
    }

    public function getOptions(): array
    {
        return $this->data['options'];
    }

    public function getInfo(): array
    {
        return $this->data['info'];
    }

    public function getContent(): mixed
    {
        return $this->data['content'] ?? null;
    }

    public function getClient(): HttpClientInterface
    {
        return $this->client;
    }

    public function getTotalTime(): string
    {
        $time = $this->data['info']['total_time'] ?? null;
        if ($time === null) {
            return 'N/A';
        }
        return round($time * 1000) . ' ms';
    }

    public function getStatus(): ?string
    {
        return current($this->data['info']['response_headers'] ?? []) ?: 'Failed';
    }
}

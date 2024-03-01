<?php

namespace Efabrica\HttpClient\Cache;

use Nette\Caching\Cache;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

class CachedHttpClient implements HttpClientInterface
{
    private Cache $cache;

    public function __construct(Cache $cache, private readonly HttpClientInterface $client)
    {
        $this->cache = $cache->derive('http-client');
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        if (!empty($options['body'])) {
            return $this->client->request($method, $url, $options);
        }
        $cache = $this->cache->derive(md5("$method $url " . serialize($options)));
        $response = $cache->load(CachedHttpResponse::CACHE_KEY);
        return $response ?? new CachedHttpResponse($cache, $this->client->request($method, $url, $options));
    }

    public function stream(iterable | ResponseInterface $responses, float $timeout = null): ResponseStreamInterface
    {
        return $this->client->stream($responses, $timeout);
    }

    public function withOptions(array $options): static
    {
        return new static($this->cache, $this->client->withOptions($options));
    }
}

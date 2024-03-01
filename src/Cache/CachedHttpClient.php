<?php

namespace Efabrica\HttpClient\Cache;

use Nette\Caching\Cache;
use Symfony\Component\HttpClient\DecoratorTrait;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Asynchronously caches responses from an HTTP client into a Nette cache storage.
 */
class CachedHttpClient implements HttpClientInterface, ResetInterface
{
    use DecoratorTrait;

    private Cache $cache;

    public function __construct(Cache $cache, private HttpClientInterface $client)
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
}

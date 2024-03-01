<?php

namespace Efabrica\HttpClient\Cache;

use Nette\Caching\Cache;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CachedHttpResponse implements ResponseInterface
{
    public const CACHE_KEY = 'response';

    private bool $saved = false;

    public function __construct(
        private readonly Cache $cache,
        private ResponseInterface $response
    ) {
    }

    private function getResponse(): ResponseInterface
    {
        if (!$this->saved) {
            $this->response = new MockResponse($this->response->getContent(), $this->response->getInfo());
            $this->cache->save(self::CACHE_KEY, $this->response);
            $this->saved = true;
        }
        return $this->response;
    }

    public function getStatusCode(): int
    {
        return $this->getResponse()->getStatusCode();
    }

    public function getHeaders(bool $throw = true): array
    {
        return $this->getResponse()->getHeaders($throw);
    }

    public function getContent(bool $throw = true): string
    {
        return $this->getResponse()->getContent($throw);
    }

    public function toArray(bool $throw = true): array
    {
        return $this->getResponse()->toArray($throw);
    }

    public function cancel(): void
    {
        $this->response->cancel();
    }

    public function getInfo(string $type = null): mixed
    {
        return $this->getResponse()->getInfo($type);
    }
}

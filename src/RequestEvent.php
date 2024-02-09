<?php

namespace Efabrica\HttpClient;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class RequestEvent
{
    public function __construct(
        private readonly HttpClientInterface $client,
        /**
         * @var HttpClientMiddleware[]
         */
        private array $middlewares,
        private readonly HttpClientRequest $request
    ) {
    }

    public function getRequest(): HttpClientRequest
    {
        return $this->request;
    }

    public function handle(): ResponseInterface
    {
        if ($middleware = current($this->middlewares)) {
            next($this->middlewares);
            return $middleware->handle($this);
        }
        $req = $this->request;
        return $this->client->request($req->method, $req->url, $req->toOptionsArray());
    }
}

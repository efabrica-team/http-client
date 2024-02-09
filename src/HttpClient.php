<?php

namespace Efabrica\HttpClient;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class HttpClient
{
    private HttpClientInterface $client;

    private array $middlewares = [];

    public function __construct(
        ?string $baseUrl = null,
        ?string $bearerToken = null,
        array $headers = [],
        float $timeout = null,
        float $maxDuration = null
    ) {
        $options = new HttpClientRequest(
            headers: $headers, timeout: $timeout, maxDuration: $maxDuration, bearerToken: $bearerToken, baseUrl: $baseUrl
        );
        $this->client = \Symfony\Component\HttpClient\HttpClient::create($options->toOptionsArray());
    }

    public function addMiddleware(HttpClientMiddleware $middleware, int $priority = 0): self
    {
        while (isset($this->middlewares[$priority])) {
            $priority++;
        }
        $this->middlewares[$priority] = $middleware;
        return $this;
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function request(HttpClientRequest $request): ResponseInterface
    {
        return (new RequestEvent($this->client, $this->middlewares, $request))->handle();
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function get(string $url, array $urlQuery = [], array $headers = []): ResponseInterface
    {
        return $this->request(new HttpClientRequest('GET', $url, urlQuery: $urlQuery, headers: $headers));
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function post(string $url, array $json = [], array $headers = []): ResponseInterface
    {
        return $this->request(new HttpClientRequest('POST', $url, jsonBody: $json, headers: $headers));
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function postForm(string $url, array $form = [], array $headers = []): ResponseInterface
    {
        return $this->request(new HttpClientRequest('POST', $url, formBody: $form, headers: $headers));
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function put(string $url, array $json = [], array $headers = []): ResponseInterface
    {
        return $this->request(new HttpClientRequest('PUT', $url, jsonBody: $json, headers: $headers));
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function patch(string $url, array $json = [], array $headers = []): ResponseInterface
    {
        return $this->request(new HttpClientRequest('PATCH', $url, jsonBody: $json, headers: $headers));
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function delete(string $url, array $json = [], array $headers = []): ResponseInterface
    {
        return $this->request(new HttpClientRequest('DELETE', $url, jsonBody: $json, headers: $headers));
    }
}

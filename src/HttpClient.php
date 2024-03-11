<?php

namespace Efabrica\HttpClient;

use Closure;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient as SymfonyHttpClient;
use Symfony\Component\HttpClient\ScopingHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;
use Symfony\Contracts\Service\ResetInterface;
use Traversable;

final class HttpClient implements ResetInterface
{
    private HttpClientInterface $client;

    /**
     * @param string|null $baseUrl The URI to resolve relative URLs, following rules in RFC 3986, section 2.
     *
     * @param string|null $bearerToken A token enabling HTTP Bearer authorization (RFC 6750).
     *
     * @param float|null $timeout The idle timeout (in seconds), defaults to ini_get('default_socket_timeout').
     *
     * @param float|null $maxDuration The maximum execution time (in seconds) for the request+response as a whole;
     *                                a value lower than or equal to 0 means it is unlimited.
     *
     * @param iterable|null $headers Headers names provided as keys or as part of values.
     *
     * @param array|string|null $basicAuth An array containing the username as the first value and optionally the
     *                                     password as the second one, or a string like username:password enabling
     *                                     HTTP Basic authentication (RFC 7617).
     *
     * @param int|null $maxRedirects The maximum number of redirects to follow; a value lower than or equal to means
     *                               redirects should not be followed.
     *
     *
     * @param ?Closure(int $dlNow, int $dlSize, array $info): mixed $onProgress
     *              A callable that MUST be called on DNS resolution,
     *              on arrival of headers, on completion, and SHOULD be called on upload/download
     *              of data and at least 1/s. Throwing any exceptions MUST abort the request.
     *
     * @param array|null $extra Additional options that can be ignored if unsupported, unlike regular options
     *
     * @param int $maxHostConnections The maximum number of connections to a single host
     *
     * @param int $maxPendingPushes The maximum number of pushed responses to accept in the queue
     *
     * @param bool $scoped Whether the auth options should be scoped to the base URI (Pre-caution to avoid leaking credentials)
     *
     * @param LoggerInterface|null $logger A PSR-3 logger
     */

    public function __construct(
        ?string $baseUrl = null,
        ?string $bearerToken = null,
        ?float $timeout = null,
        ?float $maxDuration = null,
        ?iterable $headers = null,
        array | string | null $basicAuth = null,
        ?int $maxRedirects = null,
        Closure | null $onProgress = null,
        ?array $extra = null,
        ?HttpAdvancedOptions $advanced = null,
        OptionScope $scope = OptionScope::SCOPE_HEADERS,
        ?HttpClientInterface $client = null,
        int $maxHostConnections = 6,
        int $maxPendingPushes = 50,
        private readonly ?LoggerInterface $logger = null
    ) {
        $options = [
            'base_uri' => $baseUrl,
            'bearer_token' => $bearerToken,
            'timeout' => $timeout,
            'max_duration' => $maxDuration,
            'headers' => $headers,
            'auth_basic' => $basicAuth,
            'max_redirects' => $maxRedirects,
            'on_progress' => $onProgress,
            'extra' => $extra,
        ];
        if ($advanced !== null) {
            $options += $advanced->toArray();
        }
        $options = array_filter($options, static fn($v) => $v !== null);

        $scopedOptions = $scope->filter($options);
        $options = array_diff_key($options, $scopedOptions);

        if ($client !== null) {
            $this->client = $client->withOptions($options);
        } else {
            $this->client = SymfonyHttpClient::create($options, $maxHostConnections, $maxPendingPushes);
        }

        if ($baseUrl !== null && $scopedOptions !== []) {
            $this->client = ScopingHttpClient::forBaseUri($this->client, $baseUrl, $scopedOptions);
        }

        if ($logger !== null && $this->client instanceof LoggerAwareInterface) {
            $this->client->setLogger($logger);
        }
    }

    /**
     * Sends an HTTP request to the specified URL using the given method and options.
     *
     * @param string $method The HTTP method to use for the request (e.g., 'GET', 'POST').
     * @param string $url The URL to which the request should be sent.
     * @param array|null $query An associative array of query string values to merge with the request's URL.
     * @param array|null $json If set, the request body will be JSON-encoded, and the "content-type" header will be set to "application/json".
     * @param iterable|string|resource|Traversable|Closure $body The request body. array is treated as FormData.
     * @param iterable|null $headers Headers names provided as keys or as part of values.
     * @param float|null $timeout The idle timeout (in seconds) for the request.
     * @param float|null $maxDuration The maximum execution time (in seconds) for the request+response as a whole.
     * @param mixed $userData Any extra data to attach to the request that will be available via $response->getInfo('user_data').
     * @param Closure|null $onProgress A callable function to track the progress of the request.
     * @param array|null $extra Additional options for the request
     *
     * @return ResponseInterface Asynchronous response that doesn't block until its methods are called.
     * Exceptions are thrown when the response is read.
     */
    public function request(
        string $method,
        string $url,
        ?array $query = null,
        ?array $json = null,
        mixed $body = null,
        ?iterable $headers = null,
        ?float $timeout = null,
        ?float $maxDuration = null,
        mixed $userData = null,
        ?Closure $onProgress = null,
        ?array $extra = null,
    ): ResponseInterface {
        $options = array_filter([
            'query' => $query,
            'json' => $json,
            'body' => $body,
            'headers' => $headers,
            'timeout' => $timeout,
            'max_duration' => $maxDuration,
            'user_data' => $userData,
            'on_progress' => $onProgress,
            'extra' => $extra,
        ], static fn($v) => $v !== null);

        return $this->client->request($method, $url, $options);
    }

    /**
     * @param string $url The URL to which the request should be sent.
     * @param array|null $query An associative array of query string values to merge with the request's URL.
     * @param array|null $json If set, the request body will be JSON-encoded, and the "content-type" header will be set to "application/json".
     * @param iterable|string|resource|Traversable|Closure $body The request body. array is treated as FormData.
     * @param iterable|null $headers Headers names provided as keys or as part of values.
     * @param float|null $timeout The idle timeout (in seconds) for the request.
     * @param float|null $maxDuration The maximum execution time (in seconds) for the request+response as a whole.
     * @param mixed $userData Any extra data to attach to the request that will be available via $response->getInfo('user_data').
     * @param Closure|null $onProgress A callable function to track the progress of the request.
     * @param array|null $extra Additional options for the request
     */
    public function get(
        string $url,
        ?array $query = null,
        ?array $json = null,
        mixed $body = null,
        ?iterable $headers = null,
        ?float $timeout = null,
        ?float $maxDuration = null,
        mixed $userData = null,
        ?Closure $onProgress = null,
        ?array $extra = null,
    ): ResponseInterface {
        return $this->request('GET', $url, $query, $json, $body, $headers, $timeout, $maxDuration, $userData, $onProgress, $extra);
    }

    /**
     * @param string $url The URL to which the request should be sent.
     * @param array|null $query An associative array of query string values to merge with the request's URL.
     * @param array|null $json If set, the request body will be JSON-encoded, and the "content-type" header will be set to "application/json".
     * @param iterable|string|resource|Traversable|Closure $body The request body. array is treated as FormData.
     * @param iterable|null $headers Headers names provided as keys or as part of values.
     * @param float|null $timeout The idle timeout (in seconds) for the request.
     * @param float|null $maxDuration The maximum execution time (in seconds) for the request+response as a whole.
     * @param mixed $userData Any extra data to attach to the request that will be available via $response->getInfo('user_data').
     * @param Closure|null $onProgress A callable function to track the progress of the request.
     * @param array|null $extra Additional options for the request
     */
    public function post(
        string $url,
        ?array $query = null,
        ?array $json = null,
        mixed $body = null,
        ?iterable $headers = null,
        ?float $timeout = null,
        ?float $maxDuration = null,
        mixed $userData = null,
        ?Closure $onProgress = null,
        ?array $extra = null,
    ): ResponseInterface {
        return $this->request('POST', $url, $query, $json, $body, $headers, $timeout, $maxDuration, $userData, $onProgress, $extra);
    }

    /**
     * @param string $url The URL to which the request should be sent.
     * @param array|null $query An associative array of query string values to merge with the request's URL.
     * @param array|null $json If set, the request body will be JSON-encoded, and the "content-type" header will be set to "application/json".
     * @param iterable|string|resource|Traversable|Closure $body The request body. array is treated as FormData.
     * @param iterable|null $headers Headers names provided as keys or as part of values.
     * @param float|null $timeout The idle timeout (in seconds) for the request.
     * @param float|null $maxDuration The maximum execution time (in seconds) for the request+response as a whole.
     * @param mixed $userData Any extra data to attach to the request that will be available via $response->getInfo('user_data').
     * @param Closure|null $onProgress A callable function to track the progress of the request.
     * @param array|null $extra Additional options for the request
     */
    public function put(
        string $url,
        ?array $query = null,
        ?array $json = null,
        mixed $body = null,
        ?iterable $headers = null,
        ?float $timeout = null,
        ?float $maxDuration = null,
        mixed $userData = null,
        ?Closure $onProgress = null,
        ?array $extra = null,
    ): ResponseInterface {
        return $this->request('PUT', $url, $query, $json, $body, $headers, $timeout, $maxDuration, $userData, $onProgress, $extra);
    }

    /**
     * @param string $url The URL to which the request should be sent.
     * @param array|null $query An associative array of query string values to merge with the request's URL.
     * @param array|null $json If set, the request body will be JSON-encoded, and the "content-type" header will be set to "application/json".
     * @param iterable|string|resource|Traversable|Closure $body The request body. array is treated as FormData.
     * @param iterable|null $headers Headers names provided as keys or as part of values.
     * @param float|null $timeout The idle timeout (in seconds) for the request.
     * @param float|null $maxDuration The maximum execution time (in seconds) for the request+response as a whole.
     * @param mixed $userData Any extra data to attach to the request that will be available via $response->getInfo('user_data').
     * @param Closure|null $onProgress A callable function to track the progress of the request.
     * @param array|null $extra Additional options for the request
     */
    public function patch(
        string $url,
        ?array $query = null,
        ?array $json = null,
        mixed $body = null,
        ?iterable $headers = null,
        ?float $timeout = null,
        ?float $maxDuration = null,
        mixed $userData = null,
        ?Closure $onProgress = null,
        ?array $extra = null,
    ): ResponseInterface {
        return $this->request('PATCH', $url, $query, $json, $body, $headers, $timeout, $maxDuration, $userData, $onProgress, $extra);
    }

    /**
     * @param string $url The URL to which the request should be sent.
     * @param array|null $query An associative array of query string values to merge with the request's URL.
     * @param array|null $json If set, the request body will be JSON-encoded, and the "content-type" header will be set to "application/json".
     * @param iterable|string|resource|Traversable|Closure $body The request body. array is treated as FormData.
     * @param iterable|null $headers Headers names provided as keys or as part of values.
     * @param float|null $timeout The idle timeout (in seconds) for the request.
     * @param float|null $maxDuration The maximum execution time (in seconds) for the request+response as a whole.
     * @param mixed $userData Any extra data to attach to the request that will be available via $response->getInfo('user_data').
     * @param Closure|null $onProgress A callable function to track the progress of the request.
     * @param array|null $extra Additional options for the request
     */
    public function delete(
        string $url,
        ?array $query = null,
        ?array $json = null,
        mixed $body = null,
        ?iterable $headers = null,
        ?float $timeout = null,
        ?float $maxDuration = null,
        mixed $userData = null,
        ?Closure $onProgress = null,
        ?array $extra = null,
    ): ResponseInterface {
        return $this->request('DELETE', $url, $query, $json, $body, $headers, $timeout, $maxDuration, $userData, $onProgress, $extra);
    }

    /**
     * Yields responses chunk by chunk as they complete.
     *
     * @param ResponseInterface|iterable<array-key, ResponseInterface> $responses One or more responses created by the current HTTP client
     * @param float|null $timeout The idle timeout before yielding timeout chunks
     */
    public function stream(ResponseInterface | iterable $responses, ?float $timeout = null): ResponseStreamInterface
    {
        return $this->client->stream($responses, $timeout);
    }

    /**
     * You can use this to add more decorators, combined with getClient()
     *
     * @example $client->addDecorator(new RetryableHttpClient($client->getClient(), 3))
     */
    public function addDecorator(HttpClientInterface $decorator): self
    {
        $this->client = $decorator;
        return $this;
    }

    /**
     * You can use this to create a new instance with an additional decorator, combined with getClient()
     *
     * @example $new = $client->withDecorator(new RetryableHttpClient($client->getClient(), 3))
     */
    public function withDecorator(HttpClientInterface $decorator): self
    {
        return (clone $this)->addDecorator($decorator);
    }

    /**
     * @return HttpClientInterface The inner client, possibly decorated
     */
    public function getClient(): HttpClientInterface
    {
        return $this->client;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @see self::create() for the list of available options
     * Only the non-null options from AdvancedOptions will be merged.
     */
    public function withOptions(
        ?string $baseUrl = null,
        ?string $bearerToken = null,
        ?float $timeout = null,
        ?float $maxDuration = null,
        ?iterable $headers = null,
        array | string | null $basicAuth = null,
        ?int $maxRedirects = null,
        Closure | null $onProgress = null,
        ?array $extra = null,
        ?HttpAdvancedOptions $advanced = null
    ): self {
        $new = clone $this;
        $new->client = $new->client->withOptions(array_filter([
                'base_uri' => $baseUrl,
                'bearer_token' => $bearerToken,
                'timeout' => $timeout,
                'max_duration' => $maxDuration,
                'headers' => $headers,
                'auth_basic' => $basicAuth,
                'max_redirects' => $maxRedirects,
                'on_progress' => $onProgress,
                'extra' => $extra,
            ] + ($advanced?->toArray() ?? []),
            static fn($v) => $v !== null));
        return $new;
    }

    public function __clone(): void
    {
        $this->client = clone $this->client;
    }

    public function reset(): void
    {
        if ($this->client instanceof ResetInterface) {
            $this->client->reset();
        }
    }
}

<?php

namespace Efabrica\HttpClient;

use Closure;
use Efabrica\HttpClient\Retry\RetryableHttpClient;
use Efabrica\HttpClient\Tracy\SharedTraceableHttpClient;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Riki137\AmpClient\AmpHttpClientV5;
use Symfony\Component\HttpClient\HttpClient as SymfonyHttpClient;
use Symfony\Component\HttpClient\Retry\RetryStrategyInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;
use Symfony\Contracts\Service\ResetInterface;
use Tracy\Debugger;
use Traversable;

final class HttpClient implements ResetInterface, LoggerAwareInterface
{
    private HttpClientInterface $client;

    /**
     * @param string|array<string|array<string>>|null $baseUri
     *      - If a single URI is provided:
     *         It represents the base URI for resolving relative URLs.
     *      - If an array of URIs is provided:
     *         Each URI in the array represents a base URI. The client will try these URIs in order,
     *         using the next URI if the previous one fails.
     *      - If a nested array is provided:
     *         Each inner array represents a set of base URIs to choose from for retries.
     *         The client will choose a random URI from each inner array for each retry attempt,
     *         allowing for a randomized approach to handling retries and load distribution among nodes.
     *      You can combine nesting and non-nesting to create a mix of these strategies.
     *
     * @param string|null $authBearer
     *      A token enabling HTTP Bearer authorization (RFC 6750).
     *
     * @param float|null $timeout
     *      The idle timeout (in seconds), defaults to ini_get('default_socket_timeout').
     *
     * @param float|null $maxDuration
     *      The maximum execution time (in seconds) for the request+response as a whole;
     *      a value lower than or equal to 0 means it is unlimited.
     *
     * @param iterable<int|string, string>|null $headers
     *      Headers names provided as keys or as part of values.
     *
     * @param array{0: string, 1?: string|null}|string|null $authBasic
     *      An array containing the username as the first value and optionally the
     *      password as the second one, or a string like username:password enabling
     *      HTTP Basic authentication (RFC 7617).
     *
     * @param int|null $maxRedirects
     *      The maximum number of redirects to follow; a value lower than or equal to
     *      means redirects should not be followed.
     *
     * @param ?Closure(int $dlNow, int $dlSize, array<string, mixed> $info): mixed $onProgress
     *      A callable that MUST be called on DNS resolution,
     *      on arrival of headers, on completion, and SHOULD be called on upload/download
     *      of data and at least 1/s. Throwing any exceptions MUST abort the request.
     *
     * @param array<string, mixed>|null $extra
     *      Additional options that can be ignored if unsupported, unlike regular options.
     *
     * @param string|null $httpVersion
     *      The HTTP version to use, defaults to the best supported version, typically 1.1 or 2.0.
     *
     * @param resource|bool|null|Closure(iterable<int|string, string> $headers): bool $buffer
     *      Whether the content of the response should be buffered or not, or a stream resource
     *      where the response body should be written, or a closure telling if/where
     *      the response should be buffered based on its headers.
     *
     * @param array<string, string>|null $resolve
     *      A map of host to IP address that should replace DNS resolution.
     *      Each key-value pair in the array represents a mapping where the key is the host
     *      to be resolved, and the value is the corresponding IP address to use instead of DNS.
     *      If not provided (null), the default DNS resolution behavior will be used.
     *      Example: ['example.com' => '203.0.113.1', 'api.example.org' => '198.51.100.42'].
     *
     * @param string|null $proxy
     *      The proxy server to be used for the outgoing connection.
     *      If not provided (null), the proxy settings specified by the environment variables
     *      handled by cURL will be honored by default.
     *      Example: "http://proxy.example.com:8080" or "socks5://proxy.example.com:1080".
     *
     * @param string|null $noProxy
     *      A comma-separated list of hosts that do not require a proxy to be reached.
     *
     * @param string|null $bindTo
     *      The network interface or local socket to bind the outgoing connection to.
     *      This option allows you to control the source IP address and port for the request.
     *      If specified, the request will be bound to the specified interface or local socket,
     *      influencing the network path and characteristics of the outgoing connection.
     *      The value should be in the form of "interface:port" or "local_socket:port".
     *      Example: "192.168.1.2:0" or "unix:///var/run/local_socket.sock".
     *
     * @param SSLContext|null $ssl
     *      SSL context options. If not provided (null), the default SSL context will be used.
     *      Only the non-null SSL context options will be set.
     *
     * @param RetryStrategyInterface|null $retry
     *     The retry strategy to use. If not provided and $maxRetries is greater than 0,
     *     a default Symfony's GenericRetryStrategy will be used.
     *
     * @param int $maxRetries
     *     The maximum number of retries to attempt. Defaults to 0.
     *     This is used in combination with the retry strategy.
     *
     * @param HttpClientInterface|null $client
     *      The inner client, possibly decorated. Defaults to Symfony's HttpClient::create().
     *
     * @param int $maxHostConnections
     *      The maximum number of connections to a single host.
     *      Used only when inner client is not specified.
     *
     * @param int $maxPendingPushes
     *      The maximum number of pushed responses to accept in the queue.
     *       Used only when inner client is not specified.
     *
     * @param LoggerInterface|null $logger
     *      A PSR-3 logger.
     */
    public function __construct(
        array|string|null $baseUri = null,
        ?string $authBearer = null,
        ?float $timeout = null,
        ?float $maxDuration = null,
        ?iterable $headers = null,
        array|string|null $authBasic = null,
        ?int $maxRedirects = null,
        Closure|null $onProgress = null,
        ?array $extra = null,
        ?string $httpVersion = null,
        mixed $buffer = null,
        ?array $resolve = null,
        ?string $proxy = null,
        ?string $noProxy = null,
        ?string $bindTo = null,
        ?SSLContext $ssl = null,
        ?RetryStrategyInterface $retry = null,
        int $maxRetries = 0,
        ?HttpClientInterface $client = null,
        int $maxHostConnections = 6,
        int $maxPendingPushes = 50,
        private ?LoggerInterface $logger = null,
        ?bool $debug = null
    ) {
        $options = [
                'base_uri' => $baseUri,
                'auth_bearer' => $authBearer,
                'auth_basic' => $authBasic,
                'headers' => $headers,
                'timeout' => $timeout,
                'max_duration' => $maxDuration,
                'max_redirects' => $maxRedirects,
                'on_progress' => $onProgress,
                'extra' => $extra,
                'http_version' => $httpVersion,
                'buffer' => $buffer,
                'resolve' => $resolve,
                'proxy' => $proxy,
                'no_proxy' => $noProxy,
                'bindto' => $bindTo,
            ] + ($ssl?->toArray() ?? []);
        $options = array_filter($options, static fn($v) => $v !== null);

        if ($client === null) {
            if (class_exists(AmpHttpClientV5::class)) {
                /** @var HttpClientInterface $client */
                $client = new AmpHttpClientV5($options, null, $maxHostConnections, $maxPendingPushes);
            } else {
                $client = SymfonyHttpClient::create($options, $maxHostConnections, $maxPendingPushes);
            }
            $options = [];
        }
        if ($debug !== false || (class_exists(Debugger::class) && Debugger::isEnabled())) {
            $client = new SharedTraceableHttpClient($client);
        }
        $client = new RetryableHttpClient($client, $retry, $maxRetries, $logger, $baseUri);
        $this->setClient($client, $options);
    }

    /**
     * Sends an HTTP request to the specified URL using the given method and options.
     *
     * @param string $method
     *      The HTTP method to use for the request (e.g., 'GET', 'POST').
     *
     * @param string $url
     *      The target URL to which the HTTP request should be sent.
     *
     * @param array<string,mixed>|null $query
     *      An associative array of query string values to be merged with the request's URL.
     *
     * @param mixed|null $json
     *      If set, the request body will be JSON-encoded, and the "content-type" header will be set to "application/json".
     *
     * @param mixed[]|string|resource|Traversable<mixed>|Closure(int $size): string $body
     *      The request body. An array is treated as FormData.
     *      If a Closure is provided, it should return a string smaller than the specified size argument.
     *      Traversable is treated as a stream resource.
     *
     * @param iterable<int|string, string>|null $headers
     *      Headers, provided as keys or as part of values, to be included in the HTTP request.
     *
     * @param float|null $timeout
     *      The idle timeout (in seconds) for the request.
     *
     * @param float|null $maxDuration
     *      The maximum execution time (in seconds) for the entire request and response process.
     *
     * @param mixed $userData
     *      Additional data to attach to the request, accessible via $response->getInfo('user_data').
     *
     * @param Closure(int $dlNow, int $dlSize, array<string, mixed> $info): mixed|null $onProgress
     *      A callable function to monitor the progress of the request.
     *
     * @param array<string, mixed>|null $extra
     *      Additional options for fine-tuning the request.
     *
     * @return HttpResponse
     *      An asynchronous response that doesn't block until its methods are called.
     *      Exceptions are thrown when the response is read.
     */
    public function request(
        string $method,
        string $url,
        ?array $query = null,
        mixed $json = null,
        mixed $body = null,
        ?iterable $headers = null,
        ?float $timeout = null,
        ?float $maxDuration = null,
        mixed $userData = null,
        ?Closure $onProgress = null,
        ?array $extra = null,
    ): HttpResponse {
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

        return new HttpResponse($this->client->request($method, $url, $options));
    }

    /**
     * Sends an HTTP GET request to the specified URL using the given options.
     *
     * @param string $url
     *      The target URL to which the HTTP request should be sent.
     *
     * @param array<string,mixed>|null $query
     *      An associative array of query string values to be merged with the request's URL.
     *
     * @param mixed|null $json
     *      If set, the request body will be JSON-encoded, and the "content-type" header will be set to "application/json".
     *
     * @param mixed[]|string|resource|Traversable<mixed>|Closure(int $size): string $body
     *      The request body. An array is treated as FormData.
     *      If a Closure is provided, it should return a string smaller than the specified size argument.
     *      Traversable is treated as a stream resource.
     *
     * @param iterable<int|string, string>|null $headers
     *      Headers, provided as keys or as part of values, to be included in the HTTP request.
     *
     * @param float|null $timeout
     *      The idle timeout (in seconds) for the request.
     *
     * @param float|null $maxDuration
     *      The maximum execution time (in seconds) for the entire request and response process.
     *
     * @param mixed $userData
     *      Additional data to attach to the request, accessible via $response->getInfo('user_data').
     *
     * @param Closure(int $dlNow, int $dlSize, array<string, mixed> $info): mixed|null $onProgress
     *      A callable function to monitor the progress of the request.
     *
     * @param array<string, mixed>|null $extra
     *      Additional options for fine-tuning the request.
     *
     * @return HttpResponse
     *      An asynchronous response that doesn't block until its methods are called.
     *      Exceptions are thrown when the response is read.
     */
    public function get(
        string $url,
        ?array $query = null,
        mixed $json = null,
        mixed $body = null,
        ?iterable $headers = null,
        ?float $timeout = null,
        ?float $maxDuration = null,
        mixed $userData = null,
        ?Closure $onProgress = null,
        ?array $extra = null,
    ): HttpResponse {
        return $this->request(
            'GET',
            $url,
            $query,
            $json,
            $body,
            $headers,
            $timeout,
            $maxDuration,
            $userData,
            $onProgress,
            $extra
        );
    }

    /**
     * Sends an HTTP POST request to the specified URL using the given options.
     *
     * @param string $url
     *      The target URL to which the HTTP request should be sent.
     *
     * @param array<string,mixed>|null $query
     *      An associative array of query string values to be merged with the request's URL.
     *
     * @param mixed|null $json
     *      If set, the request body will be JSON-encoded, and the "content-type" header will be set to "application/json".
     *
     * @param mixed[]|string|resource|Traversable<mixed>|Closure(int $size): string $body
     *      The request body. An array is treated as FormData.
     *      If a Closure is provided, it should return a string smaller than the specified size argument.
     *      Traversable is treated as a stream resource.
     *
     * @param iterable<int|string, string>|null $headers
     *      Headers, provided as keys or as part of values, to be included in the HTTP request.
     *
     * @param float|null $timeout
     *      The idle timeout (in seconds) for the request.
     *
     * @param float|null $maxDuration
     *      The maximum execution time (in seconds) for the entire request and response process.
     *
     * @param mixed $userData
     *      Additional data to attach to the request, accessible via $response->getInfo('user_data').
     *
     * @param Closure(int $dlNow, int $dlSize, array<string, mixed> $info): mixed|null $onProgress
     *      A callable function to monitor the progress of the request.
     *
     * @param array<string, mixed>|null $extra
     *      Additional options for fine-tuning the request.
     *
     * @return HttpResponse
     *      An asynchronous response that doesn't block until its methods are called.
     *      Exceptions are thrown when the response is read.
     */
    public function post(
        string $url,
        ?array $query = null,
        mixed $json = null,
        mixed $body = null,
        ?iterable $headers = null,
        ?float $timeout = null,
        ?float $maxDuration = null,
        mixed $userData = null,
        ?Closure $onProgress = null,
        ?array $extra = null,
    ): HttpResponse {
        return $this->request(
            'POST',
            $url,
            $query,
            $json,
            $body,
            $headers,
            $timeout,
            $maxDuration,
            $userData,
            $onProgress,
            $extra
        );
    }

    /**
     * Sends an HTTP PUT request to the specified URL using the given options.
     *
     * @param string $url
     *      The target URL to which the HTTP request should be sent.
     *
     * @param array<string,mixed>|null $query
     *      An associative array of query string values to be merged with the request's URL.
     *
     * @param mixed|null $json
     *      If set, the request body will be JSON-encoded, and the "content-type" header will be set to "application/json".
     *
     * @param mixed[]|string|resource|Traversable<mixed>|Closure(int $size): string $body
     *      The request body. An array is treated as FormData.
     *      If a Closure is provided, it should return a string smaller than the specified size argument.
     *      Traversable is treated as a stream resource.
     *
     * @param iterable<int|string, string>|null $headers
     *      Headers, provided as keys or as part of values, to be included in the HTTP request.
     *
     * @param float|null $timeout
     *      The idle timeout (in seconds) for the request.
     *
     * @param float|null $maxDuration
     *      The maximum execution time (in seconds) for the entire request and response process.
     *
     * @param mixed $userData
     *      Additional data to attach to the request, accessible via $response->getInfo('user_data').
     *
     * @param Closure(int $dlNow, int $dlSize, array<string, mixed> $info): mixed|null $onProgress
     *      A callable function to monitor the progress of the request.
     *
     * @param array<string, mixed>|null $extra
     *      Additional options for fine-tuning the request.
     *
     * @return HttpResponse
     *      An asynchronous response that doesn't block until its methods are called.
     *      Exceptions are thrown when the response is read.
     */
    public function put(
        string $url,
        ?array $query = null,
        mixed $json = null,
        mixed $body = null,
        ?iterable $headers = null,
        ?float $timeout = null,
        ?float $maxDuration = null,
        mixed $userData = null,
        ?Closure $onProgress = null,
        ?array $extra = null,
    ): HttpResponse {
        return $this->request(
            'PUT',
            $url,
            $query,
            $json,
            $body,
            $headers,
            $timeout,
            $maxDuration,
            $userData,
            $onProgress,
            $extra
        );
    }

    /**
     * Sends an HTTP PATCH request to the specified URL using the given options.
     *
     * @param string $url
     *      The target URL to which the HTTP request should be sent.
     *
     * @param array<string,mixed>|null $query
     *      An associative array of query string values to be merged with the request's URL.
     *
     * @param mixed|null $json
     *      If set, the request body will be JSON-encoded, and the "content-type" header will be set to "application/json".
     *
     * @param mixed[]|string|resource|Traversable<mixed>|Closure(int $size): string $body
     *      The request body. An array is treated as FormData.
     *      If a Closure is provided, it should return a string smaller than the specified size argument.
     *      Traversable is treated as a stream resource.
     *
     * @param iterable<int|string, string>|null $headers
     *      Headers, provided as keys or as part of values, to be included in the HTTP request.
     *
     * @param float|null $timeout
     *      The idle timeout (in seconds) for the request.
     *
     * @param float|null $maxDuration
     *      The maximum execution time (in seconds) for the entire request and response process.
     *
     * @param mixed $userData
     *      Additional data to attach to the request, accessible via $response->getInfo('user_data').
     *
     * @param Closure(int $dlNow, int $dlSize, array<string, mixed> $info): mixed|null $onProgress
     *      A callable function to monitor the progress of the request.
     *
     * @param array<string, mixed>|null $extra
     *      Additional options for fine-tuning the request.
     *
     * @return HttpResponse
     *      An asynchronous response that doesn't block until its methods are called.
     *      Exceptions are thrown when the response is read.
     */
    public function patch(
        string $url,
        ?array $query = null,
        mixed $json = null,
        mixed $body = null,
        ?iterable $headers = null,
        ?float $timeout = null,
        ?float $maxDuration = null,
        mixed $userData = null,
        ?Closure $onProgress = null,
        ?array $extra = null,
    ): HttpResponse {
        return $this->request(
            'PATCH',
            $url,
            $query,
            $json,
            $body,
            $headers,
            $timeout,
            $maxDuration,
            $userData,
            $onProgress,
            $extra
        );
    }

    /**
     * Sends an HTTP DELETE request to the specified URL using the given options.
     *
     * @param string $url
     *      The target URL to which the HTTP request should be sent.
     *
     * @param array<string,mixed>|null $query
     *      An associative array of query string values to be merged with the request's URL.
     *
     * @param mixed|null $json
     *      If set, the request body will be JSON-encoded, and the "content-type" header will be set to "application/json".
     *
     * @param mixed[]|string|resource|Traversable<mixed>|Closure(int $size): string $body
     *      The request body. An array is treated as FormData.
     *      If a Closure is provided, it should return a string smaller than the specified size argument.
     *      Traversable is treated as a stream resource.
     *
     * @param iterable<int|string, string>|null $headers
     *      Headers, provided as keys or as part of values, to be included in the HTTP request.
     *
     * @param float|null $timeout
     *      The idle timeout (in seconds) for the request.
     *
     * @param float|null $maxDuration
     *      The maximum execution time (in seconds) for the entire request and response process.
     *
     * @param mixed $userData
     *      Additional data to attach to the request, accessible via $response->getInfo('user_data').
     *
     * @param Closure(int $dlNow, int $dlSize, array<string, mixed> $info): mixed|null $onProgress
     *      A callable function to monitor the progress of the request.
     *
     * @param array<string, mixed>|null $extra
     *      Additional options for fine-tuning the request.
     *
     * @return HttpResponse
     *      An asynchronous response that doesn't block until its methods are called.
     *      Exceptions are thrown when the response is read.
     */
    public function delete(
        string $url,
        ?array $query = null,
        mixed $json = null,
        mixed $body = null,
        ?iterable $headers = null,
        ?float $timeout = null,
        ?float $maxDuration = null,
        mixed $userData = null,
        ?Closure $onProgress = null,
        ?array $extra = null,
    ): HttpResponse {
        return $this->request(
            'DELETE',
            $url,
            $query,
            $json,
            $body,
            $headers,
            $timeout,
            $maxDuration,
            $userData,
            $onProgress,
            $extra
        );
    }

    /**
     * Yields responses chunk by chunk as they complete.
     *
     * @param ResponseInterface|iterable<array-key, ResponseInterface> $responses One or more responses created by the current HTTP client
     * @param float|null $timeout The idle timeout before yielding timeout chunks
     */
    public function stream(ResponseInterface|iterable $responses, ?float $timeout = null): ResponseStreamInterface
    {
        return $this->client->stream($responses, $timeout);
    }

    /**
     * You can use this to add more decorators, combined with getClient()
     *
     * @param array<string, mixed> $options
     * @see HttpClientInterface::OPTIONS_DEFAULTS for options
     * @example $client->addDecorator(new BlackfiredHttpClient($http->getClient(), $blackfire))
     */
    public function setClient(HttpClientInterface $client, array $options = []): self
    {
        if ($options !== []) {
            $client = $client->withOptions($options);
        }
        $this->client = $client;
        if ($this->logger instanceof LoggerInterface) {
            $this->setLogger($this->logger);
        }
        return $this;
    }

    /**
     * You can use this to create a new instance with an additional decorator, combined with getClient()
     *
     * @param array<string, mixed> $options
     * @see HttpClientInterface::OPTIONS_DEFAULTS for options
     * @example $new = $client->withDecorator(new BlackfiredHttpClient($http->getClient(), $blackfire))
     */
    public function withClient(HttpClientInterface $client, array $options = []): self
    {
        return (clone $this)->setClient($client, $options);
    }

    /**
     * @see self::__construct() for the list of available options
     * Use false to reset option to default. (remove it from the options)
     * This does not apply to $buffer as false is a valid value.
     * null means the option will not be changed.
     *
     * @param array<string|string[]>|string|false|null $baseUri
     * @param iterable<int|string, string>|false|null $headers
     * @param array{0: string, 1?: string|null}|string|false|null $authBasic
     * @param Closure(int $dlNow, int $dlSize, array<string, mixed> $info): mixed|false|null $onProgress
     * @param array<string, mixed>|false|null $extra
     * @param resource|bool|null|Closure(iterable<int|string, string> $headers): bool $buffer
     * @param array<string, string>|null $resolve
     */
    public function withOptions(
        array|string|false|null $baseUri = null,
        string|false|null $authBearer = null,
        float|false|null $timeout = null,
        float|false|null $maxDuration = null,
        iterable|false|null $headers = null,
        array|string|false|null $authBasic = null,
        int|false|null $maxRedirects = null,
        Closure|false|null $onProgress = null,
        array|false|null $extra = null,
        string|false|null $httpVersion = null,
        mixed $buffer = null,
        array|false|null $resolve = null,
        string|false|null $proxy = null,
        string|false|null $noProxy = null,
        string|false|null $bindTo = null,
        SSLContext|null $ssl = null,
        RetryStrategyInterface|false $retry = null,
        int|null $maxRetries = null,
    ): self {
        $options = [
            'base_uri' => $baseUri,
            'auth_bearer' => $authBearer,
            'auth_basic' => $authBasic,
            'headers' => $headers,
            'timeout' => $timeout,
            'max_duration' => $maxDuration,
            'max_redirects' => $maxRedirects,
            'on_progress' => $onProgress,
            'extra' => $extra,
            'http_version' => $httpVersion,
            'resolve' => $resolve,
            'proxy' => $proxy,
            'no_proxy' => $noProxy,
            'bindto' => $bindTo,
            'retry_strategy' => $retry,
            'max_retries' => $maxRetries,
        ] + ($ssl?->toArray() ?? []);
        $options = array_filter($options, static fn($v) => $v !== null);
        // convert false values to null values
        $options = array_map(static fn($v) => $v === false ? null : $v, $options);
        $options['buffer'] = $buffer;

        return $this->withClient($this->client, $options);
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

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
        if ($this->client instanceof LoggerAwareInterface) {
            $this->client->setLogger($logger);
        }
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

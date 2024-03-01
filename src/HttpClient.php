<?php

namespace Efabrica\HttpClient;

use Closure;
use Symfony\Component\HttpClient\HttpClient as SymfonyHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;
use Traversable;

final class HttpClient
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @param string|null $baseUrl The URI to resolve relative URLs, following rules in RFC 3986, section 2.
     *
     * @param string|null $bearerToken A token enabling HTTP Bearer authorization (RFC 6750).
     *
     * @param float|null $timeout The idle timeout (in seconds), defaults to ini_get('default_socket_timeout').
     *
     * @param float|null $maxDuration The maximum execution time (in seconds) for the request+response as a whole;
     *                                              a value lower than or equal to 0 means it is unlimited.
     *
     * @param iterable|null $headers Headers names provided as keys or as part of values.
     *
     * @param array|string|null $basicAuth An array containing the username as the first value and optionally the
     *                                              password as the second one, or a string like username:password enabling
     *                                              HTTP Basic authentication (RFC 7617).
     *
     * @param int|null $maxRedirects The maximum number of redirects to follow; a value lower than or equal to means
     *                                              redirects should not be followed.
     *
     * @param string|null $httpVersion The HTTP version to use, defaults to the best supported version, typically 1.1 or 2.0.
     *
     * @param mixed|null $buffer Whether the content of the response should be buffered or not, or a stream resource
     *                                              where the response body should be written, or a closure telling if/where
     *                                              the response should be buffered based on its headers.
     *
     * @param Closure|null $onProgress A callable(int $dlNow, int $dlSize, array $info) that MUST be called on DNS resolution,
     *                                              on arrival of headers, on completion, and SHOULD be called on upload/download
     *                                              of data and at least 1/s. Throwing any exceptions MUST abort the request.
     *
     * @param array|null $resolve A map of host to IP address that SHOULD replace DNS resolution.
     *
     * @param string|null $proxy By default, the proxy-related env vars handled by curl SHOULD be honored.
     *
     * @param string|null $noProxy A comma-separated list of hosts that do not require a proxy to be reached.
     *
     * @param string|null $bindTo The interface or the local socket to bind to.
     *
     * @param bool|null $verifyPeer Set to true to enable peer verification
     *
     * @param array|null $extra Additional options that can be ignored if unsupported, unlike regular options
     *
     * @param int $maxHostConnections The maximum number of connections to a single host
     *
     * @param int $maxPendingPushes The maximum number of pushed responses to accept in the queue
     *
     * @return self
     *
     * @see https://php.net/context.ssl for information on SSL-related options.
     */

    public static function create(
        ?string $baseUrl = null,
        ?string $bearerToken = null,
        ?float $timeout = null,
        ?float $maxDuration = null,
        ?iterable $headers = null,
        array | string | null $basicAuth = null,
        ?int $maxRedirects = null,
        string | null $httpVersion = null,
        mixed $buffer = null,
        Closure | null $onProgress = null,
        ?array $resolve = null,
        string | null $proxy = null,
        string | null $noProxy = null,
        ?string $bindTo = null,
        ?bool $verifyPeer = null,
        ?bool $verifyHost = null,
        string | null $cafile = null,
        string | null $capath = null,
        string | null $localCert = null,
        string | null $localPk = null,
        string | null $passphrase = null,
        string | null $ciphers = null,
        string | null $peerFingerprint = null,
        bool $capturePeerCertChain = false,
        ?int $cryptoMethod = null,
        ?array $extra = null,
        int $maxHostConnections = 6,
        int $maxPendingPushes = 50
    ): self {
        return new self(SymfonyHttpClient::create(array_filter([
            'base_uri' => $baseUrl,
            'bearer_token' => $bearerToken,
            'timeout' => $timeout,
            'max_duration' => $maxDuration,
            'headers' => $headers,
            'auth_basic' => $basicAuth,
            'max_redirects' => $maxRedirects,
            'http_version' => $httpVersion,
            'buffer' => $buffer,
            'on_progress' => $onProgress,
            'resolve' => $resolve,
            'proxy' => $proxy,
            'no_proxy' => $noProxy,
            'bindto' => $bindTo,
            'verify_peer' => $verifyPeer,
            'verify_host' => $verifyHost,
            'cafile' => $cafile,
            'capath' => $capath,
            'local_cert' => $localCert,
            'local_pk' => $localPk,
            'passphrase' => $passphrase,
            'ciphers' => $ciphers,
            'peer_fingerprint' => $peerFingerprint,
            'capture_peer_cert_chain' => $capturePeerCertChain,
            'crypto_method' => $cryptoMethod,
            'extra' => $extra,
        ], static fn($v) => $v !== null), $maxHostConnections, $maxPendingPushes));
    }

    /**
     * Sends an HTTP request to the specified URL using the given method and options.
     *
     * @param string $method The HTTP method to use for the request (e.g., 'GET', 'POST').
     * @param string $url The URL to which the request should be sent.
     * @param array|null $query An associative array of query string values to merge with the request's URL.
     * @param array|null $json If set, the request body will be JSON-encoded, and the "content-type" header will be set to "application/json".
     * @param iterable|string|resource|Traversable|Closure $body The request body.
     *                                     If an array is passed, it is meant as a form payload of field names and values. (FormData)
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
        return $this->client->request($method, $url, array_filter([
            'query' => $query,
            'json' => $json,
            'body' => $body,
            'headers' => $headers,
            'timeout' => $timeout,
            'max_duration' => $maxDuration,
            'user_data' => $userData,
            'on_progress' => $onProgress,
            'extra' => $extra,
        ], static fn($v) => $v !== null));
    }

    /**
     * @param string $url The URL to which the request should be sent.
     * @param array|null $query An associative array of query string values to merge with the request's URL.
     * @param iterable|null $headers Headers names provided as keys or as part of values.
     * @param float|null $timeout The idle timeout (in seconds) for the request.
     * @param float|null $maxDuration The maximum execution time (in seconds) for the request+response as a whole.
     * @param mixed $userData Any extra data to attach to the request that will be available via $response->getInfo('user_data').
     * @param Closure|null $onProgress A callable function to track the progress of the request.
     * @param array|null $extra Additional options for the request
     *
     * @return ResponseInterface Asynchronous response that doesn't block until its methods are called.
     *  Exceptions are thrown when the response is read.
     */
    public function get(
        string $url,
        ?array $query = null,
        ?iterable $headers = null,
        ?float $timeout = null,
        ?float $maxDuration = null,
        mixed $userData = null,
        ?Closure $onProgress = null,
        ?array $extra = null,
    ): ResponseInterface {
        return $this->request('GET', $url, $query, null, null, $headers, $timeout, $maxDuration, $userData, $onProgress, $extra);
    }

    /**
     * @param string $url The URL to which the request should be sent.
     * @param array|null $query An associative array of query string values to merge with the request's URL.
     * @param array|null $json If set, the request body will be JSON-encoded, and the "content-type" header will be set to "application/json".
     * @param iterable|string|resource|Traversable|Closure $body The request body.
     *                                      If an array is passed, it is meant as a form payload of field names and values. (FormData)
     * @param iterable|null $headers Headers names provided as keys or as part of values.
     * @param float|null $timeout The idle timeout (in seconds) for the request.
     * @param float|null $maxDuration The maximum execution time (in seconds) for the request+response as a whole.
     * @param mixed $userData Any extra data to attach to the request that will be available via $response->getInfo('user_data').
     * @param Closure|null $onProgress A callable function to track the progress of the request.
     * @param array|null $extra Additional options for the request
     *
     * @return ResponseInterface Asynchronous response that doesn't block until its methods are called.
     *  Exceptions are thrown when the response is read.
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
     * @param iterable|string|resource|Traversable|Closure $body The request body.
     *                                      If an array is passed, it is meant as a form payload of field names and values. (FormData)
     * @param iterable|null $headers Headers names provided as keys or as part of values.
     * @param float|null $timeout The idle timeout (in seconds) for the request.
     * @param float|null $maxDuration The maximum execution time (in seconds) for the request+response as a whole.
     * @param mixed $userData Any extra data to attach to the request that will be available via $response->getInfo('user_data').
     * @param Closure|null $onProgress A callable function to track the progress of the request.
     * @param array|null $extra Additional options for the request
     *
     * @return ResponseInterface Asynchronous response that doesn't block until its methods are called.
     *  Exceptions are thrown when the response is read.
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
     * @param iterable|string|resource|Traversable|Closure $body The request body.
     *                                      If an array is passed, it is meant as a form payload of field names and values. (FormData)
     * @param iterable|null $headers Headers names provided as keys or as part of values.
     * @param float|null $timeout The idle timeout (in seconds) for the request.
     * @param float|null $maxDuration The maximum execution time (in seconds) for the request+response as a whole.
     * @param mixed $userData Any extra data to attach to the request that will be available via $response->getInfo('user_data').
     * @param Closure|null $onProgress A callable function to track the progress of the request.
     * @param array|null $extra Additional options for the request
     *
     * @return ResponseInterface Asynchronous response that doesn't block until its methods are called.
     *  Exceptions are thrown when the response is read.
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
     * @param iterable|string|resource|Traversable|Closure $body The request body.
     *                                      If an array is passed, it is meant as a form payload of field names and values. (FormData)
     * @param iterable|null $headers Headers names provided as keys or as part of values.
     * @param float|null $timeout The idle timeout (in seconds) for the request.
     * @param float|null $maxDuration The maximum execution time (in seconds) for the request+response as a whole.
     * @param mixed $userData Any extra data to attach to the request that will be available via $response->getInfo('user_data').
     * @param Closure|null $onProgress A callable function to track the progress of the request.
     * @param array|null $extra Additional options for the request
     *
     * @return ResponseInterface Asynchronous response that doesn't block until its methods are called.
     *  Exceptions are thrown when the response is read.
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
     * @param float|null                                               $timeout   The idle timeout before yielding timeout chunks
     */
    public function stream(iterable | ResponseInterface $responses, ?float $timeout = null): ResponseStreamInterface
    {
        return $this->client->stream($responses, $timeout);
    }

    /**
     * You can use this to add more decorators, combined with getClient()
     * @example $client->addDecorator(new CachedHttpClient($cache, $client->getClient())
     */
    public function addDecorator(HttpClientInterface $decorator): self
    {
        $this->client = $decorator;
        return $this;
    }

    /**
     * You can use this to create a new instance with an additional decorator, combined with getClient()
     * @example $new = $client->withDecorator(new CachedHttpClient($cache, $client->getClient())
     */
    public function withDecorator(HttpClientInterface $decorator): self
    {
        $new = clone $this;
        $new->client = $decorator;
        return $new;
    }

    /**
     * @return HttpClientInterface The inner client, possibly decorated
     */
    public function getClient(): HttpClientInterface
    {
        return $this->client;
    }

    /**
     * @see self::create() for the list of available options
     */
    public function withOptions(
        ?string $baseUrl = null,
        ?string $bearerToken = null,
        ?float $timeout = null,
        ?float $maxDuration = null,
        ?iterable $headers = null,
        array | string | null $basicAuth = null,
        ?int $maxRedirects = null,
        string | null $httpVersion = null,
        mixed $buffer = null,
        Closure | null $onProgress = null,
        ?array $resolve = null,
        string | null $proxy = null,
        string | null $noProxy = null,
        ?string $bindTo = null,
        ?bool $verifyPeer = null,
        ?bool $verifyHost = null,
        string | null $cafile = null,
        string | null $capath = null,
        string | null $localCert = null,
        string | null $localPk = null,
        string | null $passphrase = null,
        string | null $ciphers = null,
        string | null $peerFingerprint = null,
        bool $capturePeerCertChain = false,
        ?int $cryptoMethod = null,
        ?array $extra = null
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
            'http_version' => $httpVersion,
            'buffer' => $buffer,
            'on_progress' => $onProgress,
            'resolve' => $resolve,
            'proxy' => $proxy,
            'no_proxy' => $noProxy,
            'bindto' => $bindTo,
            'verify_peer' => $verifyPeer,
            'verify_host' => $verifyHost,
            'cafile' => $cafile,
            'capath' => $capath,
            'local_cert' => $localCert,
            'local_pk' => $localPk,
            'passphrase' => $passphrase,
            'ciphers' => $ciphers,
            'peer_fingerprint' => $peerFingerprint,
            'capture_peer_cert_chain' => $capturePeerCertChain,
            'crypto_method' => $cryptoMethod,
            'extra' => $extra,
        ], static fn($v) => $v !== null));
        return $new;
    }

    public function __clone(): void
    {
        $this->client = clone $this->client;
    }
}

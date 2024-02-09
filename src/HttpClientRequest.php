<?php

namespace Efabrica\HttpClient;

use Closure;
use Traversable;
use const STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;

/**
 * This is a simple typehinted DTO for the HttpClientInterface request
 */
class HttpClientRequest
{
    public function __construct(
        /**
         * @var string The HTTP method to use
         */
        public string $method = 'GET',
        /**
         * @var string The URL to send the request to
         */
        public string $url = '',
        /**
         * @var array An associative array of query string values to merge with the request's URL
         */
        public array $urlQuery = [],
        /**
         * @var mixed If set, implementations MUST set the "body" option to the JSON-encoded
         *            value and set the "content-type" header to a JSON-compatible value if it is not
         *            explicitly defined in the headers option - typically "application/json"
         */
        public ?array $jsonBody = null,
        /**
         * @var array|null the required 'Content-Type: application/x-www-form-urlencoded' header is automatically
         *                 added for you when using this option.
         */
        public ?array $formBody = null,
        /**
         * @var array|string|resource|Traversable|Closure The callback SHOULD yield a string
         *                                                  smaller than the amount requested as an argument; the empty
         *                                                  string signals EOF; if an array is passed, it is meant as a
         *                                                  form payload of field names and values
         */
        public mixed $body = null,
        /**
         * @var iterable|string[]|string[][] Headers names provided as keys or as part of values
         */
        public iterable $headers = [],
        /**
         * @var float|null The idle timeout (in seconds) - defaults to ini_get('default_socket_timeout')
         */
        public float | null $timeout = null,
        /**
         * @var float The maximum execution time (in seconds) for the request+response as a whole,     *            a value lower than or equal to 0 means it is unlimited
         */
        public float $maxDuration = 0,
        /**
         * @var array|string|null An array containing the username as the first value, and optionally the
         *                       password as the second one; or string like username:password - enabling HTTP Basic
         *                       authentication (RFC 7617)
         */
        public array | string | null $basicAuth = null,
        /**
         * @var string|null A token enabling HTTP Bearer authorization (RFC 6750)
         */
        public ?string $bearerToken = null,
        /**
         * @var string|null The URI to resolve relative URLs, following rules in RFC 3986, section 2
         */
        public string | null $baseUrl = null,
        /**
         * @var mixed Any extra data to attach to the request (scalar, callable, object...) that
         *             MUST be available via $response->getInfo('user_data') - not used internally
         */
        public mixed $userData = null,
        /**
         * @var int The maximum number of redirects to follow; a value lower than or equal to 0
         *          means redirects should not be followed; "Authorization" and "Cookie" headers MUST
         *          NOT follow except for the initial host name
         */
        public int $maxRedirects = 20,
        /**
         * @var string|null Defaults to the best-supported version, typically 1.1 or 2.0
         */
        public string | null $httpVersion = null,
        /**
         * @var bool|resource|Closure Whether the content of the response should be buffered or not,
         *                            or a stream resource where the response body should be written,
         *                            or a closure telling if/where the response should be buffered based on its headers
         */
        public mixed $buffer = true,
        /**
         * @var null|Closure(int $dlNow, int $dlSize, array $info): mixed
         * Throwing any exceptions will abort the request
         * It will be called on DNS resolution, on arrival of headers and on completion
         * it SHOULD be called on upload/download of data and at least 1/s
         */
        public Closure | null $onProgress = null,
        /**
         * @var array A map of host to IP address that SHOULD replace DNS resolution
         */
        public array $resolve = [],
        /**
         * @var string|null By default, the proxy-related env vars handled by curl SHOULD be honored
         */
        public string | null $proxy = null,
        /**
         * @var string|null A comma-separated list of hosts that do not require a proxy to be reached
         */
        public string | null $noProxy = null,
        /**
         * @var string The interface or the local socket to bind to
         */
        public string $bindTo = '0',
        /**
         * @var bool See https://php.net/context.ssl for the following options
         */
        public bool $verifyPeer = true,
        public bool $verifyHost = true,
        public string | null $cafile = null,
        public string | null $capath = null,
        public string | null $localCert = null,
        public string | null $localPk = null,
        public string | null $passphrase = null,
        public string | null $ciphers = null,
        public string | null $peerFingerprint = null,
        public bool $capturePeerCertChain = false,
        /**
         * @var int STREAM_CRYPTO_METHOD_TLSv*_CLIENT - minimum TLS version
         */
        public int $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
        /**
         * @var array Additional options that can be ignored if unsupported, unlike regular options
         */
        public array $extra = [],
    ) {
    }

    public function toOptionsArray(): array
    {
        return array_filter([
            'auth_basic' => $this->basicAuth,
            'auth_bearer' => $this->bearerToken,
            'query' => $this->urlQuery,
            'headers' => $this->headers,
            'body' => $this->body ?? $this->formBody,
            'json' => $this->jsonBody,
            'user_data' => $this->userData,
            'max_redirects' => $this->maxRedirects,
            'http_version' => $this->httpVersion,
            'base_uri' => $this->baseUrl,
            'buffer' => $this->buffer,
            'on_progress' => $this->onProgress,
            'resolve' => $this->resolve,
            'proxy' => $this->proxy,
            'no_proxy' => $this->noProxy,
            'timeout' => $this->timeout,
            'max_duration' => $this->maxDuration,
            'bindto' => $this->bindTo,
            'verify_peer' => $this->verifyPeer,
            'verify_host' => $this->verifyHost,
            'cafile' => $this->cafile,
            'capath' => $this->capath,
            'local_cert' => $this->localCert,
            'local_pk' => $this->localPk,
            'passphrase' => $this->passphrase,
            'ciphers' => $this->ciphers,
            'peer_fingerprint' => $this->peerFingerprint,
            'capture_peer_cert_chain' => $this->capturePeerCertChain,
            'crypto_method' => $this->cryptoMethod,
            'extra' => $this->extra,
        ], static fn ($v) => null !== $v);
    }
}

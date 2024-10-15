<?php

namespace Efabrica\HttpClient;

use ArrayAccess;
use JsonSerializable;
use LogicException;
use Serializable;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use function Amp\async;

/**
 * An asynchronous response to an HTTP request.
 * Wraps a ResponseInterface and provides:
 *  - non-blocking typed info property getters
 *  - blocking ArrayAccess implementation that is cached after the first access
 *  - blocking JsonSerializable implementation
 *  - makes response serializable (by blocking and waiting for the response content)
 * @implements ArrayAccess<string, mixed>
 */
final class HttpResponse implements ResponseInterface, Serializable, ArrayAccess, JsonSerializable
{
    /** @var mixed[]|null */
    private ?array $jsonData = null;

    public function __construct(private ResponseInterface $response)
    {
    }

    public function getInnerResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function getStatusCode(): int
    {
        // Blocks until the response headers are received
        return $this->response->getStatusCode();
    }

    public function getHeaders(bool $throw = true): array
    {
        // Blocks until the response headers are received
        return $this->response->getHeaders($throw);
    }

    public function getContent(bool $throw = true): string
    {
        // Blocks until the response content is received
        return $this->response->getContent($throw);
    }

    /**
     * @param bool $throw Throw on 300+ HTTP status codes
     * @return mixed[]
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function toArray(bool $throw = true): array
    {
        // Blocks until the response content is received
        return $this->jsonData ??= $this->response->toArray($throw);
    }

    public function cancel(): void
    {
        $this->response->cancel();
    }

    public function getInfo(?string $type = null): mixed
    {
        return $this->response->getInfo($type);
    }

    /**
     * Non-blocking. Check if the response was canceled using ResponseInterface::cancel().
     *
     * @return bool True if the response was canceled, false otherwise.
     */
    public function isCanceled(): bool
    {
        return $this->response->getInfo('canceled');
    }

    /**
     * Non-blocking. Retrieve the error message when the transfer was aborted.
     *
     * @return string|null The error message or null if no error occurred.
     */
    public function getError(): ?string
    {
        return $this->response->getInfo('error');
    }

    /**
     * Non-blocking. Get the last response code or null when it is not known yet.
     *
     * @return int<100,599>|null The HTTP response code or null if unknown.
     */
    public function getHttpCode(): ?int
    {
        $httpCode = $this->response->getInfo('http_code');
        return $httpCode > 0 ? $httpCode : null;
    }

    /**
     * Non-blocking. Get the HTTP verb of the last request. (e.g. GET, POST, etc.)
     *
     * @return string The HTTP method used in the last request.
     */
    public function getHttpMethod(): string
    {
        return $this->response->getInfo('http_method');
    }

    /**
     * Non-blocking. Get the number of redirects followed while executing the request.
     *
     * @return int<0,max> The count of redirects during the request execution.
     */
    public function getRedirectCount(): int
    {
        return $this->response->getInfo('redirect_count') ?? 0;
    }

    /**
     * Non-blocking. Get the resolved location of redirect responses, or null if not applicable.
     *
     * @return string|null The resolved redirect URL or null if no redirect occurred.
     */
    public function getRedirectUrl(): ?string
    {
        return $this->response->getInfo('redirect_url');
    }

    /**
     * Non-blocking. Get an array modeled after the $http_response_header variable containing response headers.
     *
     * @return array<int, string>|null An array list with response headers as values and their order as keys.
     */
    public function getResponseHeaders(): ?array
    {
        return $this->response->getInfo('response_headers');
    }

    /**
     * Non-blocking. Get the time when the request was sent or null when it's pending.
     *
     * @return float|null The timestamp of the request start time.
     */
    public function getStartTime(): ?float
    {
        $startTime = $this->response->getInfo('start_time');
        return ((int)$startTime === 0) ? null : $startTime;
    }

    /**
     * Non-blocking.
     *
     * @return string The last effective URL of the request.
     */
    public function getUrl(): string
    {
        return $this->response->getInfo('url');
    }

    /**
     * Non-blocking. Get the value of the "user_data" request option, or null if not set.
     *
     * @return mixed|null The user data associated with the request or null if not set.
     */
    public function getUserData(): mixed
    {
        return $this->response->getInfo('user_data');
    }

    /**
     * Non-blocking. Get the peer certificates as an array of OpenSSL X.509 resources when "capture_peer_cert_chain" is true.
     *
     * @return resource[]|null An array of OpenSSL X.509 resources representing the peer certificate chain or null if not captured.
     * @see SSLContext::$capturePeerCertChain
     */
    public function getPeerCertificateChain(): ?array
    {
        return $this->response->getInfo('peer_certificate_chain');
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->toArray()[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->toArray()[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('Response array is immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('Response array is immutable.');
    }

    public function __serialize(): array
    {
        return [
            'content' => $this->getContent(),
            'info' => $this->getInfo(),
        ];
    }

    public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    /**
     * @return mixed[]
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param array<"content"|"info", mixed> $data
     */
    public function __unserialize(array $data): void
    {
        $this->response = new SerializedResponse($data['content'], $data['info']);
    }

    public function unserialize(string $data): void
    {
        $this->__unserialize(unserialize($data, [false]));
    }

    public function __clone(): void
    {
        $this->response = clone $this->response;
    }

    public function __destruct()
    {
        if (PHP_VERSION_ID >= 84000) {
            return;
        }
        // prevent bug for fiber execution context
        $response = $this->response;
        async(static function () use ($response) {
            $response->getHeaders();
        });
        unset($this->response);
    }
}

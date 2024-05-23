<?php

namespace Efabrica\HttpClient;

use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Contracts\HttpClient\ResponseInterface;
use function count;
use function is_array;
use function strlen;
use const JSON_BIGINT_AS_STRING;
use const JSON_THROW_ON_ERROR;

final class SerializedResponse implements ResponseInterface
{
    /** @var array<string, mixed> */
    private array $headers = [];

    /** @var mixed[]|null  */
    private ?array $jsonData = null;

    /**
     * @param array<string, mixed> $info
     */
    public function __construct(private readonly string $content, private array $info = [])
    {
        self::addResponseHeaders($info['response_headers'], $this->info, $this->headers);
    }

    public function getStatusCode(): int
    {
        return $this->info['http_code'];
    }

    /**
     * @return array<string, string[]>
     */
    public function getHeaders(bool $throw = true): array
    {
        return $this->headers;
    }

    public function getContent(bool $throw = true): string
    {
        return $this->content;
    }

    /**
     * @return mixed[]
     */
    public function toArray(bool $throw = true): array
    {
        if ('' === $content = $this->getContent($throw)) {
            throw new JsonException('Response body is empty.');
        }

        if (null !== $this->jsonData) {
            return $this->jsonData;
        }

        try {
            $content = json_decode($content, true, 512, JSON_BIGINT_AS_STRING | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new JsonException("{$e->getMessage()} for \"{$this->getInfo('url')}\".", $e->getCode());
        }

        if (!is_array($content)) {
            throw new JsonException(sprintf(
                'JSON content was expected to decode to an array, "%s" returned for "%s".',
                get_debug_type($content),
                $this->getInfo('url')
            ));
        }

        return $this->jsonData = $content;
    }

    public function cancel(): void
    {
    }

    public function getInfo(string $type = null): mixed
    {
        return $type === null ? $this->info : $this->info[$type] ?? null;
    }

    /**
     * @param string[] $responseHeaders
     * @param array<string, mixed> $info
     * @param array<string, string[]> $headers
     */
    private static function addResponseHeaders(array $responseHeaders, array &$info, array &$headers, string &$debug = ''): void
    {
        foreach ($responseHeaders as $h) {
            if (11 <= strlen($h) && '/' === $h[4] && preg_match('#^HTTP/\d+(?:\.\d+)? (\d\d\d)(?: |$)#', $h, $m)) {
                if ($headers) {
                    $debug .= "< \r\n";
                    $headers = [];
                }
                $info['http_code'] = (int)$m[1];
            } elseif (2 === count($m = explode(':', $h, 2))) {
                $headers[strtolower($m[0])][] = ltrim($m[1]);
            }

            $debug .= "< {$h}\r\n";
            $info['response_headers'][] = $h;
        }

        $debug .= "< \r\n";
    }
}

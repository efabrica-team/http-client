<?php

namespace Efabrica\HttpClient\Amp;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Response\AsyncResponse;
use Symfony\Component\HttpClient\Response\ResponseStream;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;
use Symfony\Contracts\Service\ResetInterface;
use function Amp\delay;

class EventLoopHttpClient implements HttpClientInterface, ResetInterface, LoggerAwareInterface
{
    private const DELAY = 0.0005;

    public function __construct(private HttpClientInterface $client)
    {
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        return new AsyncResponse($this->client, $method, $url, $options, fn() => delay(self::DELAY));
    }

    public function stream(iterable|ResponseInterface $responses, float $timeout = null): ResponseStreamInterface
    {
        return new ResponseStream(
            $this->loopStream(
                $this->client->stream($responses, $timeout)
            )
        );
    }

    private function loopStream(ResponseStreamInterface $stream): \Generator
    {
        foreach ($stream as $response => $chunk) {
            yield $response => $chunk;
            delay(self::DELAY);
        }
    }

    public function withOptions(array $options): static
    {
        $clone = clone $this;
        $clone->client = $this->client->withOptions($options);

        return $clone;
    }

    public function reset(): void
    {
        if ($this->client instanceof ResetInterface) {
            $this->client->reset();
        }
    }

    public function setLogger(LoggerInterface $logger): void
    {
        if ($this->client instanceof LoggerAwareInterface) {
            $this->client->setLogger($logger);
        }
    }
}

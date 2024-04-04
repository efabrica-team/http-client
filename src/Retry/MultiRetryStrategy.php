<?php

namespace Efabrica\HttpClient\Retry;

use Efabrica\HttpClient\HttpClient;

/**
 * @internal
 */
class MultiRetryStrategy implements ClientRetryStrategy
{
    /** @var ClientRetryStrategy[] */
    private readonly array $strategies;

    public function __construct(ClientRetryStrategy ...$strategies)
    {
        $this->strategies = $strategies;
    }

    public function addDecorator(HttpClient $client, array|string|null $baseUrl = null): void
    {
        foreach ($this->strategies as $strategy) {
            $strategy->addDecorator($client, $baseUrl);
        }
    }
}

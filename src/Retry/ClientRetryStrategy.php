<?php

namespace Efabrica\HttpClient\Retry;

use Efabrica\HttpClient\HttpClient;

interface ClientRetryStrategy
{
    public function addDecorator(HttpClient $client, array|string|null $baseUrl = null): void;
}

<?php

namespace Efabrica\HttpClient;

use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class HttpClientMiddleware
{
    public function handle(RequestEvent $event): ResponseInterface
    {
        return $event->handle();
    }
}

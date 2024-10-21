<?php

declare(strict_types=1);

namespace Efabrica\HttpClient\Tracy;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Throwable;
use Tracy\Debugger;

final class HttpResponseBluescreen
{
    /**
     * @param Throwable|null $e
     * @return array{tab: string, panel: string}|null
     */
    public static function renderException(?Throwable $e): ?array
    {
        $response = self::getResponse($e);
        if ($response === null) {
            return null;
        }
        return [
            'tab' => 'Symfony HTTP Client Response',
            'panel' => self::renderBody($response),
        ];
    }

    private static function getResponse(?Throwable $e): ?ResponseInterface
    {
        while ($e instanceof Throwable) {
            if ($e instanceof HttpExceptionInterface) {
                return $e->getResponse();
            }
            $e = $e->getPrevious();
        }
        return null;
    }

    private static function renderBody(ResponseInterface $response): string
    {
        try {
            $body = $response->toArray(false);
        } catch (ExceptionInterface) {
            try {
                $body = $response->getContent(false);
            } catch (ExceptionInterface $e) {
                $body = get_class($e) . ': ' . $e->getMessage() . "\n\n" . $e->getTraceAsString();
            }
        }
        $body = Debugger::dump($body, true);
        $info = Debugger::dump($response->getInfo(), true);

        return <<<HTML
        <div>
            <h3>Response</h3>
            <div>$body</div>
            <h3>ResponseInfo</h3>
            <div>$info</div>
        </div>
HTML;
    }
}

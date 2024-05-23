<?php

namespace Efabrica\HttpClient\Tracy;

use Latte\Engine;
use Tracy\IBarPanel;

/**
 * Panel for Tracy debugger that shows all HTTP requests made by the SharedTraceableHttpClient.
 */
final class HttpPanel implements IBarPanel
{
    private Engine $latte;

    private float $startTime;

    public function __construct()
    {
        $this->latte = new Engine();
        $startTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? $_SERVER['REQUEST_TIME'];
        $this->startTime = $startTime * 1000.0;
    }

    public function getTab(): string
    {
        return $this->latte->renderToString(__DIR__ . '/templates/http.tab.latte', [
            'requestCount' => count(SharedTraceableHttpClient::getTracedRequests()),
        ]);
    }

    public function getPanel(): string
    {
        return $this->latte->renderToString(__DIR__ . '/templates/http.panel.latte', [
            'requests' => SharedTraceableHttpClient::getTracedRequests(),
            'startTime' => $this->startTime,
            'endTime' => microtime(true) * 1000,
        ]);
    }
}

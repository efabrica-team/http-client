<?php

namespace Efabrica\HttpClient\Tracy;

use Latte\Engine;
use Tracy\IBarPanel;

class HttpPanel implements IBarPanel
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
            'requestCount' => $this->getRequestCount(),
        ]);
    }

    public function getPanel(): string
    {
        return $this->latte->renderToString(__DIR__ . '/templates/http.panel.latte', [
            'requests' => SharedTraceableHttpClient::getTracedRequests(),
            'events' => SharedTraceableHttpClient::getEvents(),
            'requestCount' => $this->getRequestCount(),
            'startTime' => $this->startTime,
            'endTime' => microtime(true) * 1000,
        ]);
    }

    private function getRequestCount(): int
    {
        $count = 0;
        foreach (SharedTraceableHttpClient::getTracedRequests() as $request) {
            $count++;
        }
        return $count;
    }
}

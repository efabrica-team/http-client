<?php

namespace Efabrica\HttpClient\Tracy;

use Efabrica\HttpClient\HttpClient;
use Latte\Engine;
use Symfony\Component\HttpClient\TraceableHttpClient;
use Symfony\Component\Stopwatch\Stopwatch;
use Tracy\IBarPanel;

class HttpPanel implements IBarPanel
{
    private Stopwatch $stopwatch;
    private TraceableHttpClient $traceClient;
    private Engine $latte;

    public function __construct(HttpClient $client)
    {
        $this->stopwatch = new Stopwatch(true);
        $this->traceClient = new TraceableHttpClient($client->getClient(), $this->stopwatch);
        $client->addDecorator($this->traceClient);
        $this->latte = new Engine();
    }

    private function getTotalDuration(): float
    {
        $duration = 0.0;
        foreach ($this->stopwatch->getSections() as $section) {
            foreach ($section->getEvents() as $event) {
                $duration += $event->getDuration();
            }
        }
        return round($duration);
    }

    public function getTab(): string
    {
        return $this->latte->renderToString(__DIR__ . '/templates/http.tab.latte', [
            'duration' => $this->getTotalDuration(),
            'requestCount' => count($this->traceClient->getTracedRequests()),
        ]);
    }

    public function getPanel(): string
    {
        return $this->latte->renderToString(__DIR__ . '/templates/http.tab.latte', [
            'duration' => $this->getTotalDuration(),
            'requests' => $this->traceClient->getTracedRequests(),
            'stopwatch' => $this->stopwatch,
        ]);
    }

}

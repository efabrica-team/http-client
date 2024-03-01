<?php

namespace Efabrica\HttpClient\Tracy;

use Latte\Engine;
use Symfony\Component\HttpClient\TraceableHttpClient;
use Symfony\Component\Stopwatch\Stopwatch;
use Tracy\IBarPanel;

class HttpPanel implements IBarPanel
{
    private Engine $latte;

    public function __construct(private readonly TraceableHttpClient $client, private readonly Stopwatch $stopwatch)
    {
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
            'requestCount' => count($this->client->getTracedRequests()),
        ]);
    }

    public function getPanel(): string
    {
        return $this->latte->renderToString(__DIR__ . '/templates/http.tab.latte', [
            'duration' => $this->getTotalDuration(),
            'requests' => $this->client->getTracedRequests(),
            'stopwatch' => $this->stopwatch,
        ]);
    }
}

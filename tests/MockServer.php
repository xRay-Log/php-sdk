<?php

namespace XRayLog\Tests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Client;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

class MockServer
{
    private $container = [];
    private $mockHandler;
    private $handlerStack;
    private $client;

    public function __construct(array $responses = [])
    {
        $this->mockHandler = new MockHandler($responses);
        $this->handlerStack = HandlerStack::create($this->mockHandler);
        
        // Track history of requests
        $history = Middleware::history($this->container);
        $this->handlerStack->push($history);
        
        $this->client = new Client(['handler' => $this->handlerStack]);
    }

    public function addResponse(int $status = 200, array $headers = [], string $body = null)
    {
        $this->mockHandler->append(new Response($status, $headers, $body));
        return $this;
    }

    public function getLastRequest()
    {
        return end($this->container)['request'] ?? null;
    }

    public function getRequestCount(): int
    {
        return count($this->container);
    }

    public function getRequests(): array
    {
        return array_map(function ($transaction) {
            return $transaction['request'];
        }, $this->container);
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function reset()
    {
        $this->container = [];
        return $this;
    }
}

<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

abstract class TestCase extends BaseTestCase
{
    /** @var MockHandler */
    protected $mock;
    protected function getGuzzle(array $responses = [], array $extraConfig = [])
    {
        if (empty($responses)) {
            $responses = [new GuzzleResponse(200, [], '<html><body><p>Hi</p></body></html>')];
        }
        $this->mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($this->mock);
        $this->history = [];
        $handlerStack->push(Middleware::history($this->history));
        $guzzle = new GuzzleClient(array_merge(array('redirect.disable' => true, 'base_uri' => '', 'handler' => $handlerStack), $extraConfig));
        
        return $guzzle;
    }
}

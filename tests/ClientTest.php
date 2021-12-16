<?php

namespace Tests;

use DateTime;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use UKFast\SimpleSDK\Client;
use UKFast\SimpleSDK\Page;
use UKFast\SimpleSDK\Entity;

class ClientTest extends TestCase
{
    /**
     * @test
     */
    public function sends_authentication_header()
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => [[
                    'id' => 1,
                    'name' => 'First',
                ]],
                'meta' => []
            ])),
        ]);
        $container = [];
        $history = Middleware::history($container);
        $handler = HandlerStack::create($mock);
        $handler->push($history);
        $guzzle = new Guzzle(['handler' => $handler]);

        $client = new Client($guzzle);

        $client->auth("Test token");
        $client->get("/");
        
        $this->assertEquals(1, count($container));
        $headers = $container[0]['request']->getHeaders();

        $this->assertEquals(1, count($headers['Authorization']));
        $this->assertEquals('Test token', $headers['Authorization'][0]);
    }

    /**
     * @test
     */
    public function uses_live_api_as_default()
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => [[
                    'id' => 1,
                    'name' => 'First',
                ]],
                'meta' => []
            ])),
        ]);
        $container = [];
        $history = Middleware::history($container);
        $handler = HandlerStack::create($mock);
        $handler->push($history);
        $guzzle = new Guzzle(['handler' => $handler]);

        $client = new Client($guzzle);
        $client->get("/");
        
        $this->assertEquals(1, count($container));
        $uri = $container[0]['request']->getUri();

        $hitHost = $uri->getScheme() . '://' . $uri->getHost();

        $this->assertEquals('https://api.ukfast.io', $hitHost);
    }

    /**
     * @test
     */
    public function can_change_api_host_to_use()
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => [[
                    'id' => 1,
                    'name' => 'First',
                ]],
                'meta' => []
            ])),
        ]);
        $container = [];
        $history = Middleware::history($container);
        $handler = HandlerStack::create($mock);
        $handler->push($history);
        $guzzle = new Guzzle(['handler' => $handler]);

        $client = new Client($guzzle);
        $client->setBasePath('https://api.ukfast.dev');
        $client->get("/");
        
        $this->assertEquals(1, count($container));
        $uri = $container[0]['request']->getUri();

        $hitHost = $uri->getScheme() . '://' . $uri->getHost();

        $this->assertEquals('https://api.ukfast.dev', $hitHost);
    }

    /**
     * @test
     */
    public function can_set_custom_headers_to_send_on_all_requests()
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => [[
                    'id' => 1,
                    'name' => 'First',
                ]],
                'meta' => []
            ])),
        ]);
        $container = [];
        $history = Middleware::history($container);
        $handler = HandlerStack::create($mock);
        $handler->push($history);
        $guzzle = new Guzzle(['handler' => $handler]);

        $client = new Client($guzzle);

        $client->auth("Test token");
        $client->setHeaders(['Test-Header' => '123']);
        $client->get("/");
        
        $this->assertEquals(1, count($container));
        $headers = $container[0]['request']->getHeaders();

        $this->assertEquals(1, count($headers['Authorization']));
        $this->assertEquals('Test token', $headers['Authorization'][0]);

        $this->assertEquals(1, count($headers['Test-Header']));
        $this->assertEquals('123', $headers['Test-Header'][0]);
    }

    /**
     * @test
     */
    public function merges_authentication_header_with_provided_headers()
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => [
                    'id' => 1,
                ],
                'meta' => [],
            ])),
        ]);
        $container = [];
        $history = Middleware::history($container);
        $handler = HandlerStack::create($mock);
        $handler->push($history);
        $guzzle = new Guzzle(['handler' => $handler]);

        $client = new Client($guzzle);

        $client->auth("Test token");
        $client->get("/", [], [
            'X-Custom-Header' => 1,
        ]);
        
        $this->assertEquals(1, count($container));
        $headers = $container[0]['request']->getHeaders();

        $this->assertEquals(1, count($headers['Authorization']));
        $this->assertEquals('Test token', $headers['Authorization'][0]);

        $this->assertEquals(1, count($headers['X-Custom-Header']));
        $this->assertEquals(1, $headers['X-Custom-Header'][0]);
    }

    /**
     * @test
     */
    public function wraps_client_exceptions_as_ukfast_exceptions()
    {
        $this->markTestIncomplete();
        $mock = new MockHandler([
            new Response(400, [], json_encode([
                'errors' => [[
                    'title' => 'Testing errors',
                    'detail' => 'Testing errors detail',
                    'status' => 400,
                    'source' => 'test'
                ]]
            ])),
        ]);
        $handler = HandlerStack::create($mock);
        $guzzle = new Guzzle(['handler' => $handler]);
        $client = new Client($guzzle);

        try {
            $client->request('GET', '/');
        } catch (ApiException $e) {
            $this->assertEquals(1, count($e->getErrors()));
            $this->assertEquals('Testing errors detail', $e->getMessage());
            return;
        }

        $this->expectException(ApiException::class);
    }

    
    /**
     * @test
     */
    public function get_sends_get_requests()
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => [
                    'id' => 1,
                ],
                'meta' => [],
            ])),
        ]);
        $container = [];
        $history = Middleware::history($container);
        $handler = HandlerStack::create($mock);
        $handler->push($history);
        $guzzle = new Guzzle(['handler' => $handler]);
        $client = new Client($guzzle);

        $response = $client->get("/");
        
        $this->assertEquals(1, count($container));
        $this->assertEquals('GET', $container[0]['request']->getMethod());

        $this->assertFalse($response instanceof Page);
        $this->assertTrue($response instanceof Entity);
        $this->assertEquals(1, $response->id);
    }

    /**
     * @test
     */
    public function get_sends_get_requests_and_handles_paginated_responses()
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => [
                    [
                        'id' => 1,
                    ]
                ],
                'meta' => [
                    'location' => 'https://api.ukfast.io/pss/v1/requests/1'
                ]
            ])),
        ]);
        $container = [];
        $history = Middleware::history($container);
        $handler = HandlerStack::create($mock);
        $handler->push($history);
        $guzzle = new Guzzle(['handler' => $handler]);
        $client = new Client($guzzle);

        $response = $client->get("/");
        
        $this->assertEquals(1, count($container));
        $this->assertEquals('GET', $container[0]['request']->getMethod());

        $this->assertTrue($response instanceof Page);
    }

    /**
     * @test
     */
    public function create_sends_post_requests()
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => [
                    'id' => 1,
                ],
                'meta' => [
                    'location' => 'https://api.ukfast.io/pss/v1/requests/1'
                ]
            ])),
        ]);
        $container = [];
        $history = Middleware::history($container);
        $handler = HandlerStack::create($mock);
        $handler->push($history);
        $guzzle = new Guzzle(['handler' => $handler]);
        $client = new Client($guzzle);
        
        $client->create("/", ['name' => 'bing']);
        
        $this->assertEquals(1, count($container));
        $this->assertEquals('POST', $container[0]['request']->getMethod());
        $this->assertEquals('{"name":"bing"}', $container[0]['request']->getBody()->getContents());
    }

    /**
     * @test
     */
    public function update_sends_patch_requests()
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'data' => [
                    'id' => 1,
                ],
                'meta' => [
                    'location' => 'https://api.ukfast.io/pss/v1/requests/1'
                ]
            ])),
        ]);
        $container = [];
        $history = Middleware::history($container);
        $handler = HandlerStack::create($mock);
        $handler->push($history);
        $guzzle = new Guzzle(['handler' => $handler]);
        $client = new Client($guzzle);
        
        $client->update("/", ['name' => 'bing']);

        $this->assertEquals(1, count($container));
        $this->assertEquals('PATCH', $container[0]['request']->getMethod());
        $this->assertEquals('{"name":"bing"}', $container[0]['request']->getBody()->getContents());
    }

    /**
     * @test
     */
    public function destroy_sends_delete_requests()
    {
        $mock = new MockHandler([
            new Response(204),
        ]);
        $container = [];
        $history = Middleware::history($container);
        $handler = HandlerStack::create($mock);
        $handler->push($history);
        $guzzle = new Guzzle(['handler' => $handler]);
        $client = new Client($guzzle);
        
        $client->destroy("/");
        
        $this->assertEquals(1, count($container));
        $this->assertEquals('DELETE', $container[0]['request']->getMethod());
    }
}

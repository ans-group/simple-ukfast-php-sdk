<?php

namespace Tests;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use UKFast\SimpleSDK\Client;
use UKFast\SimpleSDK\Exceptions\ValidationException;
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
     * @dataProvider writeFunctionProvider
     */
    public function handles_validation_exceptions_on_create_and_update($function)
    {
        $responseError = [
            'title' => 'Testing errors',
            'detail' => 'Testing errors detail',
            'status' => 422,
            'source' => 'test'
        ];

        $mock = new MockHandler([
            new Response(422, [], json_encode([
                'errors' => [$responseError]
            ])),
        ]);
        $handler = HandlerStack::create($mock);
        $guzzle = new Guzzle(['handler' => $handler]);
        $client = new Client($guzzle);

        try {
            $client->$function('POST', '/');
        } catch (ValidationException $e) {
            $this->assertEquals(1, count($e->errors));
            $this->assertEquals('Validation error', $e->getMessage());

            $exceptionError = $e->errors[0];
            $this->assertEquals((object) $responseError, $exceptionError);

            return;
        }

        $this->expectException(ValidationException::class);
    }

    /**
     * @test
     * @dataProvider writeFunctionProvider
     */
    public function doesnt_throw_validation_exceptions_for_other_client_errors($function)
    {
        $responseError = [
            'title' => 'Unauthorized',
            'detail' => 'Unauthorized',
            'status' => 401,
        ];

        $mock = new MockHandler([
            new Response(401, [], json_encode([
                'errors' => [$responseError]
            ])),
        ]);
        $handler = HandlerStack::create($mock);
        $guzzle = new Guzzle(['handler' => $handler]);
        $client = new Client($guzzle);

        $this->expectException(ClientException::class);
        $client->$function('POST', '/');
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
    public function can_send_query_params_as_array_for_get_requests()
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

        $response = $client->get("/", [
            'id:in' => [1, 2, 3],
        ]);
        
        $this->assertEquals(1, count($container));
        $uri = $container[0]['request']->getUri();

        $this->assertEquals('id:in=1,2,3', urldecode($uri->getQuery()));
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
    public function can_send_query_params_as_array_for_post_requests()
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
        
        $client->create("/", ['name' => 'bing'], [
            'id:in' => [1, 2, 3],
        ]);
        
        $this->assertEquals(1, count($container));
        $uri = $container[0]['request']->getUri();

        $this->assertEquals('id:in=1,2,3', urldecode($uri->getQuery()));
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
    public function can_send_query_params_as_array_for_patch_requests()
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
        
        $client->update("/", ['name' => 'bing'], [
            'id:in' => [1, 2, 3],
        ]);
        
        $this->assertEquals(1, count($container));
        $uri = $container[0]['request']->getUri();

        $this->assertEquals('id:in=1,2,3', urldecode($uri->getQuery()));
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

    /**
     * @test
     */
    public function can_send_query_params_as_array_for_delete_requests()
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
        
        $client->destroy("/", [
            'id:in' => [1, 2, 3],
        ]);
        
        $this->assertEquals(1, count($container));
        $uri = $container[0]['request']->getUri();

        $this->assertEquals('id:in=1,2,3', urldecode($uri->getQuery()));
    }

    public function writeFunctionProvider()
    {
        return [
            ['create'],
            ['update'],
        ];
    }
}

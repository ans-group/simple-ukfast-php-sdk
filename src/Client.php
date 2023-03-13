<?php

namespace UKFast\SimpleSDK;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\Utils;
use UKFast\SimpleSDK\Exceptions\ValidationException;

class Client
{
    /**
     * @var GuzzleHttpClient
     */
    protected $guzzle;

    protected $basePath = 'https://api.ukfast.io';

    protected $token;

    protected $persistentHeaders = [];

    public function __construct(GuzzleHttpClient $guzzle)
    {
        $this->guzzle = $guzzle;
    }

    public function auth($token)
    {
        $this->token = $token;
        return $this;
    }

    public function get($path, $params = [], $headers = [])
    {
        return $this->getAsync($path, $params, $headers)->wait();
    }

    public function getAsync($path, $params = [], $headers = [])
    {
        $promise = $this->guzzle->getAsync($this->path($path), [
            'query' => $this->formatQueryParams($params),
            'headers' => $this->headers($headers),
        ]);

        return $promise->then(function ($response) {
            $raw = json_decode($response->getBody()->getContents());

            if (is_array($raw->data)) {
                $meta = isset($raw->meta) ? $raw->meta : null;
                return new Page($raw->data, $meta);
            }
    
            return new Entity($raw->data);
        });
    }

    public function create($path, $body = [], $params = [], $headers = [])
    {
        return $this->createAsync($path, $body, $params, $headers)->wait();
    }

    public function createAsync($path, $body = [], $params = [], $headers = [])
    {
        $promise = $this->guzzle->postAsync($this->path($path), [
            'query' => $this->formatQueryParams($params),
            'headers' => $this->headers($headers),
            'json' => $body,
        ]);

        return $promise->then(function ($response) {
            if ($response->getStatusCode() == 204) {
                return null;
            }

            $raw = json_decode($response->getBody()->getContents());
            
            return new SelfResponse($raw->data, $raw->meta);
        }, function ($e) {
            $this->handleErrorResponse($e);
        });
    }

    public function update($path, $body = [], $params = [], $headers = [], $usePut = false)
    {
        return $this->updateAsync($path, $body, $params, $headers, $usePut)->wait();
    }

    public function updateAsync($path, $body = [], $params = [], $headers = [], $usePut = false)
    {
        $method = ($usePut ? 'put' : 'patch') . 'Async';
        $promise = $this->guzzle->$method($this->path($path), [
            'query' => $this->formatQueryParams($params),
            'headers' => $this->headers($headers),
            'json' => $body,
        ]);

        return $promise->then(function ($response) {
            $raw = json_decode($response->getBody()->getContents());
            
            return new SelfResponse($raw->data, $raw->meta);
        }, function ($e) {
            $this->handleErrorResponse($e);
        });
    }

    public function destroy($path, $params = [], $headers = [])
    {
        return $this->destroyAsync($path, $params, $headers)->wait();
    }

    public function destroyAsync($path, $params = [], $headers = [])
    {
        return $this->guzzle->deleteAsync($this->path($path), [
            'query' => $this->formatQueryParams($params),
            'headers' => $this->headers($headers),
        ]);
    }

    public function concurrently($callback)
    {
        $calls = $callback(new ConcurrentClient($this));

        return Utils::unwrap($calls);
    }

    public function setHeaders($headers)
    {
        $this->persistentHeaders = $headers;
        return $this;
    }
    
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
        return $this;
    }

    protected function headers($headers)
    {
        $headers = array_merge($this->persistentHeaders, $headers);
        return array_merge($headers, [
            'Authorization' => $this->token
        ]);
    }

    protected function path($path)
    {
        return rtrim($this->basePath, '/') . '/' . ltrim($path, '/');
    }

    protected function formatQueryParams($params)
    {
        foreach ($params as &$param) {
            if (is_array($param)) {
                $param = implode(',', $param);
            }
        }
        
        return $params;
    }

    protected function handleErrorResponse($e)
    {
        if (!$e instanceof ClientException) {
            throw $e;
        }

        if ($e->getResponse()->getStatusCode() != 422) {
            throw $e;
        }

        $raw = json_decode($e->getResponse()->getBody());
        throw new ValidationException($raw->errors);
    }
}

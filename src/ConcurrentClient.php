<?php

namespace UKFast\SimpleSDK;

class ConcurrentClient
{
    /** @var Client */
    protected $originalClient;

    public function __construct($client)
    {
        $this->originalClient = $client;
    }

    public function get($path, $params = [], $headers = [])
    {
        return $this->originalClient->getAsync($path, $params, $headers);
    }

    public function create($path, $body, $params = [], $headers = [])
    {
        return $this->originalClient->createAsync($path, $body, $params, $headers);
    }

    public function update($path, $body, $params = [], $headers = [])
    {
        return $this->originalClient->updateAsync($path, $body, $params, $headers);
    }

    public function destroy($path, $params = [], $headers = [])
    {
        return $this->originalClient->destroyAsync($path, $params, $headers);
    }
}
<?php

namespace UKFast\SimpleSDK;

class SelfResponse
{
    protected $data;

    protected $meta;

    public function __construct($data, $meta)
    {
        $this->data = $data;        
        $this->meta = $meta;        
    }

    public function __get($prop)
    {
        return $this->data->{$prop};
    }

    public function meta($prop)
    {
        return $this->meta->{$prop};
    }
}
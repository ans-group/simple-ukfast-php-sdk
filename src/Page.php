<?php

namespace UKFast\SimpleSDK;

class Page
{
    protected $data;

    protected $meta;

    public function __construct($data, $meta)
    {
        $this->data = array_map(fn ($item) => new Entity($item), $data);
        $this->meta = $meta;
    }

    public function getItems()
    {
        return $this->data;
    }
    
    public function getMeta()
    {
        return $this->meta;
    }
}

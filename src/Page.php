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

    public function totalItems()
    {
        return $this->getPagination('total');
    }

    public function totalPages()
    {
        return $this->getPagination('total_pages');
    }

    public function perPage()
    {
        return $this->getPagination('per_page');
    }

    public function currentPage()
    {
        return $this->getPagination('current_page');
    }
    
    private function getPagination($key)
    {
        $pagination = $this->meta->pagination;
        return isset($pagination->{$key}) ? $pagination->{$key} : null;
    }
}

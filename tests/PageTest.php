<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use UKFast\SimpleSDK\Page;
use UKFast\SimpleSDK\Entity;

class PageTest extends TestCase
{
    /**
     * @test
     */
    public function can_get_page_items()
    {
        $page = $this->getSamplePage();

        $expectedResponse = [];
        foreach ($this->getSampleData() as $item) {
            $expectedResponse[] = new Entity($item);
        }

        $this->assertEquals($expectedResponse, $page->getItems());
    }

    /**
     * @test
     */
    public function can_get_page_meta()
    {
        $page = $this->getSamplePage();

        $this->assertEquals($this->getSampleMeta(), $page->getMeta());
    }

    /**
     * @test
     */
    public function can_get_total_items()
    {
        $page = $this->getSamplePage();

        $this->assertEquals(5, $page->totalItems());
    }

    /**
     * @test
     */
    public function can_get_total_pages()
    {
        $page = $this->getSamplePage();

        $this->assertEquals(2, $page->totalPages());
    }

    /**
     * @test
     */
    public function can_get_per_page()
    {
        $page = $this->getSamplePage();

        $this->assertEquals(3, $page->perPage());
    }

    /**
     * @test
     */
    public function can_get_current_page()
    {
        $page = $this->getSamplePage();

        $this->assertEquals(1, $page->currentPage());
    }

    protected function getSamplePage()
    {
        return new Page($this->getSampleData(), $this->getSampleMeta());
    }

    protected function getSampleData()
    {
        return [
            [
                'id' => 1,
                'name' => 'Aleksandr Orlov',
                'job_title' => 'CEO',
            ],
            [
                'id' => 2,
                'name' => 'Sergei',
                'job_title' => 'Head of IT',
            ],
            [
                'id' => 2,
                'name' => 'Yakov',
                'job_title' => 'Toyshop Owner',
            ],
        ];
    }

    protected function getSampleMeta()
    {
        return (object) [
            'pagination' => (object) [
                'total' => 5,
                'count' => 3,
                'per_page' => 3,
                'total_pages' => 2,
                'current_page' => 1,
                'links' => (object) [
                    'next' => '/?page=2',
                    'previous' => null,
                    'first' => '/?page=1',
                    'last' => '/?page=2',
                ]
            ],
        ];
    }
}

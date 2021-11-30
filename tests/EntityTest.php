<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use UKFast\SimpleSDK\Entity;

class EntityTest extends TestCase
{
    protected $entity;

    /**
     * @test
     */
    public function sets_attributes_from_an_array()
    {
        $this->loadSampleEntity();

        $this->assertEquals(1, $this->entity->id);
        $this->assertEquals('John', $this->entity->first_name);
        $this->assertEquals('Doe', $this->entity->last_name);
    }

    /**
     * @test
     */
    public function can_convert_to_an_array()
    {
        $this->loadSampleEntity();
        
        $array = $this->entity->toArray();

        $this->assertEquals(1, $array['id']);
        $this->assertEquals('John', $array['first_name']);
        $this->assertEquals('Doe', $array['last_name']);
    }

    protected function loadSampleEntity()
    {
        $this->entity = new Entity([
            'id' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    }
}

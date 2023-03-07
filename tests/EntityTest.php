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
        $this->assertEquals('test@example.com', $this->entity->email->personal);
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
        $this->assertEquals('test@example.com', $array['email']['personal']);
    }

    /**
     * @test
     */
    public function can_access_as_array()
    {
        $this->loadSampleEntity();

        $this->assertEquals(1, $this->entity['id']);
        $this->assertEquals('John', $this->entity['first_name']);
        $this->assertEquals('Doe', $this->entity['last_name']);
        $this->assertEquals('test@example.com', $this->entity['email']['personal']);
    }

    /**
     * @test
     */
    public function can_serialize_nested_arrays()
    {
        $this->loadSampleEntity();

        $this->assertInstanceOf(Entity::class, $this->entity->contacts[0]);
        $this->assertInstanceOf(Entity::class, $this->entity->contacts[1]);

        $this->assertEquals('John', $this->entity->contacts[0]->name);
        $this->assertEquals('Jane', $this->entity->contacts[1]->name);
    }

    /**
     * @test
     */
    public function does_not_serialize_nested_scalar_values()
    {
        $this->loadSampleEntity();

        $this->assertSame("Mr Jack Doe", $this->entity->aliases[0]);
        $this->assertSame("Mr J Doe", $this->entity->aliases[1]);
    }

    protected function loadSampleEntity()
    {
        $this->entity = new Entity([
            'id' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => (object) [
                'personal' => 'test@example.com'
            ],
            'contacts' => [
                [
                    'name' => 'John',
                    'email' => 'john@example.com'
                ],
                [
                    'name' => 'Jane',
                    'email' => 'jane@example.com',
                ]
            ],
            'aliases' => [
                'Mr Jack Doe',
                'Mr J Doe'
            ]
        ]);
    }
}

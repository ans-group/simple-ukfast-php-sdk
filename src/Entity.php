<?php

namespace UKFast\SimpleSDK;

use ArrayAccess;

class Entity implements ArrayAccess
{
    /**
     * @var object
     */
    protected $props;

    public function __construct($props)
    {
        $this->props = $this->findSubEntities($props);
    }

    public function __get($prop)
    {
        if (!isset($this->props->{$prop})) {
            return null;
        }

        return $this->props->{$prop};
    }

    public function __set($prop, $value)
    {
        $this->props->{$prop} = $value;
    }
    
    public function toArray()
    {
        return $this->props;
    }
    
    public function offsetExists($offset)
    {
        return isset($this->props[$offset]);
    }
    
    public function offsetGet($offset)
    {
        return $this->props[$offset];
    }
    
    public function offsetSet($offset, $value)
    {
        $this->props[$offset] = $value;
    }
    
    public function offsetUnset($offset)
    {
        unset($this->props[$offset]);
    }

    private function findSubEntities($props)
    {
        $newProps = [];
        foreach ($props as $name => $value) {
            if (is_object($value)) {
                $newProps[$name] = new Entity($value);
                continue;
            }
            $newProps[$name] = $value;
        }

        return (object) $newProps;
    }
}

<?php

namespace UKFast\SimpleSDK;

class Entity
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

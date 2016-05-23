<?php

namespace PHPixie\DI\Container;

class Group extends \PHPixie\DI\Container
{
    protected $path;

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function get($name)
    {
        return $this->processGet($name);
    }

    public function call($name, $params)
    {
        return $this->processCall($name, $params);
    }

    public function __call($name, $params)
    {
        return $this->processCall($name, $params);
    }

    protected function fullPath($name)
    {
        return $this->path.'.'.$name;
    }
}

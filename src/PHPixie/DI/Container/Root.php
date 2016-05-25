<?php

namespace PHPixie\DI\Container;

abstract class Root extends \PHPixie\DI\Container
{
    static protected $instances = array();

    protected $currentContainer;

    public function __construct()
    {
        $this->currentContainer = $this;

        $this->configure();
        static::$instances[get_called_class()] = $this;
    }

    abstract protected function configure();

    protected function value($key, $value)
    {
        $this->currentContainer->addValue($key, $value);
    }

    protected function callback($key, $callback)
    {
        $this->currentContainer->addCallback($key, $callback);
    }

    protected function build($key, $callback)
    {
        $this->currentContainer->addBuildCallback($key, $callback);
    }

    protected function instance($key, $class, $arguments)
    {
        $this->build($key, function () use($class, $arguments) {
            return $this->create($class, $arguments);
        });
    }

    protected function group($key, $callback)
    {
        $path = $this->currentContainer->fullPath($key);

        $container = new Group($path);
        $previous = $this->currentContainer;

        $this->currentContainer = $container;
        $callback();
        $this->currentContainer = $previous;

        $this->addValue($key, $container);
    }

    protected function create($class, $arguments)
    {
        foreach($arguments as $key => $value) {
            if(is_string($value) && $value{0} == '@') {
                $arguments[$key] = $this->get(substr($value, 1));
            }
        }

        $reflection  = new \ReflectionClass($class);
        return $reflection->newInstanceArgs($arguments);
    }

    public function __call($name, $params)
    {
        if($name === 'get') {
            if(empty($params)) {
                return $this;
            }

            return $this->processGet($params[0]);
        }

        if($name === 'call') {
            return $this->processCall($params[0], $params[1]);
        }

        return $this->processCall($name, $params);
    }

    public static function __callStatic($name, $params)
    {
        return call_user_func_array(
            array(static::requireInstance(), '__call'),
            array($name, $params)
        );
    }

    public static function requireInstance()
    {
        $class = get_called_class();
        if(!isset(static::$instances[$class])) {
            throw new \PHPixie\DI\Exception("This container has not been constructed yet");
        }

        return static::$instances[$class];
    }
}

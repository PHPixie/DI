<?php

namespace PHPixie\DI;

abstract class Container
{
    protected $values = array();
    protected $callbacks = array();
    protected $buildCallbacks = array();

    protected function addValue($key, $value)
    {
        $this->values[$key] = $value;
    }

    protected function addCallback($key, $callback)
    {
        $this->callbacks[$key] = $callback;
    }

    protected function addBuildCallback($key, $callback)
    {
        $this->buildCallbacks[$key] = $callback;
    }

    protected function processGet($name)
    {
        return $this->getBySplitPath($this->splitPath($name));
    }

    protected function processCall($name, $params)
    {
        return $this->getBySplitPath($this->splitPath($name), true, $params);
    }

    protected function splitPath($name)
    {
        return explode('.', $name);
    }

    protected function getBySplitPath($path, $isCall = false, $params = array())
    {
        $value = array_shift($path);

        if(empty($path)) {
            return $this->getValue($value, $isCall, $params);
        }

        $value = $this->getValue($value);

        if($value instanceof Container) {
            return $value->getBySplitPath($path, $isCall, $params);
        }

        $last = array_pop($path);

        foreach($path as $step) {
            $value = $value->$step();
        }

        if($isCall) {
            $value = call_user_func_array(array($value, $last), $params);
        } else {
            $value =  $value = $value->$last();
        }

        return $value;
    }

    protected function getValue($name, $isCall = false, $callParams = null)
    {
        if(!array_key_exists($name, $this->values)) {

            if(isset($this->buildCallbacks[$name])) {
                $this->values[$name] = $this->buildCallbacks[$name]();
                unset($this->buildCallbacks[$name]);
                return $this->values[$name];
            }

            if(isset($this->callbacks[$name])) {
                if($isCall) {
                    return call_user_func_array($this->callbacks[$name], $callParams);
                }

                return $this->callbacks[$name];
            }

            $fullPath = $this->fullPath($name);
            throw new \PHPixie\DI\Exception("'$fullPath' is not defined");
        }

        return $this->values[$name];
    }

    protected function fullPath($name)
    {
        return $name;
    }
}

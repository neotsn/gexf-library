<?php

namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use Codeception\Module;
use ReflectionClass;

class Unit extends Module
{
    /**
     * To test private methods
     *
     * @param object $object
     * @param string $methodName
     * @param array  $parameters ['param1Value', 'param2Value', ... 'paramNValue']
     *
     * @return mixed
     * @throws \ReflectionException
     */
    public function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $method = (new ReflectionClass(get_class($object)))->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}

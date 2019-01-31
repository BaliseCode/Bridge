<?php
// Abstraction for FrontRoute and BackRoute
namespace Balise\Bridge;

abstract class Route {
    static function get($route, $callback,$template = null) {
        static::$routes[] = array('GET',$route,$callback,$template);
    }
    static function post($route, $callback,$template = null) {
        static::$routes[] = array('POST',$route,$callback,$template);
    }

    /* Make sure the function is callable */
    public static function convertToCallable($callable) {
        if (!is_callable($callable)) {
            /* If the format Class@method is used */
            /* convert it to callable */ 
            if (is_string($callable)) {
                $parts = explode('@',$callable);
                if (class_exists($parts[0])) {
                    $object = new $parts[0]();
                    $callable = array($object, $parts[1]);
                }
            }

        }
        // If the callable function is still not callable, return empty function
        if (!is_callable($callable)) {
            $callable = function() {
                return '';
            };
        }
        return $callable;
    }
    /* This is where the magic happens (routes get selected) */
    public static function routesResolver($routes,$prefix="") {
        $selected = null;
        foreach($routes as $route) {
            if ($_SERVER['REQUEST_METHOD'] === $route[0]) {

                // Store the keys used
                preg_match_all('/{(\w+)}/i',$route[1],$keys);

                // Make the attibutes value as wildcard
                $resolver = preg_replace('/{\w+}/i','([^\/]+)',$route[1]);

                // If the route match
                if (preg_match('#^/'.$prefix.$resolver.'/?$#i', $_SERVER['REQUEST_URI'], $data)) {
                    $selected = $route;

                    // Make sure the attribute 2 is a callback
                    $selected[2] = self::convertToCallable($selected[2]);

                    // Match the value to the keys and add it to the selected route
                    $values = array();
                    foreach($keys[1] as $key=>$id) {
                        $values[$id] = $data[$key+1];
                    }
                    $selected[] = $values;

                    // Return selected route with data
                    return $selected;
                    break;
                }
            }
        }
        return $_SERVER['REQUEST_URI'];
    }
}





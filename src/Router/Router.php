<?php

namespace Mini\Router;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Parser;

class Router
{
    private static $basePath;

    /**
     * @param mixed $basePath
     */
    public static function setBasePath($basePath)
    {
        self::$basePath = $basePath;
    }


    public static function loadConfigFile($config) {
        if (!file_exists(self::$basePath . $config)) {
            throw new \Exception("Yaml config file not found.");
        }

        $yaml = new Parser();
        try {
            $parsed = $yaml->parse(file_get_contents(self::$basePath . '/src/routers/' . $config));

            print_r($parsed);

        } catch (ParseException $e) {
            printf("Unable to parse the YAML string: %s", $e->getMessage());
        }
    }
}
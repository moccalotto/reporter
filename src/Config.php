<?php

namespace Moccalotto\Reporter;

class Config
{
    protected $config = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public static function fromArray($config, array $defaults = [])
    {
        Ensure::isArray($config, 'The root element in the config must be an object');

        return new static(array_replace($defaults, $config));
    }

    public static function fromFile($file, array $defaults = [])
    {
        Ensure::fileIsReadable($file);

        $jsonString = file_get_contents($file);

        Ensure::validJson($jsonString);

        $config = json_decode($jsonString, true);

        return static::fromArray($config, $defaults);
    }

    public static function fromFileIfExists($file, array $defaults = [])
    {
        if (!file_exists($file)) {
            return new static($defaults);
        }

        return static::fromFile($file, $defaults);
    }

    public function get($key, $default = null)
    {
        $key_parts = explode('.', $key);

        $rest_of_key = $key;

        $current = $this->config;

        foreach ($key_parts as $sub_key) {
            if (isset($current[$rest_of_key])) {
                return $current[$rest_of_key];
            }

            $rest_of_key = substr($rest_of_key, strlen($sub_key) + 1);

            if (isset($current[$sub_key])) {
                $current = $current[$sub_key];
                continue;
            }

            if (isset($current->$sub_key)) {
                $current = $current->$sub_key;
                continue;
            }

            return $default;
        }

        return $current;
    }

    public function dumpToFile($file)
    {
        file_put_contents($file, json_encode($this->config, JSON_PRETTY_PRINT));
    }
}

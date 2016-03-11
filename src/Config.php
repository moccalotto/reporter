<?php

namespace Moccalotto\Reporter;

use RuntimeException;

class Config
{
    protected $config = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Create an instance from array.
     *
     * Merge the candidate config with the defaults.
     *
     * @param array $config
     * @param array $default
     * @return Config
     */
    public static function fromArray($config, array $defaults = [])
    {
        Ensure::isArray($config, 'The root element in the config must be an object');

        return new static(array_replace($defaults, $config));
    }

    /**
     * Create an instance from a file
     *
     * Merge the config data from the file with the defaults.
     *
     * @param string $file The filename
     * @param array $default
     * @return Config
     */
    public static function fromFile($file, array $defaults = [])
    {
        Ensure::fileIsReadable($file);

        $config = require $file;

        return static::fromArray($config, $defaults);
    }

    /**
     * Create an instance from a file, but do not fail if file is not accessible.
     *
     * Merge the config data from the file with the defaults.
     *
     * @param string $file The filename
     * @param array $default
     * @return Config
     */
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

    public function set($key, $value)
    {
        $key_parts = explode('.', $key);

        $current = &$this->config;

        $last = array_pop($key_parts);

        foreach ($key_parts as $sub_key) {
            if (isset($current[$sub_key])) {
                $current = &$current[$sub_key];
                continue;
            }

            if (isset($current->$sub_key)) {
                $current = &$current->$sub_key;
                continue;
            }

            if (is_array($current)) {
                $current[$sub_key] = [];
                $current = &$current[$sub_key];
                continue;
            }

            if (is_object($current)) {
                $current->$sub_key = [];
                $current = &$current->$sub_key;
                continue;
            }

            throw new RuntimeException(sprintf(
                'Cannot set config for key "%s". Cannot add %s because parent element is neither object nor array',
                $key,
                $sub_key
            ));
        }

        if (is_array($current)) {
            $current[$last] = $value;

            return $this;
        }

        if (is_object($current)) {
            $current->$last = $value;

            return $this;
        }

        throw new RuntimeException(sprintf(
            'Cannot set config for key "%s". Cannot add %s because parent element is neither object nor array',
            $key,
            $last
        ));
    }

    public function dumpToFile($file)
    {
        $exported = var_export($this->config, true);

        $document = <<<DOC
<?php
return $exported;

DOC;

        file_put_contents($file, $document);
    }
}

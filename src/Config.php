<?php

namespace Moccalotto\Reporter;

class Config
{
    protected $defaults = [
        'reportToUrl' => 'https://httpbin.org/post',

        'logging' => [
            'file' => 'reporter.log',
            'minLevel' => 'warning',
        ],

        'daemon' => [
            'enabled' => false,
            'interval' => 300,
        ],

        'signing' => [
            'key' => '@git-commit@',
            'algorithm' => 'sha256',
        ],

        'http' => [
            'follow_location' => true,
            'max_redirects' => 20,
            'user_agent' => 'Reporter',
            'timeout' => 10,
        ],

        'https' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false,
        ],
    ];

    protected $config = [];

    public function __construct(array $config)
    {
        $this->config = array_replace($this->defaults, $config);
    }

    public static function fromArray($config)
    {
        Ensure::isArray($config, 'The json in the config file is malformed. Root element must be an object.');

        return new static($config);
    }

    public static function fromFile($file)
    {
        Ensure::fileIsReadable($file);

        $jsonString = file_get_contents($file);

        Ensure::validJson($jsonString);

        $config = json_decode($jsonString, true);

        return static::fromArray($config);
    }

    public static function fromFileIfExists($file)
    {
        if (!file_exists($file)) {
            return new static([]);
        }

        return static::fromFile($file);
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

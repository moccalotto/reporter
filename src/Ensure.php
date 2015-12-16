<?php

namespace Moccalotto\Reporter;

use Moccalotto\Reporter\Exceptions\EnsureException;

class Ensure
{
    protected static function terminate($message, $code)
    {
        echo $message;
        flush();
        die((int) $code);
    }

    public static function that($condition, $message, $exitCode = 1)
    {
        if (!$condition) {
            throw new EnsureException($message, $exitCode);
        }
    }

    public static function fileExists($file)
    {
        static::that(file_exists($file), sprintf(
            'File "%s" does not exist',
            $file
        ));

        static::that(is_file($file), sprintf(
            'The path "%s" does not point to a file',
            $file
        ));
    }

    public static function fileIsReadable($file)
    {
        static::fileExists($file);

        static::that(is_readable($file), sprintf(
            'The file "%s" is not readable',
            $file
        ));
    }

    public static function isResource($data, $error_message = 'Data must be a resource')
    {
        static::that(is_resource($data), $error_message);
    }

    public static function isArray($data, $error_message = 'Data must be an array')
    {
        static::that(is_array($data), $error_message);
    }

    public static function isObject($data, $error_message = 'Data must be an object')
    {
        static::that(is_object($data), $error_message);
    }

    public static function validJson($jsonString, $error_message = null)
    {
        $data = json_decode($jsonString, true);

        static::isEqual(json_last_error(), JSON_ERROR_NONE, $error_message ?: sprintf(
            'Could not decode data json data: %s',
            json_last_error_msg()
        ));
    }

    public static function isEqual($var1, $var2, $message = 'Unexpected value')
    {
        static::that($var1 == $var2, $message);
    }

    public static function matches($pattern, $string, $message = null)
    {
        if (preg_match($pattern, $string)) {
            return;
        }

        if (null === $message) {
            $message = sprintf(
                'The string "%s" does not adhere to the pattern "%s"',
                $string,
                $pattern
            );
        }

        static::terminate($message);
    }
}

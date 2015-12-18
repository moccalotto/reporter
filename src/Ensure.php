<?php

namespace Moccalotto\Reporter;

use Moccalotto\Reporter\Exceptions\EnsureException;

/**
 * Class for handling assertions.
 */
class Ensure
{
    /**
     * Ensure that a given condition is true.
     *
     * @param bool $condition
     * @param string $message
     *
     * @throws EnsureException
     */
    public static function that($condition, $message)
    {
        if (!$condition) {
            throw new EnsureException($message);
        }
    }

    /**
     * Ensure that a given file exists (and is indeed a file).
     *
     * @param string $file  The complete path of the file
     *
     * @throws EnsureException
     */
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

    /**
     * Ensure that a file is readable.
     *
     * @param string $file  The complete path of the file
     *
     * @throws EnsureException
     */
    public static function fileIsReadable($file)
    {
        static::fileExists($file);

        static::that(is_readable($file), sprintf(
            'The file "%s" is not readable',
            $file
        ));
    }

    /**
     * Ensure that a variable is a resource
     *
     * @param mixed $data
     *
     * @throws EnsureException
     */
    public static function isResource($data, $error_message = 'Data must be a resource')
    {
        static::that(is_resource($data), $error_message);
    }

    /**
     * Ensure that a variable is an array
     *
     * @param mixed $data
     *
     * @throws EnsureException
     */
    public static function isArray($data, $error_message = 'Data must be an array')
    {
        static::that(is_array($data), $error_message);
    }

    /**
     * Ensure that a variable is an object
     *
     * @param mixed $data
     *
     * @throws EnsureException
     */
    public static function isObject($data, $error_message = 'Data must be an object')
    {
        static::that(is_object($data), $error_message);
    }

    /**
     * Ensure that a string contains valid json
     *
     * @param string $jsonString
     * @param string $error_message
     *
     * @throws EnsureException
     */
    public static function validJson($jsonString, $error_message = null)
    {
        $data = json_decode($jsonString, true);

        static::isEqual(json_last_error(), JSON_ERROR_NONE, $error_message ?: sprintf(
            'Could not decode data json data: %s',
            json_last_error_msg()
        ));
    }

    /**
     * Ensure that two variables are equal (==)
     *
     * @param mixed $var1
     * @param mixed $var1
     * @param mixed $message
     *
     * @throws EnsureException
     */
    public static function isEqual($var1, $var2, $message = 'Unexpected value')
    {
        static::that($var1 == $var2, $message);
    }

    /**
     * @param string $pattern
     * @param string $string
     * @param string|null $message
     *
     * @throws EnsureException
     */
    public static function matches($pattern, $string, $message = null)
    {
        if (null === $message) {
            $message = sprintf(
                'The string "%s" does not adhere to the pattern "%s"',
                $string,
                $pattern
            );
        }

        static::that(preg_match($pattern, $string), $message);
    }
}

<?php

namespace Moccalotto\Reporter;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;

class SyslogLogger implements LoggerInterface
{
    use LoggerTrait;

    protected $map = [
        LogLevel::EMERGENCY => LOG_EMERG,
        LogLevel::ALERT     => LOG_ALERT,
        LogLevel::CRITICAL  => LOG_CRIT,
        LogLevel::ERROR     => LOG_ERR,
        LogLevel::WARNING   => LOG_WARNING,
        LogLevel::NOTICE    => LOG_NOTICE,
        LogLevel::INFO      => LOG_INFO,
        LogLevel::DEBUG     => LOG_DEBUG,
    ];


    /**
     * Get a one-line text-representation of a variable'
     */
    protected function textRepresentation($var)
    {
        switch (gettype($var)) {
        case 'resource':
            // Resources cannot be serialized. So we just diplsay their type.
            return 'resource:'. get_resource_type($var);
        case 'string':
            // Ensure that the string is just a single line,
            // but without being surrounded by double quotes.
            return substr(json_encode($var), 1, -1);
        default:
            // integers, doubles, objects and arrays
            // are all json encoded.
            return json_encode($var);
        }
    }

    /**
     * Interpolates context values into the message placeholders.
     */
    protected function interpolate($message, array $context)
    {
        // Build a replacement array with braces around the context keys
        $tokens = [];
        foreach ($context as $key => $val) {
            $token = '{' . $key . '}';
            $tokens[$token] = $this->textRepresentation($val);
        }

        // Interpolate replacement values into the message and return
        return strtr($message, $tokens);
    }

    /**
     * Constructor.
     *
     * @param string $minLogLevel
     */
    public function __construct($minLogLevel)
    {
        $this->minLogLevel = $this->syslogLevel($minLogLevel);
    }

    /**
     * Convert a PSR-3 log level to a PHP LOG_* syslog level
     *
     * @param string $level
     *
     * @return int
     */
    public function syslogLevel($level)
    {
        return $this->map[$level];
    }

    /**
     * Will this logger handle the given log level
     *
     * @param string $level
     *
     * @return bool
     */
    public function handlesLevel($level)
    {
        return $this->syslogLevel($level) <= $this->minLogLevel;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     *
     * @return null
     */
    public function log($level, $message, array $context = [])
    {
        if (!$this->handlesLevel($level)) {
            return false;
        }

        return syslog(
            $this->syslogLevel($level),
            $this->interpolate($message, $context)
        );
    }
}

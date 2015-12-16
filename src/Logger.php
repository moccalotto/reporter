<?php

namespace Moccalotto\Reporter;

class Logger
{
    protected $minLevel;
    protected $file;
    protected $handle;

    protected function parseLogLevel($logLevel)
    {
        if (defined(sprintf('LOG_%s', strtoupper($logLevel)))) {
            return constant(sprintf('LOG_%s', strtoupper($logLevel)));
        }

        if (defined($logLevel)) {
            return constant($logLevel);
        }

        return (int) $logLevel;
    }

    public function __construct($file, $minLevel)
    {
        $this->minLevel = $this->parseLogLevel($minLevel);

        Ensure::fileIsReadable($file);

        $this->handle = fopen($file, 'a');
    }

    public function __destruct()
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
            $this->handle = null;
        }
    }

    public function canLog($logLevel)
    {
        return $this->minLevel <= $this->parseLogLevel($logLevel);
    }

    public function log($logLevel, $message)
    {
        if (!$this->canLog($logLevel)) {
            return false;
        }

        fwrite($this->handle, sprintf(
            '[%s] %s: %s%s',
            gmdate('Y-m-d H:i:s e'),
            $logLevel,
            $message,
            PHP_EOL
        ));

        fflush($this->handle);
    }

    public function emergency($message)
    {
        return $this->log(LOG_EMERG, $message);
    }

    public function alert($message)
    {
        return $this->log(LOG_ALERT, $message);
    }

    public function critical($message)
    {
        return $this->log(LOG_ALERT, $message);
    }

    public function error($message)
    {
        return $this->log(LOG_ERR, $message);
    }

    public function warning($message)
    {
        return $this->log(LOG_WARNING, $message);
    }

    public function notice($message)
    {
        return $this->log(LOG_NOTICE, $message);
    }

    public function info($message)
    {
        return $this->log(LOG_INFO, $message);
    }

    public function debug($message)
    {
        return $this->log(LOG_DEBUG, $message);
    }
}

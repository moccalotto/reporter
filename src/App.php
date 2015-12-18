<?php

namespace Moccalotto\Reporter;

use Psr\Log\LoggerTrait;
use Pimple\Container;
use Exception;

/**
 * App class.
 */
class App extends Container
{
    use LoggerTrait;

    /**
     * Get a config entry (via the 'config' service)
     *
     * @param string $key The name of the config entry to find.
     * @param mixed $default The default data to return in case the config entry was not set.
     *
     * @return mixed
     */
    public function cfg($key, $default = null)
    {
        return $this['config']->get($key, $default);
    }

    /**
     * Get a command line argument (via the 'args' service)
     *
     * @param string $key
     *
     * @return mixed
     */
    public function arg($key)
    {
        return $this['args'][$key];
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
        return $this['logger']->log($level, $message, $context);
    }

    /**
     * Run the application
     */
    public function run()
    {
        $this->handleArgumentActions();

        if ($this->cfg('daemon.enabled')) {
            $this->runAsDaemon();
        } else {
            $this->sendReport();
        }
    }

    /**
     * Handle any actions determined by the arguments
     *
     * --version prints the version and terminates execution
     * --dump-config [file] dumps the config variables to a file and terminates execution
     */
    protected function handleArgumentActions()
    {
        if ($this->arg('version')) {
            die($this['version'].PHP_EOL);
        }

        if ($this->arg('dump-config')) {
            $this['config']->dumpToFile($this->arg('dump-config'));
            die('Config file dumped'.PHP_EOL);
        }
    }

    /**
     * Keep the app running as a daemon.
     *
     * The app sends a report every X seconds as defined in the daemon.interval config setting.
     *
     * @throws Exceptions\EnsureException if config settings are wrong
     */
    protected function runAsDaemon()
    {
        // interval (in seconds) between sending reports.
        $interval = $this->cfg('daemon.interval');

        Ensure::that(
            $interval > $this->cfg('http.timeout'),
            'daemon.interval must be larger than http.timeout in order to run as daemon'
        );

        // start of the reporting
        $start = microtime(true);

        // A practically endless loop
        // 64 bit signed integers gives us 292,471,208,678 years of runtime with 1-second intervals
        // 32 bit signed integers gives us 69 years of runtime with 1-second intervals
        for ($i = 0; true; ++$i) {

            // The ideal starting time of this iteration
            $this_iteration_start = $start + $interval * $i;

            // The ideal starting time of the next iteration
            $next_iteration_start = $this_iteration_start + $interval;

            // Send the report. This may take a few seconds.
            try {
                $this->sendReport();
            } catch (Exception $e) {
                $this->warning('Could not send report to remote server: {message}', ['message' => $e->getMessage()]);
            }

            // Sleep until next iteration should start
            time_sleep_until($next_iteration_start);
        }
    }

    /**
     * Send a report to the foreign server.
     */
    protected function sendReport()
    {
        $payload = json_encode($this['sysinfo']->all());

        $uri = $this->cfg('report.uri');

        $signature = $this['signer']->signature($payload);

        $headers = [
            'Content-Type: application/json',
            sprintf('X-Signature: %s', $signature),
        ];

        $this['http']->makeRequest(
            $uri,
            $payload,
            $headers,
            'POST'
        );
    }
}

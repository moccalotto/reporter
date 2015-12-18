<?php

namespace Moccalotto\Reporter;

use Pimple\Container;

/**
 * App class.
 */
class App extends Container
{
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
     * Run the application
     */
    public function run()
    {
        $this->handleArgs();

        if ($this->cfg('daemon.enabled')) {
            $this->runAsDaemon();
        } else {
            $this->sendReport();
        }
    }

    protected function handleArgs()
    {
        if ($this->arg('version')) {
            die($this['version'].PHP_EOL);
        }

        if ($this->arg('dump-config')) {
            $this['config']->dumpToFile($this->arg('dump-config'));
            die('Config file dumped'.PHP_EOL);
        }
    }

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

        // an almost never ending while loop
        for ($i = 0; true; ++$i) {

            // The ideal starting time of this iteration
            $this_iteration_start = $start + $interval * $i;

            // The ideal starting time of the next iteration
            $next_iteration_start = $this_iteration_start + $interval;

            // Send the report. This may take a few seconds.
            $this->sendReport();

            // Sleep until next iteration should start
            time_sleep_until($next_iteration_start);
        }
    }

    protected function sendReport()
    {
        $payload = json_encode($this['sysinfo']->all());

        $url = $this->cfg('reportToUrl');

        $signature = $this['signer']->signature($payload);

        $headers = [
            'Content-Type: application/json',
            sprintf('X-Signature: %s', $signature),
        ];

        $this['http']->makeRequest(
            $url,
            $payload,
            $headers,
            'POST'
        );
    }
}

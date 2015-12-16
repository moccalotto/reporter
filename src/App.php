<?php

namespace Moccalotto\Reporter;

use Pimple\Container;

class App extends Container
{
    public function cfg($key, $default = null)
    {
        return $this['config']->get($key, $default);
    }

    public function arg($key)
    {
        return $this['args'][$key];
    }

    public function run()
    {
        $this->handleArgs();

        if ($this->cfg('daemon.enabled')) {
            return $this->runAsDaemon();
        } else {
            return $this->sendReport();
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
            yield $this->sendReport();

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

        return $this['http']->makeRequest(
            $url,
            $payload,
            $headers,
            'POST'
        );
    }
}

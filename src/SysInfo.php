<?php

namespace Moccalotto\Reporter;

class SysInfo
{
    protected $app;

    protected $static = [];

    public function __construct(App $app)
    {
        $this->app = $app;

        $this->static = [
            'ip' => gethostbyname(gethostname()),
            'hostname' => gethostname(),
            'cpus' => $this->cpuCount(),
            'uname' => [
                'sysname' => php_uname('s'),
                'nodename' => php_uname('n'),
                'release' => php_uname('r'),
                'version' => php_uname('v'),
                'machine' => php_uname('m'),
            ],
        ];
    }

    protected function cpuCountFromProcFile($file)
    {
        $cpuinfo = file_get_contents('/proc/cpuinfo');

        if (!preg_match_all('/^processor/m', $cpuinfo, $matches)) {
            return 1;
        }

        return count($matches[0]);
    }

    protected function cpuCountOnWindows()
    {
        $process = @popen('wmic cpu get NumberOfCores', 'rb');

        if (false === $process) {
            return 1;
        }

        fgets($process);

        $numCpus = intval(fgets($process));

        pclose($process);

        return $numCpus;
    }

    protected function cpuCountFromSysCtl()
    {
        $process = @popen('sysctl -a', 'rb');

        if (false === $process) {
            return 1;
        }

        $output = stream_get_contents($process);

        if (!preg_match('/hw.ncpu: (\d+)/', $output, $matches)) {
            return 1;
        }

        $numCpus = intval($matches[1][0]);

        pclose($process);

        return $numCpus;
    }

    protected function cpuCount()
    {
        static $numCpus = null;

        if (null !== $numCpus) {
            return $numCpus;
        }

        if (is_file('/proc/cpuinfo') && is_readable('/proc/cpuinfo')) {
            return $numCpus = $this->cpuCountFromProcFile();
        }

        if ('WIN' == strtoupper(substr(PHP_OS, 0, 3))) {
            return $numCpus = $this->cpuCountOnWindows();
        }

        return $numCpus = $this->cpuCountFromSysCtl();
    }

    public function all()
    {
        return array_replace(
            $this->static,
            $this->load(),
            $this->disk(),
            $this->report()
        );
    }

    public function load()
    {
        $load_avg = sys_getloadavg();
        $cpu_count = $this->cpuCount();
        $max_load = max($load_avg);

        if ($max_load >= 0.7 * $cpu_count) {
            $status = 'Warning';
        } elseif ($max_load >= 0.5 * $cpu_count) {
            $status = 'Warking';
        } else {
            $status = 'Idle';
        }

        return [
            'load' => [
                'average' => [
                    '1min' => $load_avg[0],
                    '5min' => $load_avg[1],
                    '15min' => $load_avg[2],
                ],
                'interpreted%' => [
                    '1min' => 100.0 * $load_avg[0] / $cpu_count,
                    '5min' => 100.0 * $load_avg[1] / $cpu_count,
                    '15min' => 100.0 * $load_avg[2] / $cpu_count,
                ],
                'status' => $status,
            ],
        ];
    }

    public function disk()
    {
        $disks = array_unique(array_merge(['/'], $this->app->cfg('partitions', [])));
        $res = [];
        foreach ($disks as $partition) {
            $total = disk_total_space($partition);
            $free = disk_free_space($partition);
            $res[$partition] = array(
                'total' => $total,
                'free' => $free,
                'free%' => 100.0 * $free / $total,
            );
        }

        return ['partition' => $res];
    }

    public function report()
    {
        return [
            'report' => [
                'software' => $this->app['version'],
                'timestamp' => microtime(true),
                'nonce' => mt_rand(),
            ],
        ];
    }
}

<?php

namespace Moccalotto\Reporter;

use Commando\Command;

require_once 'vendor/autoload.php';

$app = new App([
    'version' => '@git-version@',

    'args' => function ($app) {
        $args = new Command();

        $args->argument()
            ->aka('config')
            ->describedAs('Use a given configuration file. Defaults to reporter.php if it exists.')
            ->defaultsTo('reporter.php')
            ->file()
            ->must(function ($file) {
                Ensure::fileIsReadable($file);
                return true;
            });

        $args->option('d')
            ->aka('dump-config')
            ->map(function ($file) {
                if (null === $file) {
                    return 'php://output';
                }

                return $file;
            })
            ->must(function ($file) {
                Ensure::that(
                    !is_dir($file),
                    sprintf('Cannot dump config to %s. It is a directory', $file)
                );

                if (is_file($file)) {
                    Ensure::that(
                        is_writable($file),
                        sprintf('Cannot dump config to %s. It is not writable', $file)
                    );
                }

                return true;
            })
            ->describedAs('Dump the config to the the specified file. Defaults to stdout.');

        $args->option('k')
            ->aka('new-key')
            ->map(function ($file) {
                if (null === $file) {
                    return 'php://stdout';
                }

                return $file;
            })
            ->must(function ($file) {
                Ensure::that(
                    !is_dir($file),
                    sprintf('Cannot dump config to %s. It is a directory', $file)
                );

                if (is_file($file)) {
                    Ensure::that(
                        is_writable($file),
                        sprintf('Cannot dump config to %s. It is not writable', $file)
                    );
                }

                return true;
            })
            ->describedAs('Generate a new key and dump the config to the specified file. Defaults to stdout.');

        $args->option('v')
            ->aka('version')
            ->boolean()
            ->describedAs('Get the version number');

        return $args;
    },

    'config.defaults' => require 'resources/config.default.php',

    'config' => function ($app) {
        return Config::fromFileIfExists(
            $app['args']['config'],
            $app['config.defaults']
        );
    },

    'logger' => function ($app) {
        return new SyslogLogger($app->cfg('logging.minLevel'));
    },
]);

$app['http'] = function ($app) {
    return new Http($app);
};

$app['signer'] = function ($app) {
    return new Signer(
        $app->cfg('signing.algorithm'),
        $app->cfg('signing.key')
    );
};

$app['sysinfo'] = function ($app) {
    return new SysInfo($app);
};

$app['exceptions'] = function ($app) {
    return new ExceptionHandler($app);
};

$app['exceptions']->register();

return $app;

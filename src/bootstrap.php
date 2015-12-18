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
            ->describedAs('Use a given configuration File')
            ->defaultsTo('reporter.json')
            ->file()
            ->must(function ($file) {
                Ensure::fileIsReadable($file);
                Ensure::validJson(file_get_contents($file), sprintf(
                    'Data in "%s" is not valid json',
                    $file
                ));

                return true;
            });

        $args->option('d')
            ->aka('dump-config')
            ->describedAs('Dump the complete config to this file');

        $args->option('v')
            ->aka('version')
            ->describedAs('Get the version number')
            ->boolean();

        return $args;
    },

    'config.defaults' => [
        'report' => [
            'uri' => 'https://httpbin.org/post',
        ],

        'logging' => [
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
    ],

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

$app['exceptions'] = function($app) {
    return new ExceptionHandler($app);
};

$app['exceptions']->register();

return $app;

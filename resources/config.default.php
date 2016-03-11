<?php

return [
    'report' => [
        // Where to send the report.
        'uri' => 'https://httpbin.org/post'
    ],
    'logging' => [
        // Only log messages that are of at least this level of importance.
        'minLevel'=> 'warning'
    ],
    'daemon' => [
        // Should we run in daemon mode?
        'enabled'=> false,

        // If we run in daemon mode, how often (in seconds) should we send reports?
        'interval'=> 300
    ],
    'signing' => [
        // The key used to sign the messages.
        'key' => 'CHANGEME',

        // The algorithm used for signing.
        'algorithm' => 'sha256'
    ],

    // Http client settings:
    // See http://php.net/manual/en/context.http.php
    'http' => [
        'follow_location'=> true,
        'max_redirects'=> 20,
        'user_agent'=> 'Reporter',
        'timeout'=> 10
    ],
    // Https client settings:
    // See http://php.net/manual/en/context.ssl.php
    'https' => [
        'verify_peer'=> true,
        'verify_peer_name'=> true,
        'allow_self_signed'=> false
    ]
];

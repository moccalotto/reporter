<?php

namespace Moccalotto\Reporter;

/**
 * Http service
 */
class Http
{
    /**
     * @var array
     */
    protected $contextOptionDefaults;

    /**
     * @var App
     */
    protected $app;

    /**
     * Constructor.
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->contextOptionDefaults = [
            'http' => $app->cfg('http'),
            'ssl' => $app->cfg('https'),
        ];
    }

    /**
     * Make an HTTP request.
     *
     * @param string $url
     * @param string|array $content
     * @param array $headers
     * @param string $method
     *
     * @return string
     */
    public function makeRequest($url, $content = null, $headers = [], $method = 'GET')
    {
        $context_options = array_replace_recursive($this->contextOptionDefaults, [
            'http' => [
                'method' => $method,
                'header' => $headers,
                'content' => $content,
            ],
        ]);

        $response = file_get_contents($url, false, stream_context_create($context_options));

        $http_header = $http_response_header[0];

        Ensure::matches(
            '#HTTP/\\d+\\.\\d+\\s+2\\d{2}#Ai',
            $http_header,
            sprintf('Bad response from server: "%s"', $http_header)
        );

        return $response;
    }
}

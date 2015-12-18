<?php

namespace Moccalotto\Reporter;

class ExceptionHandler
{
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function register()
    {
        set_exception_handler([$this, 'handle']);
    }

    public function handle($exception)
    {
        $this->app->error((string) $exception);

        fwrite(STDERR, (string) $exception);
    }
}

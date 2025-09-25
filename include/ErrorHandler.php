<?php

declare(strict_types=1);

namespace Inc;

class ErrorHandler
{
    public function __construct()
    {
        set_exception_handler(
            function (\Throwable $exception) {
                $this->handle($exception);
            });
    }

    private function handle(\Throwable $exception)
    {
        if ($exception instanceof ConnectionException) {
            $this->echoW('Invalid connection for ' . $exception->getUrl(), 'WB api broken');
        } else if ($exception instanceof AuthException) {
            $this->echoW('Invalid authorization for ' . $exception->getUrl(), 'Renew token (config.php)');
        } else {
            $this->echoW('UNKNOWN');
            print_r($exception);
            echo PHP_EOL;
        }
    }

    private function echoW(string $info, string $description = '')
    {
        echo PHP_EOL;
        echo $info, PHP_EOL;
        if ($description) {
            echo $description, PHP_EOL;
        }
        echo PHP_EOL;
    }
}
<?php

declare(strict_types=1);

namespace Inc;

use Inc\Exception\AuthException;
use Inc\Exception\ConnectionException;
use Inc\Exception\LimitException;

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
        } else if ($exception instanceof LimitException) {
            $this->echoW('Limit Request ' . $exception->getUrl());
//            print_r($exception);
//            echo PHP_EOL;
        } else {
            $this->echoW('UNKNOWN');
            print_r($exception);
            echo PHP_EOL;
        }
    }

    private function echoW(string $info, string $description = '')
    {
        echo PHP_EOL;
        echo '[ERROR]: ', $info, PHP_EOL;
        if ($description) {
            echo $description, PHP_EOL;
        }
        echo PHP_EOL;
    }
}
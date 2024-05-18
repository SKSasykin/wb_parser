<?php

declare(strict_types=1);

namespace Inc;

class Resource
{
    function download(string $url, int $tries = 5): string
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/7.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6");
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        for($i=0; $i<$tries; $i++) {
            $doc = curl_exec($ch);

            if ($doc) {
                break;
            } else {
                sleep(1);
            }
        }
        curl_close($ch);

        return (string) $doc;
    }
}
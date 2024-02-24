<?php

declare(strict_types=1);

namespace Inc;

class Connection
{
    private const MAX_TRIES = 5;

    private string $url;
    private string $key;

    public function __construct(string $key, string $url)
    {
        $url = rtrim($url, '/');

        $this->url = $url;
        $this->key = $key;
    }

    public function get(string $path, array $data = []): string
    {
        $path = ltrim($path, '/');

        $ch = curl_init();
        $this->commonConfig($ch);

        curl_setopt($ch, CURLOPT_URL, "$this->url/$path?" . http_build_query($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Authorization: ' . $this->key,
        ]);

        $doc = curl_exec($ch);
//        print_r(curl_error($ch));
        curl_close($ch);

        return $doc;
    }

    public function post(string $path, array $data = []): string
    {
        $path = ltrim($path, '/');

        $ch = curl_init();
        $this->commonConfig($ch);

        curl_setopt($ch, CURLOPT_URL, "$this->url/$path");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-type: application/json',
            'Authorization: ' . $this->key,
        ]);

        $tries = 0;
        do {
            $doc = curl_exec($ch);
        } while($doc === false && ++$tries<self::MAX_TRIES);

        if(!$doc) {
            echo "[!] Error connection\n ", curl_errno($ch), ":", curl_error($ch);
            exit;
        }

        curl_close($ch);

        return $doc;
    }

    private function commonConfig($ch): void
    {
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/6.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6");
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    }
}
<?php

declare(strict_types=1);

namespace Inc;

use Inc\Exception\AuthException;
use Inc\Exception\ConnectionException;
use Inc\Exception\LimitException;

class Connection
{
    private const MAX_TRIES = 5;

    private string $url;
    private string $key;

    private string $fullUrl;

    public function __construct(string $key, string $url)
    {
        $url = rtrim($url, '/');

        $this->url = $url;
        $this->key = $key;
    }

    /**
     * @param string $path
     * @param array $data
     * @return object|mixed
     * @throws AuthException
     * @throws ConnectionException
     * @throws \JsonException
     */
    public function get(string $path, array $data = []): object
    {
        $path = ltrim($path, '/');

        $ch = curl_init();
        $this->commonConfig($ch);

        curl_setopt($ch, CURLOPT_URL, $this->fullUrl = "$this->url/$path?" . http_build_query($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Authorization: ' . $this->key,
        ]);

        $result = $this->extractResult($ch);

        curl_close($ch);

        return $result;
    }

    /**
     * @param string $path
     * @param array $data
     * @return object|mixed
     * @throws AuthException
     * @throws ConnectionException
     * @throws \JsonException
     */
    public function post(string $path, array $data = []): object
    {
        $path = ltrim($path, '/');

        $ch = curl_init();
        $this->commonConfig($ch);

        curl_setopt($ch, CURLOPT_URL, $this->fullUrl = "$this->url/$path");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-type: application/json',
            'Authorization: ' . $this->key,
        ]);

        $result = $this->extractResult($ch);

        curl_close($ch);

        return $result;
    }

    private function commonConfig($ch): void
    {
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/6.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6");
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    }

    /**
     * @param $ch
     * @return object|mixed
     * @throws AuthException
     * @throws ConnectionException
     * @throws \JsonException
     */
    private function extractResult($ch): object
    {
        $tries = 0;
        do {
            $doc = curl_exec($ch);
        } while($doc === false && ++$tries<self::MAX_TRIES);

        if(!$doc) {
            throw new ConnectionException($this->fullUrl);
        }

        $result = json_decode($doc, false, 512, JSON_THROW_ON_ERROR);

        if(curl_getinfo($ch, CURLINFO_HTTP_CODE) === 401) {
            throw new AuthException($this->fullUrl);
        }

        if(curl_getinfo($ch, CURLINFO_HTTP_CODE) === 400) {
            if($result->code == 'UploadDataLimit') {
                throw new LimitException($this->fullUrl);
            }

        }

        return $result;
    }
}
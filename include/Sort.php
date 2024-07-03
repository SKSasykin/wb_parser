<?php

declare(strict_types=1);

namespace Inc;

class Sort
{
    private $priority;

    public function __construct(string $fileName)
    {
        $this->priority = file($fileName, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    function normalize($name): string
    {
        foreach ($this->priority as $item) {
            if (stripos($name, $item) !== false) {
                return strtolower($item);
            }
        }

        return 'z';
    }
}
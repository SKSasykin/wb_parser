<?php

declare(strict_types=1);

namespace Inc;

class Sort
{
    private array $priorityFilenames;

    public function __construct(string $filename)
    {
        $this->priorityFilenames = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $this->priorityFilenames = array_map(function ($filename) {
            return $this->removeDoubleSpace($filename);
        }, $this->priorityFilenames);
    }

    public function normalize($name): string
    {
        $name = $this->removeDoubleSpace($name);

        foreach ($this->priorityFilenames as $index => $filename) {
            if (stripos($name, $filename) !== false) {
                return $index . ' ' . strtolower($filename);
            }
        }

        return '~';
    }

    private function removeDoubleSpace(string $string): string
    {
        return preg_replace('/\s+/', ' ', $string);
    }
}
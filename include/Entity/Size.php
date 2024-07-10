<?php

declare(strict_types=1);

namespace Inc\Entity;

class Size extends AbstractEntity
{
    public int $chrtID;
    public string $techSize;
    public string $wbSize;
    /**
     * @var string[]
     */
    public array $skus;
}
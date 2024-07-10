<?php

declare(strict_types=1);

namespace Inc\Entity;

class Photo extends AbstractEntity
{
    public string $big;
    public string $c246x328;
    public string $c516x688;
    public string $square;
    public string $tm;

    public function getC246x328Url()
    {
        return str_replace('.webp','.jpg', $this->c246x328);
    }
}
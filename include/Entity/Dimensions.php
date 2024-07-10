<?php

declare(strict_types=1);

namespace Inc\Entity;

class Dimensions extends AbstractEntity
{
    public int $width;
    public int $height;
    public int $length;
    public bool $isValid;
}
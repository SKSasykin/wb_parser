<?php

declare(strict_types=1);

namespace Inc\Entity;

class Characteristic extends AbstractEntity
{
    public int $id;
    public string $name;
    public array $value;
}
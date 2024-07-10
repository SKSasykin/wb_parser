<?php

declare(strict_types=1);

namespace Inc\Entity;

class Product extends AbstractEntity
{
    public int $nmID;
    public int $imtID;
    public string $nmUUID;
    public int $subjectID;
    public string $subjectName;
    public string $vendorCode;
    public string $brand;
    public string $title;
    public string $description;
    /**
     * @var Photo[]
     */
    public array $photos;
    public Dimensions $dimensions;
    /**
     * @var Characteristic[]
     */
    public array $characteristics;
    /**
     * @var Size[]
     */
    public array $sizes;
    public string $createdAt;
    public string $updatedAt;

    public function firstPhoto(): Photo
    {
        return current($this->photos);
    }

    protected function mapping(): array
    {
        return [
            'photos'          => Photo::class,
            'characteristics' => Characteristic::class,
            'sizes'           => Size::class,
        ];
    }
}
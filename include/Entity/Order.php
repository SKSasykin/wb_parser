<?php

declare(strict_types=1);

namespace Inc\Entity;

class Order extends AbstractEntity
{
    public int $id;
    public ?User $user;
    public ?int $scanPrice;
    public string $orderUid;
    public string $article;
    public string $colorCode;
    public string $rid;
    public string $createdAt;

    /**
     * @var string[]
     */
    public ?array $offices;

    /**
     * @var string[]
     */
    public array $skus;
    public int $warehouseId;
    public int $nmId;
    public int $chrtId;
    public int $price;
    public int $convertedPrice;
    public int $currencyCode;
    public int $convertedCurrencyCode;
    public int $cargoType;
    public bool $isZeroOrder;
    public ?Product $product = null;
    public string $deliveryType;

    protected function mapping(): array
    {
        return [
            'product' => Product::class
        ];
    }
}
//
//
//[address] =>
//[supplyId] => WB-GI-204503448
//[options] => stdClass Object
//(
//    [isB2B] =>
//)
//[officeId] => 242

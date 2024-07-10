<?php

declare(strict_types=1);

namespace Inc;

use Inc\Entity\Product;
use JsonException;

class Content
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @throws JsonException
     */
    public function productByVendor(string $sku): Product
    {
        $json = $this->connection->post(
            'content/v2/get/cards/list', ['settings' => ['filter' => ['withPhoto' => -1, 'textSearch' => $sku]]]
        );

        $data = json_decode($json, false, 512, JSON_THROW_ON_ERROR);

        return Product::fromObject(current($data->cards));
    }
}
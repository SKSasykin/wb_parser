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
     * @throws ConnectionException
     */
    public function productByVendor(string $sku): Product
    {
        $json = $this->connection->post(
            'content/v2/get/cards/list', ['settings' => ['filter' => ['withPhoto' => -1, 'textSearch' => $sku]]]
        );

        return Product::fromObject(current($json->cards));
    }
}
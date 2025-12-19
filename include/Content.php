<?php

declare(strict_types=1);

namespace Inc;

use Inc\Entity\Product;
use Inc\Exception\AuthException;
use Inc\Exception\ConnectionException;
class Content
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param string $sku
     * @return Product
     * @throws ConnectionException
     * @throws AuthException
     * @throws \JsonException
     */
    public function productByVendor(string $sku): Product
    {
        $json = $this->connection->post(
            'content/v2/get/cards/list', ['settings' => ['filter' => ['withPhoto' => -1, 'textSearch' => $sku]]]
        );

        return Product::fromObject(current($json->cards));
    }
}
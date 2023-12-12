<?php

declare(strict_types=1);

namespace Inc;

class Content
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function productByVendor(string $sku)
    {
        $json = $this->connection->post(
            'content/v2/get/cards/list', ['settings' => ['filter' => ['withPhoto' => -1, 'textSearch' => $sku]]]
        );

        if (!$json) {
            sleep(1);
            $json = $this->connection->post(
                'content/v2/get/cards/list', ['settings' => ['filter' => ['withPhoto' => -1, 'textSearch' => $sku]]]
            );
        }

        $r = json_decode($json, false, 512, JSON_THROW_ON_ERROR);

        return $this->productNormalize(current($r->cards));
    }

    private function productNormalize($product)
    {
        $product->subject = $product->subjectName;
        $product->name = $product->title;
        $product->mediaFiles = array_map(
            function ($item) {
                return $item->big;
            },
            $product->photos
        );

        return $product;
    }
}
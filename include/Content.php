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

    public function productByVendor(array $vendors)
    {
        $json = $this->connection->post('content/v1/cards/filter', ['vendorCodes' => $vendors]);

        if (!$json) {
            sleep(1);
            $json = $this->connection->post('content/v1/cards/filter', ['vendorCodes' => $vendors]);
        }

        $r = json_decode($json, false, 512, JSON_THROW_ON_ERROR);

        foreach ($r->data as $product) {
            if (in_array($product->vendorCode, $vendors)) {
                return $this->productNormalize($product);
            }
        }

        return null;
    }

    private function productNormalize($product)
    {
        foreach ($product->characteristics as $objValue) {
            if (isset($objValue->{'Предмет'})) {
                $product->subject = $objValue->{'Предмет'};
            }
            if (isset($objValue->{'Наименование'})) {
                $product->name = $objValue->{'Наименование'};
            }
        }

        return $product;
    }
}
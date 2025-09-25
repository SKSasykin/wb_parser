<?php

declare(strict_types=1);

namespace Inc;

use Inc\Entity\Order;
use JsonException;

class Marketplace
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function supply($limit = 50, $offset = 0): array
    {
//        echo "+$offset \n";

        $json = $this->connection->get('api/v3/supplies', ['next' => $offset, 'limit' => $limit,]);

        if (count($json->supplies)) {
            $items = $this->supply($limit, $json->next);

            if (count($items)) {
                return $this->filterSupplyNotDone(array_merge($items, $json->supplies));
            }

            return $this->filterSupplyNotDone($json->supplies);
        }

        return [];
    }

    /**
     * @param $supply
     * @return Order[]
     * @throws ConnectionException
     */
    public function orders($supply): array
    {
        $json = $this->connection->get('api/v3/supplies/' . $supply . '/orders');

        return array_map(function(object $item) {
            return Order::fromObject($item);
        }, $json->orders);
    }

    function stickers3($orderIds): array
    {
        $result = [];

        foreach(array_chunk($orderIds, 100) as $chunk) {
            $json = $this->connection->post('api/v3/orders/stickers?type=svg&width=58&height=40', ['orders' => $chunk]);

            foreach($json->stickers as $sticker) {
                $result[$sticker->orderId] = $sticker->file;
            }
        }

        return $result;
    }

    private function filterSupplyNotDone($array): array
    {
        return array_filter(
            $array,
            fn($item) => !$item->done //|| $item->id == 'WB-GI-55070554'
        );
    }
}
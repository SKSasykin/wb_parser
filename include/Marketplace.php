<?php

declare(strict_types=1);

namespace Inc;

use Inc\Entity\Order;
use Inc\Exception\AuthException;
use Inc\Exception\ConnectionException;

class Marketplace
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param $limit
     * @param $offset
     * @return array
     * @throws ConnectionException
     * @throws AuthException
     * @throws \JsonException
     */
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
     * @param $supplyId
     * @return Order[]
     * @throws ConnectionException
     * @throws AuthException
     * @throws \JsonException
     */
    public function orders($supplyId, string $dateFrom): array
    {
        $json = $this->connection->get('api/marketplace/v3/supplies/' . $supplyId . '/order-ids');
//file_put_contents('123.db', serialize($json));
//$json = unserialize(file_get_contents('123.db'));

        $orderIds = $json->orderIds;

        $orders = [];

        do {
            $json = $this->connection->get('api/v3/orders',
                ['limit' => 1000, 'next' => $json->next ?? 0, 'dateFrom' => strtotime($dateFrom) - 86400*7]
            );

            $orders = array_merge($orders,
                array_filter($json->orders, function($item) use ($supplyId, $orderIds) {
                    return $item->supplyId == $supplyId && in_array($item->id, $orderIds);
                })
            );
//            echo "+$json->next", PHP_EOL;
        } while (count($json->orders));

        return array_map(function(object $item) {
            return Order::fromObject($item);
        }, $orders);
    }

    /**
     * @param $orderIds
     * @return array
     * @throws AuthException
     * @throws ConnectionException
     * @throws \JsonException
     */
    public function stickers3($orderIds): array
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
<?php

declare(strict_types=1);

namespace Inc;

class Marketplace
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function supply($limit = 50, $offset = 0)
    {
//        echo "+$offset \n";

        $json = $this->connection->get('api/v3/supplies', ['next' => $offset, 'limit' => $limit,]);

        $r = json_decode($json, false, 512, JSON_THROW_ON_ERROR);

        if (count($r->supplies)) {
            $items = $this->supply($limit, $r->next);

            if (count($items)) {
                return $this->filterSupplyNotDone(array_merge($items, $r->supplies));
            }

            return $this->filterSupplyNotDone($r->supplies);
        }

        return [];
    }

    public function orders($supply)
    {
        $json = $this->connection->get('api/v3/supplies/' . $supply . '/orders');

        $r = json_decode($json, false, 512, JSON_THROW_ON_ERROR);

        return $r->orders;
    }

    function stickers3($orderIds)
    {
        $result = [];

        foreach(array_chunk($orderIds, 100) as $chunk) {
            $json = $this->connection->post('api/v3/orders/stickers?type=svg&width=58&height=40', ['orders' => $chunk]);

            if(!$json) {
                sleep(1);
                $json = $this->connection->post('api/v3/orders/stickers?type=svg&width=58&height=40', ['orders' => $chunk]);
            }

            $r = json_decode($json, false, 512, JSON_THROW_ON_ERROR);

            foreach($r->stickers as $sticker) {
                $result[$sticker->orderId] = $sticker->file;
            }
        }

        return $result;
    }

    private function filterSupplyNotDone($array)
    {
        return array_filter(
            $array,
            fn($item) => !$item->done //|| $item->id == 'WB-GI-55070554'
        );
    }
}
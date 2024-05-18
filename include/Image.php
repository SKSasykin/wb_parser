<?php

declare(strict_types=1);

namespace Inc;

class Image
{
    private $url;

    public function __construct($order)
    {
//print_r($order);
//        $znum = substr((string) $order->nmId, 0, -4) . '0000';
//        $num = $order->nmId;
//        $this->path = "/c246x328/new/$znum/$num-1.jpg";

//        $imgs = array_filter($order->product->mediaFiles, fn($file) => in_array(substr($file,-3), ['png','jpg']));
        $img = current($order->product->mediaFiles);

        $img = str_replace('/big/','/c246x328/', $img);
        $this->url = str_replace('.webp','.jpg', $img);
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
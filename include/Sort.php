<?php

declare(strict_types=1);

namespace Inc;

use Inc\Entity\Order;
use Inc\Entity\Product;

class Sort
{
    private array $priorityNames;

    public function __construct(string $filename)
    {
        $this->priorityNames = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $this->priorityNames = array_map(function ($filename) {
            return $this->removeDoubleSpace($filename);
        }, $this->priorityNames);
    }

    /**
     * @param Order[] $orders
     * @return void
     */
    public function orders(array &$orders): void
    {
        usort($orders,
            /**
             * @param Order $orderA
             * @param Order $orderB
             * @return int
             */
            function ($orderA, $orderB) {
                return strcmp($this->normalize($orderA->product), $this->normalize($orderB->product));
            });
    }

    private function normalize(Product $product): string
    {
        $title = $this->removeDoubleSpace($product->title);

        foreach ($this->priorityNames as $index => $priorityName) {
            if (stripos($title, $priorityName) !== false) {
                return str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT)
                    . ' ' . strtolower($product->vendorCode) . $product->nmID;
            }
        }

        return '999 ' . strtolower($product->vendorCode) . $product->nmID;
    }

    private function removeDoubleSpace(string $string): string
    {
        return preg_replace('/\s+/', ' ', $string);
    }
}
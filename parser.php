<?php

use Inc\Connection;
use Inc\Content;
use Inc\Marketplace;
use Inc\Resource;

error_reporting(E_ALL ^ E_DEPRECATED ^ E_NOTICE ^ E_WARNING);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';
require __DIR__ . '/include/pdf.php';
require __DIR__ . '/include/xls.php';

    const SORT_PRIORITY = [
        "iPhone 11",
        "iPhone 11 pro",
        "iPhone 11 pro max",
        "iPhone 12 mini",
        "iPhone 12",
        "iPhone 12 pro",
        "iPhone 12 pro max",
        "iPhone 13 mini",
        "iPhone 13",
        "iPhone 13 pro",
        "iPhone 13 pro max",
        "iPhone 14",
        "iPhone 14 plus",
        "iPhone 14 pro",
        "iPhone 14 pro max",
        "Samsung A12",
        "Samsung A13",
        "Samsung A23",
        "Samsung A32",
        "Samsung A33",
        "Samsung A50",
        "Samsung A51",
        "Samsung A52",
        "Samsung A53",
        "Samsung A73",
        "Samsung S22",
    ];

    $pdf = new PDF();
    $xls = new XLS();

    $marketplace = new Marketplace(new Connection(KEY_MARKETPLACE, URL));
    $content = new Content(new Connection(KEY_CONTENT, URL));

    $resource = new Resource('https://images.wbstatic.net');

    ignore_user_abort(true);
    set_time_limit(0);

    ini_set('memory_limit', '512M');

    $supplies = $marketplace->supply(100, 37099783);
//    print_r($supplies);exit;
    if(!$supplies) {
        exit('not found supply');
    }

    echo 'Supplies:', PHP_EOL;
    $orders = [];
    foreach($supplies as $supply) {
        echo ' - ', $supply->id, ': ', $supply->name, PHP_EOL;
        $orders = array_merge($orders, $marketplace->orders($supply->id));
    }

//$orders = array_filter($orders, fn($order) => $order->id==983898673);

echo "\nOrders count: ", count($orders), "\n";

    $orderIds = [];
    foreach($orders as $order) {
        $orderIds[]   = (int) $order->id;
        $order->product = $content->productByVendor([$order->article]);
        $order->sort  = sortNameNormalize($order->product->name);
    }
//    print_r($orders);exit;
//    print_r($orderIds);exit;

//    file_put_contents('hz.dat', serialize($orders));exit;
//    $orders=unserialize(file_get_contents('hz.dat'));

    echo "download stickers ... ";
    $stickers = $marketplace->stickers3($orderIds);
//    print_r($stickers);exit;
    echo "done\nsorting... ";
    usort($orders, function($a, $b) {
        return strcmp($a->sort . $a->nmId, $b->sort . $b->nmId);
    });
    echo "done\n";

    echo "download images: \n";

    foreach ($orders as $order) {
        $znum = substr($order->nmId, 0, -4) . '0000';
        $num = $order->nmId;
        $fileUrl = "/c246x328/new/$znum/$num-1.jpg";

        $product = $content->productByVendor([$order->article]);

        if ($product && $imgs =
                array_filter($product->mediaFiles,
                    fn($file) => in_array(substr($file,-3), ['png','jpg']))) {
//print_r($imgs);exit;
            natsort($imgs);

            $fileUrl = str_replace('/big/','/c246x328/', current($imgs));
        }

        echo " - img order:$order->id:$order->article > $fileUrl";
//print_r($product); exit;
        $fileData = $resource->download($fileUrl, 3);

        if($fileData) {
            file_put_contents($file = __DIR__ . "/tmp/$num-1.jpg", $fileData);
        } else {
            $file = '';
        }

        $xls->addRow(
            $file,
            $order->id,
            current($order->skus),
            $order->article,
            $order->nmId,
            $order->product->subject,
            $order->product->name,
            $fileUrl
        );

        $pdf->addBarcodePage(base64_decode($stickers[$order->id]));
        $pdf->addOwnerPage(current($order->skus), $order->article, $order->product->subject);

        echo "\n";
    }

    echo "\nsaving files:";

    $timestamp = date('Y-m-d_H_i_s');
    $pdf->output($pdfFile =__DIR__.'/out/file-'.$timestamp.'.pdf');
    $xls->output($xlsFile = __DIR__.'/out/file-'.$timestamp.'.xls');

    echo " $pdfFile,\n $xlsFile\n\ndone";

    function sortNameNormalize($name): string {
        foreach (SORT_PRIORITY as $item) {
            if(stripos($name, $item) !== false) {
                return strtolower($item);
            }
        }

        return 'z';
    }

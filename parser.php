<?php

use Inc\Connection;
use Inc\Content;
use Inc\Image;
use Inc\Marketplace;
use Inc\PDF;
use Inc\Resource;
use Inc\Sort;
use Inc\XLS;

error_reporting(E_ALL ^ E_DEPRECATED ^ E_NOTICE ^ E_WARNING);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';

    $pdf = new PDF();
    $xls = new XLS();

    $marketplace = new Marketplace(new Connection(KEY_MARKETPLACE, URL));
    $content = new Content(new Connection(KEY_CONTENT, URL));

    if(!file_exists($sortFile = __DIR__ . '/sort.txt')) {
        file_put_contents($sortFile, '');
    }
    $sort = new Sort($sortFile);

    $resource = new Resource();

    ignore_user_abort(true);
    set_time_limit(0);

    ini_set('memory_limit', '512M');

    $supplies = $marketplace->supply(100, 37099783);
//    print_r($supplies);exit;
    if(!$supplies) {
        exit("not found supply\n");
    }

    echo 'Supplies:', PHP_EOL;
    $orders = [];
    foreach($supplies as $supply) {
        echo ' - ', $supply->id, ': ', $supply->name, PHP_EOL;
        $orders = array_merge($orders, $marketplace->orders($supply->id));
    }

//$orders = array_filter($orders, fn($order) => $order->id==983898673);

    echo "\nOrders count: ", count($orders), "\n";

    echo "download products: \n";
    $orderIds = [];
    foreach($orders as $i => $order) {
        $orderIds[]   = (int) $order->id;
        echo " - order [", $i+1, "]:$order->id\n";
        $order->product = $content->productByVendor($order->nmId);
        $order->sort  = $sort->normalize($order->product->name);
        sleep(1);
    }
//    print_r($orders);
//    print_r($orderIds);exit;

//    file_put_contents('hz.dat', serialize($orders));exit;
//    $orders=unserialize(file_get_contents('hz.dat'));

    echo "download stickers ... ";
    $stickers = $marketplace->stickers3($orderIds);
//    print_r($stickers);exit;
    echo "done\nsorting... ";
    usort($orders, function($a, $b) use ($sort) {
        $ia = (int) $a->sort;
        $ib = (int) $b->sort;
        if ($ia > 0 && $ib > 0) {
            if ($ia > $ib) {
                return 1;
            } elseif ($ib > $ia) {
                return -1;
            } else {
                return 0;
            }
        }

        return strcmp($a->sort . $a->nmId, $b->sort . $b->nmId);
    });
    echo "done\n";

//print_r($orders);
//print_r(array_slice(array_reduce($orders, function ($orders, $item) {
//    $orders[] = $item->sort;
//    return $orders;
//}, []),0, 100));
//exit;
    echo "download images: \n";

    foreach ($orders as $order) {
        $image = new Image($order);

        echo " - img [order:$order->id]: $order->article > {$image->getUrl()}";

        $fileData = $resource->download($image->getUrl());

        if($fileData) {
            file_put_contents($file = __DIR__ . "/tmp/$order->nmId-1.jpg", $fileData);
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
            $image->getUrl()
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

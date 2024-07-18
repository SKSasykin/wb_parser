<?php

use Inc\Connection;
use Inc\Content;
use Inc\Entity\Order;
use Inc\Marketplace;
use Inc\PDF;
use Inc\Resource;
use Inc\Sort;
use Inc\XLS;

error_reporting(E_ALL ^ E_DEPRECATED ^ E_NOTICE ^ E_WARNING);

ignore_user_abort(true);
set_time_limit(0);

ini_set('memory_limit', '512M');

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

    $supplies = $marketplace->supply(100, 37099783);

    if(!$supplies) {
        exit("not found supply\n");
    }

    echo 'Supplies:', PHP_EOL;

    /** @var Order[] $orders */
    $orders = [];
    foreach($supplies as $supply) {
        echo ' - ', $supply->id, ': ', $supply->name, PHP_EOL;
        $orders = array_merge($orders, $marketplace->orders($supply->id));
    }

    echo "\nOrders count: ", count($orders), "\n";

    echo "download products: \n";
    foreach($orders as $i => $order) {
        echo " - order [", $i+1, "]:$order->id\n";
        $order->product = $content->productByVendor($order->nmId);
        sleep(1);
    }
//    print_r($orders);exit;

//    file_put_contents('hz.dat', serialize($orders));exit;
//$orders =unserialize(file_get_contents('hz.dat'));

    echo "download stickers ... ";
    $stickers = $marketplace->stickers3(array_map(fn(Order $item) => (int) $item->id, $orders));
    echo "done\nsorting... ";
    $sort->orders($orders);
    echo "done\n";

//var_dump($orders[0]);
//print_r(array_slice(array_reduce($orders, function ($result, $order) {
//    $result[] = "{$order->product->name} $order->article";
//    return $result;
//}, []),0, -1));
//exit;
    echo "download images: \n";

    /** @var Order $order */
    foreach ($orders as $order) {
        echo " - img [order:$order->id]: $order->article > {$order->product->firstPhoto()->getC246x328Url()}";

        $fileData = $resource->download($order->product->firstPhoto()->getC246x328Url());

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
            $order->product->subjectName,
            $order->product->title,
            $order->product->firstPhoto()->getC246x328Url()
        );

        $pdf->addBarcodePage(base64_decode($stickers[$order->id]));
        $pdf->addOwnerPage(current($order->skus), $order->article, $order->product->subjectName, OWNER);

        echo "\n";
    }

    echo "\nsaving files:";

    $timestamp = date('Y-m-d_H_i_s');
    $pdf->output($pdfFile =__DIR__.'/out/file-'.$timestamp.'.pdf');
    $xls->output($xlsFile = __DIR__.'/out/file-'.$timestamp.'.xls');

    echo " $pdfFile,\n $xlsFile\n\ndone";

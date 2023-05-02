<?php
    error_reporting(E_ALL ^ E_DEPRECATED ^ E_NOTICE ^ E_WARNING);

    require __DIR__ . '/config.php';

    require __DIR__ . '/vendor/autoload.php';

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

    ignore_user_abort(true);
    set_time_limit(0);

    ini_set('memory_limit', '512M');

    $marker = date('Y-m-d_H_i_s');

    $supplies = supply(100, 37099783);
//    print_r($supplies);exit;
//$supplies = ['WB-GI-46050763'];
    if(!$supplies) {
        exit;
    }

$orders = [];
    foreach($supplies as $supply) {
        $orders = array_merge($orders, orders($supply->id));
    }
//    print_r($orders);exit;

    echo "orders count: ", count($orders), "\n";

    $orderIds = [];
    foreach($orders as $order) {
        $orderIds[]   = (int) $order->id;
        $order->product = productByVendor([$order->article]);
//        print_r($order);exit;
        $order->sort  = sortNameNormalize($order->product->name);
    }
//    print_r($orders);exit;
//    print_r($orderIds);exit;

//    file_put_contents('hz.dat', serialize($orders));exit;
//    $orders=unserialize(file_get_contents('hz.dat'));

    echo "download stickers ... ";
    $stickers = stickers3($orderIds);
//print_r($stickers);exit;
    echo "done\nsorting... ";
    usort($orders, function($a, $b) {
        return strcmp($a->sort . $a->nmId, $b->sort . $b->nmId);
    });
    echo "done\n";

    echo "download images: \n";

    foreach ($orders as $order) {
        $znum = substr($order->nmId, 0, -4) . '0000';
        $num = $order->nmId;
        $fileUrl = "https://images.wbstatic.net/c246x328/new/$znum/$num-1.jpg";

        $product = productByVendor([$order->article]);

        if ($product && $imgs =
                array_filter($product->mediaFiles,
                    fn($file) => in_array(substr($file,-3), ['png','jpg']))) {
//print_r($imgs);exit;
            natsort($imgs);

            $fileUrl = str_replace('/big/','/c246x328/', current($imgs));
        }

        echo " - img order:$order->id:$order->article > $fileUrl";
//print_r($product); exit;
        $data = getResource($fileUrl);
        if(!$data) {
            sleep(1);
            $data = getResource($fileUrl);
        }
        if(!$data) {
            sleep(1);
            $data = getResource($fileUrl);
        }

        if($data) {
            file_put_contents($file = "/tmp/$num-1.jpg", $data);
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

    $pdf->output($pdfFile =__DIR__.'/out/file-'.$marker.'.pdf');
    $xls->output($xlsFile = __DIR__.'/out/file-'.$marker.'.xls');

    echo " $pdfFile,\n $xlsFile\n\ndone";

    function sortNameNormalize($name) {
        foreach (SORT_PRIORITY as $item) {
            if(stripos($name, $item) !== false) {
                return strtolower($item);
            }
        }

        return 'z';
    }

    function stickers2($orderIds)
    {
        $json = post('api/v2/orders/stickers',
            ['orderIds' => $orderIds, 'type' => 'qr']);

        if(!$json) {
            sleep(1);
            $json = post('api/v2/orders/stickers',
                ['orderIds' => $orderIds, 'type' => 'qr']);
        }

        $r = json_decode($json, false, 512, JSON_THROW_ON_ERROR);

        $result = [];

        foreach($r->data as $order) {
            $result[$order->orderId] = $order->sticker->wbStickerSvgBase64;
        }

        return $result;
    }

    function stickers3($orderIds)
    {
        $result = [];

        foreach(array_chunk($orderIds, 100) as $chunk) {
            $json = post('api/v3/orders/stickers?type=svg&width=58&height=40', ['orders' => $chunk]);

            if(!$json) {
                sleep(1);
                $json = post('api/v3/orders/stickers?type=svg&width=58&height=40', ['orders' => $chunk]);
            }

            $r = json_decode($json, false, 512, JSON_THROW_ON_ERROR);

            foreach($r->stickers as $sticker) {
                $result[$sticker->orderId] = $sticker->file;
            }
        }

        return $result;
    }

    function productByVendor(array $vendors)
    {
        $json = post('content/v1/cards/filter', ['vendorCodes' => $vendors]);

        if(!$json) {
            sleep(1);
            $json = post('content/v1/cards/filter', ['vendorCodes' => $vendors]);
        }

        $r = json_decode($json, false, 512, JSON_THROW_ON_ERROR);

        foreach($r->data as $product) {
            if(in_array($product->vendorCode, $vendors)) {
                return productNormalize($product);
            }
        }

        return null;
    }

    function productNormalize($product)
    {
        foreach($product->characteristics as $objValue) {
            if (isset($objValue->{'Предмет'})) {
                $product->subject = $objValue->{'Предмет'};
            }
            if (isset($objValue->{'Наименование'})) {
                $product->name = $objValue->{'Наименование'};
            }
        }

        return $product;
    }

    function supply($limit = 50, $offset = 0)
    {
//        echo "+$offset \n";

        $json = get('api/v3/supplies', ['next' => $offset, 'limit' => $limit,]);

        $r = json_decode($json, false, 512, JSON_THROW_ON_ERROR);

        if (count($r->supplies)) {
            $items = supply($limit, $r->next);

            if (count($items)) {
                return filterSupplyNotDone(array_merge($items, $r->supplies));
            }

            return filterSupplyNotDone($r->supplies);
        }

        return [];
    }

    function filterSupplyNotDone($array)
    {
        return array_filter(
            $array,
            fn($item) => !$item->done
        );
    }

    function orders($supply)
    {
        $json = get('api/v3/supplies/'.$supply.'/orders');

        $r = json_decode($json, false, 512, JSON_THROW_ON_ERROR);

        return $r->orders;
    }

    function getResource($url)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/6.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6");
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $doc = curl_exec($ch);
        curl_close($ch);

        return $doc;
    }

    function get($path, $data = [])
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, URL . "/$path?" . http_build_query($data));
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6");
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Authorization: ' . KEY,
        ]);

        $doc = curl_exec($ch);

        print_r(curl_error($ch));

        curl_close($ch);

        return $doc;
    }

    function post($path, $data = [])
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, URL . "/$path");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6");
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-type: application/json',
            'Authorization: ' . KEY,
        ]);

        $doc = curl_exec($ch);
        curl_close($ch);

        return $doc;
    }

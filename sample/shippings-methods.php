<?php

require __DIR__ . '/_config.php';

header('Content-Type: application/json; charset=utf-8');

// LINE Pay Online API v3 Inquiry ShippingMethods API
$data = [
    'returnCode' => "0000",
    'info' => [
        'shippingMethods' => [
            [
                'id' => 'shippingid',
                'name' => 'Delivery',
                'amount' => 20,
                'toDeliveryYmd' => date("Ymd", time()+3600*24*7),
            ],
        ],
    ],
];

echo json_encode($data);
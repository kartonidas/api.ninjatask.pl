<?php

    return [
        "allowed" => [
            "p1" => [
                "type" => "subscription",
                "name" => "premium:1",
                "price" => 29.99,
                "vat" => 23,
                "months" => 1,
                "sms" => 0,
            ],
            "p12" => [
                "type" => "subscription",
                "name" => "premium:12",
                "price" => 299.99,
                "vat" => 23,
                "months" => 12,
                "sms" => 0,
            ],
            "p1:sms_50" => [
                "type" => "subscription",
                "name" => "premium:1:sms_50",
                "price" => 35.99,
                "vat" => 23,
                "months" => 1,
                "sms" => 50,
            ],
            "p12:sms_50" => [
                "type" => "subscription",
                "name" => "premium:12:sms_50",
                "price" => 369.99,
                "vat" => 23,
                "months" => 12,
                "sms" => 600,
            ],
            "sms" => [
                "limit" => [
                    50, 100, 150, 200, 250, 300, 350, 400, 450, 500,
                    550, 600, 650, 700, 750, 800, 850, 900, 900, 1000
                ],
                "type" => "sms",
                "name" => "sms",
                "price" => 6,   // cena za każde 50 SMSów (350sms = 6PLN * 7)
                "vat" => 23,
                "sms" => 50,
                "months" => 0,
            ]
        ],
        "free" => [
            "space" => 10485760
        ],
        "paid" => [
            "space" => 209715200
        ]
    ];
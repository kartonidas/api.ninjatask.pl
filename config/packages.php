<?php

    return [
        "allowed" => [
            "p1" => [
                "type" => "subscription",
                "name" => "P1",
                "price" => 29.99,
                "vat" => 23,
                "months" => 1,
            ],
            "p12" => [
                "type" => "subscription",
                "name" => "P1",
                "price" => 299.99,
                "vat" => 23,
                "months" => 12,
            ]
        ],
        "free" => [
            "projects" => 2,
            "tasks" => 10,
            "space" => 10485760
        ]
    ];
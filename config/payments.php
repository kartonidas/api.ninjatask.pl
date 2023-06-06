<?php

return [
    "type" => env("PAYMENT_TYPE"),
    "config" => [
        "paynow" => [
            "sandbox" => env("PAYNOW_API_SANDBOX"),
            "api_signature" => env("PAYNOW_API_SIGNATURE"),
            "api_key" => env("PAYNOW_API_KEY"),
            "continue_url" => env("FRONTEND_URL") . "payment/return"
        ],
    ]
];

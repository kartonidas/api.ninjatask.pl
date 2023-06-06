<?php

return [
    "allowed_languages" => [
        "en", "pl",
    ],
    "default_language" => "en",
    "list" => [
        "size" => 50,
    ],
    "upload" => [
        "allowed_mime_types" => [
            "video/x-msvideo" => "avi",
            "text/csv" => "csv",
            "application/msword" => "doc",
            "application/vnd.openxmlformats-officedocument.wordprocessingml.document" => "docx",
            "application/gzip" => "gz",
            "image/gif" => "gif",
            "audio/mpeg" => "mp3",
            "application/vnd.oasis.opendocument.presentation" => "odp",
            "application/vnd.oasis.opendocument.spreadsheet" => "ods",
            "application/vnd.oasis.opendocument.text" => "odt",
            "image/png" => "png",
            "image/jpeg" => "jpg",
            "application/pdf" => "pdf",
            "application/vnd.ms-powerpoint" => "ppt",
            "application/vnd.openxmlformats-officedocument.presentationml.presentation" => "pptx",
            "application/x-tar" => "tar",
            "text/plain" => "txt",
            "application/zip" => "zip",
            "application/x-7z-compressed" => "7z",
            "application/vnd.ms-excel" => "xls",
            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" => "xlsx",
        ]
    ],
    "tasks" => [
        "priority" => [1 => "low", 2 => "normal", 3 => "high"]
    ],
    "notifications" => [
        "task:assign",
        "task:change_status_assigned",
        "task:add_comment_assigned",
        "task:change_status_owner",
        "task:add_comment_owner",
    ],
    "notifications_default" => [
        "task:assign",
        "task:change_status_assigned",
        "task:add_comment_assigned",
    ],
    "invoice" => [
        "invoice_city" => "Piotrków Tryb.",
        "name" => "Artur Patura - netextend.pl",
        "address" => "Łódzka",
        "street_no" => "46",
        "apartment_no" => "48",
        "post_code" => "97-300",
        "city" => "Piotrków Tryb.",
        "nip" => "PL7712671332",
        "invoice_person" => "Artur Patura"
    ]
];
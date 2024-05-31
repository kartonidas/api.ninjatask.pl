<?php

return [
    "permission" => [
        "project" => [
            "module" => "Places",
            "operation" => ["list", "create", "update", "delete"]
        ],
        "task" => [
            "module" => "Tasks",
            "operation" => ["list", "create", "update", "delete"]
        ],
        "customer" => [
            "module" => "Customers",
            "operation" => ["list", "create", "update", "delete"]
        ],
        "customer_invoices" => [
            "module" => "Customer invoices",
            "operation" => ["list", "create", "update", "delete"]
        ],
        "documents" => [
            "module" => "Documents",
            "operation" => ["list", "create", "update", "delete"]
        ],
        "stats" => [
            "module" => "Stats",
            "operation" => ["list"]
        ],
        "user" => [
            "module" => "Users",
            "operation" => ["list", "create", "update", "delete"]
        ],
        "permission" => [
            "module" => "Permissions",
            "operation" => ["list", "create", "update", "delete"]
        ],
        "status" => [
            "module" => "Statuses",
            "operation" => ["list", "create", "update", "delete"]
        ],
        "config" => [
            "module" => "Configurations",
            "operation" => ["update"]
        ],
    ]
];
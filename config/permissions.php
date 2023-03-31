<?php

return [
    "permission" => [
        "project" => [
            "module" => "Projects",
            "operation" => ["list", "create", "update", "delete"]
        ],
        "user" => [
            "module" => "Users",
            "operation" => ["list", "create", "update", "delete"]
        ],
        "permission" => [
            "module" => "Permissions",
            "operation" => ["list", "create", "update", "delete"]
        ],
    ]
];
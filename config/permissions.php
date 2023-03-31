<?php

return [
    "permission" => [
        "projects" => [
            "module" => "Projects",
            "operation" => ["list", "create", "update", "delete"]
        ],
        "users" => [
            "module" => "Users",
            "operation" => ["list", "create", "update", "delete"]
        ],
    ]
];
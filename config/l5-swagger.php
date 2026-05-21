<?php

return [
    "default" => "default",
    "documentations" => [
        "default" => [
            "api" => [
                "title" => "Notification Service API",
                "description" => "Микросервис для массовой рассылки уведомлений",
                "version" => "1.0.0",
            ],
            "routes" => [
                "api" => "api/documentation",
                "docs" => "docs",
            ],
            "paths" => [
                "use_absolute_path" => env("L5_SWAGGER_USE_ABSOLUTE_PATH", true),
                "docs_json" => "api-docs.json",
                "docs_yaml" => "api-docs.yaml",
                "annotations" => [
                    base_path("app"),
                ],
                "base" => env("L5_SWAGGER_BASE_PATH", null),
                "swagger_ui_assets_path" => env("L5_SWAGGER_UI_ASSETS_PATH", "vendor/swagger-api/swagger-ui/dist/"),
            ],
        ],
    ],
    "generate_always" => env("L5_SWAGGER_GENERATE_ALWAYS", true),
    "swagger_version" => env("SWAGGER_VERSION", "3.0"),
    "proxy" => false,
    "security" => [
        "passport" => [
            "type" => "oauth2",
            "description" => "Laravel passport oauth2 security.",
            "in" => "header",
            "scheme" => "https",
        ],
    ],
    "faker" => false,
    "constants" => [
        "L5_SWAGGER_CONST_HOST" => env("L5_SWAGGER_CONST_HOST", "http://localhost:8000"),
    ],
];

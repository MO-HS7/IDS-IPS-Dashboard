<?php

return [
    /*
    |--------------------------------------------------------------------------
    | IDS Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for the AI-Powered Intrusion Detection System
    |
    */

    /*
    |--------------------------------------------------------------------------
    | File Upload Settings
    |--------------------------------------------------------------------------
    */
    'file_upload' => [
        'max_size' => 10240, // 10MB in KB
        'allowed_mimes' => ['csv', 'txt', 'pcap'],
        'allowed_mime_types' => ['text/csv', 'text/plain', 'application/octet-stream'],
        'storage_disk' => 'public',
        'storage_path' => 'network_logs',
    ],

    /*
    |--------------------------------------------------------------------------
    | ML Model Settings
    |--------------------------------------------------------------------------
    */
    'ml_models' => [
        'storage_path' => 'models',
        'temp_path' => 'temp',
        'python_path' => env('ML_PYTHON_PATH', '/usr/bin/python3'),
        'max_model_size' => 51200, // 50MB in KB
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Settings
    |--------------------------------------------------------------------------
    */
    'dashboard' => [
        'cache_duration' => [
            'stats' => 300, // 5 minutes
            'charts' => 180, // 3 minutes
        ],
        'default_chart_data' => [
            'attack_types' => [
                ['name' => 'DDoS', 'value' => 15],
                ['name' => 'Port Scan', 'value' => 12],
                ['name' => 'SQL Injection', 'value' => 8],
                ['name' => 'XSS', 'value' => 5],
                ['name' => 'Brute Force', 'value' => 10],
            ],
            'severity_distribution' => [
                ['severity' => 'Critical', 'count' => 5, 'color' => '#ef4444'],
                ['severity' => 'High', 'count' => 12, 'color' => '#f97316'],
                ['severity' => 'Medium', 'count' => 18, 'color' => '#eab308'],
                ['severity' => 'Low', 'count' => 8, 'color' => '#22c55e'],
            ],
        ],
        'avg_processing_time' => '2.3s',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Settings
    |--------------------------------------------------------------------------
    */
    'rate_limiting' => [
        'uploads_per_minute' => 5,
        'api_requests_per_minute' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'security' => [
        'suspicious_content_patterns' => ['<?php', '<script', 'eval(', 'base64_decode'],
        'max_file_scan_size' => 1024, // 1KB for content scanning
    ],
];

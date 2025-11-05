<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Machine Learning Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for ML model inference and training
    |
    */

    'python_path' => env('ML_PYTHON_PATH', 'python'),

    'models_dir' => env('ML_MODELS_DIR', base_path('ml_models')),

    'scripts_dir' => env('ML_SCRIPTS_DIR', base_path('ml_scripts')),

    'prediction_timeout' => env('ML_PREDICTION_TIMEOUT', 30),

    'batch_size' => env('ML_BATCH_SIZE', 100),

    /*
    |--------------------------------------------------------------------------
    | Alert Generation Thresholds
    |--------------------------------------------------------------------------
    */

    'alert_threshold' => env('ML_ALERT_THRESHOLD', 70), // Confidence threshold for alert generation

    'severity_thresholds' => [
        'critical' => env('ML_SEVERITY_CRITICAL', 95),
        'high' => env('ML_SEVERITY_HIGH', 85),
        'medium' => env('ML_SEVERITY_MEDIUM', 70),
        'low' => env('ML_SEVERITY_LOW', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-Response Configuration
    |--------------------------------------------------------------------------
    */

    'auto_response' => [
        'enabled' => env('ML_AUTO_RESPONSE_ENABLED', false),
        'min_severity_score' => env('ML_AUTO_RESPONSE_MIN_SEVERITY', 85),
        'actions' => [
            'block_ip' => env('ML_AUTO_RESPONSE_BLOCK_IP', false),
            'isolate_host' => env('ML_AUTO_RESPONSE_ISOLATE', false),
            'send_notification' => env('ML_AUTO_RESPONSE_NOTIFY', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    */

    'notifications' => [
        'email_on_critical' => env('ML_EMAIL_ON_CRITICAL', true),
        'email_on_high' => env('ML_EMAIL_ON_HIGH', true),
        'email_on_medium' => env('ML_EMAIL_ON_MEDIUM', false),
        'email_recipients' => env('ML_EMAIL_RECIPIENTS', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Version
    |--------------------------------------------------------------------------
    */

    'model_version' => env('ML_MODEL_VERSION', '1.0.0'),

    'active_model' => env('ML_ACTIVE_MODEL', 'random_forest_model.pkl'),

];

<?php

return [

    // Default connection
    'connection' => 'mysql',

    // Table name where the gateway info are stored
    'model_name' => 'gateways',

    // Stripe Api Key
    'api_key' => 'Your_Stripe_key',
    
    // Gateways are used in order.
    'gateways' => ['Stripe'],

    // Autocharge for Topup payments will be in dollars by default
    // Set this to true if your autocharge is through credits
    'autocharge_recharge_points' => false,
    'duplicate_payment_time_check_seconds' => 5,

    // Retry Attempts for auto charge before it defaults
    'retry_attempts' => 3,

    // Retry intervals in Days
    'retry_interval' => [
        3,    // 3 days after first failed attempt
        7,    // 7 days after first retry attempt
        12    // 12 days after second retry attempt
    ],

    'billable_model'   => 'App\Models\User',
    'billable_table'   => 'users',
    'chargeable_model' => 'App\Models\Membership',

];


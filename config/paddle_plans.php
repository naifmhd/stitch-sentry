<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Paddle Price Mapping (plan_slug -> price_id)
    |--------------------------------------------------------------------------
    | - Keys MUST match config/features.php plan keys.
    | - Only include paid plans (exclude "free").
    | - Values are Paddle Billing price IDs (pri_...).
    */
    'prices' => [
        'starter' => env('PADDLE_PRICE_STARTER'),
        'shop' => env('PADDLE_PRICE_SHOP'),
        'digitizer' => env('PADDLE_PRICE_DIGITIZER'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cashier Subscription Name
    |--------------------------------------------------------------------------
    | Cashier identifies subscriptions by a local "name".
    | Keep this constant unless you support multiple subscriptions per org.
    */
    'subscription_name' => env('PADDLE_SUBSCRIPTION_NAME', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Strict mode
    |--------------------------------------------------------------------------
    | When true: throw if a paid plan slug is missing a price id mapping.
    | Recommended true for production.
    */
    'strict' => env('PADDLE_PLANS_STRICT', true),

];

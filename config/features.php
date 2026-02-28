
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Plans
    |--------------------------------------------------------------------------
    | You can keep these as slugs and map them from Stripe products/prices.
    */
    'plans' => [

        'free' => [
            'label' => 'Free',
            'limits' => [
                'daily_qa_runs' => 5,
                'max_file_size_mb' => 10,
                'batch_enabled' => false,
                'ai_summary' => false,
                'pdf_export' => false,
                'presets' => ['custom'], // keep very limited
                'share_links' => true,
            ],
        ],

        'starter' => [
            'label' => 'Starter',
            'limits' => [
                'daily_qa_runs' => 200,
                'max_file_size_mb' => 50,
                'batch_enabled' => false,
                'ai_summary' => true,
                'pdf_export' => true,
                'presets' => ['left_chest', 'cap', 'patch', 'custom'],
                'share_links' => true,
            ],
        ],

        'shop' => [
            'label' => 'Shop',
            'limits' => [
                'daily_qa_runs' => 2000,
                'max_file_size_mb' => 100,
                'batch_enabled' => true,
                'ai_summary' => true,
                'pdf_export' => true,
                'presets' => ['left_chest', 'cap', 'patch', 'custom'],
                'team_members' => 10,
                'share_links' => true,
                'api_access' => false,
            ],
        ],

        'digitizer' => [
            'label' => 'Digitizer',
            'limits' => [
                'daily_qa_runs' => 10000,
                'max_file_size_mb' => 250,
                'batch_enabled' => true,
                'ai_summary' => true,
                'pdf_export' => true,
                'presets' => ['left_chest', 'cap', 'patch', 'custom'],
                'team_members' => 25,
                'share_links' => true,
                'white_label_reports' => true,
                'api_access' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Credit costs (usage-based)
    |--------------------------------------------------------------------------
    | If a plan allows a feature, you can still debit credits for heavy operations.
    */
    'credits' => [
        'qa_ai_summary' => 1,
        'qa_pdf_export' => 1,
        'batch_item_proof' => 1,
        'batch_export_zip' => 5,
        'priority_queue_multiplier' => 2, // optional
    ],

];

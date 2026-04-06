<?php

return [
    'starter' => [
        'name' => 'Starter',
        'price' => 500,
        'lease_days' => 30,
        'bandwidth_limit_bytes' => 10 * 1024 * 1024 * 1024,
        'features' => [
            'pos',
            'product_management',
            'basic_sales_tracking',
            'basic_reports',
            'order_queue',
        ],
        'max_users' => 1,
    ],
    'standard' => [
        'name' => 'Standard',
        'price' => 1500,
        'lease_days' => 30,
        'bandwidth_limit_bytes' => 20 * 1024 * 1024 * 1024,
        'features' => [
            'pos',
            'product_management',
            'order_queue',
            'brewing_guides',
            'inventory_management',
            'sales_reports',
            'branding',
        ],
        'max_users' => 3,
    ],
    'business' => [
        'name' => 'Business',
        'price' => 2000,
        'lease_days' => 30,
        'bandwidth_limit_bytes' => null,
        'features' => [
            'pos',
            'product_management',
            'order_queue',
            'brewing_guides',
            'inventory_management',
            'sales_reports',
            'sales_dashboard',
            'inventory_alerts',
            'advanced_analytics',
            'multi_branch_support',
            'priority_support',
            'branding',
        ],
        'max_users' => null, // unlimited
    ],
];

<?php

return [
    'starter' => [
        'name' => 'Starter',
        'price' => 500,
        'features' => [
            'pos',
            'product_management',
            'basic_sales_tracking',
            'basic_reports',
        ],
        'max_users' => 1,
    ],
    'standard' => [
        'name' => 'Standard',
        'price' => 1500,
        'features' => [
            'pos',
            'product_management',
            'inventory_management',
            'sales_reports',
            'sales_dashboard',
            'inventory_alerts',
        ],
        'max_users' => 5,
    ],
    'business' => [
        'name' => 'Business',
        'price' => 2000,
        'features' => [
            'pos',
            'product_management',
            'inventory_management',
            'sales_reports',
            'sales_dashboard',
            'inventory_alerts',
            'advanced_analytics',
            'multi_branch_support',
            'priority_support',
        ],
        'max_users' => null, // unlimited
    ],
];

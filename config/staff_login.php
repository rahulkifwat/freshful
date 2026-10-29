<?php

/*
| Freshful staff sign-in portal (added 22 Oct 2026).
| One design for every staff login page. Only the GET page (what people see) is replaced;
| every POST /.../login (the actual password check) is the original code, unchanged.
| Switch off instantly: STAFF_LOGIN_PORTAL=false in .env, then php artisan config:clear.
*/
return [
    'enabled' => filter_var(env('STAFF_LOGIN_PORTAL', true), FILTER_VALIDATE_BOOLEAN),

    // Shown in "Forgot password?". Leave empty to show "contact your reporting manager / admin team".
    'support' => [
        'name' => env('STAFF_SUPPORT_NAME', 'Freshful Admin Team'),
        'phone' => env('STAFF_SUPPORT_PHONE', ''),        // e.g. +919876543210
        'whatsapp' => env('STAFF_SUPPORT_WHATSAPP', ''),  // digits with country code, e.g. 919876543210
        'email' => env('STAFF_SUPPORT_EMAIL', ''),        // e.g. admin@freshful.in
        'hours' => env('STAFF_SUPPORT_HOURS', '7:00 AM - 10:00 PM, every day'),
    ],

    // key => login page address (form posts back to the same address), name, what the dashboard covers, icon, group.
    // 'login_type' text = email or phone accepted (POS console accepts both).
    'roles' => [
        'hub' => ['path' => 'hub/login', 'name' => 'Hub Manager', 'desc' => 'Orders, stock, danger stock, riders and cash for your hub', 'icon' => 'store', 'group' => 'Hub & store'],
        'pos' => ['path' => 'pos/console/login', 'also' => ['pos/login'], 'name' => 'POS Counter', 'desc' => 'Live order board, billing, packing and dispatch', 'icon' => 'pos', 'group' => 'Hub & store', 'login_type' => 'text'],
        'area_manager' => ['path' => 'area_manager/login', 'name' => 'Area Manager', 'desc' => 'Every hub in your area: sales, stock and teams', 'icon' => 'pin', 'group' => 'Field management'],
        'operation_manager' => ['path' => 'operation_manager/login', 'name' => 'Operation Manager', 'desc' => 'City operations, hubs and delivery performance', 'icon' => 'route', 'group' => 'Field management'],
        'country_manager' => ['path' => 'country_manager/login', 'name' => 'Country Manager', 'desc' => 'All-India view, including franchise hubs', 'icon' => 'globe', 'group' => 'Field management'],
        'account_manager' => ['path' => 'account_manager/login', 'name' => 'Accounts', 'desc' => 'Cash deposits, collections and finance reports', 'icon' => 'wallet', 'group' => 'Head office'],
        'marketing_manager' => ['path' => 'marketing_manager/login', 'name' => 'Marketing', 'desc' => 'Banners, promotions, coupons and customers', 'icon' => 'megaphone', 'group' => 'Head office'],
        'hr_manager' => ['path' => 'hr_manager/login', 'name' => 'HR', 'desc' => 'Staff records and people management', 'icon' => 'users', 'group' => 'Head office'],
        'planning_manager' => ['path' => 'planning_manager/login', 'name' => 'Planning', 'desc' => 'Demand, purchase and stock planning', 'icon' => 'calendar', 'group' => 'Head office'],
        'production' => ['path' => 'production/login', 'name' => 'Production', 'desc' => 'Processing, cutting and packing', 'icon' => 'factory', 'group' => 'Head office'],
        // Admin is restyled too, but not offered in the picker on other pages (no need to advertise the admin door).
        'admin' => ['path' => 'admin/login', 'name' => 'Admin', 'desc' => 'Full system administration', 'icon' => 'shield', 'group' => 'Head office', 'hidden' => true],
    ],
];

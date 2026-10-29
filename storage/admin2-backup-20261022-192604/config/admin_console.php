<?php

/* Freshful admin console (admin update 1, 22 Oct 2026): head-office modules at /admin/console. */
return [
    // Folder that holds uploads/images (the live site's public folder). Empty = found automatically (../public_html/uploads).
    'uploads_root' => env('ADMIN_UPLOADS_ROOT', ''),

    // States offered for cities (states.country_id; 101 = India in the existing table).
    'country_id' => (int) env('ADMIN_COUNTRY_ID', 101),

    // Staff passwords: auto = same form the table already uses (secure if any row is secure), hash = always secure, plain = never.
    'staff_passwords' => env('ADMIN_STAFF_PASSWORDS', 'auto'),

    // Circle delivery areas: radius the hub finder uses (km), shown on the map.
    'circle_km' => (float) env('HUB_CIRCLE_KM', 3),

    // Rows one list screen loads at most.
    'list_limit' => 5000,

    // Push notifications (Firebase Cloud Messaging HTTP v1).
    'fcm' => [
        'project_id' => env('FCM_PROJECT_ID', ''),
        'service_account' => env('FCM_SERVICE_ACCOUNT', ''),   // full path of the Firebase service-account JSON, outside public_html
        'topic' => env('FCM_TOPIC', 'all'),                     // the old admin sent to /topics/all
        'mode' => env('FCM_MODE', 'data'),                      // data = like the old admin; both = also a visible notification block
    ],
];

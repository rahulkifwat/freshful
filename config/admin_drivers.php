<?php

/* Freshful admin console: Driver management (drivers update 1, 28 Oct 2026). */
return [
    // Extra folders that hold driver photos / documents, comma separated (each must contain profile_photos/ and documents/).
    // Empty = the folders the rider app API uses (laravelapp/public/uploads/driver and public_html/uploads/driver).
    'upload_dirs' => env('DRIVER_UPLOAD_DIRS', ''),

    // Largest single wallet credit or debit the admin can enter (rupees).
    'wallet_max_entry' => (float) env('DRIVER_WALLET_MAX_ENTRY', 50000),
];

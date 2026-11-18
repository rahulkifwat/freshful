#!/bin/bash
cd "/home/u182416408/domains/freshful.in/laravelapp" || exit 1
for f in resources/views/home_v2/index.blade.php app/Services/Storefront/StorefrontSettings.php app/Http/Controllers/Storefront/HomeSettingsController.php resources/views/home_v2/admin_settings.blade.php; do cp "/home/u182416408/domains/freshful.in/laravelapp/storage/home9-backup-20261108_155615_1522611/$f" "$f"; done
rm -f resources/views/home_v2/extras.blade.php resources/views/home_v2/ribbons.blade.php
php artisan view:clear >/dev/null 2>&1
echo "Undone. The home page is back to how it was."

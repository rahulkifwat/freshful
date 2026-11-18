#!/bin/bash
cd "/home/u182416408/domains/freshful.in/laravelapp"
for f in app/Services/Storefront/StorefrontSettings.php app/Http/Controllers/Storefront/HomeSettingsController.php resources/views/home_v2/admin_settings.blade.php resources/views/home_v2/extras.blade.php resources/views/home_v2/ribbons.blade.php; do cp "storage/sizes3-backup-20261110_170038_2105114/$f" "$f"; done
php artisan view:clear >/dev/null
echo "Undone."

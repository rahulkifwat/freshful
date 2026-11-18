#!/bin/bash
cd "/home/u182416408/domains/freshful.in/laravelapp"
for f in app/Services/Storefront/StorefrontSettings.php resources/views/home_v2/ribbons.blade.php; do cp "storage/sizes4-backup-20261110_171038_2203878/$f" "$f"; done
php artisan view:clear >/dev/null
echo "Undone."

#!/bin/bash
cd "/home/u182416408/domains/freshful.in/laravelapp" || exit 1
for f in app/Http/Controllers/AdminConsole/AdminConsoleController.php routes/admin_console.php app/Http/Controllers/Api/BuyerApp/CatalogController.php resources/views/country_console/app.blade.php resources/views/country_console/assets/ad.js; do cp "/home/u182416408/domains/freshful.in/laravelapp/storage/appcontrol2-backup-20261105_123658/$f" "$f"; done
rm -f app/Services/AdminConsole/AppControl.php app/Services/BuyerApp/AppCardStyle.php database/migrations/2026_11_05_150000_create_app_card_style_tables.php
php artisan view:clear >/dev/null 2>&1; php artisan config:clear >/dev/null 2>&1; php artisan route:clear >/dev/null 2>&1
echo "Undone. The two new tables were left in the database (harmless)."

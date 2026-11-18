#!/bin/bash
cd "/home/u182416408/domains/freshful.in/laravelapp" || exit 1
for f in app/Services/BuyerApp/AppHomeConfig.php app/Http/Controllers/AdminConsole/AdminConsoleController.php routes/admin_console.php resources/views/country_console/app.blade.php resources/views/country_console/assets/ad.js .env; do cp "/home/u182416408/domains/freshful.in/laravelapp/storage/homepics-backup-20261105_144309/$f" "$f"; done
rm -f app/Services/AdminConsole/HomePics.php
php artisan view:clear >/dev/null 2>&1; php artisan config:clear >/dev/null 2>&1; php artisan route:clear >/dev/null 2>&1
echo "Undone (files and .env restored). Rows you added in Home pictures stay in the database (harmless)."

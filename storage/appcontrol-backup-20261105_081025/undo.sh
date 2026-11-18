#!/bin/bash
cd "/home/u182416408/domains/freshful.in/laravelapp" || exit 1
for f in app/Services/AdminConsole/AdminResources.php app/Http/Controllers/Api/BuyerApp/CatalogController.php resources/views/country_console/app.blade.php resources/views/country_console/assets/ad.js; do cp "/home/u182416408/domains/freshful.in/laravelapp/storage/appcontrol-backup-20261105_081025/$f" "$f"; done
rm -f app/Services/BuyerApp/AppHomeConfig.php
php artisan view:clear >/dev/null 2>&1; php artisan config:clear >/dev/null 2>&1
echo "Undone. The two new tables were left in the database (harmless). To remove them: php artisan tinker, then Schema::dropIfExists('app_home_blocks'); Schema::dropIfExists('app_home_theme');"

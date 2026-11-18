#!/bin/bash
cd "/home/u182416408/domains/freshful.in/laravelapp"
cp "storage/ribbonmulti-backup-20261110_181500_2959688/HomePics.php" "app/Services/AdminConsole/HomePics.php"; cp "storage/ribbonmulti-backup-20261110_181500_2959688/ad.js" "resources/views/country_console/assets/ad.js"; php artisan view:clear >/dev/null
echo "Undone."

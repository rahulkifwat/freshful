#!/bin/bash
cd /home/u182416408/domains/freshful.in/laravelapp
cp -p storage/backup_admin_color_20261103_130937/AdminResources.php app/Services/AdminConsole/AdminResources.php; cp -p storage/backup_admin_color_20261103_130937/AdminRecords.php app/Services/AdminConsole/AdminRecords.php; cp -p storage/backup_admin_color_20261103_130937/js/ad.js resources/views/country_console/assets/ad.js
php artisan view:clear >/dev/null 2>&1; php artisan optimize:clear >/dev/null 2>&1
echo "Rolled back to the state before the colour picker."

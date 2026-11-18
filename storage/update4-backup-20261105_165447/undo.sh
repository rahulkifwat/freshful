#!/bin/bash
cd "/home/u182416408/domains/freshful.in/laravelapp" || exit 1
cp -a "/home/u182416408/domains/freshful.in/laravelapp/storage/update4-backup-20261105_165447/app" "/home/u182416408/domains/freshful.in/laravelapp/storage/update4-backup-20261105_165447/resources" . 2>/dev/null
cp -a "/home/u182416408/domains/freshful.in/laravelapp/storage/update4-backup-20261105_165447/routes/buyer_app_commerce.php" "routes/buyer_app_commerce.php"
rm -f app/Http/Controllers/*/StockAlertController.php app/Http/Controllers/StockAlertController.php
php artisan route:clear >/dev/null 2>&1; php artisan view:clear >/dev/null 2>&1
echo restored

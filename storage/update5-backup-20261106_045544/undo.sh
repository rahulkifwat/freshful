#!/bin/bash
cd "/home/u182416408/domains/freshful.in/laravelapp" || exit 1
cp -a "/home/u182416408/domains/freshful.in/laravelapp/storage/update5-backup-20261106_045544/routes/buyer_app_commerce.php" "routes/buyer_app_commerce.php"
rm -f "app/Http/Controllers/Api/BuyerApp/AppEngageController.php"
php artisan route:clear >/dev/null 2>&1
echo restored

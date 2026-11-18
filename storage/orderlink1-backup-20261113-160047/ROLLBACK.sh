#!/usr/bin/env bash
cd "/home/u182416408/domains/freshful.in/laravelapp" || exit 1
cp -p "storage/orderlink1-backup-20261113-160047/OrderController.php" "app/Http/Controllers/Api/Driver/OrderController.php"
if [ "1" = "1" ]; then rm -f "app/Services/Driver/RiderLink.php"; else cp -p "storage/orderlink1-backup-20261113-160047/RiderLink.php" "app/Services/Driver/RiderLink.php"; fi
if [ "1" = "1" ]; then rm -f "database/migrations/2026_11_13_100000_add_driver_id_to_delivery_boy.php"; fi
rm -f orderlink_check.php
echo "Rolled back. The rider app order API is as it was. (The empty driver_id column, if it was added, is harmless and stays.)"

#!/usr/bin/env bash
cd "/home/u182416408/domains/freshful.in/laravelapp" || exit 1
cp -p "storage/dview1-backup-20261113-165458/RiderLink.php" "app/Services/Driver/RiderLink.php" && cp -p "storage/dview1-backup-20261113-165458/AdminDrivers.php" "app/Services/AdminConsole/AdminDrivers.php"
rm -f dview_check.php
echo "Rolled back. RiderLink.php and AdminDrivers.php are as they were."

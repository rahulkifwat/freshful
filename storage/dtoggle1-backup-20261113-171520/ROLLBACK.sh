#!/usr/bin/env bash
cd "/home/u182416408/domains/freshful.in/laravelapp" || exit 1
cp -p "storage/dtoggle1-backup-20261113-171520/AdminDrivers.php" "app/Services/AdminConsole/AdminDrivers.php" && rm -f dtog_check.php && echo "Rolled back. AdminDrivers.php is as it was."

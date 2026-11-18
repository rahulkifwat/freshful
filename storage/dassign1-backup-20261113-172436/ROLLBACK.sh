#!/usr/bin/env bash
cd "/home/u182416408/domains/freshful.in/laravelapp" || exit 1
cp -p "storage/dassign1-backup-20261113-172436/DriverManagementService.php" "app/Services/DriverManagementService.php" && rm -f dassign_check.php && echo "Rolled back. DriverManagementService.php is as it was."

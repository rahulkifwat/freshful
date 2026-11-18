#!/usr/bin/env bash
cd "/home/u182416408/domains/freshful.in/laravelapp" || exit 1
cp "storage/empno1-backup-20261114-152019/HubPeopleService.php" "app/Services/HubConsole/HubPeopleService.php" && cp "storage/empno1-backup-20261114-152019/HubConsoleController.php" "app/Http/Controllers/HubConsole/HubConsoleController.php" && cp "storage/empno1-backup-20261114-152019/AdminDriverEdit.php" "app/Services/AdminConsole/AdminDriverEdit.php" && echo "Code files put back."
if [ "0" = "1" ]; then cp "storage/empno1-backup-20261114-152019/EmployeeNo.php" "app/Services/Driver/EmployeeNo.php"; else rm -f "app/Services/Driver/EmployeeNo.php"; fi
echo "The table rider_serials and the column cities.city_code are left in place (they are harmless and unused)."

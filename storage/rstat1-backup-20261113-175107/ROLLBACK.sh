#!/usr/bin/env bash
cd "/home/u182416408/domains/freshful.in/laravelapp" || exit 1
cp "storage/rstat1-backup-20261113-175107/HubPeopleService.php" "app/Services/HubConsole/HubPeopleService.php" && cp "storage/rstat1-backup-20261113-175107/AdminDrivers.php" "app/Services/AdminConsole/AdminDrivers.php" && echo "Code files put back."
if [ -f "storage/rstat1-backup-20261113-175107/rollback.php" ]; then php "storage/rstat1-backup-20261113-175107/rollback.php"; else echo "No rider was switched off, so none to switch back on."; fi

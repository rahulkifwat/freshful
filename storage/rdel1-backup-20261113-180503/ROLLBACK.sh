#!/usr/bin/env bash
cd "/home/u182416408/domains/freshful.in/laravelapp" || exit 1
if [ -f "storage/rdel1-backup-20261113-180503/rollback.php" ]; then php "storage/rdel1-backup-20261113-180503/rollback.php"; else echo "Nothing was deleted, so there is nothing to put back."; fi

#!/usr/bin/env bash
cd "/home/u182416408/domains/freshful.in/laravelapp" || exit 1
if [ -f "storage/pdel1-backup-20261114-160748/rollback.php" ]; then php "storage/pdel1-backup-20261114-160748/rollback.php"; else echo "Nothing was deleted, so there is nothing to put back."; fi

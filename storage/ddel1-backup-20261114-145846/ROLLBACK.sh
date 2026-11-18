#!/usr/bin/env bash
cd "/home/u182416408/domains/freshful.in/laravelapp" || exit 1
if [ -f "storage/ddel1-backup-20261114-145846/rollback.php" ]; then php "storage/ddel1-backup-20261114-145846/rollback.php"; else echo "Nothing was deleted, so there is nothing to put back."; fi

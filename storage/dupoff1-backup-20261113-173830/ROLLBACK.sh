#!/usr/bin/env bash
cd "/home/u182416408/domains/freshful.in/laravelapp" || exit 1
if [ -f "storage/dupoff1-backup-20261113-173830/rollback.php" ]; then php "storage/dupoff1-backup-20261113-173830/rollback.php"; else echo "Nothing was switched off, so there is nothing to put back."; fi

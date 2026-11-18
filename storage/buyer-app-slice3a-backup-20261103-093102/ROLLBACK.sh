#!/usr/bin/env bash
# Undo slice 3a: restores bootstrap/providers.php, removes the files slice 3a added, puts back any it replaced.
APP="/home/u182416408/domains/freshful.in/laravelapp"; BK="/home/u182416408/domains/freshful.in/laravelapp/storage/buyer-app-slice3a-backup-20261103-093102"
cp -a "$BK/providers.php.orig" "$APP/bootstrap/providers.php"
while read -r f; do [ -n "$f" ] && rm -f "$APP/$f"; done < "$BK/new.txt"
while read -r f; do [ -n "$f" ] && cp -a "$BK/files/$f" "$APP/$f"; done < "$BK/existing.txt"
cd "$APP" && php artisan route:clear >/dev/null 2>&1; php artisan config:clear >/dev/null 2>&1
echo "Slice 3a rolled back. Slices 1 and 2, the website and the driver routes were not touched."

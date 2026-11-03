#!/usr/bin/env bash
# Undo slice 2: restores bootstrap/providers.php, removes the files slice 2 added, puts back any it replaced.
APP="/home/u182416408/domains/freshful.in/laravelapp"; BK="/home/u182416408/domains/freshful.in/laravelapp/storage/buyer-app-slice2-backup-20261103-065712"
cp -a "$BK/providers.php.orig" "$APP/bootstrap/providers.php"
while read -r f; do [ -n "$f" ] && rm -f "$APP/$f"; done < "$BK/new.txt"
while read -r f; do [ -n "$f" ] && cp -a "$BK/files/$f" "$APP/$f"; done < "$BK/existing.txt"
rmdir "$APP/app/Services/BuyerApp/Shop" 2>/dev/null
cd "$APP" && php artisan route:clear >/dev/null 2>&1; php artisan config:clear >/dev/null 2>&1
echo "Slice 2 rolled back. Slice 1, the website and the driver routes were not touched."

#!/usr/bin/env bash
# Undo slice 6: removes the files slice 6 added and puts back the route file it replaced.
APP="/home/u182416408/domains/freshful.in/laravelapp"; BK="/home/u182416408/domains/freshful.in/laravelapp/storage/buyer-app-slice6-backup-20261103-140029"
while read -r f; do [ -n "$f" ] && rm -f "$APP/$f"; done < "$BK/new.txt"
while read -r f; do [ -n "$f" ] && cp -a "$BK/files/$f" "$APP/$f"; done < "$BK/existing.txt"
cd "$APP"; php artisan route:clear >/dev/null 2>&1; php artisan config:clear >/dev/null 2>&1
echo "Slice 6 rolled back. Slices 1 to 4, the website, the POS and the rider and driver routes were not touched."

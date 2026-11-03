#!/bin/bash
# Undo slice 1:  bash /home/u182416408/domains/freshful.in/laravelapp/storage/buyer-app-slice1-backup-20261103-061837/ROLLBACK.sh
cp -f "/home/u182416408/domains/freshful.in/laravelapp/storage/buyer-app-slice1-backup-20261103-061837/providers.php" "/home/u182416408/domains/freshful.in/laravelapp/bootstrap/providers.php"
while IFS= read -r f; do [ -n "$f" ] && rm -f "/home/u182416408/domains/freshful.in/laravelapp/$f"; done < "/home/u182416408/domains/freshful.in/laravelapp/storage/buyer-app-slice1-backup-20261103-061837/created.txt"
(cd "/home/u182416408/domains/freshful.in/laravelapp/storage/buyer-app-slice1-backup-20261103-061837/files" && find . -type f | while IFS= read -r f; do mkdir -p "/home/u182416408/domains/freshful.in/laravelapp/$(dirname "$f")"; cp -f "$f" "/home/u182416408/domains/freshful.in/laravelapp/$f"; done)
cd "/home/u182416408/domains/freshful.in/laravelapp" && php artisan config:clear && php artisan route:clear
echo "Slice 1 removed."

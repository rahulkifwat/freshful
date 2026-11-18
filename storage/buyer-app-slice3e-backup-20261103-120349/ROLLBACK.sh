#!/usr/bin/env bash
# Undo slice 3e: restores bootstrap/providers.php, removes the files slice 3e added, puts back any it replaced.
APP="/home/u182416408/domains/freshful.in/laravelapp"; BK="/home/u182416408/domains/freshful.in/laravelapp/storage/buyer-app-slice3e-backup-20261103-120349"
cp -a "$BK/providers.php.orig" "$APP/bootstrap/providers.php"
while read -r f; do [ -n "$f" ] && rm -f "$APP/$f"; done < "$BK/new.txt"
while read -r f; do [ -n "$f" ] && cp -a "$BK/files/$f" "$APP/$f"; done < "$BK/existing.txt"
cd "$APP"
if [ "$(cat "$BK/table_existed.txt" 2>/dev/null)" = "0" ]; then
  php artisan tinker --execute='Schema::dropIfExists("app_payment_intents"); DB::table("migrations")->where("migration","2026_11_03_160000_create_app_payment_intents_table")->delete(); echo "table removed";' >/dev/null 2>&1
fi
php artisan route:clear >/dev/null 2>&1; php artisan config:clear >/dev/null 2>&1
echo "Slice 3e rolled back. The two slice 2 cancel files were put back as they were. The website, the POS and the rider and driver routes were not touched."

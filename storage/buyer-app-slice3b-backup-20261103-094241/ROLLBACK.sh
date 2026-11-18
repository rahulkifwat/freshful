#!/usr/bin/env bash
# Undo slice 3b: restores bootstrap/providers.php, removes the files slice 3b added, puts back any it replaced.
APP="/home/u182416408/domains/freshful.in/laravelapp"; BK="/home/u182416408/domains/freshful.in/laravelapp/storage/buyer-app-slice3b-backup-20261103-094241"
cp -a "$BK/providers.php.orig" "$APP/bootstrap/providers.php"
while read -r f; do [ -n "$f" ] && rm -f "$APP/$f"; done < "$BK/new.txt"
while read -r f; do [ -n "$f" ] && cp -a "$BK/files/$f" "$APP/$f"; done < "$BK/existing.txt"
cd "$APP"
if [ "$(cat "$BK/column_existed.txt" 2>/dev/null)" = "0" ]; then
  php artisan tinker --execute='if (Schema::hasColumn("buyer_address","app_deleted_at")) { Schema::table("buyer_address", function ($t) { $t->dropColumn("app_deleted_at"); }); } DB::table("migrations")->where("migration","2026_11_03_130000_add_app_deleted_at_to_buyer_address")->delete(); echo "column removed";' >/dev/null 2>&1
fi
php artisan route:clear >/dev/null 2>&1; php artisan config:clear >/dev/null 2>&1
echo "Slice 3b rolled back. Slices 1 and 2, the website and the driver routes were not touched."

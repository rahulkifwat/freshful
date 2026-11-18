#!/usr/bin/env bash
# Undo slice 4: restores bootstrap/providers.php, removes the files slice 4 added, puts back any it replaced.
APP="/home/u182416408/domains/freshful.in/laravelapp"; BK="/home/u182416408/domains/freshful.in/laravelapp/storage/buyer-app-slice4-backup-20261103-123531"
cp -a "$BK/providers.php.orig" "$APP/bootstrap/providers.php"
while read -r f; do [ -n "$f" ] && rm -f "$APP/$f"; done < "$BK/new.txt"
while read -r f; do [ -n "$f" ] && cp -a "$BK/files/$f" "$APP/$f"; done < "$BK/existing.txt"
cd "$APP"
if [ "$(cat "$BK/column_existed.txt" 2>/dev/null)" = "0" ]; then
  php artisan tinker --execute='if (Schema::hasColumn("category","bg_color")) { Schema::table("category", function ($t) { $t->dropColumn("bg_color"); }); } DB::table("migrations")->where("migration","2026_11_03_190000_add_bg_color_to_category")->delete(); echo "column removed";' >/dev/null 2>&1
fi
php artisan route:clear >/dev/null 2>&1; php artisan config:clear >/dev/null 2>&1
echo "Slice 4 rolled back. The category colour column was removed again if this installer had added it. The website, the POS and the rider and driver routes were not touched."

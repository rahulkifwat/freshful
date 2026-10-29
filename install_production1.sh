#!/bin/bash
# Freshful production update 1 (28 Oct 2026): production manager console at /production/console.
# Run from the laravelapp folder, with freshful-production1.zip in the same folder:
#   cd ~/domains/freshful.in/laravelapp && bash install_production1.sh
# Adds new files only, plus small anchored additions to 3 files (backed up first). Every check must pass,
# otherwise everything is put back automatically.
set -u
APP="$(pwd)"
ZIP="$APP/freshful-production1.zip"
SITE="${FRESHFUL_URL:-https://freshful.in}"
TS="$(date +%Y%m%d-%H%M%S)"
BK="$APP/storage/production1-backup-$TS"
NEWFILES="app/Http/Middleware/ProductionConsoleAuth.php app/Http/Controllers/ProductionConsole/ProductionConsoleController.php app/Services/ProductionConsole/ProductionAccess.php app/Services/ProductionConsole/ProductionBoard.php app/Services/ProductionConsole/ProductionDispatch.php app/Services/ProductionConsole/ProductionLog.php app/Services/ProductionConsole/ProductionPlan.php config/production_console.php database/migrations/2026_10_28_200000_create_production_console_tables.php routes/production_console.php resources/views/country_console/assets/pr.css resources/views/country_console/assets/pr.js"
PATCHED="resources/views/country_console/app.blade.php resources/views/country_console/assets/cc.js routes/web.php"

say() { echo "[production1] $*"; }
code() { php -r '$h=@get_headers($argv[1]); echo $h ? substr($h[0],9,3) : "000";' "$1"; }
posts() { php artisan route:list --method=POST 2>/dev/null | grep -v "ccp\." | grep -E "POST" | sed 's/ \+/ /g' | sort | md5sum | cut -c1-12; }

[ -f artisan ] || { say "Run this inside the laravelapp folder."; exit 1; }
[ -f "$ZIP" ] || { say "freshful-production1.zip not found in $APP"; exit 1; }
for f in $NEWFILES; do
  if [ -e "$f" ] && ! grep -q "production update 1" "$f" 2>/dev/null; then say "STOP: $f already exists and is not from this update. Nothing changed."; exit 1; fi
done

say "1/6 Checking the site before the update"
declare -A BEFORE
PAGES="/ /staff /production/login /production/console /production/dashboard /admin/login /admin/console /country_manager/console /hub/console /hub/login /pos/console/login"
for p in $PAGES; do BEFORE[$p]=$(code "$SITE$p"); echo "   $p ${BEFORE[$p]}"; done
POSTS_BEFORE=$(posts)

say "2/6 Backup to $BK"
mkdir -p "$BK"
for f in $PATCHED; do mkdir -p "$BK/$(dirname "$f")"; cp -p "$f" "$BK/$f"; done
for f in $NEWFILES; do [ -e "$f" ] && { mkdir -p "$BK/$(dirname "$f")"; cp -p "$f" "$BK/$f"; }; done

rollback() {
  say "ROLLING BACK: $1"
  for f in $PATCHED; do cp -p "$BK/$f" "$f"; done
  for f in $NEWFILES; do if [ -e "$BK/$f" ]; then cp -p "$BK/$f" "$f"; else rm -f "$f"; fi; done
  rmdir app/Services/ProductionConsole app/Http/Controllers/ProductionConsole 2>/dev/null
  php artisan route:clear >/dev/null 2>&1; php artisan view:clear >/dev/null 2>&1; php artisan config:clear >/dev/null 2>&1
  for p in / /production/login /admin/login; do echo "   after rollback $p $(code "$SITE$p")"; done
  say "ROLLED_BACK. The site is as it was. New tables (production_*) may remain; they are unused and harmless."
  exit 1
}

say "3/6 Copying the new files"
TMP="$APP/storage/production1-unzip-$TS"
mkdir -p "$TMP" && unzip -q -o "$ZIP" -d "$TMP" || rollback "could not unzip the package"
for f in $NEWFILES; do
  [ -f "$TMP/$f" ] || rollback "missing in package: $f"
  mkdir -p "$(dirname "$f")"; cp "$TMP/$f" "$f"
done
for f in $NEWFILES; do case "$f" in *.php) php -l "$f" >/dev/null 2>&1 || rollback "PHP error in $f";; esac; done

say "4/6 Small additions to 3 existing files"
python3 "$TMP/patch_production.py" "$APP" || rollback "patch did not apply (an anchor did not match)"
for f in routes/web.php; do php -l "$f" >/dev/null 2>&1 || rollback "PHP error in $f"; done
rm -rf "$TMP"

say "5/6 Database: new tables only"
php artisan migrate --path=database/migrations/2026_10_28_200000_create_production_console_tables.php --force 2>&1 | tail -3
for t in production_plan_lines production_batches production_batch_lines production_pending_clears; do
  php -r 'require "vendor/autoload.php"; $a=require "bootstrap/app.php"; $a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); exit(Illuminate\Support\Facades\Schema::hasTable($argv[1]) ? 0 : 1);' "$t" || rollback "table $t was not created"
done
php artisan route:clear >/dev/null 2>&1; php artisan view:clear >/dev/null 2>&1; php artisan config:clear >/dev/null 2>&1

say "6/6 Checking the site after the update"
php artisan route:list --name=ccp. 2>/dev/null | grep -q "ccp.home" || rollback "the production console routes did not load"
FAIL=""
for p in $PAGES; do
  a=$(code "$SITE$p"); echo "   $p ${BEFORE[$p]} -> $a"
  b=${BEFORE[$p]}
  if [ "$b" = "200" ] && [ "$a" != "200" ]; then FAIL="$FAIL $p"; fi
  if [ "$b" = "302" ] && [ "$a" != "302" ]; then FAIL="$FAIL $p"; fi
done
[ "$(code "$SITE/production/console")" = "302" ] || FAIL="$FAIL /production/console(expected 302 when logged out)"
[ -z "$FAIL" ] || rollback "pages got worse:$FAIL"
[ "$(posts)" = "$POSTS_BEFORE" ] || rollback "existing POST routes changed"
php -r 'require "vendor/autoload.php"; $a=require "bootstrap/app.php"; $a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); file_put_contents($argv[1], app("blade.compiler")->compileString(file_get_contents("resources/views/country_console/app.blade.php")));' "$BK/app.compiled.php" && php -l "$BK/app.compiled.php" >/dev/null 2>&1 || rollback "the console page template does not compile"

say "INSTALL_OK. Production console: $SITE/production/console (log in at $SITE/production/login)."
say "Backup: $BK"

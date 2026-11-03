#!/bin/bash
# Freshful Digital Menu update 4 (31 Oct 2026)
#  - Effects engine on the TV: light streak with dust burst (KFC style), dish built from particles, spark burst,
#    smoke reveal, spotlight beam, shock ring.
#  - Background effects: light bokeh, rising embers, steam, ice mist, moving light rays, sparkles.
#  - Slide changes: fade, colour wipe, light flash, zoom blur, push slide, circle reveal, blinds.
#  - New layout "Menu wall": upright condensed heading font, 2 to 4 cut-out products per slide, price circle,
#    white label, small tags; products grow out of a red glow one after another. Flour puff effect (KFC 3D).
#  - New themes "Black and red" and "Dark slate"; heading font choice; transparent pictures are detected automatically.
#  - Rolling digit prices, light shine across each dish, light streak repeats every 12 seconds.
#  - Admin: "5. Motion and effects" with 9 quick styles.
#  - Contains everything from update 3, so it installs whether update 3 went in or not.
# Upload freshful-dm4.zip and this file to the laravelapp folder, then:
#   cd ~/domains/freshful.in/laravelapp && bash install_dm4.sh
# Backup first. Any failed check puts the old files back automatically.
set -u
HERE="$(cd "$(dirname "$0")" && pwd)"
APP="$HOME/domains/freshful.in/laravelapp"
ZIP="$HERE/freshful-dm4.zip"
SITE="${FF_SITE:-https://freshful.in}"
BK="$APP/storage/dm4-backup-$(date +%Y%m%d-%H%M%S)"
OLD=( "app/Services/DigitalMenu/Cutout.php" "app/Services/DigitalMenu/DigitalMenu.php" "app/Http/Controllers/DigitalMenu/DigitalMenuAdminController.php" "routes/digital_menu.php" "resources/views/digital_menu/player.blade.php" "resources/views/digital_menu/admin.blade.php" )
say() { echo "[dm4] $*"; }
code() { curl -s -o /dev/null -w '%{http_code}' --max-time 25 "$1"; }
clear_caches() { (cd "$APP" && php artisan route:clear >/dev/null 2>&1; php artisan view:clear >/dev/null 2>&1; php artisan config:clear >/dev/null 2>&1); }
restore() { for f in "${OLD[@]}"; do [ -f "$BK/$f" ] && cp -p "$BK/$f" "$APP/$f"; done; clear_caches; }
fail() { say "FAILED: $*"; restore; say "ROLLED_BACK (old files are back)"; exit 1; }

[ -f "$ZIP" ] || { say "freshful-dm4.zip not found next to this file ($HERE)"; exit 1; }
for f in "${OLD[@]}"; do [ -f "$APP/$f" ] || { say "missing on server: $f (install_dm1.sh and install_dm2.sh first)"; exit 1; }; done
command -v unzip >/dev/null 2>&1 || { say "unzip is not available"; exit 1; }
if grep -q "'anims' => DigitalMenu::ANIM" "$APP/app/Http/Controllers/DigitalMenu/DigitalMenuAdminController.php"; then say "Update 3 was installed before"; else say "Update 3 was not installed; this update includes it"; fi
H0="$(code "$SITE/")"; A0="$(code "$SITE/admin/console")"
say "Before: / = $H0   /admin/console = $A0"
for f in "${OLD[@]}"; do mkdir -p "$BK/$(dirname "$f")"; cp -p "$APP/$f" "$BK/$f"; done
say "Backup: $BK"

unzip -oq "$ZIP" -d "$APP" || fail "unzip"
for f in app/Services/DigitalMenu/DigitalMenu.php app/Services/DigitalMenu/Cutout.php app/Http/Controllers/DigitalMenu/DigitalMenuAdminController.php routes/digital_menu.php; do
  php -l "$APP/$f" >/dev/null || fail "syntax error in $f"
done
cd "$APP" || fail "cd"
BLF="$(php -r 'require "vendor/autoload.php"; $a = require "bootstrap/app.php"; $a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); foreach (["resources/views/digital_menu/player.blade.php","resources/views/digital_menu/admin.blade.php"] as $v) { $c = app("blade.compiler")->compileString(file_get_contents($v)); $tmp = tempnam(sys_get_temp_dir(), "ffdm"); file_put_contents($tmp, $c); echo $tmp, "\n"; }' 2>/dev/null | grep '^/')"
[ -n "$BLF" ] || fail "could not compile the pages"
for f in $BLF; do php -l "$f" >/dev/null 2>&1 || { rm -f $BLF; fail "a page does not compile"; }; done
rm -f $BLF
say "Pages compile"
if command -v node >/dev/null 2>&1; then
  TMPJ="$(mktemp)"; sed -n '/<script>/,/<\/script>/p' "$APP/resources/views/digital_menu/player.blade.php" | sed '1d;$d' > "$TMPJ"
  node --check "$TMPJ" >/dev/null 2>&1 || { rm -f "$TMPJ"; fail "TV page script error"; }; rm -f "$TMPJ"; say "TV page script checked"
fi
clear_caches
RL="$(php artisan route:list 2>&1)"
for n in dm.admin dm.player dm.data "digital-menu/api/cutout"; do echo "$RL" | grep -q "$n" || fail "route $n did not register"; done
say "Routes registered"

TEST_OUT="$(php -r '
require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB; use App\Services\DigitalMenu\DigitalMenu;
if (!isset(DigitalMenu::LAYOUTS["wall"]) || !isset(DigitalMenu::FX["intro"]["flour"]) || !isset(DigitalMenu::ANIM["pic"]["grow"]) || !isset(DigitalMenu::PRESETS["blackred"])) { echo "ERROR new settings missing\n"; exit; }
$w = DigitalMenu::sanitize(["wall_cols" => 4, "theme" => ["hfont" => "Oswald"]], DigitalMenu::defaults("t"));
if ($w["wall_cols"] !== 4 || $w["theme"]["hfont"] !== "Oswald") { echo "ERROR wall settings are not kept\n"; exit; }
$c = DigitalMenu::sanitize(["layout" => "wall", "headline" => "Fresh today!", "fx" => ["intro" => "assemble", "ambient" => "bad-value", "trans" => "iris", "glint" => "0"], "anim" => ["price" => "roll"]], DigitalMenu::defaults("t"));
if ($c["fx"]["intro"] !== "assemble" || $c["fx"]["ambient"] !== "bokeh" || $c["fx"]["trans"] !== "iris" || $c["fx"]["glint"] !== false || $c["anim"]["price"] !== "roll" || $c["layout"] !== "wall") { echo "ERROR effect settings are not kept correctly\n"; exit; }
$old = DigitalMenu::sanitize(["name" => "old screen", "layout" => "trio"]);
if (($old["fx"]["intro"] ?? "") !== "auto") { echo "ERROR old screens do not get the default effects\n"; exit; }
$hub = (int) DB::table("hubs")->orderBy("id")->value("id");
DB::beginTransaction();
try {
  $a = (int) DB::table("dm_items")->insertGetId(["name" => "Test item", "pack" => "1 kg", "price" => 99, "mrp" => 120, "category" => "Chicken", "status" => "active", "created_at" => now(), "updated_at" => now()]);
  $b = (int) DB::table("dm_items")->insertGetId(["name" => "Test item 2", "pack" => "1 kg", "price" => 199, "category" => "Seafood", "status" => "active", "created_at" => now(), "updated_at" => now()]);
  $c["items"] = [["id" => $a, "from" => null, "until" => null, "featured" => true], ["id" => $b, "from" => null, "until" => null, "featured" => false]];
  $pay = DigitalMenu::payload($c, $hub);
  if (($pay["screen"]["fx"]["intro"] ?? "") !== "assemble" || ($pay["screen"]["headline"] ?? "") !== "Fresh today!" || count($pay["items"]) !== 2) throw new Exception("payload is wrong");
  DB::rollBack(); echo "SELF_TEST_OK\n";
} catch (Throwable $e) { DB::rollBack(); echo "ERROR " . $e->getMessage() . "\n"; }
' 2>&1)"
echo "$TEST_OUT" | sed 's/^/[dm4] test: /'
echo "$TEST_OUT" | grep -q "SELF_TEST_OK" || fail "self-test did not pass"

AC="$(code "$SITE/admin/digital-menu")"
case "$AC" in 200|302) ;; *) fail "admin Digital Menu returned $AC";; esac
[ "$(code "$SITE/display/WRONGKEYWRONGKEYWRONG12")" = "404" ] || fail "a wrong TV key did not return 404"
say "Live check: admin page $AC, wrong TV key is refused"
clear_caches
H1="$(code "$SITE/")"; A1="$(code "$SITE/admin/console")"
say "After:  / = $H1   /admin/console = $A1"
[ "$H1" = "$H0" ] || fail "home page status changed ($H0 -> $H1)"
case "$A1" in 200|302) ;; *) fail "admin console returned $A1";; esac
say "INSTALL_OK"
say "Open Marketing > Digital Menu, press Ctrl+F5, edit a screen: layout Menu wall, theme Black and red, quick style Red menu board, Save draft, Publish."

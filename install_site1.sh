#!/bin/bash
# Freshful site update 1 (31 Oct 2026): new header, footer and head; compare removed;
# social links (Facebook, Instagram, X, YouTube, LinkedIn); one Google Maps loader (Stores map fix);
# new home page (home-v2) switched on at https://freshful.in/ (old page stays at /home-classic).
#
# Upload freshful-site1.zip and this file to the laravelapp folder, then over SSH:
#   cd ~/domains/freshful.in/laravelapp && bash install_site1.sh
#
# Backup first. If any check fails, every changed file is put back automatically.
# To install everything except the home page switch:  FF_SKIP_HOME=1 bash install_site1.sh
set -u
HERE="$(cd "$(dirname "$0")" && pwd)"
APP="${FF_APP:-$HOME/domains/freshful.in/laravelapp}"
ZIP="$HERE/freshful-site1.zip"
SITE="${FF_SITE:-https://freshful.in}"
BK="$APP/storage/site1-backup-$(date +%Y%m%d-%H%M%S)"
STAGE="$(mktemp -d)"
CHANGED_FILES=()
HOME_SWITCHED=0
say() { echo "[site1] $*"; }
code() { curl -s -o /dev/null -w '%{http_code}' --max-time 30 "$1"; }
clear_caches() { (cd "$APP" && php artisan view:clear >/dev/null 2>&1; php artisan config:clear >/dev/null 2>&1; php artisan route:clear >/dev/null 2>&1); }
restore() {
  if [ -d "$BK" ]; then (cd "$BK" && find . -type f ! -name '.env' | while read -r f; do cp -p "$BK/$f" "$APP/$f"; done); fi
  [ -f "$BK/.env" ] && cp -p "$BK/.env" "$APP/.env"
  clear_caches
}
fail() { say "FAILED: $*"; restore; say "ROLLED_BACK (all files and .env are back as they were)"; rm -rf "$STAGE"; exit 1; }

[ -f "$ZIP" ] || { say "freshful-site1.zip not found next to this file ($HERE)"; rm -rf "$STAGE"; exit 1; }
[ -d "$APP/resources/views" ] || { say "laravelapp not found at $APP (set FF_APP=/path if it is elsewhere)"; rm -rf "$STAGE"; exit 1; }
command -v unzip >/dev/null 2>&1 || { say "unzip is not available"; rm -rf "$STAGE"; exit 1; }
unzip -oq "$ZIP" -d "$STAGE" || { say "could not open the zip"; rm -rf "$STAGE"; exit 1; }
for f in head header footer; do [ -f "$STAGE/site1/$f.blade.php" ] || { say "zip is missing $f.blade.php"; rm -rf "$STAGE"; exit 1; }; done
php -l "$STAGE/site1/ff_site1_patch.php" >/dev/null || { say "patch script is damaged"; rm -rf "$STAGE"; exit 1; }

# ---- find the three layout files on the server (each must match exactly one file) ----
find_view() { # $1 = label, rest = strings that must all be in the file
  local label="$1"; shift
  local first="$1"; shift
  local hits=() f ok s lst="$STAGE/find.$$.txt"
  grep -rlF --include='*.blade.php' -- "$first" "$APP/resources/views" > "$lst" 2>/dev/null
  while IFS= read -r f; do
    ok=1
    for s in "$@"; do grep -qF -- "$s" "$f" || { ok=0; break; }; done
    [ "$ok" = 1 ] && hits+=("$f")
  done < "$lst"
  rm -f "$lst"
  if [ "${#hits[@]}" -ne 1 ]; then
    say "could not pick the $label file safely (found ${#hits[@]}):" >&2; printf '   %s\n' "${hits[@]:-none}" >&2
    rm -rf "$STAGE"; exit 1
  fi
  echo "${hits[0]}"
}
HEAD_F="$(find_view head '--ff-accent-gradient:linear-gradient' '<head>' 'ff-header-row')" || exit 1
HEADER_F="$(find_view header 'id="myHeader"' 'ff-utility-bar')" || exit 1
FOOTER_F="$(find_view footer '<footer id="footer"')" || exit 1
say "Head:   ${HEAD_F#$APP/}"
say "Header: ${HEADER_F#$APP/}"
say "Footer: ${FOOTER_F#$APP/}"

# ---- compile-check the new Blade files before touching anything ----
blade_check() { # files...
  local tmps t bad=0
  tmps="$(cd "$APP" && php -r 'require "vendor/autoload.php"; $a = require "bootstrap/app.php"; $a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); foreach (array_slice($argv, 1) as $v) { $c = app("blade.compiler")->compileString(file_get_contents($v)); $tmp = tempnam(sys_get_temp_dir(), "ffs1"); file_put_contents($tmp, $c); echo $tmp, "\n"; }' -- "$@" 2>/dev/null | grep '^/')"
  [ "$(echo "$tmps" | grep -c '^/')" -eq "$#" ] || return 1
  for t in $tmps; do php -l "$t" >/dev/null 2>&1 || bad=1; rm -f "$t"; done
  return $bad
}
blade_check "$STAGE/site1/head.blade.php" "$STAGE/site1/header.blade.php" "$STAGE/site1/footer.blade.php" || { say "the new layout files do not compile on this server"; rm -rf "$STAGE"; exit 1; }
say "New layout files compile."

# ---- before-state of the pages we will check ----
MAPS_URL="$(cd "$APP" && php -r 'require "vendor/autoload.php"; $a = require "bootstrap/app.php"; $a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo route("maps");' 2>/dev/null | grep -o 'https\?://[^ ]*' | head -n 1)"
[ -n "$MAPS_URL" ] || MAPS_URL="$SITE/maps"
URLS=("$SITE/" "$SITE/home-v2" "$SITE/view_product/all" "$SITE/about" "$MAPS_URL")
declare -A BEFORE
for u in "${URLS[@]}"; do BEFORE["$u"]="$(code "$u")"; say "  before: $u = ${BEFORE[$u]}"; done

# ---- backup ----
mkdir -p "$BK"
for f in "$HEAD_F" "$HEADER_F" "$FOOTER_F" "$APP/.env"; do
  rel="${f#$APP/}"; mkdir -p "$BK/$(dirname "$rel")"; cp -p "$f" "$BK/$rel"
done
say "Backup: $BK"

# ---- install layout files ----
cp "$STAGE/site1/head.blade.php" "$HEAD_F" || fail "copy head"
cp "$STAGE/site1/header.blade.php" "$HEADER_F" || fail "copy header"
cp "$STAGE/site1/footer.blade.php" "$FOOTER_F" || fail "copy footer"

# ---- other pages: remove compare buttons, one Google Maps loader ----
PATCH_OUT="$(php "$STAGE/site1/ff_site1_patch.php" "$APP" "$BK" "$HEAD_F" "$HEADER_F" "$FOOTER_F" 2>&1)" || fail "page patch: $PATCH_OUT"
echo "$PATCH_OUT" | sed 's/^/[site1]   /'
CH=()
echo "$PATCH_OUT" | grep '^CHANGED ' > "$STAGE/changed.txt"
while IFS= read -r line; do p="${line#CHANGED }"; p="${p%% (*}"; CH+=("$APP/$p"); done < "$STAGE/changed.txt"
if [ "${#CH[@]}" -gt 0 ]; then blade_check "${CH[@]}" || fail "a patched page no longer compiles"; fi
clear_caches

# ---- check the pages ----
for u in "${URLS[@]}"; do
  A="$(code "$u")"
  if [ "${BEFORE[$u]}" = "200" ] && [ "$A" != "200" ]; then
    tail -n 30 "$APP/storage/logs/laravel.log" 2>/dev/null | grep -v '^#' | tail -n 6
    fail "$u was 200 and is now $A"
  fi
  say "  after:  $u = $A"
done
PAGE="$(curl -s --max-time 30 "$SITE/about")"
[ -n "$PAGE" ] || PAGE="$(curl -s --max-time 30 "$SITE/")"
echo "$PAGE" | grep -q 'class="ff-foot-grid"' || fail "new footer not found on the page"
echo "$PAGE" | grep -q 'class="ff-utility-social"' || fail "new header not found on the page"
echo "$PAGE" | grep -q 'ffMapsBoot' || fail "Maps loader not found on the page"
echo "$PAGE" | grep -q 'id="compare-page-link"' && fail "compare link is still in the header"
for s in 'instagram.com/freshfulfood' 'facebook.com/freshfulfood' 'x.com/Freshfullfoods' 'youtube.com/@Freshfulfoods' 'linkedin.com/company/freshfulfoods'; do
  echo "$PAGE" | grep -q "$s" || fail "social link $s missing"
done
say "  header, footer, 5 social links and Maps loader are live; compare is gone."

# ---- Stores map: what the page looks like now (kept in a file for support) ----
DIAG="$APP/storage/logs/site1-maps-diag.txt"
{ echo "Stores page: $MAPS_URL  ($(date))"; curl -s --max-time 30 "$MAPS_URL" > "$STAGE/maps.html"
  echo "-- map script tags:"; grep -o '<script[^>]*src="[^"]*map[^"]*"[^>]*>' "$STAGE/maps.html" | sed 's/key=[A-Za-z0-9_-]\{8\}[A-Za-z0-9_-]*/key=XXXXXXXX.../'
  echo "-- map containers:"; grep -o '<[a-z]* [^>]*id="[^"]*map[^"]*"[^>]*>' "$STAGE/maps.html" | head -n 10
  echo "-- google map frames:"; grep -o '<iframe[^>]*google[^>]*>' "$STAGE/maps.html" | sed 's/key=[A-Za-z0-9_-]\{8\}[A-Za-z0-9_-]*/key=XXXXXXXX.../' | head -n 5
  echo "-- registered callbacks:"; grep -o "__ffMapsCb||\[\]).concat(\[[^]]*\])" "$STAGE/maps.html"; } > "$DIAG" 2>&1
say "Stores page report saved: ${DIAG#$APP/}"

# ---- make home-v2 the home page ----
if [ "${FF_SKIP_HOME:-0}" = "1" ]; then
  say "Home page switch skipped (FF_SKIP_HOME=1)."
elif [ ! -f "$APP/routes/home_v2.php" ]; then
  say "Home v2 is not installed on this server, so the home page was not switched."
elif grep -q '^STOREFRONT_HOME_V2=true' "$APP/.env"; then
  say "Home v2 is already the home page."
else
  if grep -q '^STOREFRONT_HOME_V2=' "$APP/.env"; then sed -i 's/^STOREFRONT_HOME_V2=.*/STOREFRONT_HOME_V2=true/' "$APP/.env"
  else printf '\nSTOREFRONT_HOME_V2=true\n' >> "$APP/.env"; fi
  clear_caches
  HC="$(code "$SITE/")"; CC="$(code "$SITE/home-classic")"
  HP="$(curl -s --max-time 30 "$SITE/")"
  [ "$HC" = "200" ] || fail "home page returned $HC after the switch"
  echo "$HP" | grep -q 'ffh-hero-slide' || fail "new home page not shown at / after the switch"
  say "  / = $HC (new home page)   /home-classic = $CC (old page kept)"
  HOME_SWITCHED=1
fi

rm -rf "$STAGE"
say "INSTALL_OK  Open $SITE/ and press Ctrl+F5 once. Backup: $BK"
[ "$HOME_SWITCHED" = 1 ] && say "Undo the home switch any time: set STOREFRONT_HOME_V2=false in .env, then php artisan config:clear"
exit 0

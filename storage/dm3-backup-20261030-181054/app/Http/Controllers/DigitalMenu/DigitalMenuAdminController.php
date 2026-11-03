<?php

namespace App\Http\Controllers\DigitalMenu;

use App\Http\Controllers\Controller;
use App\Services\DigitalMenu\Cutout;
use App\Services\DigitalMenu\DigitalMenu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/** Admin: Marketing > Digital Menu. JSON API used by the page at /admin/digital-menu. Admin login required for every call. */
class DigitalMenuAdminController extends Controller
{
    private function who()
    {
        try {
            return Auth::guard('admin')->user();
        } catch (Throwable $e) {
            return null;
        }
    }

    private function deny()
    {
        return response()->json(['ok' => false, 'message' => 'Please sign in to the admin again.'], 401);
    }

    private function fail(string $m, int $code = 422)
    {
        return response()->json(['ok' => false, 'message' => $m], $code);
    }

    private function guard(): bool
    {
        return (bool) $this->who() && DigitalMenu::tableReady();
    }

    public function page(Request $r)
    {
        if (! $this->who()) {
            return redirect('/admin/login');
        }

        return view('digital_menu.admin', ['ready' => DigitalMenu::tableReady(), 'embed' => $r->header('Sec-Fetch-Dest') === 'iframe' || $r->boolean('embed')]);
    }

    public function boot()
    {
        if (! $this->guard()) {
            return $this->deny();
        }
        $hubs = DB::table('hubs')->leftJoin('cities', 'cities.id', '=', 'hubs.city_id')->orderBy('hubs.id')->get(['hubs.id', 'hubs.hub', 'cities.city'])
            ->map(fn ($h) => ['id' => (int) $h->id, 'label' => trim(($h->city ?: 'Hub') . ($h->hub ? ' (' . $h->hub . ')' : ''))])->all();
        $used = DB::table('dm_screens')->select('hub_id', DB::raw('COUNT(*) c'))->groupBy('hub_id')->orderByDesc('c')->first();
        $def = (int) env('DIGITAL_MENU_HUB', env('OUTLET_KIOSK_HUB', 0)) ?: ($used->hub_id ?? ($hubs[0]['id'] ?? 0));

        return response()->json(['ok' => true, 'hubs' => $hubs, 'hub' => (int) $def, 'max' => DigitalMenu::MAX_SCREENS, 'layouts' => DigitalMenu::LAYOUTS, 'fonts' => DigitalMenu::FONTS,
            'presets' => DigitalMenu::PRESETS, 'defaults' => DigitalMenu::defaults()]);
    }

    private function summary($s): array
    {
        $d = json_decode($s->draft, true) ?: [];
        $d = DigitalMenu::sanitize($d);
        $live = $s->live ? DigitalMenu::sanitize(json_decode($s->live, true) ?: []) : null;

        return [
            'id' => (int) $s->id, 'hub_id' => (int) $s->hub_id, 'name' => $s->name, 'status' => $s->status, 'orientation' => $d['orientation'], 'layout' => $d['layout'],
            'seconds' => $d['seconds'], 'items' => count($d['items']), 'offers' => count($d['offers']), 'version' => (int) $s->version, 'published_at' => $s->published_at,
            'published' => (bool) $live, 'dirty' => ! $live || DigitalMenu::hash($live) !== DigitalMenu::hash($d), 'key' => $s->public_key,
            'url' => url('/display/' . $s->public_key), 'preview' => url('/display/' . $s->public_key . '?preview=1'),
        ];
    }

    public function screens(Request $r)
    {
        if (! $this->guard()) {
            return $this->deny();
        }
        $hub = (int) $r->query('hub');
        $rows = DB::table('dm_screens')->where('hub_id', $hub)->orderBy('sort')->orderBy('id')->get();

        return response()->json(['ok' => true, 'screens' => $rows->map(fn ($s) => $this->summary($s))->all(), 'max' => DigitalMenu::MAX_SCREENS]);
    }

    public function show(int $id)
    {
        if (! $this->guard()) {
            return $this->deny();
        }
        $s = DB::table('dm_screens')->where('id', $id)->first();
        if (! $s) {
            return $this->fail('Screen not found.', 404);
        }
        $cfg = DigitalMenu::sanitize(json_decode($s->draft, true) ?: []);

        return response()->json(['ok' => true, 'screen' => $this->summary($s), 'config' => $cfg, 'resolved' => DigitalMenu::resolve(array_column($cfg['items'], 'id'))]);
    }

    public function create(Request $r)
    {
        if (! $this->guard()) {
            return $this->deny();
        }
        $hub = (int) $r->input('hub_id');
        if (! DB::table('hubs')->where('id', $hub)->exists()) {
            return $this->fail('Choose an outlet first.');
        }
        $name = trim((string) $r->input('name'));
        if ($name === '') {
            return $this->fail('Give the screen a name, for example Counter TV 1.');
        }
        try {
            $id = DB::transaction(function () use ($hub, $name, $r) {
                $n = DB::table('dm_screens')->where('hub_id', $hub)->lockForUpdate()->count();
                if ($n >= DigitalMenu::MAX_SCREENS) {
                    return 0;
                }
                $cfg = DigitalMenu::sanitize(['name' => $name, 'orientation' => $r->input('orientation'), 'layout' => $r->input('layout')], DigitalMenu::defaults($name));

                return (int) DB::table('dm_screens')->insertGetId(['hub_id' => $hub, 'name' => $cfg['name'], 'public_key' => DigitalMenu::newKey(), 'status' => 'active',
                    'draft' => json_encode($cfg), 'sort' => $n + 1, 'created_at' => now(), 'updated_at' => now()]);
            });
        } catch (Throwable $e) {
            report($e);

            return $this->fail('Could not create the screen.', 500);
        }
        if (! $id) {
            return $this->fail('This outlet already has ' . DigitalMenu::MAX_SCREENS . ' screens, the maximum.');
        }

        return $this->show($id);
    }

    public function save(Request $r, int $id)
    {
        if (! $this->guard()) {
            return $this->deny();
        }
        $s = DB::table('dm_screens')->where('id', $id)->first();
        if (! $s) {
            return $this->fail('Screen not found.', 404);
        }
        $in = $r->input('config');
        if (! is_array($in)) {
            return $this->fail('Nothing to save.');
        }
        $cfg = DigitalMenu::sanitize($in, DigitalMenu::sanitize(json_decode($s->draft, true) ?: []));
        $ids = array_column($cfg['items'], 'id');
        if ($ids) {
            $have = DB::table('dm_items')->whereIn('id', $ids)->pluck('id')->map(fn ($x) => (int) $x)->all();
            $cfg['items'] = array_values(array_filter($cfg['items'], fn ($x) => in_array($x['id'], $have, true)));
        }
        DB::table('dm_screens')->where('id', $id)->update(['name' => $cfg['name'], 'draft' => json_encode($cfg), 'updated_at' => now()]);

        return $this->show($id);
    }

    public function publish(int $id)
    {
        if (! $this->guard()) {
            return $this->deny();
        }
        $s = DB::table('dm_screens')->where('id', $id)->first();
        if (! $s) {
            return $this->fail('Screen not found.', 404);
        }
        $cfg = DigitalMenu::sanitize(json_decode($s->draft, true) ?: []);
        if ($cfg['layout'] === 'offers') {
            if (! $cfg['offers']) {
                return $this->fail('Add at least one offer to this poster before publishing.');
            }
        } else {
            $p = DigitalMenu::payload($cfg, (int) $s->hub_id);
            if (! $cfg['items']) {
                return $this->fail('Add at least one item to the playlist before publishing.');
            }
            if (! $p['items']) {
                return $this->fail('None of the playlist items can be shown right now (switched off, out of stock or outside their dates). Check the items.');
            }
        }
        $u = $this->who();
        $ver = (int) $s->version + 1;
        DB::table('dm_screens')->where('id', $id)->update(['live' => json_encode($cfg), 'version' => $ver, 'published_at' => now(), 'published_by' => $u->id ?? null, 'updated_at' => now()]);
        DB::table('dm_publish_log')->insert(['screen_id' => $id, 'user_id' => $u->id ?? null, 'user_name' => mb_substr((string) ($u->name ?? $u->email ?? 'admin'), 0, 80), 'version' => $ver, 'hash' => DigitalMenu::hash($cfg), 'note' => null]);
        Log::info('[digital-menu] screen ' . $id . ' published v' . $ver . ' by admin ' . ($u->id ?? '?'));

        return $this->show($id);
    }

    public function status(Request $r, int $id)
    {
        if (! $this->guard()) {
            return $this->deny();
        }
        $to = $r->input('status') === 'off' ? 'off' : 'active';
        DB::table('dm_screens')->where('id', $id)->update(['status' => $to, 'updated_at' => now()]);

        return $this->show($id);
    }

    public function rekey(int $id)
    {
        if (! $this->guard()) {
            return $this->deny();
        }
        DB::table('dm_screens')->where('id', $id)->update(['public_key' => DigitalMenu::newKey(), 'updated_at' => now()]);

        return $this->show($id);
    }

    public function duplicate(int $id)
    {
        if (! $this->guard()) {
            return $this->deny();
        }
        $s = DB::table('dm_screens')->where('id', $id)->first();
        if (! $s) {
            return $this->fail('Screen not found.', 404);
        }
        $n = DB::table('dm_screens')->where('hub_id', $s->hub_id)->count();
        if ($n >= DigitalMenu::MAX_SCREENS) {
            return $this->fail('This outlet already has ' . DigitalMenu::MAX_SCREENS . ' screens, the maximum.');
        }
        $cfg = DigitalMenu::sanitize(json_decode($s->draft, true) ?: []);
        $cfg['name'] = mb_substr($cfg['name'] . ' copy', 0, 80);
        $new = DB::table('dm_screens')->insertGetId(['hub_id' => $s->hub_id, 'name' => $cfg['name'], 'public_key' => DigitalMenu::newKey(), 'status' => 'active', 'draft' => json_encode($cfg), 'sort' => $n + 1, 'created_at' => now(), 'updated_at' => now()]);

        return $this->show((int) $new);
    }

    public function destroy(int $id)
    {
        if (! $this->guard()) {
            return $this->deny();
        }
        DB::table('dm_screens')->where('id', $id)->delete();
        Log::info('[digital-menu] screen ' . $id . ' deleted by admin ' . optional($this->who())->id);

        return response()->json(['ok' => true]);
    }

    // ---------- item library ----------

    private function itemRow($i, array $res, array $usage): array
    {
        return [
            'id' => (int) $i->id, 'product_code' => $i->product_code, 'name' => $i->name, 'pack' => $i->pack, 'price' => $i->price, 'mrp' => $i->mrp, 'description' => $i->description,
            'image' => $i->image, 'cutout' => (bool) $i->cutout, 'badge' => $i->badge, 'category' => $i->category, 'veg' => $i->veg === null ? null : (bool) $i->veg, 'status' => $i->status,
            'shown' => $res[(int) $i->id] ?? null, 'used' => $usage[(int) $i->id] ?? [],
        ];
    }

    private function usage(): array
    {
        $u = [];
        foreach (DB::table('dm_screens')->get(['id', 'name', 'hub_id', 'draft']) as $s) {
            foreach ((json_decode($s->draft, true)['items'] ?? []) as $r) {
                $u[(int) ($r['id'] ?? 0)][] = ['id' => (int) $s->id, 'name' => $s->name, 'hub_id' => (int) $s->hub_id];
            }
        }

        return $u;
    }

    public function items(Request $r)
    {
        if (! $this->guard()) {
            return $this->deny();
        }
        $q = DB::table('dm_items');
        $st = $r->query('status', 'active');
        if (in_array($st, ['active', 'archived'], true)) {
            $q->where('status', $st);
        }
        if ($c = trim((string) $r->query('cat'))) {
            $q->where('category', $c);
        }
        if ($s = trim((string) $r->query('q'))) {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $s) . '%';
            $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('product_code', 'like', $like)->orWhere('category', 'like', $like));
        }
        $rows = $q->orderByDesc('id')->limit(400)->get();
        $res = DigitalMenu::resolve($rows->pluck('id')->all());
        $use = $this->usage();
        $cats = DB::table('dm_items')->whereNotNull('category')->where('category', '!=', '')->distinct()->orderBy('category')->pluck('category')->all();

        return response()->json(['ok' => true, 'items' => $rows->map(fn ($i) => $this->itemRow($i, $res, $use))->all(), 'categories' => $cats]);
    }

    private function uploadDir(Request $request): ?string
    {
        $bases = [];
        $doc = (string) $request->server('DOCUMENT_ROOT', '');
        if ($doc !== '') {
            $bases[] = rtrim($doc, '/') . '/uploads/images';
        }
        $bases[] = public_path('uploads/images');
        foreach ($bases as $b) {
            if (! is_dir($b)) {
                continue;
            }
            $d = $b . '/digital_menu';
            if (! is_dir($d)) {
                @mkdir($d, 0755, true);
            }
            if (is_dir($d) && is_writable($d)) {
                return $d;
            }
        }

        return null;
    }

    /** Validate, store and shrink (max 1600 px) a picture. Returns [filename, null] or [null, message]. */
    private function store(Request $request, string $field, string $prefix): array
    {
        $f = $request->file($field);
        if (! $f || ! $f->isValid()) {
            return [null, 'The picture could not be read.'];
        }
        $ext = strtolower($f->getClientOriginalExtension() ?: '');
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return [null, 'Use a JPG, PNG or WebP picture.'];
        }
        if ($f->getSize() > 8 * 1024 * 1024) {
            return [null, 'The picture must be 8 MB or smaller.'];
        }
        $sz = @getimagesize($f->getRealPath());
        if (! $sz || $sz[0] < 200 || $sz[1] < 200) {
            return [null, 'The picture is too small. Use at least 600 pixels on the long side for a sharp TV picture.'];
        }
        $dir = $this->uploadDir($request);
        if (! $dir) {
            return [null, 'The upload folder is not writable. Please tell your developer.'];
        }
        $ext = $ext === 'jpeg' ? 'jpg' : $ext;
        $name = $prefix . '-' . date('YmdHis') . '-' . Str::lower(Str::random(6)) . '.' . $ext;
        try {
            $f->move($dir, $name);
            $this->shrink($dir . '/' . $name, $ext, $sz);
        } catch (Throwable $e) {
            report($e);

            return [null, 'Upload failed. Please try again.'];
        }

        return [$name, null];
    }

    private function shrink(string $path, string $ext, array $sz): void
    {
        $max = 1600;
        if (max($sz[0], $sz[1]) <= $max || ! function_exists('imagecreatetruecolor')) {
            return;
        }
        $src = $ext === 'png' ? @imagecreatefrompng($path) : ($ext === 'webp' ? (function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false) : @imagecreatefromjpeg($path));
        if (! $src) {
            return;
        }
        $k = $max / max($sz[0], $sz[1]);
        $w = (int) round($sz[0] * $k);
        $h = (int) round($sz[1] * $k);
        $dst = imagecreatetruecolor($w, $h);
        if ($ext !== 'jpg') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, $sz[0], $sz[1]);
        if ($ext === 'png') {
            imagepng($dst, $path, 6);
        } elseif ($ext === 'webp' && function_exists('imagewebp')) {
            imagewebp($dst, $path, 88);
        } else {
            imagejpeg($dst, $path, 88);
        }
        imagedestroy($src);
        imagedestroy($dst);
    }

    public function upload(Request $r)
    {
        if (! $this->guard()) {
            return $this->deny();
        }
        [$name, $err] = $this->store($r, 'image', 'dm');
        if ($err) {
            return $this->fail($err);
        }

        return response()->json(['ok' => true, 'file' => $name, 'url' => '/uploads/images/digital_menu/' . $name]);
    }

    /** Remove a plain background. Source: an uploaded picture ("image"), a picture already in the Digital Menu folder ("file") or a product code ("code"). */
    public function cutout(Request $r)
    {
        if (! $this->guard()) {
            return $this->deny();
        }
        if (! Cutout::available()) {
            return $this->fail('Background removal is not available on this server (image library missing).');
        }
        @ini_set('memory_limit', '384M');
        @set_time_limit(60);
        $dir = $this->uploadDir($r);
        if (! $dir) {
            return $this->fail('The upload folder is not writable. Please tell your developer.');
        }
        $tol = (int) $r->input('tol', 40);
        $tmp = null;
        $src = null;
        if ($r->hasFile('image')) {
            $f = $r->file('image');
            $ext = strtolower($f->getClientOriginalExtension() ?: '');
            if (! $f->isValid() || ! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) || $f->getSize() > 8 * 1024 * 1024) {
                return $this->fail('Use a JPG, PNG or WebP picture up to 8 MB.');
            }
            $src = $f->getRealPath();
        } elseif ($file = basename((string) $r->input('file'))) {
            if (! preg_match('/^[A-Za-z0-9._-]{3,120}$/', $file) || ! is_file($dir . '/' . $file)) {
                return $this->fail('That picture was not found.');
            }
            $src = $dir . '/' . $file;
        } elseif ($code = trim((string) $r->input('code'))) {
            $img = DB::table('products')->where('product_id', $code)->value('product_image');
            if (! $img) {
                return $this->fail('This product has no picture.');
            }
            foreach ([rtrim((string) $r->server('DOCUMENT_ROOT', ''), '/') . '/uploads/images/products/' . basename($img), public_path('uploads/images/products/' . basename($img))] as $c) {
                if (is_file($c)) {
                    $src = $c;
                    break;
                }
            }
            if (! $src) {
                return $this->fail('The product picture file was not found on the server.');
            }
        } else {
            return $this->fail('Choose a picture first.');
        }
        $sz = @getimagesize($src);
        if (! $sz || $sz[0] < 200 || $sz[1] < 200) {
            return $this->fail('The picture is too small. Use at least 600 pixels on the long side.');
        }
        $name = 'cut-' . date('YmdHis') . '-' . Str::lower(Str::random(6)) . '.png';
        try {
            [$ok, $msg, $info] = Cutout::run($src, $dir . '/' . $name, $tol);
        } catch (Throwable $e) {
            report($e);

            return $this->fail('The background could not be removed from this picture.', 500);
        }
        if (! $ok) {
            @unlink($dir . '/' . $name);

            return $this->fail($msg);
        }

        return response()->json(['ok' => true, 'file' => $name, 'url' => '/uploads/images/digital_menu/' . $name, 'message' => $msg, 'info' => $info]);
    }

    public function itemSave(Request $r)
    {
        if (! $this->guard()) {
            return $this->deny();
        }
        $id = (int) $r->input('id');
        $row = $id ? DB::table('dm_items')->where('id', $id)->first() : null;
        if ($id && ! $row) {
            return $this->fail('Item not found.', 404);
        }
        $code = trim((string) $r->input('product_code'));
        if ($code !== '' && ! DB::table('products')->where('product_id', $code)->exists()) {
            return $this->fail('That product code was not found.');
        }
        $name = trim((string) $r->input('name'));
        $price = $r->input('price');
        $mrp = $r->input('mrp');
        if ($code === '' && ($name === '' || $price === null || $price === '' || ! is_numeric($price) || (float) $price <= 0)) {
            return $this->fail('A custom item needs a name and a price. Or pick a product to link.');
        }
        foreach ([['price', $price], ['mrp', $mrp]] as [$k, $v]) {
            if ($v !== null && $v !== '' && (! is_numeric($v) || (float) $v < 0 || (float) $v > 999999)) {
                return $this->fail('Enter a valid ' . ($k === 'mrp' ? 'MRP' : 'price') . '.');
            }
        }
        $veg = $r->input('veg');
        $data = [
            'product_code' => $code !== '' ? $code : null, 'name' => $name !== '' ? mb_substr($name, 0, 120) : null, 'pack' => mb_substr(trim((string) $r->input('pack')), 0, 40) ?: null,
            'price' => ($price === null || $price === '') ? null : round((float) $price, 2), 'mrp' => ($mrp === null || $mrp === '') ? null : round((float) $mrp, 2),
            'description' => mb_substr(trim((string) $r->input('description')), 0, 255) ?: null, 'badge' => mb_substr(trim((string) $r->input('badge')), 0, 40) ?: null,
            'category' => mb_substr(trim((string) $r->input('category')), 0, 60) ?: null, 'veg' => ($veg === '1' || $veg === 1 || $veg === true) ? 1 : (($veg === '0' || $veg === 0 || $veg === false) ? 0 : null),
            'cutout' => $r->boolean('cutout') ? 1 : 0, 'updated_at' => now(),
        ];
        if ($r->hasFile('image')) {
            [$f, $err] = $this->store($r, 'image', 'item');
            if ($err) {
                return $this->fail($err);
            }
            $data['image'] = $f;
        } elseif (($ifile = basename((string) $r->input('image_file'))) !== '' && preg_match('/^[A-Za-z0-9._-]{3,120}$/', $ifile) && ($udir = $this->uploadDir($r)) && is_file($udir . '/' . $ifile)) {
            $data['image'] = $ifile;
        } elseif ($r->boolean('remove_image')) {
            $data['image'] = null;
        }
        if ($row) {
            DB::table('dm_items')->where('id', $id)->update($data);
        } else {
            $data['status'] = 'active';
            $data['created_by'] = optional($this->who())->id;
            $data['created_at'] = now();
            $id = (int) DB::table('dm_items')->insertGetId($data);
        }
        if ($r->has('screens')) {
            $this->assign($id, array_map('intval', (array) $r->input('screens')));
        }
        $i = DB::table('dm_items')->where('id', $id)->first();

        return response()->json(['ok' => true, 'item' => $this->itemRow($i, DigitalMenu::resolve([$id]), $this->usage())]);
    }

    public function itemState(Request $r, int $id)
    {
        if (! $this->guard()) {
            return $this->deny();
        }
        DB::table('dm_items')->where('id', $id)->update(['status' => $r->input('status') === 'archived' ? 'archived' : 'active', 'updated_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function itemDuplicate(int $id)
    {
        if (! $this->guard()) {
            return $this->deny();
        }
        $i = DB::table('dm_items')->where('id', $id)->first();
        if (! $i) {
            return $this->fail('Item not found.', 404);
        }
        $a = (array) $i;
        unset($a['id']);
        $a['name'] = $a['name'] ? mb_substr($a['name'] . ' copy', 0, 120) : null;
        $a['created_at'] = $a['updated_at'] = now();
        $new = (int) DB::table('dm_items')->insertGetId($a);

        return response()->json(['ok' => true, 'id' => $new]);
    }

    private function assign(int $itemId, array $screenIds): void
    {
        foreach (DB::table('dm_screens')->get(['id', 'draft']) as $s) {
            $cfg = DigitalMenu::sanitize(json_decode($s->draft, true) ?: []);
            $has = false;
            foreach ($cfg['items'] as $r) {
                if ($r['id'] === $itemId) {
                    $has = true;
                }
            }
            $want = in_array((int) $s->id, $screenIds, true);
            if ($want === $has) {
                continue;
            }
            if ($want) {
                $cfg['items'][] = ['id' => $itemId, 'from' => null, 'until' => null, 'featured' => false];
            } else {
                $cfg['items'] = array_values(array_filter($cfg['items'], fn ($r) => $r['id'] !== $itemId));
            }
            DB::table('dm_screens')->where('id', $s->id)->update(['draft' => json_encode($cfg), 'updated_at' => now()]);
        }
    }

    public function itemAssign(Request $r, int $id)
    {
        if (! $this->guard()) {
            return $this->deny();
        }
        if (! DB::table('dm_items')->where('id', $id)->exists()) {
            return $this->fail('Item not found.', 404);
        }
        $this->assign($id, array_map('intval', (array) $r->input('screens')));

        return response()->json(['ok' => true]);
    }

    public function products(Request $r)
    {
        if (! $this->guard()) {
            return $this->deny();
        }
        $q = DB::table('products')->where('status', 'active');
        if ($s = trim((string) $r->query('q'))) {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $s) . '%';
            $q->where(fn ($w) => $w->where('product_name', 'like', $like)->orWhere('product_id', 'like', $like)->orWhere('tag', 'like', $like));
        }
        $rows = $q->orderBy('ranking')->orderBy('product_name')->limit(60)->get(['product_id', 'product_name', 'unit_quantity', 'product_unit', 'main_price', 'MRP', 'product_image', 'tag']);
        $linked = DB::table('dm_items')->whereIn('product_code', $rows->pluck('product_id')->all())->pluck('product_code')->flip();

        return response()->json(['ok' => true, 'products' => $rows->map(fn ($p) => [
            'code' => $p->product_id, 'name' => trim((string) $p->product_name), 'pack' => trim($p->unit_quantity . ' ' . $p->product_unit), 'price' => (float) $p->main_price, 'mrp' => (float) $p->MRP,
            'img' => $p->product_image ? '/uploads/images/products/' . $p->product_image : '', 'tag' => trim(explode(',', (string) $p->tag)[0] ?? ''), 'linked' => isset($linked[$p->product_id]),
        ])->all()]);
    }

    public function fromProducts(Request $r)
    {
        if (! $this->guard()) {
            return $this->deny();
        }
        $codes = array_slice(array_values(array_unique(array_map('strval', (array) $r->input('codes')))), 0, 40);
        $prods = DB::table('products')->whereIn('product_id', $codes)->get(['product_id', 'tag'])->keyBy('product_id');
        $made = [];
        foreach ($codes as $c) {
            if (! isset($prods[$c]) || DB::table('dm_items')->where('product_code', $c)->where('status', 'active')->exists()) {
                continue;
            }
            $cat = trim(explode(',', (string) $prods[$c]->tag)[0] ?? '');
            $made[] = (int) DB::table('dm_items')->insertGetId(['product_code' => $c, 'category' => $cat !== '' ? mb_substr($cat, 0, 60) : null, 'status' => 'active', 'created_by' => optional($this->who())->id, 'created_at' => now(), 'updated_at' => now()]);
        }

        return response()->json(['ok' => true, 'created' => $made]);
    }
}

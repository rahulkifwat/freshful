<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Storefront\StorefrontSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Admin page: /admin/home-settings
 * - Shop by category background: two colours, angle, transparency.
 * - High-resolution image per category (used only on the new home page).
 * Admin only (guard "admin", the same login as the admin console).
 */
class HomeSettingsController extends Controller
{
    private function admin()
    {
        try {
            return Auth::guard('admin')->user();
        } catch (Throwable $e) {
            return null;
        }
    }

    private function categories()
    {
        return DB::table('category')->where('status', 'active')->orderBy('ranking')->get(['id', 'name', 'circle_image']);
    }

    public function show(Request $request, StorefrontSettings $settings)
    {
        if (! $this->admin()) {
            return redirect('/admin/login');
        }

        return view('home_v2.admin_settings', [
            'settings' => $settings->all(),
            'presets' => StorefrontSettings::PRESETS,
            'background' => $settings->categoryBackground(),
            'categories' => $this->categories(),
            'hd' => $settings->categoryHd(),
            'ready' => $settings->tableReady(),
            'embed' => $request->header('Sec-Fetch-Dest') === 'iframe' || $request->boolean('embed'),
            'tags' => $this->tags(),
            'banners' => $this->banners(),
        ]);
    }

    private function tags(): array
    {
        try {
            return DB::table('products')->where('status', 'active')->pluck('tag')
                ->flatMap(fn ($s) => array_map('trim', explode(',', (string) $s)))
                ->filter()->unique(fn ($t) => mb_strtolower($t))->sort()->values()->all();
        } catch (Throwable $e) {
            return [];
        }
    }

    private function banners()
    {
        try {
            return DB::table('banner_images')->where('status', 'active')->get();
        } catch (Throwable $e) {
            return collect();
        }
    }

    /** Hero, sections, texts and stock rules. */
    public function saveControls(Request $request, StorefrontSettings $settings)
    {
        if (! $this->admin()) {
            abort(403);
        }
        $data = $request->validate([
            'hero_source' => ['required', 'in:products,banners,both'],
            'hero_seconds' => ['required', 'integer', 'min:2', 'max:30'],
            'hero_count' => ['required', 'integer', 'min:1', 'max:8'],
            'hero_tag' => ['nullable', 'string', 'max:80'],
            'section_limit' => ['required', 'integer', 'min:4', 'max:24'],
            'low_stock_at' => ['required', 'integer', 'min:1', 'max:50'],
            'cat_title' => ['required', 'string', 'max:60'],
            'cat_sub' => ['nullable', 'string', 'max:120'],
            'cat_columns' => ['required', 'integer', 'min:4', 'max:10'],
            'hidden' => ['nullable', 'array'],
            'hidden.*' => ['string', 'max:80'],
        ]);

        $values = [
            'hero_source' => $data['hero_source'],
            'hero_seconds' => (int) $data['hero_seconds'],
            'hero_count' => (int) $data['hero_count'],
            'hero_tag' => trim((string) ($data['hero_tag'] ?? '')),
            'section_limit' => (int) $data['section_limit'],
            'low_stock_at' => (int) $data['low_stock_at'],
            'cat_title' => trim($data['cat_title']),
            'cat_sub' => trim((string) ($data['cat_sub'] ?? '')),
            'cat_columns' => (int) $data['cat_columns'],
            'hidden_tags' => implode('|', array_map('trim', $data['hidden'] ?? [])),
        ];
        foreach (StorefrontSettings::FLAGS as $flag) {
            $values[$flag] = $request->boolean($flag) ? '1' : '0';
        }
        $settings->set($values);
        Log::info('[storefront] home controls changed by admin '.optional($this->admin())->id);

        return redirect()->to(route('home.settings').'#controls')->with('status', 'Saved. The home page shows the change now.');
    }

    public function saveBackground(Request $request, StorefrontSettings $settings)
    {
        if (! $this->admin()) {
            abort(403);
        }
        if ($request->input('action') === 'reset') {
            $settings->set(StorefrontSettings::DEFAULTS);

            return redirect()->route('home.settings')->with('status', 'Background reset to the default.');
        }

        $data = $request->validate([
            'cat_bg_from' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'cat_bg_to' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'cat_bg_angle' => ['required', 'integer', 'min:0', 'max:360'],
            'cat_bg_opacity' => ['required', 'integer', 'min:0', 'max:100'],
        ], [
            'cat_bg_from.regex' => 'Pick the first colour again.',
            'cat_bg_to.regex' => 'Pick the second colour again.',
        ]);

        $settings->set([
            'cat_bg_from' => strtoupper($data['cat_bg_from']),
            'cat_bg_to' => strtoupper($data['cat_bg_to']),
            'cat_bg_angle' => (int) $data['cat_bg_angle'],
            'cat_bg_opacity' => (int) $data['cat_bg_opacity'],
        ]);
        Log::info('[storefront] category background changed by admin '.optional($this->admin())->id);

        return redirect()->route('home.settings')->with('status', 'Background saved. It shows on the home page now.');
    }

    public function uploadImage(Request $request, StorefrontSettings $settings, int $id)
    {
        if (! $this->admin()) {
            abort(403);
        }
        if (! DB::table('category')->where('id', $id)->exists()) {
            return back()->withErrors(['image' => 'Category not found.']);
        }

        $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'image.mimes' => 'Use a JPG, PNG or WebP image.',
            'image.max' => 'The image must be 5 MB or smaller.',
        ]);

        $file = $request->file('image');
        $size = @getimagesize($file->getRealPath());
        if (! $size || $size[0] < 400 || $size[1] < 400) {
            $got = $size ? $size[0].' x '.$size[1] : 'unknown';

            return back()->withErrors(['image' => 'This image is too small ('.$got.' pixels). Use at least 600 x 600 pixels for a sharp picture.']);
        }

        $dir = $this->uploadDir($request);
        if (! $dir) {
            return back()->withErrors(['image' => 'The upload folder is not writable. Please tell your developer.']);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }
        $name = 'cat'.$id.'-'.date('YmdHis').'-'.Str::lower(Str::random(6)).'.'.$ext;
        try {
            $file->move($dir, $name);
        } catch (Throwable $e) {
            Log::warning('[storefront] category image upload failed: '.$e->getMessage());

            return back()->withErrors(['image' => 'Upload failed. Please try again.']);
        }

        $settings->set(['cat_hd_'.$id => $name]);
        Log::info('[storefront] HD image for category '.$id.' uploaded by admin '.optional($this->admin())->id);

        return redirect()->to(route('home.settings').'#cat-'.$id)->with('status', 'Image saved ('.$size[0].' x '.$size[1].' pixels).');
    }

    public function removeImage(Request $request, StorefrontSettings $settings, int $id)
    {
        if (! $this->admin()) {
            abort(403);
        }
        $settings->forget('cat_hd_'.$id);

        return redirect()->to(route('home.settings').'#cat-'.$id)->with('status', 'Back to the original category image.');
    }

    /** The website's uploads/images folder (the same place the other images live). */
    private function uploadDir(Request $request): ?string
    {
        $candidates = [];
        $docRoot = (string) $request->server('DOCUMENT_ROOT', '');
        if ($docRoot !== '') {
            $candidates[] = rtrim($docRoot, '/').'/uploads/images';
        }
        $candidates[] = public_path('uploads/images');

        foreach ($candidates as $base) {
            if (! is_dir($base)) {
                continue;
            }
            $dir = $base.'/category_hd';
            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            if (is_dir($dir) && is_writable($dir)) {
                return $dir;
            }
        }

        return null;
    }
}

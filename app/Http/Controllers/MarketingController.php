<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MarketingController extends Controller
{
    // ---- Listings --------------------------------------------------------

    public function banners()
    {
        $rows = DB::table('banner_images')->orderByDesc('id')->paginate(20);
        return view('admin.banner', compact('rows'));
    }

    public function app_banners()
    {
        $rows = DB::table('app_banner_images')->orderByDesc('id')->paginate(20);
        return view('admin.app_banner', compact('rows'));
    }

    public function home_offers()
    {
        $rows = DB::table('home_offers')->orderByDesc('id')->paginate(20);
        return view('admin.home_offers', compact('rows'));
    }

    public function promotions()
    {
        $rows = DB::table('home_promotions')->orderByDesc('id')->paginate(20);
        return view('admin.promotions', compact('rows'));
    }

    public function coupons()
    {
        $rows = DB::table('coupons_and_deals')->orderByDesc('id')->paginate(20);
        return view('admin.coupons_and_deals', compact('rows'));
    }

    // ---- Banner ----------------------------------------------------------

    public function banner_form(Request $request)
    {
        $row = $this->fetch('banner_images', $request->query('id'));
        return view('admin.banner_form', compact('row'));
    }

    public function banner_store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:191',
            'url'   => 'nullable|string|max:255',
            'image' => 'nullable|image|max:5120',
        ]);

        $data = ['title' => $request->title, 'url' => $request->url];
        $this->applyImage($request, $data, 'banners', $request->id ? $this->fetch('banner_images', $request->id) : null);

        return $this->save('banner_images', $request->id, $data, 'admin.banners', 'Banner');
    }

    // ---- App Banner ------------------------------------------------------

    public function app_banner_form(Request $request)
    {
        $row = $this->fetch('app_banner_images', $request->query('id'));
        return view('admin.app_banner_form', compact('row'));
    }

    public function app_banner_store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:191',
            'url'   => 'nullable|string|max:255',
            'image' => 'nullable|image|max:5120',
        ]);

        $data = ['title' => $request->title, 'url' => $request->url];
        // Legacy stores app banners under uploads/images/banners/ — keep that
        // so existing rows still resolve.
        $this->applyImage($request, $data, 'banners', $request->id ? $this->fetch('app_banner_images', $request->id) : null);

        return $this->save('app_banner_images', $request->id, $data, 'admin.app_banners', 'App banner');
    }

    // ---- Home Offer ------------------------------------------------------

    public function home_offer_form(Request $request)
    {
        $row = $this->fetch('home_offers', $request->query('id'));
        return view('admin.home_offer_form', compact('row'));
    }

    public function home_offer_store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:191',
            'description' => 'nullable|string|max:1000',
            'image'       => 'nullable|image|max:5120',
        ]);

        $data = [
            'title'       => $request->title,
            'description' => $request->description,
        ];
        $this->applyImage($request, $data, 'offers', $request->id ? $this->fetch('home_offers', $request->id) : null);

        return $this->save('home_offers', $request->id, $data, 'admin.home_offers', 'Offer');
    }

    // ---- Promotion -------------------------------------------------------

    public function promotion_form(Request $request)
    {
        $row = $this->fetch('home_promotions', $request->query('id'));
        return view('admin.promotion_form', compact('row'));
    }

    public function promotion_store(Request $request)
    {
        $request->validate([
            'title'         => 'required|string|max:191',
            'percentage'    => 'required|numeric|min:0',
            'min_amount'    => 'required|numeric|min:0',
            'description'   => 'nullable|string|max:2000',
            'expiry'        => 'nullable|date',
            'max_usage'     => 'nullable|integer|min:0',
            'user_eligible' => 'nullable|in:everyone,new user',
            'image'         => 'nullable|image|max:5120',
        ]);

        $data = [
            'title'         => $request->title,
            'percentage'    => $request->percentage,
            'min_amount'    => $request->min_amount,
            'description'   => $request->description,
            'expiry'        => $request->expiry ?: '0',
            'max_usage'     => $request->max_usage ?: 0,
            'user_eligible' => $request->user_eligible ?: 'everyone',
        ];
        $this->applyImage($request, $data, 'promotions', $request->id ? $this->fetch('home_promotions', $request->id) : null);

        return $this->save('home_promotions', $request->id, $data, 'admin.promotions', 'Promotion');
    }

    // ---- Coupon ----------------------------------------------------------

    public function coupon_form(Request $request)
    {
        $row = $this->fetch('coupons_and_deals', $request->query('id'));
        return view('admin.coupon_form', compact('row'));
    }

    public function coupon_store(Request $request)
    {
        $request->validate([
            'type'  => 'required|in:normal,discount',
            'image' => 'nullable|image|max:5120',
        ]);

        $data = ['type' => $request->type];
        $this->applyImage($request, $data, 'coupons', $request->id ? $this->fetch('coupons_and_deals', $request->id) : null);

        return $this->save('coupons_and_deals', $request->id, $data, 'admin.coupons', 'Coupon');
    }

    // ---- Helpers ---------------------------------------------------------

    private function fetch(string $table, $id)
    {
        $id = (int) $id;
        return $id ? DB::table($table)->where('id', $id)->first() : null;
    }

    /**
     * Apply an uploaded image to $data['image']. When editing and no new file
     * is sent, keep the existing filename. Old image is removed on replace.
     */
    private function applyImage(Request $request, array &$data, string $folder, $existing): void
    {
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $name = $this->uploadImage($file, $folder);
            $data['image'] = $name;

            if ($existing && !empty($existing->image)) {
                $old = public_path('uploads/images/'.$folder.'/'.$existing->image);
                if (is_file($old)) { @unlink($old); }
            }
        }
    }

    private function uploadImage(UploadedFile $file, string $folder): string
    {
        $dir = public_path('uploads/images/'.$folder);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $ext  = $file->getClientOriginalExtension() ?: 'jpg';
        $name = Str::random(5).date('YmdHis').'.'.strtolower($ext);
        $file->move($dir, $name);
        return $name;
    }

    private function save(string $table, $id, array $data, string $route, string $label)
    {
        if ($id && DB::table($table)->where('id', $id)->exists()) {
            DB::table($table)->where('id', $id)->update($data);
            $msg = $label.' updated.';
        } else {
            if (DB::getSchemaBuilder()->hasColumn($table, 'created_at')) {
                $data['created_at'] = now();
                $data['updated_at'] = now();
            }
            DB::table($table)->insert($data);
            $msg = $label.' added.';
        }
        return redirect()->route($route)->with('success', $msg);
    }
}

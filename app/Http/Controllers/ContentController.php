<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ContentController extends Controller
{
    // ---- Privacy Policy (single record) ---------------------------------

    public function privacy_policy()
    {
        $row = DB::table('privacy_policy')->first();
        return view('admin.privacy_policy', compact('row'));
    }

    public function privacy_policy_update(Request $request)
    {
        $request->validate(['content' => 'required|string']);

        $existing = DB::table('privacy_policy')->first();
        if ($existing) {
            DB::table('privacy_policy')->where('id', $existing->id)->update(['content' => $request->content]);
        } else {
            DB::table('privacy_policy')->insert(['content' => $request->content]);
        }
        return redirect()->route('admin.privacy_policy')->with('success', 'Privacy policy updated.');
    }

    // ---- Policies (rows keyed by type) ----------------------------------

    public function policies(Request $request)
    {
        $type    = $request->query('type');
        $current = $type ? DB::table('policies')->where('type', $type)->first() : null;
        $all     = DB::table('policies')->orderBy('type')->get(['id', 'type']);

        return view('admin.policies', compact('current', 'all', 'type'));
    }

    public function policies_update(Request $request)
    {
        $request->validate([
            'type'    => 'required|string|max:100',
            'content' => 'required|string',
        ]);

        $existing = DB::table('policies')->where('type', $request->type)->first();
        if ($existing) {
            DB::table('policies')->where('id', $existing->id)->update(['content' => $request->content]);
        } else {
            DB::table('policies')->insert([
                'type'    => $request->type,
                'content' => $request->content,
            ]);
        }
        return redirect()->route('admin.policies', ['type' => $request->type])->with('success', 'Policy saved.');
    }

    // ---- Push Notifications ---------------------------------------------

    public function push()
    {
        $rows = Schema::hasTable('push_noti')
            ? DB::table('push_noti')->orderByDesc('id')->paginate(20)
            : collect();
        return view('admin.push', compact('rows'));
    }

    public function push_send(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:191',
            'description' => 'nullable|string|max:2000',
        ]);

        $row = [
            'title'       => $request->title,
            'description' => $request->description,
        ];
        if (Schema::hasColumn('push_noti', 'created_at')) {
            $row['created_at'] = now();
        }
        if (Schema::hasColumn('push_noti', 'date_added')) {
            $row['date_added'] = now();
        }

        DB::table('push_noti')->insert($row);
        return redirect()->route('admin.push')->with('success', 'Notification queued.');
    }

    // ---- Role Type (read-only listing of role assignments) --------------

    public function role_type()
    {
        $rows = collect();
        if (Schema::hasTable('role_user') && Schema::hasTable('roles')) {
            $rows = DB::table('role_user')
                ->leftJoin('roles', 'roles.id', '=', 'role_user.role_id')
                ->select('role_user.*', 'roles.type as role_name')
                ->orderByDesc('role_user.id')
                ->paginate(50);
        }
        return view('admin.role_type', compact('rows'));
    }

    // ---- Certificate (CRUD with image) ----------------------------------

    public function certificate()
    {
        $rows = DB::table('certificates')->orderByDesc('id')->paginate(20);
        return view('admin.certificate', compact('rows'));
    }

    public function certificate_form(Request $request)
    {
        $id  = (int) $request->query('id');
        $row = $id ? DB::table('certificates')->where('id', $id)->first() : null;
        return view('admin.certificate_form', compact('row'));
    }

    public function certificate_store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:191',
            'image' => 'nullable|image|max:5120',
        ]);

        $editingId = (int) $request->input('id');
        $existing  = $editingId ? DB::table('certificates')->where('id', $editingId)->first() : null;

        $data = ['title' => $request->title];
        if ($request->hasFile('image')) {
            $name = $this->uploadImage($request->file('image'), 'certificates');
            $data['image'] = $name;
            if ($existing && !empty($existing->image)) {
                $old = public_path('uploads/images/certificates/'.$existing->image);
                if (is_file($old)) { @unlink($old); }
            }
        }

        if ($existing) {
            DB::table('certificates')->where('id', $editingId)->update($data);
            $msg = 'Certificate updated.';
        } else {
            DB::table('certificates')->insert($data);
            $msg = 'Certificate added.';
        }
        return redirect()->route('admin.certificate')->with('success', $msg);
    }

    // ---- Delivery Price (CRUD; FK to cities) ----------------------------

    public function delivery_price()
    {
        $rows = DB::table('delivery_price as d')
            ->leftJoin('cities as c', 'c.id', '=', 'd.city')
            ->select('d.*', 'c.city as city_name')
            ->orderBy('c.city')
            ->paginate(50);
        return view('admin.delivery_price', compact('rows'));
    }

    public function delivery_price_form(Request $request)
    {
        $id     = (int) $request->query('id');
        $row    = $id ? DB::table('delivery_price')->where('id', $id)->first() : null;
        $cities = DB::table('cities')->where('status', 'active')->orderBy('city')->get(['id', 'city']);
        return view('admin.delivery_price_form', compact('row', 'cities'));
    }

    public function delivery_price_store(Request $request)
    {
        $request->validate([
            'city'  => 'required|integer',
            'price' => 'required|numeric|min:0',
        ]);

        $data = ['city' => $request->city, 'price' => $request->price];

        if ($request->filled('id')) {
            DB::table('delivery_price')->where('id', $request->id)->update($data);
            $msg = 'Delivery price updated.';
        } else {
            DB::table('delivery_price')->insert($data);
            $msg = 'Delivery price added.';
        }
        return redirect()->route('admin.delivery_price')->with('success', $msg);
    }

    // ---- Helpers --------------------------------------------------------

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
}

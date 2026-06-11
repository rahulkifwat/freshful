<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CatalogController extends Controller
{
    // ── Main Categories (categories plural) ───────────────────────────────────

    public function mainCategories(Request $request)
    {
        $query = DB::table('categories');
        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }
        $rows = $query->orderByDesc('id')->paginate(25)->withQueryString();
        return view('admin.add_category', compact('rows'));
    }

    public function mainCategoryForm(Request $request)
    {
        $id  = (int) $request->query('id');
        $row = $id ? DB::table('categories')->where('id', $id)->first() : null;
        return view('admin.main_category_form', compact('row'));
    }

    public function mainCategoryStore(Request $request)
    {
        $editingId = (int) $request->input('id');
        $existing  = $editingId ? DB::table('categories')->where('id', $editingId)->first() : null;

        $request->validate([
            'name'  => 'required|string|max:191',
            'image' => 'nullable|image|max:4096',
        ]);

        $data = ['name' => $request->name];

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploadImage($request->file('image'), 'categories');
            if ($existing && !empty($existing->image)) {
                $old = public_path('uploads/images/categories/'.$existing->image);
                if (is_file($old)) { @unlink($old); }
            }
        }

        if ($existing) {
            DB::table('categories')->where('id', $editingId)->update($data);
            return redirect()->route('admin.main_categories')->with('success', 'Category updated.');
        }

        $data['status'] = 'active';
        DB::table('categories')->insert($data);
        return redirect()->route('admin.main_categories')->with('success', 'Category added.');
    }

    // ── Sub Categories (category singular) ────────────────────────────────────

    public function subCategories(Request $request)
    {
        $query = DB::table('category as c')
            ->leftJoin('categories as mc', 'mc.name', '=', 'c.group_type')
            ->select('c.*', 'mc.name as main_category_name');
        if ($request->filled('search')) {
            $query->where('c.name', 'like', '%'.$request->search.'%');
        }
        $rows = $query->orderByDesc('c.id')->paginate(25)->withQueryString();
        return view('admin.category', compact('rows'));
    }

    public function subCategoryForm(Request $request)
    {
        $id  = (int) $request->query('id');
        $row = $id ? DB::table('category')->where('id', $id)->first() : null;
        $main_categories = DB::table('categories')->orderBy('name')->get(['id', 'name']);
        return view('admin.sub_category_form', compact('row', 'main_categories'));
    }

    public function subCategoryStore(Request $request)
    {
        $editingId = (int) $request->input('id');
        $existing  = $editingId ? DB::table('category')->where('id', $editingId)->first() : null;

        $request->validate([
            'name'         => 'required|string|max:191',
            'group_type'   => 'nullable|string|max:191',
            'ranking'      => 'nullable|integer|min:0',
            'image'        => 'nullable|image|max:4096',
            'circle_image' => 'nullable|image|max:4096',
        ]);

        $data = [
            'name'       => $request->name,
            'group_type' => $request->group_type,
            'ranking'    => $request->ranking ?? 0,
        ];

        foreach (['image', 'circle_image'] as $field) {
            if ($request->hasFile($field)) {
                $data[$field] = $this->uploadImage($request->file($field), 'categories');
                if ($existing && !empty($existing->$field)) {
                    $old = public_path('uploads/images/categories/'.$existing->$field);
                    if (is_file($old)) { @unlink($old); }
                }
            }
        }

        if ($existing) {
            DB::table('category')->where('id', $editingId)->update($data);
            return redirect()->route('admin.sub_categories')->with('success', 'Sub-category updated.');
        }

        $data['status'] = 'active';
        DB::table('category')->insert($data);
        return redirect()->route('admin.sub_categories')->with('success', 'Sub-category added.');
    }

    // ── Items ─────────────────────────────────────────────────────────────────

    public function items(Request $request)
    {
        $query = DB::table('items as i')
            ->leftJoin('category as c', 'c.id', '=', 'i.cat_id')
            ->select('i.*', 'c.name as category_name');
        if ($request->filled('search')) {
            $query->where('i.item', 'like', '%'.$request->search.'%');
        }
        $rows = $query->orderByDesc('i.id')->paginate(25)->withQueryString();
        return view('admin.items', compact('rows'));
    }

    public function itemForm(Request $request)
    {
        $id  = (int) $request->query('id');
        $row = $id ? DB::table('items')->where('id', $id)->first() : null;
        $categories = DB::table('category')->orderBy('name')->get(['id', 'name']);
        return view('admin.item_form', compact('row', 'categories'));
    }

    public function itemStore(Request $request)
    {
        $editingId = (int) $request->input('id');
        $existing  = $editingId ? DB::table('items')->where('id', $editingId)->first() : null;

        $request->validate([
            'item'   => 'required|string|max:191',
            'cat_id' => 'nullable|integer',
            'image'  => 'nullable|image|max:4096',
        ]);

        $data = [
            'item'   => $request->item,
            'cat_id' => $request->cat_id,
        ];

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploadImage($request->file('image'), 'items');
            if ($existing && !empty($existing->image)) {
                $old = public_path('uploads/images/items/'.$existing->image);
                if (is_file($old)) { @unlink($old); }
            }
        }

        if ($existing) {
            DB::table('items')->where('id', $editingId)->update($data);
            return redirect()->route('admin.items')->with('success', 'Item updated.');
        }

        $data['status'] = 'active';
        DB::table('items')->insert($data);
        return redirect()->route('admin.items')->with('success', 'Item added.');
    }

    // ── Product Tags ──────────────────────────────────────────────────────────

    public function productTags(Request $request)
    {
        $rows = DB::table('product_tags')->orderByDesc('id')->paginate(25)->withQueryString();
        return view('admin.product_tag', compact('rows'));
    }

    public function productTagStore(Request $request)
    {
        $request->validate(['tag' => 'required|string|max:100']);
        DB::table('product_tags')->insert(['tag' => $request->tag, 'status' => 'active']);
        return redirect()->route('admin.product_tags')->with('success', 'Tag added.');
    }

    // ── Product Units ─────────────────────────────────────────────────────────

    public function productUnits(Request $request)
    {
        $rows = DB::table('product_units')->orderByDesc('id')->paginate(25)->withQueryString();
        return view('admin.product_unit', compact('rows'));
    }

    public function productUnitStore(Request $request)
    {
        $request->validate(['unit' => 'required|string|max:50']);
        DB::table('product_units')->insert(['unit' => $request->unit, 'status' => 'active']);
        return redirect()->route('admin.product_units')->with('success', 'Unit added.');
    }

    // ── Product Filters ───────────────────────────────────────────────────────

    public function productFilters(Request $request)
    {
        $query = DB::table('product_filter as pf')
            ->leftJoin('categories as mc', 'mc.id', '=', 'pf.main_cat_id')
            ->leftJoin('category as sc', 'sc.id', '=', 'pf.subcat_id')
            ->select('pf.*', 'mc.name as main_cat_name', 'sc.name as sub_cat_name');
        $rows            = $query->orderByDesc('pf.id')->paginate(25)->withQueryString();
        $main_categories = DB::table('categories')->orderBy('name')->get(['id', 'name']);
        $sub_categories  = DB::table('category')->orderBy('name')->get(['id', 'name']);
        return view('admin.product_filter', compact('rows', 'main_categories', 'sub_categories'));
    }

    public function productFilterStore(Request $request)
    {
        $editingId = (int) $request->input('id');
        $request->validate([
            'main_cat_id'   => 'nullable|integer',
            'subcat_id'     => 'nullable|integer',
            'property_name' => 'required|string|max:191',
            'parameter'     => 'nullable|string|max:191',
        ]);

        $data = [
            'main_cat_id'   => $request->main_cat_id,
            'subcat_id'     => $request->subcat_id,
            'property_name' => $request->property_name,
            'parameter'     => $request->parameter,
        ];

        if ($editingId) {
            DB::table('product_filter')->where('id', $editingId)->update($data);
            return redirect()->route('admin.product_filters')->with('success', 'Filter updated.');
        }

        DB::table('product_filter')->insert($data);
        return redirect()->route('admin.product_filters')->with('success', 'Filter added.');
    }

    // ── Shared ────────────────────────────────────────────────────────────────

    private function uploadImage(UploadedFile $file, string $folder): string
    {
        $dir = public_path('uploads/images/'.$folder);
        if (!is_dir($dir)) { mkdir($dir, 0755, true); }
        $ext  = $file->getClientOriginalExtension() ?: 'jpg';
        $name = Str::random(5).date('YmdHis').'.'.strtolower($ext);
        $file->move($dir, $name);
        return $name;
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function form(Request $request)
    {
        $id  = (int) $request->query('id');
        $row = $id ? DB::table('products')->where('id', $id)->first() : null;

        // `categories` (plural) holds MAIN categories; `category` (singular)
        // holds sub-categories. Sub-category links to main via group_type=name.
        $main_categories = DB::table('categories')->orderBy('name')
            ->get(['id', 'name as main_category_name']);
        $categories      = DB::table('category')->orderBy('name')
            ->get(['id', 'name as category_name', 'group_type as main_category_name']);

        // Items dropdown — narrow to active rows when status column exists.
        $itemsQ = DB::table('items');
        if (Schema::hasColumn('items', 'status')) {
            $itemsQ->where('status', 'active');
        }
        $items = $itemsQ->orderBy('item')->get(Schema::hasColumn('items', 'cat_id')
            ? ['id', 'item', 'cat_id']
            : ['id', 'item']);

        $units = Schema::hasTable('product_units')
            ? DB::table('product_units')->orderBy('unit')->get(['unit'])
            : collect();
        $tags  = Schema::hasTable('product_tags')
            ? DB::table('product_tags')->where('status', 'active')->orderBy('tag')->get(['tag'])
            : collect();

        return view('admin.product_form', compact(
            'row', 'main_categories', 'categories', 'items', 'units', 'tags'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'main_category_id' => 'nullable|string|max:191',
            'category_id'      => 'nullable|integer',
            'item_id'          => 'required|integer',
            'product_unit'     => 'nullable|string|max:50',
            'unit_quantity'    => 'nullable|numeric',
            'gross_quantity'   => 'nullable|numeric',
            'MRP'              => 'required|numeric|min:0',
            'main_price'       => 'required|numeric|min:0',
            'cost_price'       => 'nullable|numeric|min:0',
            'stock'            => 'nullable|integer|min:0',
            'pieces'           => 'nullable|string|max:50',
            'serves'           => 'nullable|string|max:50',
            'cooking_time'     => 'nullable|string|max:50',
            'description'      => 'nullable|string|max:5000',
            'tag'              => 'nullable|array',
            'property'         => 'nullable|array',
            'product_image'    => 'nullable|image|max:8192',
        ]);

        $editingId = (int) $request->input('id');
        $existing  = $editingId ? DB::table('products')->where('id', $editingId)->first() : null;

        // products table has no main_category_id column (per legacy); skip it.
        $data = [
            'category_id'      => $request->category_id,
            'item_id'          => $request->item_id,
            'product_unit'     => $request->product_unit,
            'unit_quantity'    => $request->unit_quantity,
            'gross_quantity'   => $request->gross_quantity,
            'MRP'              => $request->MRP,
            'main_price'       => $request->main_price,
            'cost_price'       => $request->cost_price,
            'stock'            => $request->stock,
            'pieces'           => $request->pieces,
            'serves'           => $request->serves,
            'cooking_time'     => $request->cooking_time,
            'description'      => $request->description,
            'tag'              => $request->filled('tag') ? implode(',', $request->tag) : null,
            'property'         => $request->filled('property') ? implode(',', array_filter($request->property)) : null,
        ];

        // product_name is derived from the chosen item.
        $itemName = DB::table('items')->where('id', $request->item_id)->value('item');
        if ($itemName) {
            $data['product_name'] = $itemName;
        }

        // Image upload (keep existing on edit if no new file).
        if ($request->hasFile('product_image')) {
            $name = $this->uploadImage($request->file('product_image'), 'products');
            $data['product_image'] = $name;

            if ($existing && !empty($existing->product_image)) {
                $old = public_path('uploads/images/products/'.$existing->product_image);
                if (is_file($old)) { @unlink($old); }
            }
        }

        if ($existing) {
            DB::table('products')->where('id', $editingId)->update($data);
            return redirect()->route('admin.products')->with('success', 'Product updated.');
        }

        // New product: auto-generate product_id (PRD00001 → PRD00002 → …).
        $data['product_id'] = $this->nextProductCode();
        $data['status']     = 'active';
        $data['ranking']    = (int) DB::table('products')->count() + 1;

        DB::table('products')->insert($data);
        return redirect()->route('admin.products')->with('success', 'Product added.');
    }

    private function nextProductCode(): string
    {
        $last = DB::table('products')->orderByDesc('id')->value('product_id');
        if (! $last) {
            return 'PRD00001';
        }
        // Strip any non-digit prefix, increment numeric portion, pad to 5 digits.
        $num = (int) preg_replace('/\D+/', '', $last);
        return 'PRD'.str_pad((string) ($num + 1), 5, '0', STR_PAD_LEFT);
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
}

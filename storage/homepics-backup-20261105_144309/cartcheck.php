<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo "cart columns: ", implode(',', \Illuminate\Support\Facades\Schema::getColumnListing('cart')), "\n";
$pc = ['id', 'product_id', 'product_name', 'product_unit', 'unit_quantity', 'main_price', 'item_id', 'product_image'];
foreach (\Illuminate\Support\Facades\DB::table('cart')->limit(12)->get() as $r) {
    echo json_encode($r), "\n";
    echo "  by products.id   : ", json_encode(\Illuminate\Support\Facades\DB::table('products')->where('id', $r->product_id)->first($pc)), "\n";
    echo "  by products.code : ", json_encode(\Illuminate\Support\Facades\DB::table('products')->where('product_id', $r->product_id)->first($pc)), "\n";
}
echo "files that build the app cart:\n";
echo shell_exec('grep -rlni "from(.cart.)\|table(.cart.)" app/Services app/Http/Controllers 2>/dev/null | head -12');

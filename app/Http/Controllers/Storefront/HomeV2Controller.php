<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Storefront\HomeV2Data;
use Illuminate\Http\Request;

// New home page (Freshful storefront v2). Read-only; the old HomeController is not changed.
class HomeV2Controller extends Controller
{
    public function index(Request $request, HomeV2Data $data)
    {
        return view('home_v2.index', $data->build($request));
    }
}

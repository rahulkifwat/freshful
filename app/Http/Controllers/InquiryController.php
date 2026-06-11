<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InquiryController extends Controller
{
    public function contact_us()
    {
        $rows = DB::table('contact_us')
            ->whereNotNull('email')->where('email', '!=', '')
            ->orderByDesc('id')
            ->paginate(25);
        return view('admin.contact_us', compact('rows'));
    }

    public function newsletters()
    {
        $rows = DB::table('news_letter')
            ->whereNotNull('email')->where('email', '!=', '')
            ->orderByDesc('id')
            ->paginate(50);
        return view('admin.news_letters', compact('rows'));
    }

    public function frenchisee_enquiry()
    {
        $rows = DB::table('frenchisee_enquiry')
            ->whereNotNull('email')->where('email', '!=', '')
            ->orderByDesc('id')
            ->paginate(25);
        return view('admin.frenchisee_enquiry', compact('rows'));
    }
}

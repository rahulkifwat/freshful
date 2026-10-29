<?php

namespace App\Http\Controllers\AdminNew;

use App\Http\Controllers\Controller;

/**
 * Freshful new admin - dashboard.
 * Phase 1 step 1: shows the new layout and sidebar. Live KPIs arrive in Phase 2.
 */
class DashboardController extends Controller
{
    public function index()
    {
        return view('admin_new.dashboard', [
            'menuKey'   => 'dashboard',
            'pageTitle' => 'Dashboard',
        ]);
    }
}

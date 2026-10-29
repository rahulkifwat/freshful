<?php

/*
| Freshful new admin console (/admin/new).
| Loaded by ONE require line at the end of routes/web.php.
| Uses the existing "admin" middleware, so only logged-in admins can open it.
| The classic admin (/admin/...) is not touched.
*/

use App\Http\Controllers\AdminNew\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['admin'])->prefix('admin/new')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('adminnew.dashboard');
});

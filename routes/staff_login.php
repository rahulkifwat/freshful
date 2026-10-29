<?php

/*
| Freshful staff sign-in portal (22 Oct 2026). Loaded by ONE guarded require line at the end of routes/web.php.
| For each staff login address it replaces ONLY the GET page with the new design, keeping the old route's
| name and middleware. POST routes (the password check) are not touched.
| Also adds freshful.in/staff : the same page with no dashboard pre-selected.
*/

use App\Http\Controllers\StaffLogin\StaffLoginController;
use Illuminate\Support\Facades\Route;

if (! config('staff_login.enabled', true)) {
    return;
}

$flExisting = [];
foreach (Route::getRoutes()->getRoutes() as $flRoute) {
    if (in_array('GET', $flRoute->methods(), true)) {
        $flExisting[trim($flRoute->uri(), '/')] = $flRoute;
    }
}

foreach ((array) config('staff_login.roles', []) as $flKey => $flRole) {
    foreach (array_merge([$flRole['path']], (array) ($flRole['also'] ?? [])) as $flPath) {
        $flOld = $flExisting[$flPath] ?? null;
        if (! $flOld) {
            continue; // only restyle pages that already exist; never invent a login address
        }
        StaffLoginController::remember($flPath, $flOld);
        $flNew = Route::get($flPath, [StaffLoginController::class, 'show'])
            ->middleware(array_values(array_diff((array) $flOld->middleware(), ['web'])));
        if ($flOld->getName()) {
            $flNew->name($flOld->getName());
        }
    }
}

if (! isset($flExisting['staff'])) {
    Route::get('staff', [StaffLoginController::class, 'show'])->name('staff.portal');
}

unset($flExisting, $flRoute, $flKey, $flRole, $flPath, $flOld, $flNew);

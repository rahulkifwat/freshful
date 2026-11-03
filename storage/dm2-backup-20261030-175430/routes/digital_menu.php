<?php

// Freshful Digital Menu (30 Oct 2026). Loaded by one guarded require at the end of routes/web.php.
// Admin pages need the admin login (guard "admin", the same as the admin console). The TV player needs only its private link.

use App\Http\Controllers\DigitalMenu\DigitalMenuAdminController as A;
use App\Http\Controllers\DigitalMenu\DigitalMenuPlayerController as P;
use Illuminate\Support\Facades\Route;

Route::get('/admin/digital-menu', [A::class, 'page'])->name('dm.admin');
Route::prefix('/admin/digital-menu/api')->group(function () {
    Route::get('/boot', [A::class, 'boot']);
    Route::get('/screens', [A::class, 'screens']);
    Route::post('/screens', [A::class, 'create']);
    Route::get('/screens/{id}', [A::class, 'show'])->whereNumber('id');
    Route::post('/screens/{id}', [A::class, 'save'])->whereNumber('id');
    Route::post('/screens/{id}/publish', [A::class, 'publish'])->whereNumber('id');
    Route::post('/screens/{id}/status', [A::class, 'status'])->whereNumber('id');
    Route::post('/screens/{id}/rekey', [A::class, 'rekey'])->whereNumber('id');
    Route::post('/screens/{id}/duplicate', [A::class, 'duplicate'])->whereNumber('id');
    Route::post('/screens/{id}/delete', [A::class, 'destroy'])->whereNumber('id');
    Route::get('/items', [A::class, 'items']);
    Route::post('/items', [A::class, 'itemSave']);
    Route::post('/items/{id}/state', [A::class, 'itemState'])->whereNumber('id');
    Route::post('/items/{id}/duplicate', [A::class, 'itemDuplicate'])->whereNumber('id');
    Route::post('/items/{id}/assign', [A::class, 'itemAssign'])->whereNumber('id');
    Route::get('/products', [A::class, 'products']);
    Route::post('/items/from-products', [A::class, 'fromProducts']);
    Route::post('/upload', [A::class, 'upload']);
});

Route::get('/display/{key}', [P::class, 'show'])->where('key', '[A-Za-z0-9]{20,40}')->middleware('throttle:120,1')->name('dm.player');
Route::get('/display/{key}/data', [P::class, 'data'])->where('key', '[A-Za-z0-9]{20,40}')->middleware('throttle:240,1')->name('dm.data');

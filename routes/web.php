<?php

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuthController;

// Route::get('/', function () {
//     return view('welcome');
// });
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/product/{category?}/{type?}', [HomeController::class, 'product'])->name('product');

Route::get('/product_detail', [HomeController::class, 'product_detail'])->name('product_detail');

Route::get('/view_product/{category?}/{type?}', [HomeController::class, 'view_product'])->name('view_product');

Route::get('/about', function () {
    return view('about');
});

Route::get('/franchisee', function () {
    return view('franchisee');
});
Route::get('/contact_us', function () {
    return view('contact_us');
});
Route::get('/refer_earn', function () {
    return view('refer_earn');
});
Route::get('/privacypolicy', function () {
    return view('privacypolicy');
});
Route::get('/shipping-policy', function () {
    return view('shipping-policy');
});
Route::get('/terms', function () {
    return view('terms');
});
Route::get('/faq', function () {
    return view('faq');
});
Route::get('/refund_policy', function () {
    return view('refund_policy');
});
Route::get('/why-freshful', function () {
    return view('why-freshful');
});

Route::get('/checkout', [HomeController::class, 'checkout'])->name('checkout');

Route::prefix('admin')->group(function () {

    Route::middleware(['redirectMiddleware'])->group(function () {
        Route::view('/login', 'admin.login')->name('admin.login');
        Route::post('/login', [AuthController::class, 'AdminLogin'])->name('admin.loginSubmit');
    });

    Route::middleware(['admin'])->group(function () {
        Route::get('', [AdminController::class, 'index'])->name('admin.dashboard');

        Route::get('/all_orders', [AdminController::class, 'all_orders'])->name('admin.all_orders');

        Route::get('/order', [AdminController::class, 'order'])->name('admin.order');
        Route::get('/products', [AdminController::class, 'products'])->name('admin.products');
    
    // Route::get('/order', function () {
    //     return view('admin.order');
    // });
    Route::get('/edit_product', function () {
        return view('admin.edit_product');
    });
    Route::get('/buyers', function () {
        return view('admin.buyers');
    });
    Route::get('/pos_users', function () {
        return view('admin.pos_users');
    });
    Route::get('/hub_users', function () {
        return view('admin.hub_users');
    });
    Route::get('/inventory', function () {
        return view('admin.inventory');
    });
    Route::get('/add-product', function () {
        return view('admin.add_product');
    });
    Route::get('/view_buyer', function () {
        return view('admin.view_buyer');
    });
    Route::get('/view_pos_user', function () {
        return view('admin.view_pos_user');
    });
    Route::get('/hub', function () {
        return view('admin.hub');
    });
    Route::get('/add_pos_user', function () {
        return view('admin.add_pos_user');
    });
    Route::get('/add_hub_user', function () {
        return view('admin.add_hub_user');
    });
    Route::get('/view_hub_user', function () {
        return view('admin.view_hub_user');
    });
    Route::get('/marketing_managers', function () {
        return view('admin.marketing_managers');
    });
    Route::get('/view_marketing_manager', function () {
        return view('admin.view_marketing_manager');
    });
    Route::get('/add_marketing_manager', function () {
        return view('admin.add_marketing_manager');
    });
    Route::get('/setting', function () {
        return view('admin.setting');
    });
    Route::get('/logout', function () {
        return view('admin.logout');
    });
    Route::get('/app_banner', function () {
        return view('admin.app_banner');
    });
    Route::get('/city', function () {
        return view('admin.city');
    });
    Route::get('/delivery_price', function () {
        return view('admin.delivery_price');
    });
    Route::get('/hub', function () {
        return view('admin.hub');
    });
    Route::get('/home_offers', function () {
        return view('admin.home_offers');
    });
    Route::get('/promotions', function () {
        return view('admin.promotions');
    });
    Route::get('/privacy_policy', function () {
        return view('admin.privacy_policy');
    });
    Route::get('/push', function () {
        return view('admin.push');
    });
    Route::get('/role_type', function () {
        return view('admin.role_type');
    });
    Route::get('/news_letters', function () {
        return view('admin.news_letters');
    });
    Route::get('/contact_us', function () {
        return view('admin.contact_us');
    });
    Route::get('/frenchisee_enquiry', function () {
        return view('admin.frenchisee_enquiry');
    });
    Route::get('/policies', function () {
        return view('admin.policies');
    });
    Route::get('/queries', function () {
        return view('admin.queries');
    });
    Route::get('/add_product', function () {
        return view('admin.add_product');
    });
    Route::get('/edit_product', function () {
        return view('admin.edit_product');
    });
    Route::get('/account_managers', function () {
        return view('admin.account_managers');
    });
    Route::get('/marketing_managers', function () {
        return view('admin.marketing_managers');
    });
    Route::get('/customer_care_managers', function () {
        return view('admin.customer_care_managers');
    });
    Route::get('/hr_managers', function () {
        return view('admin.hr_managers');
    });
    Route::get('/planning_managers', function () {
        return view('admin.planning_managers');
    });
    Route::get('/area_managers', function () {
        return view('admin.area_managers');
    });
    Route::get('/country_managers', function () {
        return view('admin.country_managers');
    });
    Route::get('/add_account_manager', function () {
        return view('admin.add_account_manager');
    });
    Route::get('/view_account_manager', function () {
        return view('admin.view_account_manager');
    });
    Route::get('/add_marketing_manager', function () {
        return view('admin.add_marketing_manager');
    });
    Route::get('/view_marketing_manager', function () {
        return view('admin.view_marketing_manager');
    });
    Route::get('/add_customer_care_manager', function () {
        return view('admin.add_customer_care_manager');
    });
    Route::get('/view_customer_care_manager', function () {
        return view('admin.view_customer_care_manager');
    });
    Route::get('/add_hr_manager', function () {
        return view('admin.add_hr_manager');
    });
    Route::get('/view_hr_manager', function () {
        return view('admin.view_hr_manager');
    });
    Route::get('/add_planning_manager', function () {
        return view('admin.add_planning_manager');
    });
    Route::get('/view_planning_manager', function () {
        return view('admin.view_planning_manager');
    });
    Route::get('/add_area_manager', function () {
        return view('admin.add_area_manager');
    });
    Route::get('/view_area_manager', function () {
        return view('admin.view_area_manager');
    });
    Route::get('/add_country_manager', function () {
        return view('admin.add_country_manager');
    });
    Route::get('/view_country_manager', function () {
        return view('admin.view_country_manager');
    });
    Route::get('/production_user', function () {
        return view('admin.production_user');
    });
    Route::get('/add_production_user', function () {
        return view('admin.add_production_user');
    }); 
    Route::get('/view_production_user', function () {
        return view('admin.view_production_user');
    }); 
    Route::get('/operation_managers', function () {
        return view('admin.operation_managers');
    }); 
    Route::get('/add_operation_manager', function () {
        return view('admin.add_operation_manager');
    }); 
    Route::get('/view_operation_manager', function () {
        return view('admin.view_operation_manager');
    }); 

    Route::get('/profile', function () {
        return view('admin.profile');
    }); 
    Route::get('/edit_profile', function () {
        return view('admin.edit_profile');
    }); 
    Route::get('/certificate', function () {
        return view('admin.certificate');
    }); 
    
    Route::get('/inventory_report_detail', function () {
        return view('admin.inventory_report_detail');
    }); 
    Route::get('/sale_summary', function () {
        return view('admin.sale_summary');
    }); 
});



});

Route::prefix('/user')->group(function () {
    Route::post('/login', [AuthController::class, 'userLogin'])->name('user.login');
    Route::get('/logout', [AuthController::class, 'logout'])->name('user.logout');
    Route::get('/myaccount', [HomeController::class, 'myAccount'])->name('myaccount');
    Route::post('/storeAddress', [HomeController::class, 'storeAddress'])->name('storeAddress');
    Route::get('/deleteAddress/{id}', [HomeController::class, 'deleteAddress'])->name('deleteAddress');
    Route::post('updateAccount', [HomeController::class, 'updateAccount'])->name('updateAccount');
});

Route::post('/add-order', [HomeController::class, 'addOrder'])->name('add-order');
Route::post('/send-otp', [AuthController::class, 'sendOtp'])->name('send-otp');
Route::get('/get-cart', [HomeController::class, 'cart'])->name('get-cart');
Route::post('/add-cart', [HomeController::class, 'addCart'])->name('add-cart');
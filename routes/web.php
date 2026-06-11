<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminAjaxController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\AccountManagerAuthController;
use App\Http\Controllers\AccountManagerController;
use App\Http\Controllers\AreaManagerAuthController;
use App\Http\Controllers\AreaManagerController;
use App\Http\Controllers\CountryManagerAuthController;
use App\Http\Controllers\CountryManagerController;
use App\Http\Controllers\CustomerCareManagerAuthController;
use App\Http\Controllers\CustomerCareManagerController;
use App\Http\Controllers\HrManagerAuthController;
use App\Http\Controllers\HrManagerController;
use App\Http\Controllers\HubAuthController;
use App\Http\Controllers\HubController;
use App\Http\Controllers\MarketingManagerAuthController;
use App\Http\Controllers\MarketingManagerController;

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
        Route::get('/', [AdminController::class, 'index']);
        Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
        Route::get('/logout', [AuthController::class, 'adminLogout'])->name('admin.logout');

        // Orders / products / buyers
        Route::get('/all_orders', [AdminController::class, 'all_orders'])->name('admin.all_orders');
        Route::get('/order',      [AdminController::class, 'order'])->name('admin.order');
        Route::get('/products',   [AdminController::class, 'products'])->name('admin.products');
        Route::get('/buyers',     [AdminController::class, 'buyers'])->name('admin.buyers');
        Route::get('/view_buyer', [AdminController::class, 'view_buyer'])->name('admin.view_buyer');

        // Logged-in admin self-service
        Route::get('/profile',           [AdminController::class, 'profile'])->name('admin.profile');
        Route::get('/edit_profile',      [AdminController::class, 'edit_profile'])->name('admin.edit_profile');
        Route::post('/edit_profile',     [AdminController::class, 'update_profile'])->name('admin.update_profile');

        // Universal AJAX endpoints (status toggle, delete, order status change)
        Route::post('/ajax/change-status',       [AdminAjaxController::class, 'changeStatus'])->name('admin.ajax.change_status');
        Route::post('/ajax/delete-record',       [AdminAjaxController::class, 'deleteRecord'])->name('admin.ajax.delete_record');
        Route::post('/ajax/change-order-status', [AdminAjaxController::class, 'changeOrderStatus'])->name('admin.ajax.change_order_status');

        // Generic CRUD for every staff/manager role. The legacy URL patterns
        // (foo_managers / add_foo_manager / view_foo_manager) all funnel into
        // StaffController via a single role key.
        $staffRoles = [
            'account_managers'       => ['list' => 'account_managers',       'add' => 'add_account_manager',       'view' => 'view_account_manager'],
            'area_managers'          => ['list' => 'area_managers',          'add' => 'add_area_manager',          'view' => 'view_area_manager'],
            'country_managers'       => ['list' => 'country_managers',       'add' => 'add_country_manager',       'view' => 'view_country_manager'],
            'customer_care_managers' => ['list' => 'customer_care_managers', 'add' => 'add_customer_care_manager', 'view' => 'view_customer_care_manager'],
            'hr_managers'            => ['list' => 'hr_managers',            'add' => 'add_hr_manager',            'view' => 'view_hr_manager'],
            'marketing_managers'     => ['list' => 'marketing_managers',     'add' => 'add_marketing_manager',     'view' => 'view_marketing_manager'],
            'operation_managers'     => ['list' => 'operation_managers',     'add' => 'add_operation_manager',     'view' => 'view_operation_manager'],
            'planning_managers'      => ['list' => 'planning_managers',      'add' => 'add_planning_manager',      'view' => 'view_planning_manager'],
            'production_user'        => ['list' => 'production_user',        'add' => 'add_production_user',       'view' => 'view_production_user'],
            'pos_users'              => ['list' => 'pos_users',              'add' => 'add_pos_user',              'view' => 'view_pos_user'],
            'hub_users'              => ['list' => 'hub_users',              'add' => 'add_hub_user',              'view' => 'view_hub_user'],
        ];
        foreach ($staffRoles as $key => $u) {
            Route::get('/'.$u['list'], [StaffController::class, 'index'])->defaults('role', $key)->name('admin.'.$key.'.index');
            Route::get('/'.$u['add'],  [StaffController::class, 'create'])->defaults('role', $key)->name('admin.'.$key.'.create');
            Route::get('/'.$u['view'], [StaffController::class, 'show'])->defaults('role', $key)->name('admin.'.$key.'.show');
            Route::post('/staff/'.$key.'/store',  [StaffController::class, 'store'])->defaults('role', $key)->name('admin.'.$key.'.store');
            Route::post('/staff/'.$key.'/update', [StaffController::class, 'update'])->defaults('role', $key)->name('admin.'.$key.'.update');
        }

        // Hubs / Cities — list + add/edit/delete (foundational, FK'd by everything else).
        Route::get('/hub',         [AdminController::class, 'hub'])->name('admin.hub');
        Route::get('/hub_form',    [AdminController::class, 'hub_form'])->name('admin.hub_form');
        Route::post('/hub_form',   [AdminController::class, 'hub_store'])->name('admin.hub_store');
        Route::get('/city',        [AdminController::class, 'city'])->name('admin.city');
        Route::get('/city_form',   [AdminController::class, 'city_form'])->name('admin.city_form');
        Route::post('/city_form',  [AdminController::class, 'city_store'])->name('admin.city_store');

        // Order detail
        Route::get('/view_operation_order', [AdminController::class, 'view_operation_order'])
            ->name('admin.view_operation_order');

        // Inquiry tables (read-only listings of inbound forms)
        Route::get('/contact_us',         [InquiryController::class, 'contact_us'])->name('admin.contact_us');
        Route::get('/news_letters',       [InquiryController::class, 'newsletters'])->name('admin.news_letters');
        Route::get('/frenchisee_enquiry', [InquiryController::class, 'frenchisee_enquiry'])->name('admin.frenchisee_enquiry');

        // Marketing — list + add/edit (image upload) + status + delete.
        Route::get('/banner',                  [MarketingController::class, 'banners'])->name('admin.banners');
        Route::get('/banner_form',             [MarketingController::class, 'banner_form'])->name('admin.banner_form');
        Route::post('/banner_form',            [MarketingController::class, 'banner_store'])->name('admin.banner_store');

        Route::get('/app_banner',              [MarketingController::class, 'app_banners'])->name('admin.app_banners');
        Route::get('/app_banner_form',         [MarketingController::class, 'app_banner_form'])->name('admin.app_banner_form');
        Route::post('/app_banner_form',        [MarketingController::class, 'app_banner_store'])->name('admin.app_banner_store');

        Route::get('/home_offers',             [MarketingController::class, 'home_offers'])->name('admin.home_offers');
        Route::get('/home_offer_form',         [MarketingController::class, 'home_offer_form'])->name('admin.home_offer_form');
        Route::post('/home_offer_form',        [MarketingController::class, 'home_offer_store'])->name('admin.home_offer_store');

        Route::get('/promotions',              [MarketingController::class, 'promotions'])->name('admin.promotions');
        Route::get('/promotion_form',          [MarketingController::class, 'promotion_form'])->name('admin.promotion_form');
        Route::post('/promotion_form',         [MarketingController::class, 'promotion_store'])->name('admin.promotion_store');

        Route::get('/coupons_and_deals',       [MarketingController::class, 'coupons'])->name('admin.coupons');
        Route::get('/coupon_form',             [MarketingController::class, 'coupon_form'])->name('admin.coupon_form');
        Route::post('/coupon_form',            [MarketingController::class, 'coupon_store'])->name('admin.coupon_store');

        // Products — Add/Edit routed through ProductController.
        Route::get('/product_form',   [ProductController::class, 'form'])->name('admin.product_form');
        Route::post('/product_form',  [ProductController::class, 'store'])->name('admin.product_store');
        // Legacy URLs preserved as aliases (sidebar / older links).
        Route::get('/add_product',    [ProductController::class, 'form']);
        Route::get('/edit_product',   [ProductController::class, 'form']);

        // Inventory + reporting
        Route::get('/inventory',                [InventoryController::class, 'inventory'])->name('admin.inventory');
        Route::get('/inventory_report_detail',  [InventoryController::class, 'inventory_report_detail'])->name('admin.inventory_report');
        Route::get('/sale_summary',             [InventoryController::class, 'sale_summary'])->name('admin.sale_summary');

        // Content / config
        Route::get('/privacy_policy',           [ContentController::class, 'privacy_policy'])->name('admin.privacy_policy');
        Route::post('/privacy_policy',          [ContentController::class, 'privacy_policy_update'])->name('admin.privacy_policy_update');

        Route::get('/policies',                 [ContentController::class, 'policies'])->name('admin.policies');
        Route::post('/policies',                [ContentController::class, 'policies_update'])->name('admin.policies_update');

        Route::get('/push',                     [ContentController::class, 'push'])->name('admin.push');
        Route::post('/push',                    [ContentController::class, 'push_send'])->name('admin.push_send');

        Route::get('/role_type',                [ContentController::class, 'role_type'])->name('admin.role_type');

        Route::get('/certificate',              [ContentController::class, 'certificate'])->name('admin.certificate');
        Route::get('/certificate_form',         [ContentController::class, 'certificate_form'])->name('admin.certificate_form');
        Route::post('/certificate_form',        [ContentController::class, 'certificate_store'])->name('admin.certificate_store');

        Route::get('/delivery_price',           [ContentController::class, 'delivery_price'])->name('admin.delivery_price');
        Route::get('/delivery_price_form',      [ContentController::class, 'delivery_price_form'])->name('admin.delivery_price_form');
        Route::post('/delivery_price_form',     [ContentController::class, 'delivery_price_store'])->name('admin.delivery_price_store');

        // Catalog — main categories, sub categories, items, tags, units, filters.
        Route::get('/add_category',         [CatalogController::class, 'mainCategories'])->name('admin.main_categories');
        Route::get('/add_category_form',    [CatalogController::class, 'mainCategoryForm'])->name('admin.main_category_form');
        Route::post('/add_category_form',   [CatalogController::class, 'mainCategoryStore'])->name('admin.main_category_store');

        Route::get('/category',             [CatalogController::class, 'subCategories'])->name('admin.sub_categories');
        Route::get('/category_form',        [CatalogController::class, 'subCategoryForm'])->name('admin.sub_category_form');
        Route::post('/category_form',       [CatalogController::class, 'subCategoryStore'])->name('admin.sub_category_store');

        Route::get('/items',                [CatalogController::class, 'items'])->name('admin.items');
        Route::get('/item_form',            [CatalogController::class, 'itemForm'])->name('admin.item_form');
        Route::post('/item_form',           [CatalogController::class, 'itemStore'])->name('admin.item_store');

        Route::get('/product_tag',          [CatalogController::class, 'productTags'])->name('admin.product_tags');
        Route::post('/product_tag',         [CatalogController::class, 'productTagStore'])->name('admin.product_tag_store');

        Route::get('/product_unit',         [CatalogController::class, 'productUnits'])->name('admin.product_units');
        Route::post('/product_unit',        [CatalogController::class, 'productUnitStore'])->name('admin.product_unit_store');

        Route::get('/product_filter',       [CatalogController::class, 'productFilters'])->name('admin.product_filters');
        Route::post('/product_filter',      [CatalogController::class, 'productFilterStore'])->name('admin.product_filter_store');

        // Setting → mirror of edit_profile (legacy "Change Password" page).
        Route::get('/setting', fn () => redirect()->route('admin.edit_profile'));

        // Queries — no legacy table; alias to contact_us so the link doesn't 404.
        Route::get('/queries', fn () => redirect()->route('admin.contact_us'));
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

Route::prefix('account_manager')->group(function () {

    Route::middleware(['account_manager_redirect'])->group(function () {
        Route::get('/login',  [AccountManagerAuthController::class, 'login'])->name('account_manager.login');
        Route::post('/login', [AccountManagerAuthController::class, 'loginSubmit'])->name('account_manager.loginSubmit');
    });

    Route::middleware(['account_manager'])->group(function () {
        Route::get('/logout',   [AccountManagerAuthController::class, 'logout'])->name('account_manager.logout');

        Route::get('/',          [AccountManagerController::class, 'dashboard'])->name('account_manager.dashboard');
        Route::get('/dashboard', [AccountManagerController::class, 'dashboard']);

        Route::get('/all_customers',  [AccountManagerController::class, 'allCustomers'])->name('account_manager.all_customers');
        Route::get('/all_orders',     [AccountManagerController::class, 'allOrders'])->name('account_manager.all_orders');
        Route::get('/products',       [AccountManagerController::class, 'products'])->name('account_manager.products');
        Route::get('/grievance',      [AccountManagerController::class, 'grievance'])->name('account_manager.grievance');
        Route::get('/delivery_boy',   [AccountManagerController::class, 'deliveryBoy'])->name('account_manager.delivery_boy');
        Route::get('/wallet_history', [AccountManagerController::class, 'walletHistory'])->name('account_manager.wallet_history');
        Route::get('/hub_inventory',  [AccountManagerController::class, 'hubInventory'])->name('account_manager.hub_inventory');
        Route::get('/danger_stock',   [AccountManagerController::class, 'dangerStock'])->name('account_manager.danger_stock');
        Route::get('/wastage_reports',[AccountManagerController::class, 'wastageReports'])->name('account_manager.wastage_reports');
        Route::get('/pending_inward', [AccountManagerController::class, 'pendingInward'])->name('account_manager.pending_inward');
        Route::get('/cash_deposit',   [AccountManagerController::class, 'cashDeposit'])->name('account_manager.cash_deposit');
        Route::get('/sales_report',   [AccountManagerController::class, 'salesReport'])->name('account_manager.sales_report');

        // AJAX endpoints (reuse AdminAjaxController, protected by account_manager middleware)
        Route::post('/ajax/change-status',       [AdminAjaxController::class, 'changeStatus'])->name('account_manager.ajax.change_status');
        Route::post('/ajax/delete-record',       [AdminAjaxController::class, 'deleteRecord'])->name('account_manager.ajax.delete_record');
        Route::post('/ajax/change-order-status', [AdminAjaxController::class, 'changeOrderStatus'])->name('account_manager.ajax.change_order_status');
    });
});

Route::prefix('area_manager')->group(function () {

    Route::middleware(['area_manager_redirect'])->group(function () {
        Route::get('/login',  [AreaManagerAuthController::class, 'login'])->name('area_manager.login');
        Route::post('/login', [AreaManagerAuthController::class, 'loginSubmit'])->name('area_manager.loginSubmit');
    });

    Route::middleware(['area_manager'])->group(function () {
        Route::get('/logout', [AreaManagerAuthController::class, 'logout'])->name('area_manager.logout');

        Route::get('/',          [AreaManagerController::class, 'dashboard'])->name('area_manager.dashboard');
        Route::get('/dashboard', [AreaManagerController::class, 'dashboard']);

        Route::get('/all_customers',        [AreaManagerController::class, 'allCustomers'])->name('area_manager.all_customers');
        Route::get('/all_orders',           [AreaManagerController::class, 'allOrders'])->name('area_manager.all_orders');
        Route::get('/view_order',           [AreaManagerController::class, 'viewOrder'])->name('area_manager.view_order');
        Route::get('/scheduled_orders',     [AreaManagerController::class, 'scheduledOrders'])->name('area_manager.scheduled_orders');
        Route::get('/interhub_orders',      [AreaManagerController::class, 'interhubOrders'])->name('area_manager.interhub_orders');
        Route::get('/products',             [AreaManagerController::class, 'products'])->name('area_manager.products');
        Route::get('/delivery_boy',         [AreaManagerController::class, 'deliveryBoy'])->name('area_manager.delivery_boy');
        Route::get('/grievance',            [AreaManagerController::class, 'grievance'])->name('area_manager.grievance');
        Route::get('/hub_inventory',        [AreaManagerController::class, 'hubInventory'])->name('area_manager.hub_inventory');
        Route::get('/pending_inward',       [AreaManagerController::class, 'pendingInward'])->name('area_manager.pending_inward');
        Route::get('/inward_outward',       [AreaManagerController::class, 'inwardOutward'])->name('area_manager.inward_outward');
        Route::get('/danger_stock',         [AreaManagerController::class, 'dangerStock'])->name('area_manager.danger_stock');
        Route::get('/wastage_reports',      [AreaManagerController::class, 'wastageReports'])->name('area_manager.wastage_reports');
        Route::get('/sales_report',         [AreaManagerController::class, 'salesReport'])->name('area_manager.sales_report');
        Route::get('/wallet_history',       [AreaManagerController::class, 'walletHistory'])->name('area_manager.wallet_history');
        Route::get('/online_payment_history',[AreaManagerController::class, 'onlinePaymentHistory'])->name('area_manager.online_payment_history');
        Route::get('/rating_reviews',       [AreaManagerController::class, 'ratingReviews'])->name('area_manager.rating_reviews');
        Route::get('/news_letters',         [AreaManagerController::class, 'newsLetters'])->name('area_manager.news_letters');
        Route::get('/profile',              [AreaManagerController::class, 'profile'])->name('area_manager.profile');
        Route::get('/edit_profile',         [AreaManagerController::class, 'editProfile'])->name('area_manager.edit_profile');
        Route::post('/edit_profile',        [AreaManagerController::class, 'updateProfile'])->name('area_manager.update_profile');

        Route::post('/ajax/change-status',       [AdminAjaxController::class, 'changeStatus'])->name('area_manager.ajax.change_status');
        Route::post('/ajax/delete-record',       [AdminAjaxController::class, 'deleteRecord'])->name('area_manager.ajax.delete_record');
        Route::post('/ajax/change-order-status', [AdminAjaxController::class, 'changeOrderStatus'])->name('area_manager.ajax.change_order_status');
    });
});

Route::prefix('country_manager')->group(function () {

    Route::middleware(['country_manager_redirect'])->group(function () {
        Route::get('/login',  [CountryManagerAuthController::class, 'login'])->name('country_manager.login');
        Route::post('/login', [CountryManagerAuthController::class, 'loginSubmit'])->name('country_manager.loginSubmit');
    });

    Route::middleware(['country_manager'])->group(function () {
        Route::get('/logout', [CountryManagerAuthController::class, 'logout'])->name('country_manager.logout');

        Route::get('/',              [CountryManagerController::class, 'dashboard'])->name('country_manager.dashboard');
        Route::get('/dashboard',     [CountryManagerController::class, 'dashboard']);

        Route::get('/all_customers',          [CountryManagerController::class, 'allCustomers'])->name('country_manager.all_customers');
        Route::get('/all_orders',             [CountryManagerController::class, 'allOrders'])->name('country_manager.all_orders');
        Route::get('/view_order',             [CountryManagerController::class, 'viewOrder'])->name('country_manager.view_order');
        Route::get('/scheduled_orders',       [CountryManagerController::class, 'scheduledOrders'])->name('country_manager.scheduled_orders');
        Route::get('/pending_orders',         [CountryManagerController::class, 'pendingOrders'])->name('country_manager.pending_orders');
        Route::get('/interhub_orders',        [CountryManagerController::class, 'interhubOrders'])->name('country_manager.interhub_orders');
        Route::get('/day_end_report',         [CountryManagerController::class, 'dayEndReport'])->name('country_manager.day_end_report');
        Route::get('/products',               [CountryManagerController::class, 'products'])->name('country_manager.products');
        Route::get('/hubs',                   [CountryManagerController::class, 'hubs'])->name('country_manager.hubs');
        Route::get('/delivery_boy',           [CountryManagerController::class, 'deliveryBoy'])->name('country_manager.delivery_boy');
        Route::get('/grievance',              [CountryManagerController::class, 'grievance'])->name('country_manager.grievance');
        Route::get('/hub_inventory',          [CountryManagerController::class, 'hubInventory'])->name('country_manager.hub_inventory');
        Route::get('/pending_inward',         [CountryManagerController::class, 'pendingInward'])->name('country_manager.pending_inward');
        Route::get('/inward_outward',         [CountryManagerController::class, 'inwardOutward'])->name('country_manager.inward_outward');
        Route::get('/locked_inventory',       [CountryManagerController::class, 'lockedInventory'])->name('country_manager.locked_inventory');
        Route::get('/danger_stock',           [CountryManagerController::class, 'dangerStock'])->name('country_manager.danger_stock');
        Route::get('/wastage_reports',        [CountryManagerController::class, 'wastageReports'])->name('country_manager.wastage_reports');
        Route::get('/sku_report',             [CountryManagerController::class, 'skuReport'])->name('country_manager.sku_report');
        Route::get('/sales_report',           [CountryManagerController::class, 'salesReport'])->name('country_manager.sales_report');
        Route::get('/wallet_history',         [CountryManagerController::class, 'walletHistory'])->name('country_manager.wallet_history');
        Route::get('/cash_deposit',           [CountryManagerController::class, 'cashDeposit'])->name('country_manager.cash_deposit');
        Route::get('/online_payment_history', [CountryManagerController::class, 'onlinePaymentHistory'])->name('country_manager.online_payment_history');
        Route::get('/rating_reviews',         [CountryManagerController::class, 'ratingReviews'])->name('country_manager.rating_reviews');
        Route::get('/news_letters',           [CountryManagerController::class, 'newsLetters'])->name('country_manager.news_letters');
        Route::get('/profile',                [CountryManagerController::class, 'profile'])->name('country_manager.profile');
        Route::get('/edit_profile',           [CountryManagerController::class, 'editProfile'])->name('country_manager.edit_profile');
        Route::post('/edit_profile',          [CountryManagerController::class, 'updateProfile'])->name('country_manager.update_profile');

        Route::post('/ajax/change-status',       [AdminAjaxController::class, 'changeStatus'])->name('country_manager.ajax.change_status');
        Route::post('/ajax/delete-record',       [AdminAjaxController::class, 'deleteRecord'])->name('country_manager.ajax.delete_record');
        Route::post('/ajax/change-order-status', [AdminAjaxController::class, 'changeOrderStatus'])->name('country_manager.ajax.change_order_status');
    });
});

// ─── Customer Care Manager ────────────────────────────────────────────────────
Route::prefix('customer_care_manager')->group(function () {
    Route::middleware('customer_care_manager_redirect')->group(function () {
        Route::get('/login',  [CustomerCareManagerAuthController::class, 'login'])->name('customer_care_manager.login');
        Route::post('/login', [CustomerCareManagerAuthController::class, 'loginSubmit'])->name('customer_care_manager.login.submit');
    });

    Route::middleware('customer_care_manager')->group(function () {
        Route::get('/logout', [CustomerCareManagerAuthController::class, 'logout'])->name('customer_care_manager.logout');

        Route::get('/dashboard',              [CustomerCareManagerController::class, 'dashboard'])->name('customer_care_manager.dashboard');

        // Customers
        Route::get('/all_customers',          [CustomerCareManagerController::class, 'allCustomers'])->name('customer_care_manager.all_customers');
        Route::get('/customer_order',         [CustomerCareManagerController::class, 'customerOrder'])->name('customer_care_manager.customer_order');

        // Orders
        Route::get('/all_orders',             [CustomerCareManagerController::class, 'allOrders'])->name('customer_care_manager.all_orders');
        Route::get('/view_order',             [CustomerCareManagerController::class, 'viewOrder'])->name('customer_care_manager.view_order');
        Route::get('/scheduled_orders',       [CustomerCareManagerController::class, 'scheduledOrders'])->name('customer_care_manager.scheduled_orders');

        // Products & Delivery
        Route::get('/products',               [CustomerCareManagerController::class, 'products'])->name('customer_care_manager.products');
        Route::get('/delivery_boy',           [CustomerCareManagerController::class, 'deliveryBoy'])->name('customer_care_manager.delivery_boy');

        // Grievance
        Route::get('/grievance',              [CustomerCareManagerController::class, 'grievance'])->name('customer_care_manager.grievance');
        Route::get('/grievance_categories',   [CustomerCareManagerController::class, 'grievanceCategories'])->name('customer_care_manager.grievance_categories');
        Route::post('/grievance_categories',  [CustomerCareManagerController::class, 'grievanceCategoryStore'])->name('customer_care_manager.grievance_category.store');

        // Inventory
        Route::get('/hub_inventory',          [CustomerCareManagerController::class, 'hubInventory'])->name('customer_care_manager.hub_inventory');
        Route::get('/pending_inward',         [CustomerCareManagerController::class, 'pendingInward'])->name('customer_care_manager.pending_inward');
        Route::get('/inward_outward',         [CustomerCareManagerController::class, 'inwardOutward'])->name('customer_care_manager.inward_outward');
        Route::get('/danger_stock',           [CustomerCareManagerController::class, 'dangerStock'])->name('customer_care_manager.danger_stock');
        Route::get('/wastage_reports',        [CustomerCareManagerController::class, 'wastageReports'])->name('customer_care_manager.wastage_reports');

        // Wallet & Finance
        Route::get('/wallet_history',         [CustomerCareManagerController::class, 'walletHistory'])->name('customer_care_manager.wallet_history');
        Route::get('/withdraw_money',         [CustomerCareManagerController::class, 'withdrawMoney'])->name('customer_care_manager.withdraw_money');
        Route::get('/cash_deposit',           [CustomerCareManagerController::class, 'cashDeposit'])->name('customer_care_manager.cash_deposit');
        Route::get('/online_payment_history', [CustomerCareManagerController::class, 'onlinePaymentHistory'])->name('customer_care_manager.online_payment_history');

        // Reports & Misc
        Route::get('/sales_report',           [CustomerCareManagerController::class, 'salesReport'])->name('customer_care_manager.sales_report');
        Route::get('/rating_reviews',         [CustomerCareManagerController::class, 'ratingReviews'])->name('customer_care_manager.rating_reviews');
        Route::get('/news_letters',           [CustomerCareManagerController::class, 'newsLetters'])->name('customer_care_manager.news_letters');

        // Profile
        Route::get('/profile',                [CustomerCareManagerController::class, 'profile'])->name('customer_care_manager.profile');
        Route::get('/edit_profile',           [CustomerCareManagerController::class, 'editProfile'])->name('customer_care_manager.edit_profile');
        Route::post('/edit_profile',          [CustomerCareManagerController::class, 'updateProfile'])->name('customer_care_manager.update_profile');

        // AJAX
        Route::post('/ajax/change-status',       [AdminAjaxController::class, 'changeStatus'])->name('customer_care_manager.ajax.change_status');
        Route::post('/ajax/delete-record',       [AdminAjaxController::class, 'deleteRecord'])->name('customer_care_manager.ajax.delete_record');
        Route::post('/ajax/change-order-status', [AdminAjaxController::class, 'changeOrderStatus'])->name('customer_care_manager.ajax.change_order_status');
    });
});

// ─── HR Manager ───────────────────────────────────────────────────────────────
Route::prefix('hr_manager')->group(function () {
    Route::middleware('hr_manager_redirect')->group(function () {
        Route::get('/login',  [HrManagerAuthController::class, 'login'])->name('hr_manager.login');
        Route::post('/login', [HrManagerAuthController::class, 'loginSubmit'])->name('hr_manager.login.submit');
    });

    Route::middleware('hr_manager')->group(function () {
        Route::get('/logout', [HrManagerAuthController::class, 'logout'])->name('hr_manager.logout');

        Route::get('/dashboard',              [HrManagerController::class, 'dashboard'])->name('hr_manager.dashboard');

        // Customers
        Route::get('/all_customers',          [HrManagerController::class, 'allCustomers'])->name('hr_manager.all_customers');
        Route::get('/customer_order',         [HrManagerController::class, 'customerOrder'])->name('hr_manager.customer_order');

        // Orders
        Route::get('/all_orders',             [HrManagerController::class, 'allOrders'])->name('hr_manager.all_orders');
        Route::get('/view_order',             [HrManagerController::class, 'viewOrder'])->name('hr_manager.view_order');
        Route::get('/scheduled_orders',       [HrManagerController::class, 'scheduledOrders'])->name('hr_manager.scheduled_orders');
        Route::get('/interhub_orders',        [HrManagerController::class, 'interhubOrders'])->name('hr_manager.interhub_orders');

        // Products & Delivery
        Route::get('/products',               [HrManagerController::class, 'products'])->name('hr_manager.products');
        Route::get('/delivery_boy',           [HrManagerController::class, 'deliveryBoy'])->name('hr_manager.delivery_boy');

        // Grievance
        Route::get('/grievance',              [HrManagerController::class, 'grievance'])->name('hr_manager.grievance');
        Route::get('/grievance_categories',   [HrManagerController::class, 'grievanceCategories'])->name('hr_manager.grievance_categories');
        Route::post('/grievance_categories',  [HrManagerController::class, 'grievanceCategoryStore'])->name('hr_manager.grievance_category.store');

        // Inventory
        Route::get('/hub_inventory',          [HrManagerController::class, 'hubInventory'])->name('hr_manager.hub_inventory');
        Route::get('/pending_inward',         [HrManagerController::class, 'pendingInward'])->name('hr_manager.pending_inward');
        Route::get('/inward_outward',         [HrManagerController::class, 'inwardOutward'])->name('hr_manager.inward_outward');
        Route::get('/locked_inventory',       [HrManagerController::class, 'lockedInventory'])->name('hr_manager.locked_inventory');
        Route::get('/danger_stock',           [HrManagerController::class, 'dangerStock'])->name('hr_manager.danger_stock');

        // Wastage
        Route::get('/wastage_reports',        [HrManagerController::class, 'wastageReports'])->name('hr_manager.wastage_reports');
        Route::get('/sku_report',             [HrManagerController::class, 'skuReport'])->name('hr_manager.sku_report');

        // Wallet & Finance
        Route::get('/wallet_history',         [HrManagerController::class, 'walletHistory'])->name('hr_manager.wallet_history');
        Route::get('/add_wallet_money',       [HrManagerController::class, 'addWalletMoney'])->name('hr_manager.add_wallet_money');
        Route::post('/add_wallet_money',      [HrManagerController::class, 'addWalletMoneySubmit'])->name('hr_manager.add_wallet_money.submit');
        Route::get('/withdraw_money',         [HrManagerController::class, 'withdrawMoney'])->name('hr_manager.withdraw_money');
        Route::get('/cash_deposit',           [HrManagerController::class, 'cashDeposit'])->name('hr_manager.cash_deposit');
        Route::get('/online_payment_history', [HrManagerController::class, 'onlinePaymentHistory'])->name('hr_manager.online_payment_history');

        // Marketing
        Route::get('/banner',                 [HrManagerController::class, 'banner'])->name('hr_manager.banner');
        Route::get('/home_offers',            [HrManagerController::class, 'homeOffers'])->name('hr_manager.home_offers');
        Route::get('/promotions',             [HrManagerController::class, 'promotions'])->name('hr_manager.promotions');

        // Reports & Misc
        Route::get('/sales_report',           [HrManagerController::class, 'salesReport'])->name('hr_manager.sales_report');
        Route::get('/rating_reviews',         [HrManagerController::class, 'ratingReviews'])->name('hr_manager.rating_reviews');
        Route::get('/news_letters',           [HrManagerController::class, 'newsLetters'])->name('hr_manager.news_letters');

        // Profile
        Route::get('/profile',                [HrManagerController::class, 'profile'])->name('hr_manager.profile');
        Route::get('/edit_profile',           [HrManagerController::class, 'editProfile'])->name('hr_manager.edit_profile');
        Route::post('/edit_profile',          [HrManagerController::class, 'updateProfile'])->name('hr_manager.update_profile');

        // AJAX
        Route::post('/ajax/change-status',       [AdminAjaxController::class, 'changeStatus'])->name('hr_manager.ajax.change_status');
        Route::post('/ajax/delete-record',       [AdminAjaxController::class, 'deleteRecord'])->name('hr_manager.ajax.delete_record');
        Route::post('/ajax/change-order-status', [AdminAjaxController::class, 'changeOrderStatus'])->name('hr_manager.ajax.change_order_status');
    });
});

// ─── Hub ─────────────────────────────────────────────────────────────────────
Route::prefix('hub')->group(function () {
    Route::middleware('hub_redirect')->group(function () {
        Route::get('/login',  [HubAuthController::class, 'login'])->name('hub.login');
        Route::post('/login', [HubAuthController::class, 'loginSubmit'])->name('hub.login.submit');
    });

    Route::middleware('hub')->group(function () {
        Route::get('/logout', [HubAuthController::class, 'logout'])->name('hub.logout');

        Route::get('/dashboard',             [HubController::class, 'dashboard'])->name('hub.dashboard');

        // Orders
        Route::get('/my_today_order',        [HubController::class, 'myTodayOrder'])->name('hub.my_today_order');
        Route::get('/scheduled_orders',      [HubController::class, 'scheduledOrders'])->name('hub.scheduled_orders');
        Route::get('/today_cancel_orders',   [HubController::class, 'todayCancelOrders'])->name('hub.today_cancel_orders');
        Route::get('/view_order',            [HubController::class, 'viewOrder'])->name('hub.view_order');
        Route::get('/interhub_orders',       [HubController::class, 'interhubOrders'])->name('hub.interhub_orders');

        // Customers
        Route::get('/all_customers',         [HubController::class, 'allCustomers'])->name('hub.all_customers');
        Route::get('/customer_order',        [HubController::class, 'customerOrder'])->name('hub.customer_order');
        Route::get('/wallet_history',        [HubController::class, 'walletHistory'])->name('hub.wallet_history');

        // Inventory
        Route::get('/hub_inventory',         [HubController::class, 'hubInventory'])->name('hub.hub_inventory');
        Route::get('/pending_inward',        [HubController::class, 'pendingInward'])->name('hub.pending_inward');
        Route::get('/accept_inward_stocks',  [HubController::class, 'acceptInwardStocks'])->name('hub.accept_inward_stocks');
        Route::get('/locked_inventory',      [HubController::class, 'lockedInventory'])->name('hub.locked_inventory');

        // Transactions & Delivery
        Route::get('/hub_transactions',      [HubController::class, 'hubTransactions'])->name('hub.hub_transactions');
        Route::get('/delivery_boy',          [HubController::class, 'deliveryBoy'])->name('hub.delivery_boy');

        // Finance
        Route::get('/cash_deposit',          [HubController::class, 'cashDeposit'])->name('hub.cash_deposit');

        // Profile
        Route::get('/profile',               [HubController::class, 'profile'])->name('hub.profile');
        Route::get('/edit_profile',          [HubController::class, 'editProfile'])->name('hub.edit_profile');
        Route::post('/edit_profile',         [HubController::class, 'updateProfile'])->name('hub.update_profile');

        // AJAX
        Route::post('/ajax/change-status',       [AdminAjaxController::class, 'changeStatus'])->name('hub.ajax.change_status');
        Route::post('/ajax/delete-record',       [AdminAjaxController::class, 'deleteRecord'])->name('hub.ajax.delete_record');
        Route::post('/ajax/change-order-status', [AdminAjaxController::class, 'changeOrderStatus'])->name('hub.ajax.change_order_status');
    });
});

// ─── Marketing Manager ───────────────────────────────────────────────────────
Route::prefix('marketing_manager')->group(function () {
    Route::middleware('marketing_manager_redirect')->group(function () {
        Route::get('/login',  [MarketingManagerAuthController::class, 'login'])->name('marketing_manager.login');
        Route::post('/login', [MarketingManagerAuthController::class, 'loginSubmit'])->name('marketing_manager.login.submit');
    });

    Route::middleware('marketing_manager')->group(function () {
        Route::get('/logout', [MarketingManagerAuthController::class, 'logout'])->name('marketing_manager.logout');

        Route::get('/dashboard',             [MarketingManagerController::class, 'dashboard'])->name('marketing_manager.dashboard');

        // Customers & Orders
        Route::get('/all_customers',         [MarketingManagerController::class, 'allCustomers'])->name('marketing_manager.all_customers');
        Route::get('/customer_order',        [MarketingManagerController::class, 'customerOrder'])->name('marketing_manager.customer_order');
        Route::get('/all_orders',            [MarketingManagerController::class, 'allOrders'])->name('marketing_manager.all_orders');
        Route::get('/view_order',            [MarketingManagerController::class, 'viewOrder'])->name('marketing_manager.view_order');
        Route::get('/scheduled_orders',      [MarketingManagerController::class, 'scheduledOrders'])->name('marketing_manager.scheduled_orders');

        // Products & Catalog
        Route::get('/products',              [MarketingManagerController::class, 'products'])->name('marketing_manager.products');
        Route::get('/category',              [MarketingManagerController::class, 'category'])->name('marketing_manager.category');
        Route::post('/category',             [MarketingManagerController::class, 'categoryStore'])->name('marketing_manager.category.store');
        Route::get('/items',                 [MarketingManagerController::class, 'items'])->name('marketing_manager.items');

        // Delivery
        Route::get('/delivery_boy',          [MarketingManagerController::class, 'deliveryBoy'])->name('marketing_manager.delivery_boy');

        // Grievances
        Route::get('/grievance',             [MarketingManagerController::class, 'grievance'])->name('marketing_manager.grievance');
        Route::get('/grievance_categories',  [MarketingManagerController::class, 'grievanceCategories'])->name('marketing_manager.grievance_categories');

        // Inventory
        Route::get('/hub_inventory',         [MarketingManagerController::class, 'hubInventory'])->name('marketing_manager.hub_inventory');
        Route::get('/danger_stock',          [MarketingManagerController::class, 'dangerStock'])->name('marketing_manager.danger_stock');
        Route::get('/locked_inventory',      [MarketingManagerController::class, 'lockedInventory'])->name('marketing_manager.locked_inventory');
        Route::get('/wastage_reports',       [MarketingManagerController::class, 'wastageReports'])->name('marketing_manager.wastage_reports');
        Route::get('/sku_report',            [MarketingManagerController::class, 'skuReport'])->name('marketing_manager.sku_report');

        // Hubs
        Route::get('/hubs',                  [MarketingManagerController::class, 'hubs'])->name('marketing_manager.hubs');

        // Wallet & Finance
        Route::get('/wallet_history',        [MarketingManagerController::class, 'walletHistory'])->name('marketing_manager.wallet_history');
        Route::get('/add_wallet_money',      [MarketingManagerController::class, 'addWalletMoney'])->name('marketing_manager.add_wallet_money');
        Route::post('/add_wallet_money',     [MarketingManagerController::class, 'addWalletMoneySubmit'])->name('marketing_manager.add_wallet_money.submit');
        Route::get('/withdraw_money',        [MarketingManagerController::class, 'withdrawMoney'])->name('marketing_manager.withdraw_money');
        Route::get('/cash_deposit',          [MarketingManagerController::class, 'cashDeposit'])->name('marketing_manager.cash_deposit');
        Route::get('/online_payment_history',[MarketingManagerController::class, 'onlinePaymentHistory'])->name('marketing_manager.online_payment_history');

        // Marketing
        Route::get('/banner',                [MarketingManagerController::class, 'banner'])->name('marketing_manager.banner');
        Route::get('/home_offers',           [MarketingManagerController::class, 'homeOffers'])->name('marketing_manager.home_offers');
        Route::get('/promotions',            [MarketingManagerController::class, 'promotions'])->name('marketing_manager.promotions');

        // Reports & Misc
        Route::get('/sales_report',          [MarketingManagerController::class, 'salesReport'])->name('marketing_manager.sales_report');
        Route::get('/rating_reviews',        [MarketingManagerController::class, 'ratingReviews'])->name('marketing_manager.rating_reviews');
        Route::get('/news_letters',          [MarketingManagerController::class, 'newsLetters'])->name('marketing_manager.news_letters');

        // Profile
        Route::get('/profile',               [MarketingManagerController::class, 'profile'])->name('marketing_manager.profile');
        Route::get('/edit_profile',          [MarketingManagerController::class, 'editProfile'])->name('marketing_manager.edit_profile');
        Route::post('/edit_profile',         [MarketingManagerController::class, 'updateProfile'])->name('marketing_manager.update_profile');

        // AJAX
        Route::post('/ajax/change-status',       [AdminAjaxController::class, 'changeStatus'])->name('marketing_manager.ajax.change_status');
        Route::post('/ajax/delete-record',       [AdminAjaxController::class, 'deleteRecord'])->name('marketing_manager.ajax.delete_record');
        Route::post('/ajax/change-order-status', [AdminAjaxController::class, 'changeOrderStatus'])->name('marketing_manager.ajax.change_order_status');
    });
});
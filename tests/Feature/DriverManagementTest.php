<?php

use App\Models\Admin;
use App\Support\DriverJwt;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/*
| The driver / order tables pre-date Laravel and have no migrations, so this file
| creates the columns the Driver Management module uses (matching the MySQL schema).
*/
beforeEach(function () {
    Schema::create('admins', function (Blueprint $t) {
        $t->increments('id');
        $t->string('email');
        $t->string('password');
        $t->string('name')->nullable();
        $t->string('image')->nullable();
        $t->text('address')->nullable();
        $t->dateTime('date_added')->nullable();
        $t->timestamp('updated_at')->nullable();
    });
    Schema::create('driver_registration', function (Blueprint $t) {
        $t->increments('id');
        $t->string('name')->nullable();
        $t->string('emp_no')->nullable();
        $t->string('email')->nullable();
        $t->string('phone')->nullable();
        $t->string('password')->nullable();
        $t->string('status')->nullable();
        $t->string('verification_status')->nullable();
        $t->integer('approved_by')->nullable();
        $t->dateTime('approved_at')->nullable();
        $t->text('rejection_reason')->nullable();
        $t->dateTime('verified_at')->nullable();
    });
    Schema::create('driver_profile', function (Blueprint $t) {
        $t->increments('id');
        $t->integer('regid');
        foreach (['name', 'father_name', 'phone', 'blood_group', 'emergency_name', 'emergency_relationship', 'emergency_phone', 'referral_code', 'language', 'profile_photo'] as $c) {
            $t->string($c)->nullable();
        }
        $t->date('dob')->nullable();
        $t->text('address')->nullable();
        // added by migration 2026_10_15_000002 (skipped there because this table is created here)
        $t->string('working_status', 20)->default('off_duty');
        $t->dateTime('working_status_at')->nullable();
        $t->timestamps();
    });
    Schema::create('driver_vehicles', function (Blueprint $t) {
        $t->increments('id');
        $t->integer('regid');
        $t->string('vehicle_type')->default('');
        $t->string('vehicle_number')->default('');
        $t->timestamps();
    });
    Schema::create('driver_bank_details', function (Blueprint $t) {
        $t->increments('id');
        $t->integer('regid');
        foreach (['account_holder_name', 'account_number', 'ifsc_code', 'bank_name', 'branch_name'] as $c) {
            $t->string($c)->default('');
        }
        $t->timestamps();
    });
    Schema::create('driver_identity_documents', function (Blueprint $t) {
        $t->increments('id');
        $t->integer('regid');
        foreach (['aadhaar_card', 'driving_license', 'pan_card'] as $d) {
            $t->text($d.'_front')->nullable();
            $t->text($d.'_back')->nullable();
            $t->string($d.'_status')->default('');
        }
        $t->timestamps();
    });
    Schema::create('driver_wallet', function (Blueprint $t) {
        $t->increments('id');
        $t->integer('regid');
        $t->decimal('balance', 10, 2)->default(0);
        $t->string('currency')->default('INR');
        $t->string('status')->default('active');
        $t->timestamps();
    });
    Schema::create('delivery_boy', function (Blueprint $t) {
        $t->increments('id');
        $t->string('name')->nullable();
        $t->string('phone')->nullable();
        $t->string('employee_id')->nullable();
        $t->string('password')->default('');
        $t->integer('hub_id')->default(0);
        $t->string('status')->default('active');
        $t->string('regid')->nullable();
    });
    Schema::create('hubs', function (Blueprint $t) {
        $t->increments('id');
        $t->integer('city_id')->default(0);
        $t->string('hub');
        $t->string('status')->default('active');
    });
    Schema::create('buyers', function (Blueprint $t) {
        $t->increments('id');
        $t->string('name')->nullable();
        $t->string('phone')->nullable();
    });
    Schema::create('buyer_address', function (Blueprint $t) {
        $t->increments('id');
        $t->integer('buyer_id');
        foreach (['name', 'phone', 'city', 'street_name', 'locality', 'landmark'] as $c) {
            $t->string($c)->nullable();
        }
    });
    Schema::create('orders', function (Blueprint $t) {
        $t->increments('id');
        $t->string('order_id');
        $t->integer('product_id')->default(0);
        $t->integer('buyer_id')->default(0);
        $t->integer('address_id')->default(0);
        $t->integer('hub_id')->default(0);
        $t->integer('rider_id')->default(0);
        $t->string('total_amount')->default('0');
        $t->string('amount_to_collect')->nullable();
        $t->integer('quantity')->default(1);
        $t->string('order_status');
        $t->string('payment_status')->default('Pending');
        $t->string('payment_type')->nullable();
        $t->string('delivery_type')->nullable();
        $t->string('schedule_date')->nullable();
        $t->string('schedule_time')->nullable();
        $t->dateTime('date_added')->nullable();
    });

    $this->adminId = DB::table('admins')->insertGetId(['email' => 'admin@test.com', 'password' => bcrypt('secret'), 'name' => 'Test Admin']);
    $this->admin = Admin::find($this->adminId);

    $hub = DB::table('hubs')->insertGetId(['hub' => 'Central']);
    $buyer = DB::table('buyers')->insertGetId(['name' => 'Asha Buyer', 'phone' => '9000000001']);
    $this->addressId = DB::table('buyer_address')->insertGetId(['buyer_id' => $buyer, 'name' => 'Asha', 'phone' => '9000000002', 'city' => 'Indore', 'street_name' => 'MG Road']);
    $this->hubId = $hub;
    $this->buyerId = $buyer;
});

function makeDriver(string $verification, string $status = 'active', array $extra = []): int
{
    // Names/emails use the row id (ids restart for every test).
    $id = DB::table('driver_registration')->insertGetId([
        'password' => 'x', 'status' => $status, 'verification_status' => $verification,
    ] + $extra);
    DB::table('driver_registration')->where('id', $id)->update(['email' => "driver$id@test.com", 'phone' => "98000000$id", 'emp_no' => (string) $id]);
    DB::table('driver_profile')->insert(['regid' => $id, 'name' => "Driver $id"]);
    DB::table('driver_vehicles')->insert(['regid' => $id, 'vehicle_type' => 'Bike', 'vehicle_number' => "MP09-$id"]);

    return $id;
}

/** An order with $lines product rows (orders are stored one row per product). */
function makeOrder(string $orderId, string $status = 'Order Placed', int $riderId = 0, int $lines = 2): string
{
    for ($i = 0; $i < $lines; $i++) {
        DB::table('orders')->insert([
            'order_id' => $orderId, 'product_id' => $i + 1, 'buyer_id' => test()->buyerId, 'address_id' => test()->addressId,
            'hub_id' => test()->hubId, 'rider_id' => $riderId, 'total_amount' => '250', 'order_status' => $status,
            'payment_type' => 'Cash', 'delivery_type' => 'Express', 'date_added' => now()->toDateTimeString(),
        ]);
    }

    return $orderId;
}

function riderIds(string $orderId): array
{
    return DB::table('orders')->where('order_id', $orderId)->pluck('rider_id')->map(fn ($v) => (int) $v)->unique()->values()->all();
}

function assignAs($test, string $orderId, int $driverId, int $expected = 0)
{
    return $test->actingAs($test->admin, 'admin')->postJson('/admin/order_assignment/assign', [
        'order_id' => $orderId, 'driver_id' => $driverId, 'expected_rider_id' => $expected,
    ]);
}

// ---- Access control --------------------------------------------------------

test('guests cannot open driver pages or call driver actions', function () {
    $pending = makeDriver('pending_admin_approval');
    makeOrder('ORD1');
    $approved = makeDriver('approved');

    $this->get('/admin/drivers')->assertRedirect(route('admin.login'));
    $this->get('/admin/order_assignment')->assertRedirect(route('admin.login'));
    $this->post('/admin/drivers/approve', ['id' => $pending])->assertRedirect(route('admin.login'));
    $this->post('/admin/order_assignment/assign', ['order_id' => 'ORD1', 'driver_id' => $approved, 'expected_rider_id' => 0])
        ->assertRedirect(route('admin.login'));

    expect(DB::table('driver_registration')->where('id', $pending)->value('verification_status'))->toBe('pending_admin_approval')
        ->and(riderIds('ORD1'))->toBe([0]);
});

test('a logged-in buyer session or driver token is not an admin', function () {
    $pending = makeDriver('pending_admin_approval');
    $driverToken = DriverJwt::encode(['id' => $pending, 'role' => 'driver']);

    $this->withHeader('Authorization', 'Bearer '.$driverToken)
        ->postJson('/api/driver/admin/driver-approval', ['regid' => $pending, 'approve' => 'true'])
        ->assertJson(['result' => false]);
    $this->withHeader('Authorization', 'Bearer '.$driverToken)
        ->postJson('/api/driver/admin/assign-order', ['order_id' => 'X', 'regid' => $pending, 'expected_rider_id' => 0])
        ->assertJson(['result' => false, 'message' => 'Unauthorized: admin token required']);

    expect(DB::table('driver_registration')->where('id', $pending)->value('verification_status'))->toBe('pending_admin_approval');
});

// ---- Driver list and details -----------------------------------------------

test('driver list shows drivers and filters by status and search', function () {
    $pending = makeDriver('pending_admin_approval');
    $approved = makeDriver('approved');
    $suspended = makeDriver('approved', 'deactive');
    $rejected = makeDriver('rejected');

    $this->actingAs($this->admin, 'admin')->get('/admin/drivers')
        ->assertOk()->assertSee('Driver List')
        ->assertSee("Driver $pending")->assertSee("Driver $approved")->assertSee("Driver $rejected");

    $this->actingAs($this->admin, 'admin')->get('/admin/drivers?status=pending')
        ->assertOk()->assertSee("driver$pending@test.com")->assertDontSee("driver$approved@test.com")
        ->assertSee('js-driver-approve', false);

    $this->actingAs($this->admin, 'admin')->get('/admin/drivers?status=suspended')
        ->assertOk()->assertSee("driver$suspended@test.com")->assertDontSee("driver$approved@test.com");

    $this->actingAs($this->admin, 'admin')->get('/admin/drivers?search=MP09-'.$rejected)
        ->assertOk()->assertSee("driver$rejected@test.com")->assertDontSee("driver$pending@test.com");

    $this->actingAs($this->admin, 'admin')->get('/admin/drivers/pending')
        ->assertOk()->assertSee('Pending Driver Approvals')->assertSee("driver$pending@test.com")->assertDontSee("driver$rejected@test.com");
});

test('empty states render', function () {
    $this->actingAs($this->admin, 'admin')->get('/admin/drivers/pending')->assertOk()->assertSee('No drivers are waiting for approval.');
    $this->actingAs($this->admin, 'admin')->get('/admin/order_assignment')->assertOk()->assertSee('No orders are waiting for a driver.');
});

test('driver details show profile, vehicle, bank and documents', function () {
    $id = makeDriver('pending_admin_approval');
    DB::table('driver_bank_details')->insert(['regid' => $id, 'account_holder_name' => 'Ravi K', 'account_number' => '1234567890', 'ifsc_code' => 'SBIN0001', 'bank_name' => 'SBI', 'branch_name' => 'Indore']);
    DB::table('driver_identity_documents')->insert(['regid' => $id, 'driving_license_front' => 'dl_front.png', 'driving_license_back' => 'dl_back.png', 'driving_license_status' => 'action']);

    $this->actingAs($this->admin, 'admin')->get('/admin/view_driver?id='.$id)
        ->assertOk()
        ->assertSee("Driver $id")->assertSee('MP09-'.$id)->assertSee('SBIN0001')
        ->assertSee('dl_front.png', false)->assertSee('Missing')   // Aadhaar/PAN not uploaded
        ->assertSee('js-driver-approve', false)
        ->assertDontSee('"password"', false);
});

test('missing driver redirects back to the list', function () {
    $this->actingAs($this->admin, 'admin')->get('/admin/view_driver?id=999')->assertRedirect(route('admin.drivers.index'));
});

// ---- Approve / reject ----------------------------------------------------------

test('approving a pending driver updates the database and creates a wallet', function () {
    $id = makeDriver('pending_admin_approval');

    $this->actingAs($this->admin, 'admin')->postJson('/admin/drivers/approve', ['id' => $id])
        ->assertOk()->assertJson(['result' => true, 'message' => 'Driver approved successfully']);

    $row = DB::table('driver_registration')->where('id', $id)->first();
    expect($row->verification_status)->toBe('approved')
        ->and((int) $row->approved_by)->toBe($this->adminId)
        ->and($row->approved_at)->not->toBeNull()
        ->and(DB::table('driver_wallet')->where('regid', $id)->count())->toBe(1);
});

test('duplicate approval and invalid transitions are refused', function () {
    $pending = makeDriver('pending_admin_approval');
    $incomplete = makeDriver('incomplete');
    $rejected = makeDriver('rejected');

    $this->actingAs($this->admin, 'admin')->postJson('/admin/drivers/approve', ['id' => $pending])->assertJson(['result' => true]);
    $this->actingAs($this->admin, 'admin')->postJson('/admin/drivers/approve', ['id' => $pending])
        ->assertJson(['result' => false, 'message' => 'Driver is already approved']);
    $this->actingAs($this->admin, 'admin')->postJson('/admin/drivers/reject', ['id' => $pending])
        ->assertJson(['result' => false, 'message' => 'Driver is already approved']);
    $this->actingAs($this->admin, 'admin')->postJson('/admin/drivers/approve', ['id' => $incomplete])
        ->assertJson(['result' => false]);
    $this->actingAs($this->admin, 'admin')->postJson('/admin/drivers/approve', ['id' => $rejected])
        ->assertJson(['result' => false, 'message' => 'Driver is already rejected']);
    $this->actingAs($this->admin, 'admin')->postJson('/admin/drivers/approve', ['id' => 999])
        ->assertJson(['result' => false, 'message' => 'Driver not found']);

    expect(DB::table('driver_wallet')->where('regid', $pending)->count())->toBe(1)
        ->and(DB::table('driver_registration')->where('id', $incomplete)->value('verification_status'))->toBe('incomplete')
        ->and(DB::table('driver_registration')->where('id', $rejected)->value('verification_status'))->toBe('rejected');
});

test('rejecting stores the reason; ids are validated', function () {
    $id = makeDriver('pending_admin_approval');

    $this->actingAs($this->admin, 'admin')->postJson('/admin/drivers/reject', ['id' => $id, 'reason' => 'Licence photo unreadable'])
        ->assertJson(['result' => true, 'message' => 'Driver rejected']);

    $row = DB::table('driver_registration')->where('id', $id)->first();
    expect($row->verification_status)->toBe('rejected')
        ->and($row->rejection_reason)->toBe('Licence photo unreadable')
        ->and(DB::table('driver_wallet')->where('regid', $id)->count())->toBe(0);

    $this->actingAs($this->admin, 'admin')->postJson('/admin/drivers/approve', ['id' => 'abc'])->assertStatus(422);
    $this->actingAs($this->admin, 'admin')->postJson('/admin/drivers/approve', [])->assertStatus(422);
});

test('the admin token API uses the same approval rules', function () {
    $id = makeDriver('pending_admin_approval');
    $token = $this->admin->createToken('t', ['admin'])->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/driver/admin/driver-approval', ['regid' => $id, 'approve' => 'true'])
        ->assertJson(['result' => true]);
    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/driver/admin/driver-approval', ['regid' => $id, 'approve' => 'true'])
        ->assertJson(['result' => false, 'message' => 'Driver is already approved']);
});

// ---- Order assignment ------------------------------------------------------------

test('assignment page lists only assignable orders, filtered by assignment', function () {
    $driver = makeDriver('approved');
    makeOrder('ORD-PLACED');
    makeOrder('ORD-PROCESSED', 'Order Processed');
    makeOrder('ORD-ASSIGNED', 'Order Placed', $driver);
    makeOrder('ORD-PENDING', 'Order Pending');
    makeOrder('ORD-DISPATCHED', 'Order Dispatched');
    makeOrder('ORD-CANCEL', 'Order Cancel');

    $this->actingAs($this->admin, 'admin')->get('/admin/order_assignment')
        ->assertOk()->assertSee('ORD-PLACED')->assertSee('ORD-PROCESSED')->assertSee('Asha Buyer')->assertSee('MG Road')
        ->assertDontSee('ORD-ASSIGNED')->assertDontSee('ORD-PENDING')->assertDontSee('ORD-DISPATCHED')->assertDontSee('ORD-CANCEL');

    $this->actingAs($this->admin, 'admin')->get('/admin/order_assignment?filter=assigned')
        ->assertOk()->assertSee('ORD-ASSIGNED')->assertSee("Driver $driver")->assertDontSee('ORD-PLACED');

    // one row per order even though each order has 2 product rows
    $res = $this->actingAs($this->admin, 'admin')->get('/admin/order_assignment?filter=all');
    expect(substr_count($res->getContent(), 'data-order="ORD-PLACED"'))->toBe(1);
});

test('the driver picker only offers approved, non-suspended drivers', function () {
    $ok = makeDriver('approved');
    $pending = makeDriver('pending_admin_approval');
    $rejected = makeDriver('rejected');
    $suspended = makeDriver('approved', 'deactive');
    makeOrder('ORD-W', 'Order Placed', $ok);

    $res = $this->actingAs($this->admin, 'admin')->getJson('/admin/order_assignment/drivers')->assertOk()->assertJson(['result' => true]);
    $ids = collect($res->json('drivers'))->pluck('id')->all();

    expect($ids)->toBe([$ok])
        ->and($res->json('drivers.0.open_orders'))->toBe(1)
        ->and($res->json('drivers.0.vehicle_number'))->toBe('MP09-'.$ok);

    $this->actingAs($this->admin, 'admin')->getJson('/admin/order_assignment/drivers?search=nobody')->assertJson(['drivers' => []]);
});

test('assigning an approved driver updates every row of the order', function () {
    $driver = makeDriver('approved');
    makeOrder('ORD1', 'Order Placed', 0, 3);

    assignAs($this, 'ORD1', $driver)->assertOk()->assertJson(['result' => true, 'rider_id' => $driver, 'reassigned' => false]);

    expect(riderIds('ORD1'))->toBe([$driver]);
});

test('unapproved, rejected, suspended and unknown drivers cannot be assigned', function () {
    makeOrder('ORD1');
    $cases = [
        makeDriver('pending_admin_approval')    => 'Only approved drivers can be assigned orders',
        makeDriver('incomplete')                => 'Only approved drivers can be assigned orders',
        makeDriver('rejected')                  => 'Only approved drivers can be assigned orders',
        makeDriver('approved', 'deactive')    => 'This driver is suspended and cannot be assigned orders',
        999                                      => 'Driver not found',
    ];

    foreach ($cases as $driver => $message) {
        assignAs($this, 'ORD1', $driver)->assertJson(['result' => false, 'message' => $message]);
    }

    expect(riderIds('ORD1'))->toBe([0]);
});

test('cancelled, pending, dispatched and delivered orders cannot be assigned', function () {
    $driver = makeDriver('approved');

    foreach (['Order Cancel', 'Order Pending', 'Order Dispatched', 'Order Shipped', 'Order Delivered'] as $i => $status) {
        makeOrder("ORD-$i", $status);
        assignAs($this, "ORD-$i", $driver)->assertJson(['result' => false]);
        expect(riderIds("ORD-$i"))->toBe([0]);
    }

    assignAs($this, 'NO-SUCH-ORDER', $driver)->assertJson(['result' => false, 'message' => 'Order not found']);

    $this->actingAs($this->admin, 'admin')->postJson('/admin/order_assignment/assign', ['order_id' => 'ORD-0'])->assertStatus(422);
});

test('duplicate and concurrent assignments are refused', function () {
    $a = makeDriver('approved');
    $b = makeDriver('approved');
    makeOrder('ORD1');

    // Two admins open the dialog while the order is unassigned (both expect rider 0).
    assignAs($this, 'ORD1', $a, 0)->assertJson(['result' => true]);
    assignAs($this, 'ORD1', $b, 0)
        ->assertJson(['result' => false, 'message' => 'This order was just assigned to another driver. Refresh the list and try again.']);
    expect(riderIds('ORD1'))->toBe([$a]);

    // Same driver again
    assignAs($this, 'ORD1', $a, $a)->assertJson(['result' => false, 'message' => 'Order is already assigned to this driver']);
});

test('reassignment works before dispatch and is locked afterwards', function () {
    $a = makeDriver('approved');
    $b = makeDriver('approved');
    makeOrder('ORD1', 'Order Processed', $a);

    assignAs($this, 'ORD1', $b, $a)->assertJson(['result' => true, 'reassigned' => true, 'previous_rider_id' => $a]);
    expect(riderIds('ORD1'))->toBe([$b]);

    DB::table('orders')->where('order_id', 'ORD1')->update(['order_status' => 'Order Dispatched']);
    assignAs($this, 'ORD1', $a, $b)->assertJson(['result' => false]);
    expect(riderIds('ORD1'))->toBe([$b]);
});

test('an order with a mix of eligible and dispatched rows is not assignable', function () {
    $driver = makeDriver('approved');
    makeOrder('ORD1', 'Order Placed', 0, 2);
    DB::table('orders')->where('order_id', 'ORD1')->orderByDesc('id')->limit(1)->update(['order_status' => 'Order Dispatched']);

    assignAs($this, 'ORD1', $driver)->assertJson(['result' => false]);
    expect(riderIds('ORD1'))->toBe([0]);
});

test('the admin token API exposes the same assignment rules', function () {
    $ok = makeDriver('approved');
    $suspended = makeDriver('approved', 'deactive');
    makeOrder('ORD1');
    $token = $this->admin->createToken('t', ['admin'])->plainTextToken;
    $api = fn () => $this->withHeader('Authorization', 'Bearer '.$token);

    $api()->getJson('/api/driver/admin/assignable-orders')->assertJson(['result' => true, 'count' => 1, 'orders' => [['order_id' => 'ORD1']]]);
    $api()->getJson('/api/driver/admin/eligible-drivers')->assertJson(['result' => true, 'count' => 1]);
    $api()->postJson('/api/driver/admin/assign-order', ['order_id' => 'ORD1', 'regid' => $suspended, 'expected_rider_id' => 0])->assertJson(['result' => false]);
    $api()->postJson('/api/driver/admin/assign-order', ['order_id' => 'ORD1', 'regid' => $ok])->assertJson(['result' => false]); // expected_rider_id missing
    $api()->postJson('/api/driver/admin/assign-order', ['order_id' => 'ORD1', 'regid' => $ok, 'expected_rider_id' => 0])->assertJson(['result' => true]);

    expect(riderIds('ORD1'))->toBe([$ok]);
});

// ---- Account status (active / deactive) ----------------------------------------

test('toggle-driver-status only writes active or deactive and takes the driver off duty', function () {
    $id = makeDriver('approved');
    DB::table('driver_profile')->where('regid', $id)->update(['working_status' => 'on_duty']);
    $legacy = makeDriver('approved', 'Deactivate'); // old value still in some rows until migrated
    $token = $this->admin->createToken('t', ['admin'])->plainTextToken;
    $toggle = fn ($regid) => $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/driver/admin/toggle-driver-status', ['regid' => $regid]);

    $toggle($id)->assertJson(['result' => true, 'status' => 'deactive']);
    expect(DB::table('driver_registration')->where('id', $id)->value('status'))->toBe('deactive')
        ->and(DB::table('driver_profile')->where('regid', $id)->value('working_status'))->toBe('off_duty');

    $toggle($id)->assertJson(['result' => true, 'status' => 'active']);
    $toggle($legacy)->assertJson(['result' => true, 'status' => 'active']);
    $toggle(999)->assertJson(['result' => false, 'message' => 'Driver not found']);

    expect(DB::table('driver_registration')->pluck('status')->unique()->values()->all())->toBe(['active']);
});

test('the old Deactivate value still counts as deactivated', function () {
    $legacy = makeDriver('approved', 'Deactivate');
    makeOrder('ORD1');

    assignAs($this, 'ORD1', $legacy)->assertJson(['result' => false, 'message' => 'This driver is suspended and cannot be assigned orders']);
    $this->actingAs($this->admin, 'admin')->get('/admin/drivers?status=suspended')->assertSee("driver$legacy@test.com");
});

// ---- Working status (on duty / off duty) ----------------------------------------

function driverApi($test, int $regid)
{
    return $test->withHeader('Authorization', 'Bearer '.DriverJwt::encode(['id' => $regid, 'phone' => '1', 'role' => 'driver']));
}

test('driver can read and change working status', function () {
    $id = makeDriver('approved');

    driverApi($this, $id)->getJson('/api/driver/driver/working-status')->assertJson(['result' => true, 'working_status' => 'off_duty']);
    driverApi($this, $id)->postJson('/api/driver/driver/working-status', ['working_status' => 'On Duty'])
        ->assertJson(['result' => true, 'working_status' => 'on_duty', 'message' => 'You are now on duty']);
    $row = DB::table('driver_profile')->where('regid', $id)->first();
    expect($row->working_status)->toBe('on_duty')->and($row->working_status_at)->not->toBeNull();

    driverApi($this, $id)->postJson('/api/driver/driver/working-status', ['working_status' => 'off_duty'])->assertJson(['result' => true, 'working_status' => 'off_duty']);
    driverApi($this, $id)->postJson('/api/driver/driver/working-status', ['working_status' => 'busy'])
        ->assertJson(['result' => false, 'message' => 'working_status must be on_duty or off_duty']);
    $this->flushHeaders()->postJson('/api/driver/driver/working-status', ['working_status' => 'on_duty'])->assertJson(['result' => false, 'message' => 'No token provided']);
});

test('only approved, active drivers can go on duty; anyone can go off duty', function () {
    $pending = makeDriver('pending_admin_approval');
    $deactive = makeDriver('approved', 'deactive');

    driverApi($this, $pending)->postJson('/api/driver/driver/working-status', ['working_status' => 'on_duty'])
        ->assertJson(['result' => false, 'message' => 'You can go on duty after your account is approved']);
    driverApi($this, $deactive)->postJson('/api/driver/driver/working-status', ['working_status' => 'on_duty'])
        ->assertJson(['result' => false, 'message' => 'Your account is deactivated. Please contact support.']);
    driverApi($this, $deactive)->postJson('/api/driver/driver/working-status', ['working_status' => 'off_duty'])->assertJson(['result' => true]);

    expect(DB::table('driver_profile')->whereIn('regid', [$pending, $deactive])->pluck('working_status')->unique()->values()->all())->toBe(['off_duty']);
});

test('working status creates the profile row if the driver has none yet', function () {
    $id = makeDriver('approved');
    DB::table('driver_profile')->where('regid', $id)->delete();

    driverApi($this, $id)->postJson('/api/driver/driver/working-status', ['working_status' => 'on_duty'])->assertJson(['result' => true]);
    expect(DB::table('driver_profile')->where('regid', $id)->value('working_status'))->toBe('on_duty');
});

test('assign modal lists on-duty drivers first with their duty status', function () {
    $off = makeDriver('approved');
    $on = makeDriver('approved');
    DB::table('driver_profile')->where('regid', $on)->update(['working_status' => 'on_duty']);

    $res = $this->actingAs($this->admin, 'admin')->getJson('/admin/order_assignment/drivers')->assertOk();
    expect(collect($res->json('drivers'))->map(fn ($d) => [$d['id'], $d['working_status']])->all())
        ->toBe([[$on, 'on_duty'], [$off, 'off_duty']]);
});

// ---- Current location ------------------------------------------------------------

test('driver saves current location; one row per driver, updated in place', function () {
    $id = makeDriver('approved');

    driverApi($this, $id)->getJson('/api/driver/driver/current-location')->assertJson(['result' => true, 'location' => null]);
    driverApi($this, $id)->postJson('/api/driver/driver/current-location', ['address' => 'MG Road, Indore', 'lat' => '22.7196', 'lng' => '75.8577'])
        ->assertJson(['result' => true, 'message' => 'Location updated']);
    driverApi($this, $id)->postJson('/api/driver/driver/current-location', ['address' => 'Vijay Nagar', 'lat' => 22.7533, 'lng' => 75.8937])
        ->assertJson(['result' => true]);

    expect(DB::table('driver_locations')->where('regid', $id)->count())->toBe(1);
    $row = DB::table('driver_locations')->where('regid', $id)->first();
    expect($row->address)->toBe('Vijay Nagar')->and((float) $row->lat)->toBe(22.7533)->and((float) $row->lng)->toBe(75.8937);

    driverApi($this, $id)->getJson('/api/driver/driver/current-location')->assertJson(['location' => ['address' => 'Vijay Nagar']]);

    $this->actingAs($this->admin, 'admin')->get('/admin/view_driver?id='.$id)
        ->assertSee('Vijay Nagar')->assertSee('google.com/maps?q=22.7533', false);
});

test('current location is validated and needs a driver token', function () {
    $id = makeDriver('approved');

    driverApi($this, $id)->postJson('/api/driver/driver/current-location', ['address' => 'x'])->assertJson(['result' => false, 'message' => 'lat and lng are required']);
    driverApi($this, $id)->postJson('/api/driver/driver/current-location', ['lat' => 'abc', 'lng' => 75])->assertJson(['result' => false]);
    driverApi($this, $id)->postJson('/api/driver/driver/current-location', ['lat' => 95, 'lng' => 75])
        ->assertJson(['result' => false, 'message' => 'lat must be between -90 and 90, lng between -180 and 180']);
    $this->flushHeaders()->postJson('/api/driver/driver/current-location', ['lat' => 22, 'lng' => 75])->assertJson(['result' => false, 'message' => 'No token provided']);

    expect(DB::table('driver_locations')->count())->toBe(0);
});

test('admin panel Active switch toggles account status', function () {
    $id = makeDriver('approved');
    DB::table('driver_profile')->where('regid', $id)->update(['working_status' => 'on_duty']);

    $this->post('/admin/drivers/toggle-status', ['id' => $id])->assertRedirect(route('admin.login'));
    expect(DB::table('driver_registration')->where('id', $id)->value('status'))->toBe('active');

    $this->actingAs($this->admin, 'admin')->get('/admin/drivers')->assertSee('js-driver-status', false);

    $this->actingAs($this->admin, 'admin')->postJson('/admin/drivers/toggle-status', ['id' => $id])
        ->assertJson(['result' => true, 'status' => 'deactive', 'message' => 'Driver deactivated']);
    expect(DB::table('driver_registration')->where('id', $id)->value('status'))->toBe('deactive')
        ->and(DB::table('driver_profile')->where('regid', $id)->value('working_status'))->toBe('off_duty');

    $this->actingAs($this->admin, 'admin')->postJson('/admin/drivers/toggle-status', ['id' => $id])
        ->assertJson(['result' => true, 'status' => 'active', 'message' => 'Driver activated']);
    $this->actingAs($this->admin, 'admin')->postJson('/admin/drivers/toggle-status', ['id' => 999])
        ->assertJson(['result' => false, 'message' => 'Driver not found']);
    $this->actingAs($this->admin, 'admin')->postJson('/admin/drivers/toggle-status', [])->assertStatus(422);
});

// ---- Driver app: assigned orders ------------------------------------------------

test('driver sees only their own unfinished assigned orders, one entry per order', function () {
    // Columns the driver order endpoints read that the shared test schema doesn't create.
    Schema::create('products', function (Blueprint $t) {
        $t->increments('id');
        $t->string('product_name')->nullable();
        $t->string('product_image')->nullable();
        $t->string('product_unit')->nullable();
    });
    Schema::table('buyer_address', function (Blueprint $t) {
        $t->string('user_lat')->nullable();
        $t->string('user_lng')->nullable();
    });
    Schema::table('hubs', function (Blueprint $t) {
        $t->string('hub_lat')->nullable();
        $t->string('hub_lng')->nullable();
    });
    DB::table('products')->insert([['id' => 1, 'product_name' => 'Chicken Curry Cut', 'product_image' => 'c.png', 'product_unit' => '500 g'], ['id' => 2, 'product_name' => 'Eggs', 'product_image' => null, 'product_unit' => '6 pcs']]);

    $me = makeDriver('approved');
    $other = makeDriver('approved');
    makeOrder('ORD-A', 'Order Placed', $me, 2);        // pending, 2 items
    makeOrder('ORD-B', 'Order Dispatched', $me, 1);    // in progress
    makeOrder('ORD-C', 'Order Delivered', $me, 1);     // finished: hidden
    makeOrder('ORD-D', 'Order Cancel', $me, 1);        // cancelled: hidden
    makeOrder('ORD-E', 'Order Placed', $other, 1);     // someone else's
    makeOrder('ORD-F', 'Order Placed', 0, 1);          // unassigned

    $res = driverApi($this, $me)->getJson('/api/driver/orders/assigned-orders')->assertOk()
        ->assertJson(['result' => true, 'count' => 2, 'summary' => ['pending' => 1, 'in_progress' => 1]]);

    expect(collect($res->json('orders'))->pluck('order_id')->all())->toBe(['ORD-A', 'ORD-B'])
        ->and($res->json('orders.0.stage'))->toBe('pending')
        ->and($res->json('orders.0.items'))->toHaveCount(2)
        ->and($res->json('orders.0.items.0.product_name'))->toBe('Chicken Curry Cut')
        ->and($res->json('orders.0.items.0.product_image'))->toEndWith('/uploads/images/products/c.png')
        ->and($res->json('orders.0.buyer.name'))->toBe('Asha Buyer')
        ->and($res->json('orders.0.address.street_name'))->toBe('MG Road')
        ->and($res->json('orders.1.stage'))->toBe('in_progress');

    driverApi($this, $me)->getJson('/api/driver/orders/assigned-orders?filter=pending')
        ->assertJson(['count' => 1, 'orders' => [['order_id' => 'ORD-A']]]);
    driverApi($this, $me)->getJson('/api/driver/orders/assigned-orders?filter=in_progress')
        ->assertJson(['count' => 1, 'orders' => [['order_id' => 'ORD-B']]]);
    driverApi($this, $me)->getJson('/api/driver/orders/assigned-orders?filter=bogus')
        ->assertJson(['result' => false, 'message' => 'Invalid filter. Allowed: all, pending, in_progress']);

    $none = makeDriver('approved');
    driverApi($this, $none)->getJson('/api/driver/orders/assigned-orders')
        ->assertJson(['result' => true, 'count' => 0, 'orders' => [], 'message' => 'No assigned orders']);

    $this->flushHeaders()->getJson('/api/driver/orders/assigned-orders')->assertJson(['result' => false, 'message' => 'No token provided']);
});

// ---- Driver app: mark order delivered --------------------------------------------

test('assigned driver can mark an order delivered, once', function () {
    Schema::table('orders', function (Blueprint $t) {
        $t->string('delivery_complete_time')->nullable();
        $t->dateTime('updated_at')->nullable();
    });
    $me = makeDriver('approved');
    $other = makeDriver('approved');
    makeOrder('ORD-PLACED', 'Order Placed', $me, 3);
    makeOrder('ORD-DISPATCHED', 'Order Dispatched', $me, 1);
    makeOrder('ORD-OTHER', 'Order Placed', $other, 1);
    makeOrder('ORD-CANCEL', 'Order Cancel', $me, 1);
    makeOrder('ORD-PENDING', 'Order Pending', $me, 1);
    $deliver = fn ($order) => driverApi($this, $me)->postJson('/api/driver/orders/deliver', ['order_id' => $order]);

    $deliver('ORD-PLACED')->assertJson(['result' => true, 'message' => 'Order delivered successfully', 'order_status' => 'Order Delivered']);
    $rows = DB::table('orders')->where('order_id', 'ORD-PLACED')->get();
    expect($rows->pluck('order_status')->unique()->all())->toBe(['Order Delivered'])
        ->and($rows->whereNull('delivery_complete_time')->count())->toBe(0)
        ->and($rows->pluck('payment_status')->unique()->all())->toBe(['Pending']); // payment untouched

    $deliver('ORD-PLACED')->assertJson(['result' => false, 'message' => 'Order is already delivered']);
    $deliver('ORD-DISPATCHED')->assertJson(['result' => true]);

    $deliver('ORD-OTHER')->assertJson(['result' => false, 'message' => 'This order is not assigned to you']);
    $deliver('ORD-CANCEL')->assertJson(['result' => false, 'message' => 'This order was cancelled']);
    $deliver('ORD-PENDING')->assertJson(['result' => false, 'message' => 'Order cannot be delivered in status "Order Pending"']);
    $deliver('NOPE')->assertJson(['result' => false, 'message' => 'Order not found']);
    driverApi($this, $me)->postJson('/api/driver/orders/deliver', [])->assertJson(['result' => false, 'message' => 'order_id is required']);

    expect(DB::table('orders')->where('order_id', 'ORD-OTHER')->value('order_status'))->toBe('Order Placed')
        ->and(DB::table('orders')->where('order_id', 'ORD-PENDING')->value('order_status'))->toBe('Order Pending');

    $this->flushHeaders()->postJson('/api/driver/orders/deliver', ['order_id' => 'ORD-DISPATCHED'])->assertJson(['result' => false, 'message' => 'No token provided']);
});

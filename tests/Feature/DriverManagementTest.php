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

function makeDriver(string $verification, string $status = 'Activate', array $extra = []): int
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
    $suspended = makeDriver('approved', 'Deactivate');
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
    $suspended = makeDriver('approved', 'Deactivate');
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
        makeDriver('approved', 'Deactivate')    => 'This driver is suspended and cannot be assigned orders',
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
    $suspended = makeDriver('approved', 'Deactivate');
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

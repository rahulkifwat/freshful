<?php
/* Order payment guard tests. Run on the server: php tests/OrderGuard/run.php  (needs the app autoloader)  */
$boot = getenv('OG_RIG');
if ($boot) { require $boot; } else { require __DIR__ . '/../../vendor/autoload.php'; $app = require __DIR__ . '/../../bootstrap/app.php'; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); }
use App\Services\Orders\PaymentGuard;
$p = 0; $f = 0;
function ok($c, $m) { global $p, $f; if ($c) { $p++; echo "  ok   $m\n"; } else { $f++; echo "  FAIL $m\n"; } }
app('config')->set('order_guard.allow_online_wallet', false);
foreach (['Cash', 'cash', 'COD', ' Cash on delivery ', '', null] as $t) ok(PaymentGuard::refusal($t) === null && PaymentGuard::forLegacy($t) === 'Cash', 'cash accepted and saved as Cash: ' . var_export($t, true));
foreach (['Online', 'Wallet', 'wallet', 'Card', 'UPI', 'Razorpay'] as $t) {
    ok(PaymentGuard::refusal($t) === PaymentGuard::MESSAGE, "refused: $t");
    $thrown = false; try { PaymentGuard::forLegacy($t); } catch (\App\Exceptions\ApiException $e) { $thrown = $e->getMessage() === PaymentGuard::MESSAGE; }
    ok($thrown, "legacy endpoint throws the standard API error for: $t");
}
app('config')->set('order_guard.allow_online_wallet', true);
ok(PaymentGuard::refusal('Online') === null && PaymentGuard::refusal('Wallet') === null, 'switch on: Online and Wallet pass');
ok(PaymentGuard::forLegacy('Wallet') === 'Wallet' && PaymentGuard::forLegacy('') === 'Cash', 'switch on: type kept, blank still Cash');
app('config')->set('order_guard.allow_online_wallet', false);
ok(strpos(PaymentGuard::MESSAGE, 'Cash on delivery') !== false, 'message tells the customer what to do');
echo "\n$p passed, $f failed\n"; exit($f ? 1 : 0);

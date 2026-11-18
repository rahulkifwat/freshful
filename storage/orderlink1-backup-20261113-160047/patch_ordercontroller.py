#!/usr/bin/env python3
# orderlink-v1: make the rider app order API use the delivery_boy id (through RiderLink) and add accept().
# Exit codes: 0 patched, 10 already patched, 3 an anchor did not match (file left untouched).
import re
import sys

path = sys.argv[1]
raw = open(path, 'rb').read()
txt = raw.decode('utf-8')
nl = '\r\n' if '\r\n' in txt else '\n'
s = txt.replace('\r\n', '\n')

if 'orderlink-v1' in s:
    print('ALREADY')
    sys.exit(10)

problems = []


def once(label, pattern, repl, expect=1, flags=re.M):
    global s
    new, n = re.subn(pattern, repl, s, flags=flags)
    if n != expect:
        problems.append('%s: found %d, expected %d' % (label, n, expect))
        return
    s = new


BOY = "RiderLink::boyId((int) $this->driverId($request))"

once('use line',
     r'^use App\\Services\\DriverWalletService;[ \t]*$',
     lambda m: m.group(0) + '\nuse App\\Services\\Driver\\RiderLink;')
once('driver-orders query',
     r"->where\('rider_id', \$this->driverId\(\$request\)\)->orderByDesc\('date_added'\)",
     lambda m: "->where('rider_id', " + BOY + ")->orderByDesc('date_added')")
once('joined queries (orders-by-status, assigned-orders)',
     r"^([ \t]*)->where\('o\.rider_id', \$this->driverId\(\$request\)\)",
     lambda m: m.group(1) + "->where('o.rider_id', " + BOY + ")", expect=2)
once('deliver: transaction',
     r"^([ \t]*)\$result = DB::transaction\(function \(\) use \(\$orderId, \$driverId, \$deliverable\) \{",
     lambda m: m.group(1) + "$boyId = RiderLink::boyId((int) $driverId);\n" + m.group(1)
     + "$result = DB::transaction(function () use ($orderId, $driverId, $deliverable, $boyId) {")
once('deliver: owner check',
     r"^([ \t]*)if \(\$rows->contains\(fn \(\$r\) => \(int\) \$r->rider_id !== \$driverId\)\) \{",
     lambda m: m.group(1) + "if ($rows->contains(fn ($r) => (int) $r->rider_id !== $boyId)) {")
once('deliver: update filter',
     r"^([ \t]*)->where\('rider_id', \$driverId\)",
     lambda m: m.group(1) + "->where('rider_id', $boyId)")

ACCEPT = r'''
    /**
     * POST /api/driver/orders/accept: order_id
     * The rider the order is assigned to confirms it; the POS then shows it as seen and accepted.
     * A rider cannot take an order that belongs to somebody else or to nobody. (orderlink-v1)
     */
    public function accept(Request $request): JsonResponse
    {
        $orderId = trim((string) $request->input('order_id', $request->input('orderId', '')));
        if ($orderId === '') {
            throw new ApiException('order_id is required');
        }

        $driverId = (int) $this->driverId($request);
        $boyId = RiderLink::boyId($driverId);

        $rows = DB::table('orders')->where('order_id', $orderId)->get(['order_status', 'rider_id', 'hub_id']);
        if ($rows->isEmpty()) {
            throw new ApiException('Order not found');
        }
        if ($boyId < 1 || $rows->contains(fn ($r) => (int) $r->rider_id !== $boyId)) {
            throw new ApiException('This order is not assigned to you');
        }
        if (in_array((string) $rows->first()->order_status, ['Order Cancel', 'Order Delivered'], true)) {
            throw new ApiException('This order is already closed');
        }

        $now = date('Y-m-d H:i:s');
        try {
            $mine = DB::table('pos_rider_assign')->where('order_id', $orderId)->where('rider_id', $boyId);
            if ((clone $mine)->exists()) {
                (clone $mine)->whereNull('seen_at')->update(['seen_at' => $now]);
                (clone $mine)->whereNull('accepted_at')->update(['accepted_at' => $now]);
            } else {
                DB::table('pos_rider_assign')->insert([
                    'order_id' => $orderId, 'rider_id' => $boyId, 'hub_id' => (int) $rows->first()->hub_id,
                    'assigned_at' => $now, 'seen_at' => $now, 'accepted_at' => $now,
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[orderlink] accept could not be recorded for the POS', ['order' => $orderId, 'error' => $e->getMessage()]);
        }

        return $this->respond([
            'result'   => true,
            'message'  => 'Order accepted',
            'order_id' => $orderId,
            'rider_id' => $driverId,
        ]);
    }
'''

body = s.rstrip()
if not body.endswith('}'):
    problems.append('class end: the file does not end with a closing brace')
else:
    s = body[:-1].rstrip('\n') + '\n' + ACCEPT + '}\n'

if problems:
    print('ANCHOR PROBLEM')
    for p in problems:
        print(' - ' + p)
    sys.exit(3)

out = s.replace('\n', nl)
open(path, 'wb').write(out.encode('utf-8'))
print('PATCHED')

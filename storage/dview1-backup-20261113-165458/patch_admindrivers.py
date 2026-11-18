import sys
p = sys.argv[1]
b = open(p, 'rb').read()
nl = b'\r\n' if b'\r\n' in b else b'\n'
anchor = b"if (!$regids || !self::has('delivery_boy', 'regid')) return [];"
if b'orderlink-v2' in b:
    sys.exit(10)
if b.count(anchor) != 1:
    print("anchor", b.count(anchor)); sys.exit(3)
add = nl.join([
 b"        // orderlink-v2: same rider record the rider app uses (see RiderLink); the old lookup below stays as the fallback",
 b"        try { return \\App\\Services\\Driver\\RiderLink::many($regids); } catch (\\Throwable $e) { \\Illuminate\\Support\\Facades\\Log::warning('[freshful] RiderLink::many failed, using the old lookup: ' . $e->getMessage()); }",
])
b = b.replace(anchor, anchor + nl + add)
open(p, 'wb').write(b)

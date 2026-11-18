import re, sys
p = sys.argv[1]
b = open(p, 'rb').read()
nl = b'\r\n' if b'\r\n' in b else b'\n'
if b'orderlink-v4' in b:
    sys.exit(10)
start_pat = b'public function assign(string $orderId, int $regid, int $expectedRiderId): array'
end_pat = b'public function assertDriverEligible('
if b.count(start_pat) != 1 or b.count(end_pat) != 1:
    print("function markers", b.count(start_pat), b.count(end_pat)); sys.exit(3)
s = b.index(start_pat); e = b.index(end_pat)
if e < s:
    print("function order"); sys.exit(3)
body = b[s:e]
a1 = b'return DB::transaction(function () use ($orderId, $regid, $expectedRiderId) {'
a2 = b'if ($current === $regid) {'
a3 = b"->update(['rider_id' => $regid]);"
a4 = re.compile(rb"'rider_id'(\s+)=> \$regid,")
for a in (a1, a2, a3):
    if body.count(a) != 1:
        print("anchor", a, body.count(a)); sys.exit(3)
if len(a4.findall(body)) != 1:
    print("return key", len(a4.findall(body))); sys.exit(3)
ind = b'        '
pre = nl.join([
 ind + b"// orderlink-v4: orders.rider_id holds the delivery_boy id (what the rider app, POS and hubs use), not the driver id",
 ind + b"$boyId = \\App\\Services\\Driver\\RiderLink::boyId($regid);",
 ind + b"if ($boyId < 1) {",
 ind + b"    throw new ApiException('This driver has no rider record yet (no hub), so an order given to them would never reach the rider app. Open the driver and set a hub first.');",
 ind + b"}",
 ind + b"$boyStatus = strtolower(trim((string) DB::table('delivery_boy')->where('id', $boyId)->value('status')));",
 ind + b"if (! in_array($boyStatus, ['active', '1'], true)) {",
 ind + b"    throw new ApiException('This rider is switched off, so they cannot be given orders. Switch the rider on first.');",
 ind + b"}",
 b"",
 ind,
])
body = body.replace(a1, pre + b'return DB::transaction(function () use ($orderId, $regid, $expectedRiderId, $boyId) {')
body = body.replace(a2, b'if ($current === $boyId) {')
body = body.replace(a3, b"->update(['rider_id' => $boyId]);")
body = a4.sub(lambda m: b"'rider_id'" + m.group(1) + b"=> $boyId,", body)
open(p, 'wb').write(b[:s] + body + b[e:])

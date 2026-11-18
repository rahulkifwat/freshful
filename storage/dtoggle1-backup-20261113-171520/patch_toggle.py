import sys
p = sys.argv[1]
b = open(p, 'rb').read()
nl = b'\r\n' if b'\r\n' in b else b'\n'
if b'orderlink-v3' in b:
    sys.exit(10)
anchor = b"$next = self::svc()->toggleAccountStatus($id);"
if b.count(anchor) != 1:
    print("anchor", b.count(anchor)); sys.exit(3)
lines = b.split(b'\n')
hit = [i for i, l in enumerate(lines) if anchor in l]
if len(hit) != 1:
    print("lines", len(hit)); sys.exit(3)
cr = b'\r' if lines[hit[0]].endswith(b'\r') else b''
add = [
 b"        // orderlink-v3: the linked delivery_boy row follows the account switch, so the POS and hubs stop (or start) giving this rider orders",
 b"        try { if (!empty($x['boy'])) DB::table('delivery_boy')->where('id', (int) $x['boy']->id)->update(['status' => $next === 'active' ? 'active' : 'inactive']); } catch (\\Throwable $e) { \\Illuminate\\Support\\Facades\\Log::warning('[freshful] could not sync the delivery boy status: ' . $e->getMessage()); }",
]
lines[hit[0] + 1:hit[0] + 1] = [a + cr for a in add]
open(p, 'wb').write(b'\n'.join(lines))

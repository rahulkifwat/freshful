@php
    $m = fn ($v) => '&#8377;' . number_format((float) $v, 2);
    $q = fn ($v) => rtrim(rtrim(number_format((float) $v, 3, '.', ''), '0'), '.');
    $t = $inv['totals'];
    $when = $inv['date'] ? \Carbon\Carbon::parse($inv['date'])->format('d M Y, h:i A') : '';
    $canRate = $inv['delivered'];
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Order {{ $inv['id'] }} - Freshful</title>
<style>
*{box-sizing:border-box}
body{margin:0;background:#F4F5F7;color:#111827;font:14px/1.5 -apple-system,"Segoe UI",Roboto,Arial,sans-serif}
.top{background:#fff;border-bottom:1px solid #E5E7EB;padding:12px 16px}
.top .in{max-width:880px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:12px}
.top img{height:38px;display:block}
.top a.back{color:#B91C1C;text-decoration:none;font-weight:700}
.wrap{max-width:880px;margin:18px auto;padding:0 14px 40px}
.card{background:#fff;border:1px solid #E5E7EB;border-radius:14px;padding:18px;margin-bottom:14px}
h1{margin:0 0 4px;font-size:20px}
.sub{color:#6B7280;font-size:13px}
.pill{display:inline-block;border-radius:99px;padding:4px 12px;font-size:12px;font-weight:700;background:#FEE2E2;color:#991B1B}
.pill.ok{background:#E0E7FF;color:#3730A3}
.btns{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}
.btn{display:inline-flex;align-items:center;min-height:42px;padding:0 18px;border-radius:10px;border:1px solid #D1D5DB;background:#fff;color:#111827;font-weight:700;font-size:14px;text-decoration:none;cursor:pointer;font-family:inherit}
.btn.pri{border:0;color:#fff;background:linear-gradient(135deg,#F70202,#FF5500)}
.item{display:flex;gap:14px;padding:14px 0;border-top:1px solid #F3F4F6}
.item:first-of-type{border-top:0}
.item img{width:72px;height:72px;border-radius:10px;object-fit:cover;background:#F3F4F6;flex:none}
.item .body{flex:1;min-width:0}
.item .amt{font-weight:800;white-space:nowrap}
.stars{display:flex;gap:2px;margin:8px 0 6px}
.stars button{border:0;background:none;font-size:26px;line-height:1;cursor:pointer;color:#D1D5DB;padding:0 2px}
.stars button.on{color:#F59E0B}
.fb{width:100%;border:1px solid #D1D5DB;border-radius:10px;padding:9px 11px;font:inherit;resize:vertical}
.row{display:flex;justify-content:space-between;padding:4px 0}
.row.net{border-top:2px solid #111827;margin-top:6px;padding-top:10px;font-size:17px;font-weight:800}
.msg{font-size:12.5px;margin-top:6px;color:#3730A3;font-weight:600}
.msg.bad{color:#B91C1C}
.note{color:#6B7280;font-size:12.5px}
</style>
</head>
<body>
<div class="top"><div class="in"><a href="/"><img src="{{ $co['logo'] }}" alt="Freshful"></a><a class="back" href="{{ route('myaccount') }}">&larr; My account</a></div></div>
<div class="wrap">
  <div class="card">
    <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:flex-start">
      <div><h1>Order #{{ $inv['id'] }}</h1><div class="sub">Placed {{ \Carbon\Carbon::parse($inv['placed'])->format('d M Y, h:i A') }} &middot; {{ $inv['delivery']['type'] }}@if ($inv['delivery']['slot']) &middot; {{ $inv['delivery']['slot'] }}@endif</div></div>
      <span class="pill {{ $inv['delivered'] ? 'ok' : '' }}">{{ $inv['status'] }}</span>
    </div>
    <div class="btns">
      <a class="btn pri" href="{{ route('invoice.order', $inv['id']) }}" target="_blank">View / download invoice</a>
      @if (!$inv['delivered'] && !$inv['cancelled'])<a class="btn" href="{{ route('track-order', $inv['id']) }}">Track order</a>@endif
    </div>
    @if ($inv['customer']['address'])<p class="note" style="margin:12px 0 0">Delivered to: {{ $inv['customer']['address'] }}</p>@endif
  </div>

  <div class="card">
    <b>Items in this order</b>
    @foreach ($inv['items'] as $it)
      @php $rv = $reviews[$it['product_id']] ?? null; $img = $it['image'] ? '/uploads/images/products/' . $it['image'] : '/assets/images/freshful-placeholder.png'; @endphp
      <div class="item" data-pid="{{ $it['product_id'] }}" data-rating="{{ $rv->rating ?? 0 }}">
        <img src="{{ $img }}" alt="" onerror="this.src='/assets/images/freshful-placeholder.png'">
        <div class="body">
          <div style="display:flex;justify-content:space-between;gap:10px"><b>{{ $it['name'] }}</b><span class="amt">{!! $m($it['amount']) !!}</span></div>
          <div class="sub">{{ $it['pack'] }} &middot; Qty {{ $q($it['qty']) }} &middot; {!! $m($it['rate']) !!} each</div>
          @if ($canRate)
            <div class="stars" role="group" aria-label="Rate {{ $it['name'] }}">@for ($s = 1; $s <= 5; $s++)<button type="button" data-s="{{ $s }}" aria-label="{{ $s }} star">&#9733;</button>@endfor</div>
            <textarea class="fb" rows="2" maxlength="300" placeholder="Tell us about the quality (optional)">{{ $rv->feedback ?? '' }}</textarea>
            <div style="margin-top:8px"><button type="button" class="btn sub-btn" style="min-height:36px;padding:0 14px">{{ $rv ? 'Update rating' : 'Submit rating' }}</button><span class="msg"></span></div>
          @endif
        </div>
      </div>
    @endforeach
    @if (!$canRate)<p class="note" style="margin:10px 0 0">You can rate each item once the order is delivered.</p>@endif
  </div>

  <div class="card">
    <b>Payment summary</b>
    <div style="margin-top:8px">
      <div class="row"><span>Items total</span><span>{!! $m($t['base']) !!}</span></div>
      @if ($t['discount'] > 0)<div class="row"><span>Discount{{ $t['coupon'] ? ' (' . $t['coupon'] . ')' : '' }}</span><span>- {!! $m($t['discount']) !!}</span></div>@endif
      @if (abs($t['adjust']) >= 0.01)<div class="row"><span>Weight / price adjustment</span><span>{{ $t['adjust'] < 0 ? '-' : '+' }} {!! $m(abs($t['adjust'])) !!}</span></div>@endif
      <div class="row"><span>Delivery charges</span><span>{!! $m($t['delivery']) !!}</span></div>
      <div class="row net"><span>Net payable</span><span>{!! $m($t['net']) !!}</span></div>
      <div class="row note"><span>Paid by {{ $inv['payment']['method'] }}</span><span>{{ $inv['payment']['status'] }}</span></div>
    </div>
  </div>
</div>
<script>
(function () {
  var tok = document.querySelector('meta[name=csrf-token]').content;
  document.querySelectorAll('.item').forEach(function (it) {
    var stars = it.querySelectorAll('.stars button'); if (!stars.length) return;
    var val = +it.getAttribute('data-rating') || 0;
    function paint() { stars.forEach(function (b) { b.classList.toggle('on', +b.getAttribute('data-s') <= val); }); }
    paint();
    stars.forEach(function (b) { b.onclick = function () { val = +b.getAttribute('data-s'); paint(); }; });
    var btn = it.querySelector('.sub-btn'), msg = it.querySelector('.msg');
    btn.onclick = function () {
      if (!val) { msg.className = 'msg bad'; msg.textContent = ' Please choose a star rating.'; return; }
      btn.disabled = true;
      var body = new URLSearchParams(); body.set('product_id', it.getAttribute('data-pid')); body.set('rating', val); body.set('feedback', it.querySelector('.fb').value);
      fetch('{{ route('add-review') }}', { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': tok, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: body })
        .then(function (r) { return r.json(); })
        .then(function (j) { btn.disabled = false; msg.className = 'msg' + (j.result ? '' : ' bad'); msg.textContent = ' ' + (j.message || (j.result ? 'Thank you.' : 'Could not save.')); if (j.result) btn.textContent = 'Update rating'; })
        .catch(function () { btn.disabled = false; msg.className = 'msg bad'; msg.textContent = ' Network problem. Please try again.'; });
    };
  });
})();
</script>
</body>
</html>

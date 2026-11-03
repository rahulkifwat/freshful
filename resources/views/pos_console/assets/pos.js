/* Freshful POS console v3 - live board, new-order sound, weighing, hub transfer, rider cash, history (no libraries) */
(function () {
  'use strict';
  var C = window.POSC || {};
  var root = document.documentElement;
  var $ = function (id) { return document.getElementById(id); };
  var S = {
    orders: [], counts: {}, kpis: {}, riders: [], version: '', offset: 0, tab: 'board',
    known: null, fresh: {}, sound: true, audioOk: false, snoozeUntil: 0,
    fails: 0, openId: null, detail: null, busy: false, lastOk: 0,
    hist: { from: '', to: '', stage: '', rows: null, loading: false }
  };
  var COLS = [
    ['new', 'New', '--c-new', 'No new orders. New orders appear here with a sound.'],
    ['accepted', 'Accepted - weigh', '--c-acc', 'Accepted orders waiting to be weighed.'],
    ['ready', 'Ready to dispatch', '--c-pack', 'Weighed orders waiting for a rider.'],
    ['out', 'Out for delivery', '--c-out', 'Nothing out for delivery.'],
    ['unsettled', 'To settle', '--c-set', 'Delivered orders whose money is still with the rider.']
  ];
  var BASE_TITLE = document.title;

  function store(k, v) { try { localStorage.setItem(k, v); } catch (e) {} }
  function read(k) { try { return localStorage.getItem(k); } catch (e) { return null; } }
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function r2(n) { return Math.round(Number(n || 0) * 100) / 100; }
  function money(n) { n = r2(n); var neg = n < 0; n = Math.abs(n); return (neg ? '-' : '') + '₹' + n.toLocaleString('en-IN', { minimumFractionDigits: n % 1 ? 2 : 0, maximumFractionDigits: 2 }); }
  function signed(n) { n = r2(n); return (n > 0 ? '+' : '') + money(n); }
  function now() { return Math.floor(Date.now() / 1000) + S.offset; }
  function dur(sec) {
    sec = Math.abs(sec); var m = Math.floor(sec / 60), h = Math.floor(m / 60), d = Math.floor(h / 24);
    if (d >= 60) return Math.floor(d / 30) + ' months';
    if (d >= 3) return d + ' days';
    if (d >= 1) return d + 'd ' + (h % 24) + 'h';
    if (h >= 1) return h + 'h ' + (m % 60) + 'm';
    return Math.max(1, m) + 'm';
  }
  function ago(ts) { return ts ? dur(now() - ts) + ' ago' : ''; }
  function clock(ts) {
    if (!ts) return '';
    var d = new Date(ts * 1000);
    if (d.getFullYear() !== new Date().getFullYear()) return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
    return d.toLocaleString('en-IN', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
  }
  function sqlTs(s) { if (!s) return null; var t = new Date(String(s).replace(' ', 'T')).getTime(); return isNaN(t) ? null : Math.floor(t / 1000); }
  function today() { var d = new Date(); return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); }
  function toast(msg, kind) {
    var t = document.createElement('div');
    t.className = 'pc-toast' + (kind ? ' ' + kind : '');
    t.textContent = msg;
    $('pcToasts').appendChild(t);
    setTimeout(function () { t.remove(); }, kind === 'bad' ? 6500 : 3500);
  }
  function label(stage) { return (C.labels && C.labels[stage]) || stage; }

  /* ---------------- Server calls ---------------- */
  function api(method, url, body) {
    var opt = { method: method, headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' };
    if (body) {
      opt.headers['Content-Type'] = 'application/json';
      opt.headers['X-CSRF-TOKEN'] = C.csrf;
      opt.body = JSON.stringify(body);
    }
    return fetch(url, opt).then(function (r) {
      if (r.status === 401 || r.status === 419) { window.location.href = C.login; throw new Error('Session ended'); }
      return r.json().catch(function () { return { ok: false, message: 'Unexpected reply (' + r.status + ')' }; }).then(function (j) {
        if (!r.ok && j && j.errors) { j.message = Object.values(j.errors)[0][0]; }
        if (!r.ok) j.ok = false;
        return j;
      });
    });
  }
  function orderUrl(id, tail) { return C.order + '/' + encodeURIComponent(id) + (tail || ''); }

  /* ---------------- Sound (generated, no audio file needed) ---------------- */
  var actx = null;
  function audio() {
    if (!actx) { try { actx = new (window.AudioContext || window.webkitAudioContext)(); } catch (e) { actx = null; } }
    if (actx && actx.state === 'suspended') actx.resume();
    return actx;
  }
  function tone(freq, start, len, vol) {
    var a = audio(); if (!a) return;
    var o = a.createOscillator(), g = a.createGain();
    o.type = 'triangle'; o.frequency.value = freq;
    g.gain.setValueAtTime(0.0001, a.currentTime + start);
    g.gain.exponentialRampToValueAtTime(vol, a.currentTime + start + 0.02);
    g.gain.exponentialRampToValueAtTime(0.0001, a.currentTime + start + len);
    o.connect(g); g.connect(a.destination);
    o.start(a.currentTime + start); o.stop(a.currentTime + start + len + 0.05);
  }
  function chime(times) {
    if (!S.sound || !S.audioOk) return;
    for (var i = 0; i < (times || 1); i++) {
      var t = i * 0.9;
      tone(1046.5, t, 0.35, 0.5); tone(1318.5, t + 0.16, 0.45, 0.45); tone(1568, t + 0.32, 0.6, 0.4);
    }
  }
  function setSound(on) {
    S.sound = !!on;
    store('ff-pos-sound', on ? 'on' : 'off');
    $('pcSound').classList.toggle('is-on', S.sound);
    $('pcSound').title = S.sound ? 'New-order sound is ON (click to mute)' : 'New-order sound is OFF (click to turn on)';
  }

  /* ---------------- Alerts for new orders ---------------- */
  var titleTimer = null, flip = false;
  function isNewLive(o) { return o.stage === 'new' && !o.stale; }
  function countNew() { return S.orders.filter(isNewLive).length; }
  function alerts() {
    var newOrders = S.orders.filter(isNewLive);
    var n = newOrders.length;
    var bar = $('pcAlert');
    if (n > 0) {
      bar.hidden = false;
      $('pcAlertText').textContent = n === 1 ? 'New order ' + newOrders[0].order_id + ' waiting to be accepted (' + ago(newOrders[0].placed_at).replace(' ago', '') + ')'
        : n + ' new orders waiting to be accepted';
      if (!titleTimer) titleTimer = setInterval(function () { flip = !flip; document.title = flip ? '(' + countNew() + ') NEW ORDER · Freshful POS' : BASE_TITLE; }, 1000);
    } else {
      bar.hidden = true;
      if (titleTimer) { clearInterval(titleTimer); titleTimer = null; document.title = BASE_TITLE; }
    }
  }
  setInterval(function () {   // keep ringing while orders wait, every 12 seconds
    if (countNew() > 0 && Date.now() > S.snoozeUntil) chime(1);
  }, 12000);
  function notifyDesktop(o) {
    if (!('Notification' in window) || Notification.permission !== 'granted') return;
    try {
      var n = new Notification('New order ' + o.order_id, { body: o.customer + ' · ' + money(o.amount) + ' · ' + o.delivery_type, tag: 'ff-' + o.order_id, requireInteraction: true });
      n.onclick = function () { window.focus(); openOrder(o.order_id); n.close(); };
    } catch (e) {}
  }

  /* ---------------- Polling ---------------- */
  var pollTimer = null;
  function poll(force) {
    clearTimeout(pollTimer);
    return api('GET', C.feed).then(function (j) {
      if (!j || !j.ok) throw new Error(j && j.message || 'Feed failed');
      S.fails = 0; S.lastOk = Date.now();
      S.offset = j.server_time - Math.floor(Date.now() / 1000);
      $('pcOffline').hidden = true;
      $('pcLive').classList.remove('is-off');
      var ids = {};
      j.orders.forEach(function (o) { ids[o.order_id] = 1; });
      if (S.known === null) {
        S.known = ids;
      } else {
        var arrivals = j.orders.filter(function (o) { return isNewLive(o) && !S.known[o.order_id]; });
        arrivals.forEach(function (o) { S.fresh[o.order_id] = 1; notifyDesktop(o); toast('New order ' + o.order_id + ' · ' + money(o.amount)); });
        if (arrivals.length) chime(3);
        Object.keys(ids).forEach(function (k) { S.known[k] = 1; });
      }
      var changed = force || j.version !== S.version;
      S.orders = j.orders; S.counts = j.counts; S.kpis = j.kpis; S.riders = j.riders || []; S.version = j.version;
      if (changed && !(S.tab === 'history' && !force)) render(); else { renderKpis(); tick(); }
      alerts();
      if (S.openId && changed) refreshDrawer();
    }).catch(function () {
      S.fails++;
      if (S.fails >= 2) { $('pcOffline').hidden = false; $('pcLive').classList.add('is-off'); }
    }).then(function () {
      pollTimer = setTimeout(poll, document.hidden ? 15000 : 8000);
    });
  }
  setInterval(function () {
    var s = S.lastOk ? Math.round((Date.now() - S.lastOk) / 1000) : null;
    $('pcLiveText').textContent = s === null ? 'Connecting…' : (S.fails >= 2 ? 'Offline' : 'Live · ' + (s < 3 ? 'just now' : s + 's ago'));
    tick();
  }, 1000);

  /* ---------------- Rendering ---------------- */
  function kpi(label, value, sub, cls) {
    return '<div class="pc-kpi ' + (cls || '') + '"><div class="l">' + esc(label) + '</div><div class="v">' + value + '</div><div class="s">' + esc(sub || '') + '</div></div>';
  }
  function renderKpis() {
    var k = S.kpis || {}, c = S.counts || {}, st = k.settled_today || {};
    $('pcKpis').innerHTML =
      kpi('Orders today', esc(k.orders_today || 0), (k.cancelled_today || 0) + ' cancelled') +
      kpi('Sales today', esc(money(k.sales_today)), 'after weight changes, excl. cancelled') +
      kpi('Delivered today', esc(k.delivered_today || 0), k.avg_delivery_min != null ? 'avg ' + k.avg_delivery_min + ' min from order' : 'no deliveries yet') +
      kpi('Late now', esc(c.late || 0), 'past their delivery time', (c.late || 0) > 0 ? 'is-bad' : '') +
      kpi('With riders', esc(money(k.with_riders)), (k.with_riders_count || 0) + ' delivered, not settled', (k.with_riders_count || 0) > 0 ? 'is-warn' : '') +
      kpi('Received today', esc(money(st.received)), 'cash ' + money(st.cash) + ' · UPI ' + money(st.upi) + (st.card ? ' · card ' + money(st.card) : ''));
    var boardN = S.orders.filter(onBoard).length;
    $('tnBoard').textContent = boardN;
    $('tnBoard').classList.toggle('is-alert', countNew() > 0);
    $('tnRiders').textContent = S.riders.filter(function (r) { return r.id && r.state === 'available'; }).length;
    $('tnLater').textContent = c.scheduled_later || 0;
    $('tnDone').textContent = (c.delivered || 0) + (c.cancelled || 0);
    $('tnStale').textContent = c.stale || 0;
    var slaBad = slaOpen().filter(function (o) { return slaState(o) !== 'ok'; }).length;
    $('tnSla').textContent = slaBad; $('tnSla').classList.toggle('is-alert', slaOpen().some(function (o) { return slaState(o) === 'breach'; }));
    $('tabStale').hidden = !(c.stale > 0) && S.tab !== 'stale';
  }
  function onBoard(o) {
    if (o.stale) return false;
    if (o.stage === 'new' || o.stage === 'unsettled' || o.stage === 'out') return true;
    return o.stage === 'accepted' && !o.later;
  }

  function dueChip(o) {
    if (!o.due_at || ['new', 'accepted', 'out'].indexOf(o.stage) < 0) return '';
    var left = o.due_at - now();
    var cls = left < 0 ? 'bad' : (left < 1800 ? 'warn' : 'ok');
    return '<span class="pc-chip ' + cls + '" data-due="' + o.due_at + '">' + (left < 0 ? 'Late ' + dur(left) : 'Due in ' + dur(left)) + '</span>';
  }
  function payChip(o) {
    var t = (o.payment_type || 'Cash');
    if (o.stage === 'delivered') return '<span class="pc-chip ok">Settled</span>';
    return '<span class="pc-chip ' + (o.prepaid ? 'info' : 'warn') + '">' + esc(t === 'Cash' ? 'COD' : t) + '</span>';
  }
  function typeChip(o) {
    if ((o.delivery_type || '').toLowerCase() === 'scheduled') {
      var d = o.schedule_date ? new Date(o.schedule_date + 'T00:00:00') : null;
      var when = d && !isNaN(d) ? d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short' }) : 'date not set';
      return '<span class="pc-chip">Scheduled ' + esc(when + (o.slot ? ' · ' + o.slot : '')) + '</span>';
    }
    return '<span class="pc-chip brand">Express</span>';
  }
  function adjChip(o) {
    if (!o.weighed && !o.adjustment) return '';
    if (!o.adjustment) return '<span class="pc-chip">Weighed</span>';
    return '<span class="pc-chip ' + (o.adjustment > 0 ? 'warn' : 'ok') + '">' + (o.adjustment > 0 ? 'Collect +' : 'Return ') + esc(money(Math.abs(o.adjustment))) + '</span>';
  }
  var PRIMARY = {
    'new': ['accept', 'Accept order', 'pri'], accepted: ['weigh', 'Weigh items', 'pri'], ready: ['dispatch', 'Out for delivery', 'pri'],
    out: ['deliver', 'Mark delivered', 'ok'], unsettled: ['settle', 'Settle payment', 'ok']
  };
  function card(o, color) {
    var p = PRIMARY[o.board];
    var showAmt = o.stage === 'unsettled' ? o.expected : o.amount;
    var sub = o.stage === 'out' && o.since ? 'Out ' + dur(now() - o.since) + (o.rider ? ' with ' + o.rider : '')
      : (o.stage === 'unsettled' && o.since ? 'Delivered ' + dur(now() - o.since) + ' ago' : '');
    return '<article class="pc-card' + (S.fresh[o.order_id] ? ' is-fresh' : '') + '" style="--c:var(' + color + ')" data-open="' + esc(o.order_id) + '">' +
      '<div class="pc-card-t"><b>' + esc(o.order_id) + '</b>' + dueChip(o) + '<span class="ago" data-ago="' + (o.placed_at || '') + '">' + esc(ago(o.placed_at)) + '</span></div>' +
      '<div class="who">' + esc(o.customer) + '</div>' + (o.area ? '<div class="where">' + esc(o.area) + '</div>' : '') +
      '<div class="row">' + typeChip(o) + payChip(o) + adjChip(o) + (o.rider ? '<span class="pc-chip">' + esc(o.rider) + '</span>' : '') +
      '<span class="amt">' + esc(money(showAmt)) + '</span></div>' +
      '<div class="row" style="justify-content:space-between"><span class="sub2">' + o.items + ' item' + (o.items === 1 ? '' : 's') + (o.stage === 'unsettled' ? ' · to collect' : '') + '</span><span class="sub2">' + esc(sub) + '</span></div>' +
      (o.board === 'new' ? '<button type="button" class="pc-btn block pri" data-open="' + esc(o.order_id) + '">View order</button>'
        : (p ? '<button type="button" class="pc-btn block ' + p[2] + '" data-act="' + p[0] + '" data-id="' + esc(o.order_id) + '">' + p[1] + '</button>' : '')) +
      '</article>';
  }
  function renderBoard() {
    var st = (S.counts && S.counts.stale) || 0;
    var html = st ? '<div class="pc-banner is-warn" style="margin-bottom:14px">' + st + ' old open order(s) from earlier days are kept off the live board and do not ring.<span class="sp"></span><button type="button" data-tab="stale">Review</button></div>' : '';
    html += '<div class="pc-board">';
    COLS.forEach(function (c) {
      var list = S.orders.filter(function (o) { return o.board === c[0] && onBoard(o); })
        .sort(function (a, b) { return c[0] === 'unsettled' ? (a.since || 0) - (b.since || 0) : (a.due_at || 9e12) - (b.due_at || 9e12); });
      var total = c[0] === 'unsettled' ? list.reduce(function (s, o) { return s + o.expected; }, 0) : 0;
      html += '<section class="pc-col"><div class="pc-col-h" style="--c:var(' + c[2] + ')"><i></i>' + c[1] + (total ? ' <span class="sub2" style="font-weight:700">' + esc(money(total)) + '</span>' : '') + '<span class="n">' + list.length + '</span></div><div class="pc-col-b">' +
        (list.length ? list.map(function (o) { return card(o, c[2]); }).join('') : '<div class="pc-empty">' + c[3] + '</div>') +
        '</div></section>';
    });
    return html + '</div>';
  }
  function renderList(list, empty, opts) {
    opts = opts || {};
    if (!list.length) return '<div class="pc-list"><div class="pc-empty" style="padding:40px">' + esc(empty) + '</div></div>';
    return '<div class="pc-list"><table class="pc-table"><thead><tr><th>Order</th><th>Customer</th><th class="hide-sm">Placed</th><th>Status</th><th class="hide-sm">Payment</th><th class="r">Amount</th></tr></thead><tbody>' +
      list.map(function (o) {
        var lbl = label(o.board === 'ready' ? 'ready' : o.stage);
        var cls = o.stage === 'cancelled' ? 'bad' : (o.stage === 'delivered' ? 'ok' : (o.stage === 'unsettled' ? 'warn' : 'info'));
        return '<tr data-open="' + esc(o.order_id) + '"><td><b>' + esc(o.order_id) + '</b><div style="font-size:12px;color:var(--ink-3)">' + o.items + ' item(s) · ' + esc(o.delivery_type) + '</div></td>' +
          '<td>' + esc(o.customer) + '<div style="font-size:12px;color:var(--ink-3)">' + esc(o.phone) + '</div></td>' +
          '<td class="hide-sm">' + esc(clock(o.placed_at)) + '</td>' +
          '<td><span class="pc-chip ' + cls + '">' + esc(lbl) + '</span> ' + dueChip(o) + (o.cancel_reason ? '<div style="font-size:12px;color:var(--ink-3);margin-top:3px">' + esc(o.cancel_reason) + '</div>' : '') + '</td>' +
          '<td class="hide-sm">' + payChip(o) + ' ' + adjChip(o) + (o.rider ? ' <span class="pc-chip">' + esc(o.rider) + '</span>' : '') + '</td>' +
          '<td class="r"><b>' + esc(money(o.amount)) + '</b></td></tr>';
      }).join('') + '</tbody></table></div>';
  }
  /* SLA: every open order against its promised time (old POS "SLA Breach / On-Time", improved) */
  function slaState(o) { if (!o.due_at) return 'ok'; var left = o.due_at - now(); return left < 0 ? 'breach' : (left < 900 ? 'risk' : 'ok'); }
  function slaOpen() { return S.orders.filter(function (o) { return ['new', 'accepted', 'out'].indexOf(o.stage) >= 0 && !o.stale; }); }
  function renderSla() {
    var open = slaOpen().sort(function (a, b) { return (a.due_at || 9e12) - (b.due_at || 9e12); });
    var br = open.filter(function (o) { return slaState(o) === 'breach'; }), rk = open.filter(function (o) { return slaState(o) === 'risk'; });
    var t0 = new Date(); t0.setHours(0, 0, 0, 0); var start = Math.floor(t0.getTime() / 1000) + S.offset;
    var done = S.orders.filter(function (o) { return o.delivered_at && o.delivered_at >= start && o.due_at; });
    var onTime = done.filter(function (o) { return o.delivered_at <= o.due_at; }).length;
    var pct = done.length ? Math.round(onTime * 100 / done.length) : null;
    var stepLbl = { 'new': 'Waiting to accept', accepted: 'Accepted / packing', out: 'Out for delivery' };
    var head = '<section class="pc-kpis" style="margin-bottom:14px">' +
      kpi('On-time today', pct === null ? '–' : pct + '%', done.length ? onTime + ' of ' + done.length + ' delivered on time' : 'no deliveries yet', pct !== null && pct < 90 ? 'is-warn' : '') +
      kpi('SLA breached now', esc(br.length), 'open orders past their promised time', br.length ? 'is-bad' : '') +
      kpi('At risk', esc(rk.length), 'due within 15 minutes', rk.length ? 'is-warn' : '') +
      kpi('On track', esc(open.length - br.length - rk.length), 'open orders with time to spare') +
      kpi('Express promise', esc((C.expressMin || 30) + ' min'), 'from order to doorstep') +
      kpi('Delivered late today', esc(done.length - onTime), 'after the promised time', (done.length - onTime) ? 'is-bad' : '') + '</section>';
    if (!open.length) return head + '<div class="pc-list"><div class="pc-empty" style="padding:40px">No open orders right now.</div></div>';
    return head + '<div class="pc-list"><table class="pc-table"><thead><tr><th>Order</th><th>Customer</th><th>Step</th><th class="hide-sm">In this step</th><th>Promised by</th><th>SLA</th><th class="hide-sm">Rider</th></tr></thead><tbody>' +
      open.map(function (o) {
        var st = slaState(o), left = o.due_at ? o.due_at - now() : null;
        var chip = st === 'breach' ? '<span class="pc-chip bad">Breached ' + dur(left) + '</span>' : (st === 'risk' ? '<span class="pc-chip warn">At risk · ' + dur(left) + ' left</span>' : '<span class="pc-chip ok">On track · ' + (left === null ? '' : dur(left) + ' left') + '</span>');
        return '<tr data-open="' + esc(o.order_id) + '"><td><b>' + esc(o.order_id) + '</b><div style="font-size:12px;color:var(--ink-3)">' + esc(o.delivery_type) + (o.slot ? ' · ' + esc(o.slot) : '') + '</div></td>' +
          '<td>' + esc(o.customer) + '<div style="font-size:12px;color:var(--ink-3)">' + esc(o.area) + '</div></td>' +
          '<td>' + esc(stepLbl[o.stage] || o.status) + (o.board === 'ready' ? ' (weighed)' : '') + '</td>' +
          '<td class="hide-sm">' + esc(o.since ? dur(now() - o.since) : '–') + '</td>' +
          '<td>' + esc(o.due_at ? clock(o.due_at) : '–') + '</td><td>' + chip + '</td>' +
          '<td class="hide-sm">' + esc(o.rider || '–') + '</td></tr>';
      }).join('') + '</tbody></table></div>';
  }
  /* Riders: live state of every delivery boy, smart pick, duty, money */
  var RSTATE = { available: ['At hub · free', 'ok'], out: ['On delivery', 'info'], returning: ['Returning to hub', 'warn'], off: ['Off duty', ''], none: ['', ''] };
  function autoOn() { return read('ff-pos-auto') !== 'off'; }
  function riderLine(r) {
    if (r.state === 'available') return r.idle_since ? 'Waiting ' + dur(now() - r.idle_since) : 'Free';
    if (r.state === 'out') return r.out + ' order' + (r.out === 1 ? '' : 's') + (r.out_since ? ' · out ' + dur(now() - r.out_since) : '') + (r.areas && r.areas.length ? ' · ' + r.areas.slice(0, 2).join(', ') : '');
    if (r.state === 'returning') return r.eta ? (r.eta > now() ? 'Back in about ' + dur(r.eta - now()) : 'Due back now') : 'On the way back';
    if (r.state === 'off') return 'Not taking orders';
    return '';
  }
  function renderRiders() {
    var list = (S.riders || []).filter(function (r) { return r.id; });
    var none = (S.riders || []).filter(function (r) { return !r.id && r.unsettled; })[0];
    if (!list.length && !none) return '<div class="pc-list"><div class="pc-empty" style="padding:40px">No riders are set up for this hub yet.</div></div>';
    var cnt = function (st) { return list.filter(function (r) { return r.state === st; }).length; };
    var total = (S.riders || []).reduce(function (s2, r) { return s2 + (r.cash_due || 0); }, 0);
    var smart = list.filter(function (r) { return r.smart; })[0];
    var head = '<div class="pc-banner" style="margin-bottom:14px;background:var(--surface);border:1px solid var(--line);flex-wrap:wrap;gap:10px">' +
      '<span><b>' + cnt('available') + '</b> at hub · <b>' + cnt('out') + '</b> on delivery · <b>' + cnt('returning') + '</b> returning · <b>' + cnt('off') + '</b> off duty</span>' +
      '<span class="pc-hint">' + (smart ? 'Next order goes to <b>' + esc(smart.name) + '</b> (' + esc(smart.smart_reason) + ')' : 'No rider free right now') + '</span>' +
      '<span class="sp"></span><span>Money with riders: <b>' + esc(money(total)) + '</b></span>' +
      '<label style="display:flex;align-items:center;gap:8px;font-weight:700;cursor:pointer"><input type="checkbox" id="pcAutoToggle"' + (autoOn() ? ' checked' : '') + ' style="width:18px;height:18px;accent-color:var(--brand)"> Smart auto-assign</label></div>';
    var card = function (r) {
      var stt = RSTATE[r.state] || ['', ''];
      var btns = [];
      if (r.state === 'out' || r.state === 'returning') btns.push('<button type="button" class="pc-btn sm" data-rs="back" data-rid="' + r.id + '">Back at hub</button>');
      if (r.state === 'available') btns.push('<button type="button" class="pc-btn sm" data-rs="off" data-rid="' + r.id + '">Off duty</button>');
      if (r.state === 'off') btns.push('<button type="button" class="pc-btn sm ok" data-rs="on" data-rid="' + r.id + '">Start duty</button>');
      if (r.unsettled) btns.push('<button type="button" class="pc-btn sm ok" data-collect="' + r.id + '">Collect ' + esc(money(r.cash_due)) + '</button>');
      return '<div class="pc-rider' + (r.cash_due ? ' is-due' : '') + '" style="' + (r.smart ? 'border-color:var(--ok);' : '') + (r.state === 'off' ? 'opacity:.7' : '') + '">' +
        '<div class="h"><span class="pc-avatar" aria-hidden="true">' + esc((r.name || 'R').charAt(0).toUpperCase()) + '</span><div><b>' + esc(r.name) + '</b><small>' + esc([r.code, r.phone].filter(Boolean).join(' · ')) + '</small></div>' +
        (r.phone ? '<a class="pc-btn sm" style="margin-left:auto" href="tel:' + esc(r.phone) + '">Call</a>' : '') + '</div>' +
        '<div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">' + (stt[0] ? '<span class="pc-chip ' + stt[1] + '">' + stt[0] + '</span>' : '') + (r.smart ? '<span class="pc-chip ok">Next in line</span>' : '') + '<span class="pc-hint">' + esc(riderLine(r)) + '</span></div>' +
        '<div class="st"><div><small>Delivered today</small><b>' + (r.delivered_today || 0) + '</b></div><div><small>To settle</small><b>' + r.unsettled + '</b></div><div><small>Amount</small><b>' + esc(money(r.cash_due)) + '</b></div></div>' +
        (btns.length ? '<div style="display:flex;gap:8px;flex-wrap:wrap">' + btns.join('') + '</div>' : '') + riderOrders(r) + '</div>';
    };
    var html = head + '<div class="pc-riders">' + list.map(card).join('') + '</div>';
    if (none) html += '<h3 style="margin:18px 0 8px;font-size:14px">Delivered without a rider</h3><div class="pc-riders">' + card(Object.assign({}, none, { state: 'none' })) + '</div>';
    return html;
  }
  /* ---- OUTLET3 (30 Oct 2026): outlet counter, scan only. UPI QR, cash, card. Stock is shared with online orders. ---- */
  var QRG = (function () { var exports = {}, module = { exports: exports }; var qrcode=function(){var t=function(t,r){var e=t,n=g[r],o=null,i=0,a=null,u=[],f={},c=function(t,r){o=function(t){for(var r=new Array(t),e=0;e<t;e+=1){r[e]=new Array(t);for(var n=0;n<t;n+=1)r[e][n]=null}return r}(i=4*e+17),l(0,0),l(i-7,0),l(0,i-7),s(),h(),d(t,r),e>=7&&v(t),null==a&&(a=p(e,n,u)),w(a,r)},l=function(t,r){for(var e=-1;e<=7;e+=1)if(!(t+e<=-1||i<=t+e))for(var n=-1;n<=7;n+=1)r+n<=-1||i<=r+n||(o[t+e][r+n]=0<=e&&e<=6&&(0==n||6==n)||0<=n&&n<=6&&(0==e||6==e)||2<=e&&e<=4&&2<=n&&n<=4)},h=function(){for(var t=8;t<i-8;t+=1)null==o[t][6]&&(o[t][6]=t%2==0);for(var r=8;r<i-8;r+=1)null==o[6][r]&&(o[6][r]=r%2==0)},s=function(){for(var t=B.getPatternPosition(e),r=0;r<t.length;r+=1)for(var n=0;n<t.length;n+=1){var i=t[r],a=t[n];if(null==o[i][a])for(var u=-2;u<=2;u+=1)for(var f=-2;f<=2;f+=1)o[i+u][a+f]=-2==u||2==u||-2==f||2==f||0==u&&0==f}},v=function(t){for(var r=B.getBCHTypeNumber(e),n=0;n<18;n+=1){var a=!t&&1==(r>>n&1);o[Math.floor(n/3)][n%3+i-8-3]=a}for(n=0;n<18;n+=1){a=!t&&1==(r>>n&1);o[n%3+i-8-3][Math.floor(n/3)]=a}},d=function(t,r){for(var e=n<<3|r,a=B.getBCHTypeInfo(e),u=0;u<15;u+=1){var f=!t&&1==(a>>u&1);u<6?o[u][8]=f:u<8?o[u+1][8]=f:o[i-15+u][8]=f}for(u=0;u<15;u+=1){f=!t&&1==(a>>u&1);u<8?o[8][i-u-1]=f:u<9?o[8][15-u-1+1]=f:o[8][15-u-1]=f}o[i-8][8]=!t},w=function(t,r){for(var e=-1,n=i-1,a=7,u=0,f=B.getMaskFunction(r),c=i-1;c>0;c-=2)for(6==c&&(c-=1);;){for(var g=0;g<2;g+=1)if(null==o[n][c-g]){var l=!1;u<t.length&&(l=1==(t[u]>>>a&1)),f(n,c-g)&&(l=!l),o[n][c-g]=l,-1==(a-=1)&&(u+=1,a=7)}if((n+=e)<0||i<=n){n-=e,e=-e;break}}},p=function(t,r,e){for(var n=A.getRSBlocks(t,r),o=b(),i=0;i<e.length;i+=1){var a=e[i];o.put(a.getMode(),4),o.put(a.getLength(),B.getLengthInBits(a.getMode(),t)),a.write(o)}var u=0;for(i=0;i<n.length;i+=1)u+=n[i].dataCount;if(o.getLengthInBits()>8*u)throw"code length overflow. ("+o.getLengthInBits()+">"+8*u+")";for(o.getLengthInBits()+4<=8*u&&o.put(0,4);o.getLengthInBits()%8!=0;)o.putBit(!1);for(;!(o.getLengthInBits()>=8*u||(o.put(236,8),o.getLengthInBits()>=8*u));)o.put(17,8);return function(t,r){for(var e=0,n=0,o=0,i=new Array(r.length),a=new Array(r.length),u=0;u<r.length;u+=1){var f=r[u].dataCount,c=r[u].totalCount-f;n=Math.max(n,f),o=Math.max(o,c),i[u]=new Array(f);for(var g=0;g<i[u].length;g+=1)i[u][g]=255&t.getBuffer()[g+e];e+=f;var l=B.getErrorCorrectPolynomial(c),h=k(i[u],l.getLength()-1).mod(l);for(a[u]=new Array(l.getLength()-1),g=0;g<a[u].length;g+=1){var s=g+h.getLength()-a[u].length;a[u][g]=s>=0?h.getAt(s):0}}var v=0;for(g=0;g<r.length;g+=1)v+=r[g].totalCount;var d=new Array(v),w=0;for(g=0;g<n;g+=1)for(u=0;u<r.length;u+=1)g<i[u].length&&(d[w]=i[u][g],w+=1);for(g=0;g<o;g+=1)for(u=0;u<r.length;u+=1)g<a[u].length&&(d[w]=a[u][g],w+=1);return d}(o,n)};f.addData=function(t,r){var e=null;switch(r=r||"Byte"){case"Numeric":e=M(t);break;case"Alphanumeric":e=x(t);break;case"Byte":e=m(t);break;case"Kanji":e=L(t);break;default:throw"mode:"+r}u.push(e),a=null},f.isDark=function(t,r){if(t<0||i<=t||r<0||i<=r)throw t+","+r;return o[t][r]},f.getModuleCount=function(){return i},f.make=function(){if(e<1){for(var t=1;t<40;t++){for(var r=A.getRSBlocks(t,n),o=b(),i=0;i<u.length;i++){var a=u[i];o.put(a.getMode(),4),o.put(a.getLength(),B.getLengthInBits(a.getMode(),t)),a.write(o)}var g=0;for(i=0;i<r.length;i++)g+=r[i].dataCount;if(o.getLengthInBits()<=8*g)break}e=t}c(!1,function(){for(var t=0,r=0,e=0;e<8;e+=1){c(!0,e);var n=B.getLostPoint(f);(0==e||t>n)&&(t=n,r=e)}return r}())},f.createTableTag=function(t,r){t=t||2;var e="";e+='<table style="',e+=" border-width: 0px; border-style: none;",e+=" border-collapse: collapse;",e+=" padding: 0px; margin: "+(r=void 0===r?4*t:r)+"px;",e+='">',e+="<tbody>";for(var n=0;n<f.getModuleCount();n+=1){e+="<tr>";for(var o=0;o<f.getModuleCount();o+=1)e+='<td style="',e+=" border-width: 0px; border-style: none;",e+=" border-collapse: collapse;",e+=" padding: 0px; margin: 0px;",e+=" width: "+t+"px;",e+=" height: "+t+"px;",e+=" background-color: ",e+=f.isDark(n,o)?"#000000":"#ffffff",e+=";",e+='"/>';e+="</tr>"}return e+="</tbody>",e+="</table>"},f.createSvgTag=function(t,r,e,n){var o={};"object"==typeof arguments[0]&&(t=(o=arguments[0]).cellSize,r=o.margin,e=o.alt,n=o.title),t=t||2,r=void 0===r?4*t:r,(e="string"==typeof e?{text:e}:e||{}).text=e.text||null,e.id=e.text?e.id||"qrcode-description":null,(n="string"==typeof n?{text:n}:n||{}).text=n.text||null,n.id=n.text?n.id||"qrcode-title":null;var i,a,u,c,g=f.getModuleCount()*t+2*r,l="";for(c="l"+t+",0 0,"+t+" -"+t+",0 0,-"+t+"z ",l+='<svg version="1.1" xmlns="http://www.w3.org/2000/svg"',l+=o.scalable?"":' width="'+g+'px" height="'+g+'px"',l+=' viewBox="0 0 '+g+" "+g+'" ',l+=' preserveAspectRatio="xMinYMin meet"',l+=n.text||e.text?' role="img" aria-labelledby="'+y([n.id,e.id].join(" ").trim())+'"':"",l+=">",l+=n.text?'<title id="'+y(n.id)+'">'+y(n.text)+"</title>":"",l+=e.text?'<description id="'+y(e.id)+'">'+y(e.text)+"</description>":"",l+='<rect width="100%" height="100%" fill="white" cx="0" cy="0"/>',l+='<path d="',a=0;a<f.getModuleCount();a+=1)for(u=a*t+r,i=0;i<f.getModuleCount();i+=1)f.isDark(a,i)&&(l+="M"+(i*t+r)+","+u+c);return l+='" stroke="transparent" fill="black"/>',l+="</svg>"},f.createDataURL=function(t,r){t=t||2,r=void 0===r?4*t:r;var e=f.getModuleCount()*t+2*r,n=r,o=e-r;return I(e,e,function(r,e){if(n<=r&&r<o&&n<=e&&e<o){var i=Math.floor((r-n)/t),a=Math.floor((e-n)/t);return f.isDark(a,i)?0:1}return 1})},f.createImgTag=function(t,r,e){t=t||2,r=void 0===r?4*t:r;var n=f.getModuleCount()*t+2*r,o="";return o+="<img",o+=' src="',o+=f.createDataURL(t,r),o+='"',o+=' width="',o+=n,o+='"',o+=' height="',o+=n,o+='"',e&&(o+=' alt="',o+=y(e),o+='"'),o+="/>"};var y=function(t){for(var r="",e=0;e<t.length;e+=1){var n=t.charAt(e);switch(n){case"<":r+="&lt;";break;case">":r+="&gt;";break;case"&":r+="&amp;";break;case'"':r+="&quot;";break;default:r+=n}}return r};return f.createASCII=function(t,r){if((t=t||1)<2)return function(t){t=void 0===t?2:t;var r,e,n,o,i,a=1*f.getModuleCount()+2*t,u=t,c=a-t,g={"██":"█","█ ":"▀"," █":"▄","  ":" "},l={"██":"▀","█ ":"▀"," █":" ","  ":" "},h="";for(r=0;r<a;r+=2){for(n=Math.floor((r-u)/1),o=Math.floor((r+1-u)/1),e=0;e<a;e+=1)i="█",u<=e&&e<c&&u<=r&&r<c&&f.isDark(n,Math.floor((e-u)/1))&&(i=" "),u<=e&&e<c&&u<=r+1&&r+1<c&&f.isDark(o,Math.floor((e-u)/1))?i+=" ":i+="█",h+=t<1&&r+1>=c?l[i]:g[i];h+="\n"}return a%2&&t>0?h.substring(0,h.length-a-1)+Array(a+1).join("▀"):h.substring(0,h.length-1)}(r);t-=1,r=void 0===r?2*t:r;var e,n,o,i,a=f.getModuleCount()*t+2*r,u=r,c=a-r,g=Array(t+1).join("██"),l=Array(t+1).join("  "),h="",s="";for(e=0;e<a;e+=1){for(o=Math.floor((e-u)/t),s="",n=0;n<a;n+=1)i=1,u<=n&&n<c&&u<=e&&e<c&&f.isDark(o,Math.floor((n-u)/t))&&(i=0),s+=i?g:l;for(o=0;o<t;o+=1)h+=s+"\n"}return h.substring(0,h.length-1)},f.renderTo2dContext=function(t,r){r=r||2;for(var e=f.getModuleCount(),n=0;n<e;n++)for(var o=0;o<e;o++)t.fillStyle=f.isDark(n,o)?"black":"white",t.fillRect(o*r,n*r,r,r)},f};t.stringToBytes=(t.stringToBytesFuncs={default:function(t){for(var r=[],e=0;e<t.length;e+=1){var n=t.charCodeAt(e);r.push(255&n)}return r}}).default,t.createStringToBytes=function(t,r){var e=function(){for(var e=S(t),n=function(){var t=e.read();if(-1==t)throw"eof";return t},o=0,i={};;){var a=e.read();if(-1==a)break;var u=n(),f=n()<<8|n();i[String.fromCharCode(a<<8|u)]=f,o+=1}if(o!=r)throw o+" != "+r;return i}(),n="?".charCodeAt(0);return function(t){for(var r=[],o=0;o<t.length;o+=1){var i=t.charCodeAt(o);if(i<128)r.push(i);else{var a=e[t.charAt(o)];"number"==typeof a?(255&a)==a?r.push(a):(r.push(a>>>8),r.push(255&a)):r.push(n)}}return r}};var r,e,n,o,i,a=1,u=2,f=4,c=8,g={L:1,M:0,Q:3,H:2},l=0,h=1,s=2,v=3,d=4,w=5,p=6,y=7,B=(r=[[],[6,18],[6,22],[6,26],[6,30],[6,34],[6,22,38],[6,24,42],[6,26,46],[6,28,50],[6,30,54],[6,32,58],[6,34,62],[6,26,46,66],[6,26,48,70],[6,26,50,74],[6,30,54,78],[6,30,56,82],[6,30,58,86],[6,34,62,90],[6,28,50,72,94],[6,26,50,74,98],[6,30,54,78,102],[6,28,54,80,106],[6,32,58,84,110],[6,30,58,86,114],[6,34,62,90,118],[6,26,50,74,98,122],[6,30,54,78,102,126],[6,26,52,78,104,130],[6,30,56,82,108,134],[6,34,60,86,112,138],[6,30,58,86,114,142],[6,34,62,90,118,146],[6,30,54,78,102,126,150],[6,24,50,76,102,128,154],[6,28,54,80,106,132,158],[6,32,58,84,110,136,162],[6,26,54,82,110,138,166],[6,30,58,86,114,142,170]],e=1335,n=7973,i=function(t){for(var r=0;0!=t;)r+=1,t>>>=1;return r},(o={}).getBCHTypeInfo=function(t){for(var r=t<<10;i(r)-i(e)>=0;)r^=e<<i(r)-i(e);return 21522^(t<<10|r)},o.getBCHTypeNumber=function(t){for(var r=t<<12;i(r)-i(n)>=0;)r^=n<<i(r)-i(n);return t<<12|r},o.getPatternPosition=function(t){return r[t-1]},o.getMaskFunction=function(t){switch(t){case l:return function(t,r){return(t+r)%2==0};case h:return function(t,r){return t%2==0};case s:return function(t,r){return r%3==0};case v:return function(t,r){return(t+r)%3==0};case d:return function(t,r){return(Math.floor(t/2)+Math.floor(r/3))%2==0};case w:return function(t,r){return t*r%2+t*r%3==0};case p:return function(t,r){return(t*r%2+t*r%3)%2==0};case y:return function(t,r){return(t*r%3+(t+r)%2)%2==0};default:throw"bad maskPattern:"+t}},o.getErrorCorrectPolynomial=function(t){for(var r=k([1],0),e=0;e<t;e+=1)r=r.multiply(k([1,C.gexp(e)],0));return r},o.getLengthInBits=function(t,r){if(1<=r&&r<10)switch(t){case a:return 10;case u:return 9;case f:case c:return 8;default:throw"mode:"+t}else if(r<27)switch(t){case a:return 12;case u:return 11;case f:return 16;case c:return 10;default:throw"mode:"+t}else{if(!(r<41))throw"type:"+r;switch(t){case a:return 14;case u:return 13;case f:return 16;case c:return 12;default:throw"mode:"+t}}},o.getLostPoint=function(t){for(var r=t.getModuleCount(),e=0,n=0;n<r;n+=1)for(var o=0;o<r;o+=1){for(var i=0,a=t.isDark(n,o),u=-1;u<=1;u+=1)if(!(n+u<0||r<=n+u))for(var f=-1;f<=1;f+=1)o+f<0||r<=o+f||0==u&&0==f||a==t.isDark(n+u,o+f)&&(i+=1);i>5&&(e+=3+i-5)}for(n=0;n<r-1;n+=1)for(o=0;o<r-1;o+=1){var c=0;t.isDark(n,o)&&(c+=1),t.isDark(n+1,o)&&(c+=1),t.isDark(n,o+1)&&(c+=1),t.isDark(n+1,o+1)&&(c+=1),0!=c&&4!=c||(e+=3)}for(n=0;n<r;n+=1)for(o=0;o<r-6;o+=1)t.isDark(n,o)&&!t.isDark(n,o+1)&&t.isDark(n,o+2)&&t.isDark(n,o+3)&&t.isDark(n,o+4)&&!t.isDark(n,o+5)&&t.isDark(n,o+6)&&(e+=40);for(o=0;o<r;o+=1)for(n=0;n<r-6;n+=1)t.isDark(n,o)&&!t.isDark(n+1,o)&&t.isDark(n+2,o)&&t.isDark(n+3,o)&&t.isDark(n+4,o)&&!t.isDark(n+5,o)&&t.isDark(n+6,o)&&(e+=40);var g=0;for(o=0;o<r;o+=1)for(n=0;n<r;n+=1)t.isDark(n,o)&&(g+=1);return e+=Math.abs(100*g/r/r-50)/5*10},o),C=function(){for(var t=new Array(256),r=new Array(256),e=0;e<8;e+=1)t[e]=1<<e;for(e=8;e<256;e+=1)t[e]=t[e-4]^t[e-5]^t[e-6]^t[e-8];for(e=0;e<255;e+=1)r[t[e]]=e;var n={glog:function(t){if(t<1)throw"glog("+t+")";return r[t]},gexp:function(r){for(;r<0;)r+=255;for(;r>=256;)r-=255;return t[r]}};return n}();function k(t,r){if(void 0===t.length)throw t.length+"/"+r;var e=function(){for(var e=0;e<t.length&&0==t[e];)e+=1;for(var n=new Array(t.length-e+r),o=0;o<t.length-e;o+=1)n[o]=t[o+e];return n}(),n={getAt:function(t){return e[t]},getLength:function(){return e.length},multiply:function(t){for(var r=new Array(n.getLength()+t.getLength()-1),e=0;e<n.getLength();e+=1)for(var o=0;o<t.getLength();o+=1)r[e+o]^=C.gexp(C.glog(n.getAt(e))+C.glog(t.getAt(o)));return k(r,0)},mod:function(t){if(n.getLength()-t.getLength()<0)return n;for(var r=C.glog(n.getAt(0))-C.glog(t.getAt(0)),e=new Array(n.getLength()),o=0;o<n.getLength();o+=1)e[o]=n.getAt(o);for(o=0;o<t.getLength();o+=1)e[o]^=C.gexp(C.glog(t.getAt(o))+r);return k(e,0).mod(t)}};return n}var A=function(){var t=[[1,26,19],[1,26,16],[1,26,13],[1,26,9],[1,44,34],[1,44,28],[1,44,22],[1,44,16],[1,70,55],[1,70,44],[2,35,17],[2,35,13],[1,100,80],[2,50,32],[2,50,24],[4,25,9],[1,134,108],[2,67,43],[2,33,15,2,34,16],[2,33,11,2,34,12],[2,86,68],[4,43,27],[4,43,19],[4,43,15],[2,98,78],[4,49,31],[2,32,14,4,33,15],[4,39,13,1,40,14],[2,121,97],[2,60,38,2,61,39],[4,40,18,2,41,19],[4,40,14,2,41,15],[2,146,116],[3,58,36,2,59,37],[4,36,16,4,37,17],[4,36,12,4,37,13],[2,86,68,2,87,69],[4,69,43,1,70,44],[6,43,19,2,44,20],[6,43,15,2,44,16],[4,101,81],[1,80,50,4,81,51],[4,50,22,4,51,23],[3,36,12,8,37,13],[2,116,92,2,117,93],[6,58,36,2,59,37],[4,46,20,6,47,21],[7,42,14,4,43,15],[4,133,107],[8,59,37,1,60,38],[8,44,20,4,45,21],[12,33,11,4,34,12],[3,145,115,1,146,116],[4,64,40,5,65,41],[11,36,16,5,37,17],[11,36,12,5,37,13],[5,109,87,1,110,88],[5,65,41,5,66,42],[5,54,24,7,55,25],[11,36,12,7,37,13],[5,122,98,1,123,99],[7,73,45,3,74,46],[15,43,19,2,44,20],[3,45,15,13,46,16],[1,135,107,5,136,108],[10,74,46,1,75,47],[1,50,22,15,51,23],[2,42,14,17,43,15],[5,150,120,1,151,121],[9,69,43,4,70,44],[17,50,22,1,51,23],[2,42,14,19,43,15],[3,141,113,4,142,114],[3,70,44,11,71,45],[17,47,21,4,48,22],[9,39,13,16,40,14],[3,135,107,5,136,108],[3,67,41,13,68,42],[15,54,24,5,55,25],[15,43,15,10,44,16],[4,144,116,4,145,117],[17,68,42],[17,50,22,6,51,23],[19,46,16,6,47,17],[2,139,111,7,140,112],[17,74,46],[7,54,24,16,55,25],[34,37,13],[4,151,121,5,152,122],[4,75,47,14,76,48],[11,54,24,14,55,25],[16,45,15,14,46,16],[6,147,117,4,148,118],[6,73,45,14,74,46],[11,54,24,16,55,25],[30,46,16,2,47,17],[8,132,106,4,133,107],[8,75,47,13,76,48],[7,54,24,22,55,25],[22,45,15,13,46,16],[10,142,114,2,143,115],[19,74,46,4,75,47],[28,50,22,6,51,23],[33,46,16,4,47,17],[8,152,122,4,153,123],[22,73,45,3,74,46],[8,53,23,26,54,24],[12,45,15,28,46,16],[3,147,117,10,148,118],[3,73,45,23,74,46],[4,54,24,31,55,25],[11,45,15,31,46,16],[7,146,116,7,147,117],[21,73,45,7,74,46],[1,53,23,37,54,24],[19,45,15,26,46,16],[5,145,115,10,146,116],[19,75,47,10,76,48],[15,54,24,25,55,25],[23,45,15,25,46,16],[13,145,115,3,146,116],[2,74,46,29,75,47],[42,54,24,1,55,25],[23,45,15,28,46,16],[17,145,115],[10,74,46,23,75,47],[10,54,24,35,55,25],[19,45,15,35,46,16],[17,145,115,1,146,116],[14,74,46,21,75,47],[29,54,24,19,55,25],[11,45,15,46,46,16],[13,145,115,6,146,116],[14,74,46,23,75,47],[44,54,24,7,55,25],[59,46,16,1,47,17],[12,151,121,7,152,122],[12,75,47,26,76,48],[39,54,24,14,55,25],[22,45,15,41,46,16],[6,151,121,14,152,122],[6,75,47,34,76,48],[46,54,24,10,55,25],[2,45,15,64,46,16],[17,152,122,4,153,123],[29,74,46,14,75,47],[49,54,24,10,55,25],[24,45,15,46,46,16],[4,152,122,18,153,123],[13,74,46,32,75,47],[48,54,24,14,55,25],[42,45,15,32,46,16],[20,147,117,4,148,118],[40,75,47,7,76,48],[43,54,24,22,55,25],[10,45,15,67,46,16],[19,148,118,6,149,119],[18,75,47,31,76,48],[34,54,24,34,55,25],[20,45,15,61,46,16]],r=function(t,r){var e={};return e.totalCount=t,e.dataCount=r,e},e={};return e.getRSBlocks=function(e,n){var o=function(r,e){switch(e){case g.L:return t[4*(r-1)+0];case g.M:return t[4*(r-1)+1];case g.Q:return t[4*(r-1)+2];case g.H:return t[4*(r-1)+3];default:return}}(e,n);if(void 0===o)throw"bad rs block @ typeNumber:"+e+"/errorCorrectionLevel:"+n;for(var i=o.length/3,a=[],u=0;u<i;u+=1)for(var f=o[3*u+0],c=o[3*u+1],l=o[3*u+2],h=0;h<f;h+=1)a.push(r(c,l));return a},e}(),b=function(){var t=[],r=0,e={getBuffer:function(){return t},getAt:function(r){var e=Math.floor(r/8);return 1==(t[e]>>>7-r%8&1)},put:function(t,r){for(var n=0;n<r;n+=1)e.putBit(1==(t>>>r-n-1&1))},getLengthInBits:function(){return r},putBit:function(e){var n=Math.floor(r/8);t.length<=n&&t.push(0),e&&(t[n]|=128>>>r%8),r+=1}};return e},M=function(t){var r=a,e=t,n={getMode:function(){return r},getLength:function(t){return e.length},write:function(t){for(var r=e,n=0;n+2<r.length;)t.put(o(r.substring(n,n+3)),10),n+=3;n<r.length&&(r.length-n==1?t.put(o(r.substring(n,n+1)),4):r.length-n==2&&t.put(o(r.substring(n,n+2)),7))}},o=function(t){for(var r=0,e=0;e<t.length;e+=1)r=10*r+i(t.charAt(e));return r},i=function(t){if("0"<=t&&t<="9")return t.charCodeAt(0)-"0".charCodeAt(0);throw"illegal char :"+t};return n},x=function(t){var r=u,e=t,n={getMode:function(){return r},getLength:function(t){return e.length},write:function(t){for(var r=e,n=0;n+1<r.length;)t.put(45*o(r.charAt(n))+o(r.charAt(n+1)),11),n+=2;n<r.length&&t.put(o(r.charAt(n)),6)}},o=function(t){if("0"<=t&&t<="9")return t.charCodeAt(0)-"0".charCodeAt(0);if("A"<=t&&t<="Z")return t.charCodeAt(0)-"A".charCodeAt(0)+10;switch(t){case" ":return 36;case"$":return 37;case"%":return 38;case"*":return 39;case"+":return 40;case"-":return 41;case".":return 42;case"/":return 43;case":":return 44;default:throw"illegal char :"+t}};return n},m=function(r){var e=f,n=t.stringToBytes(r),o={getMode:function(){return e},getLength:function(t){return n.length},write:function(t){for(var r=0;r<n.length;r+=1)t.put(n[r],8)}};return o},L=function(r){var e=c,n=t.stringToBytesFuncs.SJIS;if(!n)throw"sjis not supported.";!function(){var t=n("友");if(2!=t.length||38726!=(t[0]<<8|t[1]))throw"sjis not supported."}();var o=n(r),i={getMode:function(){return e},getLength:function(t){return~~(o.length/2)},write:function(t){for(var r=o,e=0;e+1<r.length;){var n=(255&r[e])<<8|255&r[e+1];if(33088<=n&&n<=40956)n-=33088;else{if(!(57408<=n&&n<=60351))throw"illegal char at "+(e+1)+"/"+n;n-=49472}n=192*(n>>>8&255)+(255&n),t.put(n,13),e+=2}if(e<r.length)throw"illegal char at "+(e+1)}};return i},D=function(){var t=[],r={writeByte:function(r){t.push(255&r)},writeShort:function(t){r.writeByte(t),r.writeByte(t>>>8)},writeBytes:function(t,e,n){e=e||0,n=n||t.length;for(var o=0;o<n;o+=1)r.writeByte(t[o+e])},writeString:function(t){for(var e=0;e<t.length;e+=1)r.writeByte(t.charCodeAt(e))},toByteArray:function(){return t},toString:function(){var r="";r+="[";for(var e=0;e<t.length;e+=1)e>0&&(r+=","),r+=t[e];return r+="]"}};return r},S=function(t){var r=t,e=0,n=0,o=0,i={read:function(){for(;o<8;){if(e>=r.length){if(0==o)return-1;throw"unexpected end of file./"+o}var t=r.charAt(e);if(e+=1,"="==t)return o=0,-1;t.match(/^\s$/)||(n=n<<6|a(t.charCodeAt(0)),o+=6)}var i=n>>>o-8&255;return o-=8,i}},a=function(t){if(65<=t&&t<=90)return t-65;if(97<=t&&t<=122)return t-97+26;if(48<=t&&t<=57)return t-48+52;if(43==t)return 62;if(47==t)return 63;throw"c:"+t};return i},I=function(t,r,e){for(var n=function(t,r){var e=t,n=r,o=new Array(t*r),i={setPixel:function(t,r,n){o[r*e+t]=n},write:function(t){t.writeString("GIF87a"),t.writeShort(e),t.writeShort(n),t.writeByte(128),t.writeByte(0),t.writeByte(0),t.writeByte(0),t.writeByte(0),t.writeByte(0),t.writeByte(255),t.writeByte(255),t.writeByte(255),t.writeString(","),t.writeShort(0),t.writeShort(0),t.writeShort(e),t.writeShort(n),t.writeByte(0);var r=a(2);t.writeByte(2);for(var o=0;r.length-o>255;)t.writeByte(255),t.writeBytes(r,o,255),o+=255;t.writeByte(r.length-o),t.writeBytes(r,o,r.length-o),t.writeByte(0),t.writeString(";")}},a=function(t){for(var r=1<<t,e=1+(1<<t),n=t+1,i=u(),a=0;a<r;a+=1)i.add(String.fromCharCode(a));i.add(String.fromCharCode(r)),i.add(String.fromCharCode(e));var f,c,g,l=D(),h=(f=l,c=0,g=0,{write:function(t,r){if(t>>>r!=0)throw"length over";for(;c+r>=8;)f.writeByte(255&(t<<c|g)),r-=8-c,t>>>=8-c,g=0,c=0;g|=t<<c,c+=r},flush:function(){c>0&&f.writeByte(g)}});h.write(r,n);var s=0,v=String.fromCharCode(o[s]);for(s+=1;s<o.length;){var d=String.fromCharCode(o[s]);s+=1,i.contains(v+d)?v+=d:(h.write(i.indexOf(v),n),i.size()<4095&&(i.size()==1<<n&&(n+=1),i.add(v+d)),v=d)}return h.write(i.indexOf(v),n),h.write(e,n),h.flush(),l.toByteArray()},u=function(){var t={},r=0,e={add:function(n){if(e.contains(n))throw"dup key:"+n;t[n]=r,r+=1},size:function(){return r},indexOf:function(r){return t[r]},contains:function(r){return void 0!==t[r]}};return e};return i}(t,r),o=0;o<r;o+=1)for(var i=0;i<t;i+=1)n.setPixel(i,o,e(i,o));var a=D();n.write(a);for(var u=function(){var t=0,r=0,e=0,n="",o={},i=function(t){n+=String.fromCharCode(a(63&t))},a=function(t){if(t<0);else{if(t<26)return 65+t;if(t<52)return t-26+97;if(t<62)return t-52+48;if(62==t)return 43;if(63==t)return 47}throw"n:"+t};return o.writeByte=function(n){for(t=t<<8|255&n,r+=8,e+=1;r>=6;)i(t>>>r-6),r-=6},o.flush=function(){if(r>0&&(i(t<<6-r),t=0,r=0),e%3!=0)for(var o=3-e%3,a=0;a<o;a+=1)n+="="},o.toString=function(){return n},o}(),f=a.toByteArray(),c=0;c<f.length;c+=1)u.writeByte(f[c]);return u.flush(),"data:image/gif;base64,"+u};return t}();qrcode.stringToBytesFuncs["UTF-8"]=function(t){return function(t){for(var r=[],e=0;e<t.length;e++){var n=t.charCodeAt(e);n<128?r.push(n):n<2048?r.push(192|n>>6,128|63&n):n<55296||n>=57344?r.push(224|n>>12,128|n>>6&63,128|63&n):(e++,n=65536+((1023&n)<<10|1023&t.charCodeAt(e)),r.push(240|n>>18,128|n>>12&63,128|n>>6&63,128|63&n))}return r}(t)},function(t){"function"==typeof define&&define.amd?define([],t):"object"==typeof exports&&(module.exports=t())}(function(){return qrcode}); ; return module.exports; })();
  var OUTL = { cat: { tags: [], tag: '', q: '', items: [], busy: false }, sale: null, upi: { vpa: '', name: 'Freshful' }, mode: 'upi', msg: '', tone: '', busy: false, done: null };
  function oUrl(p) { return posBase() + '/outlet' + p; }
  function oCss() {
    if (document.getElementById('poCss')) return;
    var s = document.createElement('style'); s.id = 'poCss';
    s.textContent = '#pcOutlet{position:fixed;inset:0;z-index:900;background:#F1F4F9;color:#0F172A;display:flex;flex-direction:column;font-family:inherit}' +
      '#pcOutlet *{box-sizing:border-box}.po-top{display:flex;align-items:center;gap:12px;padding:12px 20px;background:#fff;border-bottom:1px solid #E2E8F0}.po-top b{font-size:17px}' +
      '.po-main{flex:1;min-height:0;display:flex;gap:16px;padding:16px}.po-left{flex:1 1 0;min-width:0;display:flex;flex-direction:column;gap:12px}.po-right{width:380px;flex:none;display:flex;flex-direction:column;gap:12px;overflow:auto}' +
      '.po-scan{background:#fff;border:2px dashed #FF5500;border-radius:16px;padding:16px 18px;display:flex;gap:14px;align-items:center}.po-scan input{flex:1;font-size:20px;padding:12px 14px;border:1px solid #CBD5E1;border-radius:12px;min-width:0}' +
      '.po-msg{border-radius:12px;padding:10px 14px;font-size:14px;font-weight:600}.po-msg.ok{background:#E0E7FF;color:#3730A3}.po-msg.bad{background:#FEE2E2;color:#991B1B}' +
      '.po-tbl{flex:1;min-height:0;background:#fff;border:1px solid #E2E8F0;border-radius:16px;overflow:auto}.po-row{display:flex;gap:10px;align-items:center;padding:12px 16px;border-bottom:1px solid #EEF2F7;font-size:14px}' +
      '.po-row.h{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#64748B;position:sticky;top:0;background:#fff}.po-card{background:#fff;border:1px solid #E2E8F0;border-radius:16px;padding:16px}' +
      '.po-btn{min-height:46px;padding:0 18px;border-radius:12px;border:1px solid #CBD5E1;background:#fff;font-size:14px;font-weight:700;cursor:pointer;color:#334155;font-family:inherit}.po-btn.pri{border:0;color:#fff;background:linear-gradient(135deg,#F70202,#FF5500)}' +
      '.po-btn.dark{border:0;background:#0F172A;color:#fff}.po-btn[disabled]{opacity:.5;cursor:default}.po-btn.sm{min-height:34px;padding:0 12px;font-size:13px}.po-pm{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}' +
      '.po-pm .po-btn.on{border:2px solid #F70202;color:#B91C1C;background:#FEF2F2}.po-in{width:100%;padding:12px;border:1px solid #CBD5E1;border-radius:12px;font-size:16px;font-family:inherit}.po-hint{font-size:12px;color:#64748B}' +
      '.po-dlg{position:absolute;inset:0;background:rgba(15,23,42,.55);display:flex;align-items:center;justify-content:center;z-index:5}.po-dlg>div{background:#fff;border-radius:16px;padding:20px;width:min(520px,94vw);max-height:90vh;overflow:auto}' +
      'body>.po-dlg{position:fixed;z-index:950}.po-split{flex:1;min-height:0;display:flex;gap:12px}.po-cat{flex:2.2 1 0;min-width:0;display:flex;flex-direction:column;gap:8px;background:#fff;border:1px solid #E2E8F0;border-radius:16px;padding:12px}.po-bill{flex:1 1 0;min-width:330px;display:flex;flex-direction:column;gap:8px}' +
      '.po-chips{display:flex;gap:6px;overflow-x:auto;padding-bottom:2px}.po-chip{flex:none;border:1px solid #CBD5E1;background:#fff;border-radius:99px;padding:7px 14px;font-size:13px;font-weight:700;cursor:pointer;color:#334155;font-family:inherit}.po-chip.on{border-color:#F70202;background:#FEF2F2;color:#B91C1C}' +
      '.po-grid{flex:1;min-height:0;overflow:auto;display:grid;grid-template-columns:repeat(auto-fill,minmax(168px,1fr));gap:10px;align-content:start}' +
      '.po-tile{border:1px solid #E2E8F0;border-radius:14px;background:#fff;padding:8px;text-align:left;cursor:pointer;font-family:inherit;display:flex;flex-direction:column;gap:4px;color:#0F172A}.po-tile:hover{border-color:#FF5500}.po-tile[disabled]{opacity:.45;cursor:not-allowed}' +
      '.po-tile img{width:100%;aspect-ratio:1/1;object-fit:cover;border-radius:10px;background:#F1F5F9}.po-tile b{font-size:13px;line-height:1.25;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:32px}.po-tile span{font-size:12px;color:#64748B}.po-tile em{font-style:normal;font-weight:800;font-size:14px}' +
      '.po-payb{display:flex;gap:14px;align-items:center;justify-content:space-between;padding:14px 16px}.po-dlg .po-card{margin:0}@media(max-width:1100px){.po-split{flex-direction:column;overflow:auto}}@media(max-width:900px){.po-main{flex-direction:column;overflow:auto}.po-right{width:auto}}';
    document.head.appendChild(s);
  }
  function oBeep(ok) { try { var a = new (window.AudioContext || window.webkitAudioContext)(), o = a.createOscillator(); o.frequency.value = ok ? 880 : 220; o.connect(a.destination); o.start(); setTimeout(function () { o.stop(); a.close(); }, ok ? 80 : 260); } catch (e) {} }
  function oFlash(m, tone) { OUTL.msg = m; OUTL.tone = tone || ''; var e = document.getElementById('poMsg'); if (e) { e.className = 'po-msg ' + OUTL.tone; e.textContent = m; e.style.display = m ? '' : 'none'; } var e2 = document.getElementById('poPayMsg'); if (e2) { e2.className = 'po-msg ' + OUTL.tone; e2.textContent = m; e2.style.display = m ? '' : 'none'; } }
  function oFocus() { var i = document.getElementById('poScan'); if (i && !document.querySelector('.po-dlg')) i.focus(); }
  function oTotal() { return OUTL.sale ? +OUTL.sale.total || 0 : 0; }
  function oQr(text) { try { var q = QRG(0, 'M'); q.addData(text); q.make(); return q.createSvgTag({ cellSize: 5, margin: 2, scalable: true }); } catch (e) { return ''; } }
  function oPay() {
    var t = oTotal(), s = OUTL.sale;
    if (OUTL.done) return '<div class="po-card" style="text-align:center"><div style="font-size:15px;font-weight:700;color:#475569">Paid by ' + esc(String(OUTL.done.method).toUpperCase()) + '</div><div style="font-size:34px;font-weight:800;margin:6px 0">' + esc(money(OUTL.done.total)) + '</div>' +
      (OUTL.done.change ? '<div style="font-size:16px;font-weight:700;margin-bottom:8px">Give back ' + esc(money(OUTL.done.change)) + '</div>' : '') + '<div class="po-hint" style="margin-bottom:12px">Bill ' + esc(OUTL.done.sale_no) + '</div>' +
      '<div style="display:grid;gap:8px"><button type="button" class="po-btn" id="poPrint">Print receipt (till)</button><button type="button" class="po-btn" id="poPrintA4">Print A4 invoice</button><button type="button" class="po-btn pri" id="poNew">New bill</button></div></div>';
    var h = '<div class="po-card"><div style="display:flex;justify-content:space-between;align-items:baseline"><span style="font-weight:700">To pay</span><span style="font-size:32px;font-weight:800">' + esc(money(t)) + '</span></div>' +
      '<div class="po-hint">' + (s ? s.packs : 0) + ' pack(s)</div></div><div class="po-card"><div class="po-pm">' +
      ['upi', 'cash', 'card'].map(function (m) { return '<button type="button" class="po-btn' + (OUTL.mode === m ? ' on' : '') + '" data-mode="' + m + '">' + (m === 'upi' ? 'UPI QR' : m === 'cash' ? 'Cash' : 'Card') + '</button>'; }).join('') + '</div><div style="margin-top:12px">';
    if (OUTL.mode === 'upi') {
      var link = (OUTL.upi.vpa && s) ? 'upi://pay?pa=' + encodeURIComponent(OUTL.upi.vpa) + '&pn=' + encodeURIComponent(OUTL.upi.name) + '&am=' + t.toFixed(2) + '&cu=INR&tn=' + encodeURIComponent(s.sale_no) + '&tr=' + encodeURIComponent(s.sale_no) : '';
      h += link ? '<div style="max-width:230px;margin:0 auto;background:#fff">' + oQr(link) + '</div><div class="po-hint" style="text-align:center;margin:6px 0">Customer scans with any UPI app. Check the amount and the bank message, then confirm.</div>'
                : '<div class="po-msg bad">The shop UPI id is not set (OUTLET_UPI_VPA). Ask the customer to pay to the shop QR, then confirm.</div>';
      h += '<input class="po-in" id="poRef" placeholder="UPI reference, last digits (optional)" maxlength="40" autocomplete="off">';
    } else if (OUTL.mode === 'cash') {
      h += '<input class="po-in" id="poCash" type="number" inputmode="decimal" min="0" step="0.01" placeholder="Cash given" autocomplete="off"><div style="display:flex;gap:6px;flex-wrap:wrap;margin:8px 0">' +
        [t, Math.ceil(t / 100) * 100, Math.ceil(t / 500) * 500, Math.ceil(t / 2000) * 2000].filter(function (v, i, a) { return v > 0 && a.indexOf(v) === i; }).map(function (v) { return '<button type="button" class="po-btn sm" data-cash="' + v + '">' + esc(money(v)) + '</button>'; }).join('') +
        '</div><div id="poChg" style="font-weight:700"></div>';
    } else {
      h += '<input class="po-in" id="poRef" placeholder="Card approval code, last 4 digits (optional)" maxlength="40" autocomplete="off">';
    }
    return h + '</div><button type="button" class="po-btn pri" id="poConfirm" style="width:100%;margin-top:12px"' + (!s || !s.items.length || OUTL.busy ? ' disabled' : '') + '>' + (OUTL.mode === 'upi' ? 'Payment received' : 'Confirm payment') + '</button></div>';
  }
  function oRender() {
    var o = document.getElementById('pcOutlet'); if (!o) return;
    var s = OUTL.sale, items = s ? s.items : [];
    if (!OUTL.done && (!s || !items.length)) OUTL.payOpen = false;
    o.innerHTML = '<div class="po-top"><b>Outlet counter</b><span class="pc-hint">' + (s ? 'Bill ' + esc(s.sale_no) : 'No bill yet') + '</span><span style="margin-left:auto;display:flex;gap:8px"><a class="po-btn sm" href="/" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;text-decoration:none">Open website</a><button type="button" class="po-btn sm" id="poKio">Kiosk orders' + (OUTL.kn ? ' (' + OUTL.kn + ')' : '') + '</button><button type="button" class="po-btn sm" id="poInv">Invoices</button><button type="button" class="po-btn sm" id="poToday">Today</button><button type="button" class="po-btn sm" id="poClose">Close</button></span></div>' +
      '<div class="po-main"><div class="po-left"><div class="po-scan"><span style="font-weight:800;font-size:15px">Scan product</span><input id="poScan" autocomplete="off" inputmode="none" placeholder="Point the scanner at the label"></div>' +
      '<div id="poMsg" class="po-msg ' + OUTL.tone + '" style="' + (OUTL.msg ? '' : 'display:none') + '">' + esc(OUTL.msg) + '</div><div class="po-split">' + oCatalogHtml() + '<div class="po-bill"><div class="po-tbl"><div class="po-row h"><span style="flex:3">Product</span><span style="flex:1.4">Packs</span><span style="flex:1">Rate</span><span style="flex:1;text-align:right">Amount</span></div>' +
      (items.length ? items.map(function (i) {
        return '<div class="po-row"><span style="flex:3;min-width:0"><b>' + esc(i.name) + '</b><br><span class="po-hint">' + esc(i.pack || '') + (i.weight ? ' · label ' + esc(String(i.weight)) + ' g' : '') + '</span></span>' +
          '<span style="flex:1.9;white-space:nowrap">' + (i.weight ? '1' : '<button type="button" class="po-btn sm" data-q="' + i.id + '" data-v="' + (i.qty - 1) + '">-</button> <b>' + i.qty + '</b> <button type="button" class="po-btn sm" data-q="' + i.id + '" data-v="' + (i.qty + 1) + '">+</button>') + '</span>' +
          '<span style="flex:1">' + esc(money(i.rate)) + '</span><span style="flex:1;text-align:right"><b>' + esc(money(i.amount)) + '</b> <button type="button" class="po-btn sm" data-q="' + i.id + '" data-v="0" aria-label="Remove">x</button></span></div>';
      }).join('') : '<div style="padding:50px 16px;text-align:center;color:#64748B">Scan the first product. No typing needed.</div>') +
      '</div><div class="po-card po-payb"><div><div class="po-hint" style="font-weight:700;letter-spacing:.4px">TO PAY</div><div style="font-size:30px;font-weight:800;line-height:1.1">' + esc(money(oTotal())) + '</div><div class="po-hint">' + (s ? s.packs : 0) + ' pack(s)</div></div>' +
      '<div style="display:grid;gap:8px;flex:1;max-width:250px"><button type="button" class="po-btn pri" id="poPayBtn" style="min-height:56px;font-size:17px"' + (items.length ? '' : ' disabled') + '>Take payment</button>' + (items.length ? '<button type="button" class="po-btn sm" id="poVoid">Cancel this bill</button>' : '') + '</div></div>' +
      '<div class="po-hint">Scanned or tapped packs are held for this bill, so online customers cannot take them. An unpaid bill releases after 15 minutes without a scan.</div></div></div></div></div>' +
      ((OUTL.payOpen || OUTL.done) ? '<div class="po-dlg" id="poPayDlg"><div style="width:min(460px,94vw);display:grid;gap:12px;padding:16px;background:#F1F4F9"><div id="poPayMsg" class="po-msg ' + OUTL.tone + '" style="' + (OUTL.msg ? '' : 'display:none') + '">' + esc(OUTL.msg) + '</div>' + oPay() + (OUTL.done ? '' : '<button type="button" class="po-btn" id="poPayX">Back to bill</button>') + '</div></div>' : '');
    oWire(o); oFocus();
  }
  function oSet(j) { if (j && j.sale) OUTL.sale = j.sale; }
  function oWire(o) {
    var $ = function (id) { return o.querySelector(id); };
    $('#poClose').onclick = function () { document.removeEventListener('keydown', oKey, true); o.remove(); };
    $('#poToday').onclick = oToday;
    $('#poInv').onclick = oInvoices;
    $('#poKio').onclick = oKiosk;
    var ctl = o.querySelector('#poGrid'); if (ctl) oWireCatalog(o);
    var sc = $('#poScan');
    sc.onkeydown = function (e) { if (e.key === 'Enter') { e.preventDefault(); var v = sc.value.trim(); sc.value = ''; if (v) oScan(v); } };
    o.querySelectorAll('[data-mode]').forEach(function (b) { b.onclick = function () { OUTL.mode = b.getAttribute('data-mode'); oRender(); }; });
    o.querySelectorAll('[data-q]').forEach(function (b) { b.onclick = function () {
      api('POST', oUrl('/sale/' + OUTL.sale.id + '/item/' + b.getAttribute('data-q') + '/qty'), { qty: +b.getAttribute('data-v') }).then(function (j) { if (j && j.ok) { oSet(j); oRender(); } else oFlash((j && j.message) || 'Could not change.', 'bad'); }).catch(function () { oFlash('Network problem. Try again.', 'bad'); });
    }; });
    o.querySelectorAll('[data-cash]').forEach(function (b) { b.onclick = function () { var c = $('#poCash'); c.value = b.getAttribute('data-cash'); c.dispatchEvent(new Event('input')); }; });
    var cash = $('#poCash'); if (cash) cash.oninput = function () { var d = (+cash.value || 0) - oTotal(); $('#poChg').textContent = cash.value === '' ? '' : (d >= 0 ? 'Give back ' + money(d) : 'Short by ' + money(-d)); };
    var cf = $('#poConfirm'); if (cf) cf.onclick = function () {
      var body = { method: OUTL.mode }, ref = $('#poRef'); if (ref && ref.value.trim()) body.ref = ref.value.trim();
      if (OUTL.mode === 'cash') { if (!cash.value) { oFlash('Enter the cash given.', 'bad'); return; } body.tendered = +cash.value; }
      OUTL.busy = true; cf.disabled = true;
      api('POST', oUrl('/sale/' + OUTL.sale.id + '/pay'), body).then(function (j) {
        OUTL.busy = false;
        if (j && j.ok) { OUTL.done = { method: j.sale.method, total: j.sale.total, change: j.sale.change, sale_no: j.sale.sale_no, id: j.sale.id }; OUTL.sale = null; oBeep(true); oRender(); oPrintTry(OUTL.done.id); }
        else { oFlash((j && j.message) || 'Payment not saved.', 'bad'); oRender(); }
      }).catch(function () { OUTL.busy = false; oFlash('Network problem. Check the bill before trying again.', 'bad'); oRender(); });
    };
    var pb = $('#poPayBtn'); if (pb) pb.onclick = function () { OUTL.payOpen = true; OUTL.msg = ''; oRender(); };
    var px = $('#poPayX'); if (px) px.onclick = function () { OUTL.payOpen = false; oRender(); };
    var vd = $('#poVoid'); if (vd) vd.onclick = function () { if (!confirm('Cancel this bill and release the packs?')) return; api('POST', oUrl('/sale/' + OUTL.sale.id + '/cancel')).then(function () { OUTL.sale = null; oFlash('Bill cancelled.', ''); oRender(); }); };
    var pr = $('#poPrint'); if (pr) pr.onclick = function () { oPrint(OUTL.done.id, 'thermal'); };
    var p4 = $('#poPrintA4'); if (p4) p4.onclick = function () { oPrint(OUTL.done.id, 'a4'); };
    var nw = $('#poNew'); if (nw) nw.onclick = function () { OUTL.done = null; OUTL.payOpen = false; OUTL.msg = ''; oRender(); };
    o.onclick = function (e) { if (!/INPUT|TEXTAREA|BUTTON/.test(e.target.tagName)) oFocus(); };
  }
  function oKey(e) { var o = document.getElementById('pcOutlet'); if (!o) return; if (e.target.id === 'poScan' || /INPUT|TEXTAREA/.test(e.target.tagName)) return; if (document.querySelector('.po-dlg')) return; if (e.key.length === 1 || e.key === 'Enter') oFocus(); }
  function oScan(code) {
    if (/^K\d{2,5}$/i.test(String(code).trim())) { oKioskTake(String(code).trim()); return; }
    if (OUTL.done) { OUTL.done = null; oRender(); }
    api('POST', oUrl('/scan'), { barcode: code, sale_id: OUTL.sale ? OUTL.sale.id : null }).then(function (j) {
      if (j && j.ok) { oSet(j); OUTL.msg = j.added + ' added'; OUTL.tone = 'ok'; oBeep(true); oRender(); }
      else if (j && j.unknown) { oBeep(false); oLink(j.unknown, j.message); }
      else { oBeep(false); oFlash((j && j.message) || 'Could not add.', 'bad'); }
    }).catch(function () { oBeep(false); oFlash('Network problem. Scan again.', 'bad'); });
  }
  function oDlg(html, wire) {
    var o = document.getElementById('pcOutlet') || document.body, d = document.createElement('div'); d.className = 'po-dlg'; d.innerHTML = '<div>' + html + '</div>'; o.appendChild(d);
    var close = function () { d.remove(); oFocus(); }; d.querySelectorAll('[data-x]').forEach(function (b) { b.onclick = close; }); wire(d, close); return d;
  }
  function oLink(code, why) {
    var scale = /^2\d{12}$/.test(code), pick = null;
    oDlg('<h3 style="margin:0 0 6px">New barcode</h3><p class="po-hint" style="margin:0 0 10px">' + esc(why) + ' Link it once and every scan after this is automatic.</p><div class="po-card" style="margin-bottom:10px"><b>' + esc(code) + '</b></div>' +
      '<input class="po-in" id="poQ" placeholder="Search the product by name or code" autocomplete="off"><div id="poRes" style="margin:8px 0;max-height:220px;overflow:auto"></div>' +
      (scale ? '<label class="po-hint" style="display:flex;gap:8px;align-items:center"><input type="checkbox" id="poW" checked> This is a scale label (the product code is the 5 digits after the first digit)</label>' : '') +
      '<div style="display:flex;gap:8px;margin-top:12px"><button type="button" class="po-btn" data-x>Cancel</button><button type="button" class="po-btn pri" id="poLinkGo" disabled>Link and add</button></div>',
      function (d, close) {
        var q = d.querySelector('#poQ'), res = d.querySelector('#poRes'), go = d.querySelector('#poLinkGo'), tm = null; q.focus();
        q.oninput = function () { clearTimeout(tm); tm = setTimeout(function () { api('GET', oUrl('/products') + '?q=' + encodeURIComponent(q.value)).then(function (j) {
          res.innerHTML = ((j && j.items) || []).map(function (p, i) { return '<button type="button" class="po-btn" style="width:100%;text-align:left;margin-bottom:6px" data-i="' + i + '"><b>' + esc(p.name) + '</b> <span class="po-hint">' + esc(p.pack) + ' · ' + esc(p.code) + ' · ' + esc(money(p.price)) + '</span></button>'; }).join('') || '<div class="po-hint">No product found.</div>';
          res.querySelectorAll('[data-i]').forEach(function (b) { b.onclick = function () { pick = j.items[+b.getAttribute('data-i')]; res.querySelectorAll('[data-i]').forEach(function (x) { x.classList.remove('on'); }); b.classList.add('on'); go.disabled = false; go.textContent = 'Link to ' + pick.name + ' and add'; }; });
        }); }, 250); };
        go.onclick = function () { if (!pick) return; go.disabled = true; var w = d.querySelector('#poW');
          api('POST', oUrl('/link'), { barcode: code, code: pick.code, weighed: !!(w && w.checked) }).then(function (j) { if (j && j.ok) { close(); oScan(code); } else { go.disabled = false; oFlash((j && j.message) || 'Could not link.', 'bad'); close(); } }).catch(function () { go.disabled = false; });
        };
      });
  }
  function oToday() {
    api('GET', oUrl('/summary')).then(function (j) {
      if (!j || !j.ok) { oFlash((j && j.message) || 'Could not load.', 'bad'); return; }
      var t = j.today, b = t.by, nm = { upi: 'UPI', cash: 'Cash', card: 'Card' };
      oDlg('<h3 style="margin:0 0 10px">Today at this counter</h3><div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-bottom:10px"><div class="po-card"><div class="po-hint">Bills</div><b style="font-size:22px">' + t.bills + '</b></div><div class="po-card"><div class="po-hint">Sales</div><b style="font-size:22px">' + esc(money(t.total)) + '</b></div></div>' +
        '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:10px">' + ['upi', 'cash', 'card'].map(function (k) { return '<div class="po-card"><div class="po-hint">' + nm[k] + ' (' + b[k].n + ')</div><b>' + esc(money(b[k].v)) + '</b></div>'; }).join('') + '</div>' +
        '<div style="max-height:240px;overflow:auto">' + (j.recent || []).map(function (s) { return '<div class="po-row"><span style="flex:1"><b>' + esc(s.sale_no) + '</b> <span class="po-hint">' + esc(nm[s.method] || '') + '</span></span><b>' + esc(money(s.total)) + '</b><button type="button" class="po-btn sm" data-r="' + s.id + '">Receipt</button></div>'; }).join('') + '</div>' +
        '<div style="margin-top:12px"><button type="button" class="po-btn" data-x>Close</button></div>',
        function (d) { d.querySelectorAll('[data-r]').forEach(function (x) { x.onclick = function () { oPrint(+x.getAttribute('data-r'), 'thermal'); }; }); });
    });
  }
  function oOpenInvoice(type, id, fmt, print) {
    var w = window.open('', '_blank'); /* opened at the click, so the browser never blocks it */
    if (!w) { toast('Allow pop-ups for this site to print.', 'bad'); return; }
    try { w.document.write('<p style="font:14px sans-serif;padding:20px">Preparing invoice...</p>'); } catch (e) {}
    api('GET', posBase() + '/invoice/link/' + type + '/' + encodeURIComponent(id) + '?fmt=' + (fmt || 'a4') + (print ? '&print=1' : '')).then(function (j) {
      if (j && j.ok && j.url) w.location.href = j.url; else { w.close(); toast((j && j.message) || 'Could not open the invoice.', 'bad'); }
    }).catch(function () { w.close(); toast('Network problem. Please try again.', 'bad'); });
  }
  function oPrint(id, fmt) { oOpenInvoice('outlet', id, fmt || 'thermal', true); }
  function oPrintTry(id) {
    /* straight after payment the browser may refuse a new window; the Print buttons on the paid screen always work */
    api('GET', posBase() + '/invoice/link/outlet/' + id + '?fmt=thermal&print=1').then(function (j) { if (j && j.ok && j.url) { var w = window.open(j.url, '_blank'); if (!w) toast('Paid. Press Print receipt to print.', ''); } }).catch(function () {});
  }
  function oInvoices() {
    oCss();
    function ymd(d) { var m = d.getMonth() + 1, x = d.getDate(); return d.getFullYear() + '-' + (m < 10 ? '0' : '') + m + '-' + (x < 10 ? '0' : '') + x; }
    var today = ymd(new Date()), yest = ymd(new Date(Date.now() - 86400000)), wk = ymd(new Date(Date.now() - 6 * 86400000));
    oDlg('<h3 style="margin:0 0 8px">Find an invoice</h3><input class="po-in" id="poIq" placeholder="Order number, customer name or phone, or bill number" autocomplete="off">' +
      '<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:10px 0 6px"><label class="po-hint">From <input type="date" class="po-in" id="poIf" style="width:auto;padding:8px 10px;font-size:14px"></label><label class="po-hint">To <input type="date" class="po-in" id="poIt" style="width:auto;padding:8px 10px;font-size:14px"></label>' +
      '<select class="po-in" id="poIk" style="width:auto;padding:8px 10px;font-size:14px"><option value="all">Counter bills and online orders</option><option value="bills">Counter bills only</option><option value="orders">Online orders only</option></select></div>' +
      '<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:6px"><button type="button" class="po-chip" data-d="today">Today</button><button type="button" class="po-chip" data-d="yest">Yesterday</button><button type="button" class="po-chip" data-d="week">Last 7 days</button><button type="button" class="po-chip" data-d="all">All dates</button></div>' +
      '<div id="poIs" class="po-hint" style="margin-bottom:4px"></div><div id="poIr" style="margin:6px 0;max-height:46vh;overflow:auto"></div><button type="button" class="po-btn" data-x>Close</button>',
      function (d) {
        var q = d.querySelector('#poIq'), res = d.querySelector('#poIr'), sm = d.querySelector('#poIs'), df = d.querySelector('#poIf'), dt = d.querySelector('#poIt'), dk = d.querySelector('#poIk'), tm = null;
        df.value = today; dt.value = today;
        function row(title, sub, amt, type, id) { return '<div class="po-row"><span style="flex:1;min-width:0"><b>' + esc(title) + '</b><br><span class="po-hint">' + esc(sub) + '</span></span><b>' + esc(money(amt)) + '</b><button type="button" class="po-btn sm" data-t="' + type + '" data-i="' + esc(id) + '" data-f="thermal">Receipt</button><button type="button" class="po-btn sm" data-t="' + type + '" data-i="' + esc(id) + '" data-f="a4">A4 invoice</button></div>'; }
        function load() {
          var qs = '?q=' + encodeURIComponent(q.value) + '&from=' + encodeURIComponent(df.value) + '&to=' + encodeURIComponent(dt.value) + '&kind=' + encodeURIComponent(dk.value);
          api('GET', posBase() + '/invoice/find' + qs).then(function (j) {
            if (!j || !j.ok) { res.innerHTML = '<div class="po-hint">' + esc((j && j.message) || 'Could not search.') + '</div>'; return; }
            var nb = (j.bills || []).length, no = (j.orders || []).length, sum = 0;
            (j.bills || []).forEach(function (b) { sum += +b.total || 0; }); (j.orders || []).forEach(function (x) { sum += +x.total || 0; });
            sm.textContent = (nb + no) + ' invoice(s) - ' + nb + ' counter bill(s), ' + no + ' online order(s) - total ' + money(sum);
            res.innerHTML = (j.bills || []).map(function (b) { return row(b.no, 'Counter bill - ' + String(b.method || '').toUpperCase() + ' - ' + (b.at || ''), b.total, 'outlet', b.id); }).join('') +
              (j.orders || []).map(function (x) { return row(x.id, (x.name || 'Customer') + ' - ' + (x.status || '') + ' - ' + (x.at || ''), x.total, 'order', x.id); }).join('') || '<div class="po-hint">Nothing found for these dates.</div>';
            res.querySelectorAll('[data-t]').forEach(function (b) { b.onclick = function () { oOpenInvoice(b.getAttribute('data-t'), b.getAttribute('data-i'), b.getAttribute('data-f'), true); }; });
          }).catch(function () { res.innerHTML = '<div class="po-hint">Network problem.</div>'; });
        }
        d.querySelectorAll('[data-d]').forEach(function (b) { b.onclick = function () { var k = b.getAttribute('data-d'); df.value = k === 'today' ? today : k === 'yest' ? yest : k === 'week' ? wk : ''; dt.value = k === 'today' ? today : k === 'yest' ? yest : k === 'week' ? today : ''; load(); }; });
        df.onchange = load; dt.onchange = load; dk.onchange = load;
        q.oninput = function () { clearTimeout(tm); tm = setTimeout(load, 300); }; q.focus(); load();
      });
  }
  function oCloseDialogs() { document.querySelectorAll('.po-dlg').forEach(function (d) { d.remove(); }); }
  function oKioskTake(ref) {
    api('POST', oUrl('/kiosk/adopt'), { ref: ref }).then(function (j) {
      if (j && j.ok) { OUTL.sale = j.sale; OUTL.done = null; oCloseDialogs(); oBeep(true); OUTL.msg = 'Kiosk order ' + (j.sale.pickup || '') + ' opened. Pick the items, check the weights, then take payment.'; OUTL.tone = 'ok'; oRender(); oKioskCount(); }
      else { oBeep(false); oFlash((j && j.message) || 'Could not open that kiosk order.', 'bad'); }
    }).catch(function () { oFlash('Network problem. Try again.', 'bad'); });
  }
  function oKioskCount() {
    api('GET', oUrl('/kiosk')).then(function (j) {
      if (!j || !j.ok) return; var n = (j.orders || []).length;
      if (n !== OUTL.kn) { OUTL.kn = n; var b = document.getElementById('poKio'); if (b) b.textContent = 'Kiosk orders' + (n ? ' (' + n + ')' : ''); }
    }).catch(function () {});
  }
  function oKiosk() {
    oDlg('<h3 style="margin:0 0 8px">Kiosk orders waiting</h3><div id="poKl" style="margin:10px 0;max-height:56vh;overflow:auto"><div class="po-hint">Loading...</div></div><button type="button" class="po-btn" data-x>Close</button>',
      function (d) {
        api('GET', oUrl('/kiosk')).then(function (j) {
          var l = d.querySelector('#poKl'); if (!j || !j.ok) { l.innerHTML = '<div class="po-hint">Could not load.</div>'; return; }
          var o = j.orders || []; OUTL.kn = o.length;
          l.innerHTML = o.map(function (k) { return '<div class="po-row"><span style="flex:1"><b style="font-size:18px">' + esc(k.pickup) + '</b> <span class="po-hint">' + k.packs + ' pack(s) - ' + esc(String(k.at || '').slice(11, 16)) + '</span></span><b>' + esc(money(k.total)) + '</b><button type="button" class="po-btn sm pri" data-k="' + k.id + '">Open at counter</button></div>'; }).join('') || '<div class="po-hint">No kiosk order is waiting. You can also type the pickup number (for example K007) in the scan box.</div>';
          l.querySelectorAll('[data-k]').forEach(function (b) { b.onclick = function () { oKioskTake(b.getAttribute('data-k')); }; });
        }).catch(function () { d.querySelector('#poKl').innerHTML = '<div class="po-hint">Network problem.</div>'; });
      });
  }
  /* ---- catalogue: tap a picture to add the pack, same as scanning it ---- */
  function oCatalogHtml() {
    var c = OUTL.cat;
    return '<div class="po-cat"><input class="po-in" id="poCq" placeholder="Search by name or product code" value="' + esc(c.q) + '" autocomplete="off"><div class="po-chips" id="poChips"></div><div class="po-grid" id="poGrid"></div></div>';
  }
  function oPaintCatalog() {
    var c = OUTL.cat, chips = document.getElementById('poChips'), grid = document.getElementById('poGrid'); if (!grid) return;
    chips.innerHTML = '<button type="button" class="po-chip' + (c.tag === '' ? ' on' : '') + '" data-tag="">All</button>' + c.tags.map(function (g) { return '<button type="button" class="po-chip' + (c.tag === g ? ' on' : '') + '" data-tag="' + esc(g) + '">' + esc(g) + '</button>'; }).join('');
    chips.querySelectorAll('[data-tag]').forEach(function (b) { b.onclick = function () { c.tag = b.getAttribute('data-tag'); oPaintCatalog(); oLoadCatalog(); }; });
    grid.innerHTML = c.busy && !c.items.length ? '<div class="po-hint">Loading...</div>' : (c.items.map(function (p, i) {
      var out = p.avail < 1;
      return '<button type="button" class="po-tile" data-p="' + i + '"' + (out ? ' disabled' : '') + '><img loading="lazy" src="' + esc(p.img || '/assets/images/freshful-placeholder.png') + '" alt="" onerror="this.src=\'/assets/images/freshful-placeholder.png\'"><b>' + esc(p.name) + '</b><span>' + esc(p.pack) + '</span><em>' + esc(money(p.price)) + '</em><span>' + (out ? 'Out of stock' : esc(String(Math.floor(p.avail))) + ' available') + '</span></button>';
    }).join('') || '<div class="po-hint">No product found.</div>');
    grid.querySelectorAll('[data-p]').forEach(function (b) { b.onclick = function () { oScan(c.items[+b.getAttribute('data-p')].code); }; });
  }
  function oLoadCatalog() {
    var c = OUTL.cat; c.busy = true;
    api('GET', oUrl('/catalog') + '?tag=' + encodeURIComponent(c.tag) + '&q=' + encodeURIComponent(c.q)).then(function (j) {
      c.busy = false; if (!j || !j.ok) { oFlash((j && j.message) || 'Could not load products.', 'bad'); return; }
      c.tags = j.tags || c.tags; c.items = j.items || []; oPaintCatalog();
    }).catch(function () { c.busy = false; oFlash('Network problem loading products.', 'bad'); });
  }
  function oWireCatalog(o) {
    var q = o.querySelector('#poCq'), tm = null;
    q.oninput = function () { clearTimeout(tm); tm = setTimeout(function () { OUTL.cat.q = q.value.trim(); oLoadCatalog(); }, 300); };
    oPaintCatalog();
    if (!OUTL.cat.items.length && !OUTL.cat.busy) oLoadCatalog();
  }
  function outletOpen() {
    if (document.getElementById('pcOutlet')) return;
    oCss(); var o = document.createElement('div'); o.id = 'pcOutlet'; document.body.appendChild(o); document.addEventListener('keydown', oKey, true);
    OUTL.done = null; OUTL.msg = ''; o.innerHTML = '<div style="padding:40px;text-align:center">Loading...</div>';
    api('GET', oUrl('/current')).then(function (j) {
      if (!j || !j.ok) { o.remove(); toast((j && j.message) || 'The outlet counter is not available.', 'bad'); return; }
      OUTL.sale = j.sale; OUTL.upi = j.upi || OUTL.upi; oRender(); oKioskCount();
      var kt = setInterval(function () { if (!document.getElementById('pcOutlet')) { clearInterval(kt); return; } oKioskCount(); }, 20000);
    }).catch(function () { o.remove(); toast('Network problem. Please try again.', 'bad'); });
  }
  function outletLauncher() {
    if (document.getElementById('pcOutletBtn')) return;
    var b = document.createElement('button'); b.id = 'pcOutletBtn'; b.type = 'button'; b.textContent = 'Outlet counter';
    b.style.cssText = 'position:fixed;right:18px;bottom:18px;z-index:800;min-height:48px;padding:0 20px;border:0;border-radius:14px;font-weight:800;font-size:14px;color:#fff;cursor:pointer;background:linear-gradient(135deg,#F70202,#FF5500);box-shadow:0 6px 18px rgba(247,2,2,.35)';
    b.onclick = outletOpen; document.body.appendChild(b);
    var v = document.createElement('button'); v.id = 'pcInvBtn'; v.type = 'button'; v.textContent = 'Invoices';
    v.style.cssText = 'position:fixed;right:178px;bottom:18px;z-index:800;min-height:48px;padding:0 20px;border:1px solid #CBD5E1;border-radius:14px;font-weight:800;font-size:14px;color:#0F172A;cursor:pointer;background:#fff;box-shadow:0 6px 18px rgba(15,23,42,.18)';
    v.onclick = oInvoices; document.body.appendChild(v);
  }
  setTimeout(outletLauncher, 1200);
  /* ---- POS2 (30 Oct 2026): assign to rider, hand over several orders, mark delivered for the rider ---- */
  S.asg = {};
  function posBase() { return String(C.rider || '').replace(/\/rider\/?$/, ''); }
  function loadAsg() {
    return api('GET', posBase() + '/assignments').then(function (j) {
      if (!j || !j.ok) return;
      var m = j.map || {};
      if (JSON.stringify(m) !== JSON.stringify(S.asg)) { S.asg = m; if (S.tab === 'riders') render(); }
    }).catch(function () {});
  }
  setTimeout(loadAsg, 1500);
  setInterval(function () { if (S.tab === 'riders' && !document.hidden) loadAsg(); }, 12000);
  function asgChip(o) {
    var a = S.asg[o.order_id];
    if (!a || +a.rider_id !== +o.rider_id) return '<span class="pc-chip">Sent to rider</span>';
    if (a.accepted) return '<span class="pc-chip ok">Accepted by rider</span>';
    return '<span class="pc-chip">' + (a.seen ? 'Opened in app' : 'Sent to rider') + '</span>';
  }
  function riderOrders(r) {
    var mine = S.orders.filter(function (o) { return +o.rider_id === +r.id; });
    var asg = mine.filter(function (o) { return o.stage === 'accepted'; });
    var out = mine.filter(function (o) { return o.stage === 'out'; });
    if (!asg.length && !out.length) return '';
    var line = function (o, chips) { return '<div style="display:flex;gap:8px;justify-content:space-between;align-items:center;font-size:12.5px"><span><b>' + esc(o.order_id) + '</b> · ' + esc(o.area || o.customer || '') + '</span><span>' + chips + '</span></div>'; };
    var h = '<div style="display:grid;gap:6px;margin-top:8px;border-top:1px solid var(--line);padding-top:8px">';
    if (asg.length) {
      h += '<b style="font-size:12px">Assigned, waiting for hand-over (' + asg.length + ')</b>' +
        asg.map(function (o) { return line(o, asgChip(o) + (o.weighed ? '' : ' <span class="pc-chip">Not weighed</span>')); }).join('') +
        '<button type="button" class="pc-btn sm pri" data-handover="' + r.id + '">Hand over and send out (' + asg.length + ')</button>';
    }
    if (out.length) {
      h += '<b style="font-size:12px">Out for delivery (' + out.length + ')</b>' +
        out.map(function (o) { return line(o, '<span class="pc-hint">' + (o.since ? esc(dur(now() - o.since)) : '') + '</span>'); }).join('') +
        '<button type="button" class="pc-btn sm" data-mdeliver="' + r.id + '">Mark delivered for rider</button>';
    }
    return h + '</div>';
  }
  function assignModal(id) {
    var list = (S.riders || []).filter(function (r) { return r.id; });
    if (!list.length) { toast('No riders are set up for this hub.', 'bad'); return; }
    var cur = (findOrder(id) || {}).rider_id;
    modal('<div class="pc-mh"><h3>Assign to rider · ' + esc(id) + '</h3><p>Delivery boys of this hub only. The order appears in the rider\'s app straight away. You send it out when the rider is at the hub.</p></div>' +
      '<div class="pc-mb">' + list.map(function (r) {
        var stt = RSTATE[r.state] || ['', ''];
        var chk = (cur && +cur === +r.id) || (!cur && r.smart);
        return '<label style="display:flex;gap:10px;align-items:center;cursor:pointer;padding:8px 10px;border:1px solid var(--line);border-radius:10px;margin-bottom:6px;' + (r.state === 'off' ? 'opacity:.6' : '') + '">' +
          '<input type="radio" name="pcAsgR" value="' + r.id + '"' + (chk ? ' checked' : '') + '>' +
          '<span><b>' + esc(r.name) + '</b>' + (r.smart ? ' <span class="pc-chip ok">Smart pick</span>' : '') + '<br><span class="pc-hint">' + esc(stt[0]) + ' · ' + esc(riderLine(r)) + (r.phone ? ' · ' + esc(r.phone) : '') + '</span></span></label>';
      }).join('') + '</div>' +
      '<div class="pc-mf"><button type="button" class="pc-btn lg" data-close>Back</button><button type="button" class="pc-btn lg pri" id="pcAsgGo">Assign</button></div>',
      function (m, close) {
        m.querySelector('#pcAsgGo').onclick = function () {
          var c = m.querySelector('input[name=pcAsgR]:checked');
          if (!c) { toast('Choose a rider.', 'bad'); return; }
          var b = this; busyBtn(b, true);
          api('POST', orderUrl(id, '/assign'), { rider_id: +c.value }).then(function (j) {
            busyBtn(b, false);
            if (afterChange(j, id)) { close(); loadAsg(); }
          }).catch(function () { busyBtn(b, false); toast('Network problem. Please try again.', 'bad'); });
        };
      });
  }
  function pickModal(title, intro, list, rowChips, goText, onGo) {
    modal('<div class="pc-mh"><h3>' + esc(title) + '</h3><p>' + intro + '</p></div>' +
      '<div class="pc-mb"><div class="pc-box" style="padding:4px 12px">' + list.map(function (o) {
        var dis = o._disabled ? ' disabled' : ' checked';
        return '<label style="display:flex;gap:10px;align-items:center;padding:8px 0;border-bottom:1px solid var(--line)"><input type="checkbox" data-o="' + esc(o.order_id) + '"' + dis + '>' +
          '<span style="flex:1"><b>' + esc(o.order_id) + '</b> · ' + esc(o.customer) + '<br><span class="pc-hint">' + esc(o.area || '') + ' · ' + esc(money(o.amount)) + '</span></span>' + rowChips(o) + '</label>';
      }).join('') + '</div>' +
      '<div id="pcPickExtra"></div></div>' +
      '<div class="pc-mf"><button type="button" class="pc-btn lg" data-close>Back</button><button type="button" class="pc-btn lg pri" id="pcPickGo">' + esc(goText) + '</button></div>',
      function (m, close) {
        var extra = m.querySelector('#pcPickExtra');
        if (onGo.note) extra.innerHTML = '<label style="display:block;margin-top:10px;font-size:13px">Note (optional)<input type="text" id="pcPickNote" maxlength="150" placeholder="e.g. Rider called at 5:40 pm, no network" style="width:100%;margin-top:4px;padding:9px 10px;border:1px solid var(--line);border-radius:8px;font:inherit"></label>';
        m.querySelector('#pcPickGo').onclick = function () {
          var ids = [].slice.call(m.querySelectorAll('input[data-o]:checked')).map(function (x) { return x.getAttribute('data-o'); });
          if (!ids.length) { toast('Tick at least one order.', 'bad'); return; }
          if (onGo.confirm && !onGo.confirm(ids)) return;
          var b = this; busyBtn(b, true);
          var nt = m.querySelector('#pcPickNote');
          onGo.run(ids, nt ? nt.value.trim() : '').then(function (j) {
            busyBtn(b, false);
            if (afterChange(j)) { close(); loadAsg(); }
          }).catch(function () { busyBtn(b, false); toast('Network problem. Please try again.', 'bad'); });
        };
      });
  }
  function handoverModal(rid) {
    var r = (S.riders || []).filter(function (x) { return +x.id === +rid; })[0]; if (!r) return;
    var list = S.orders.filter(function (o) { return +o.rider_id === +rid && o.stage === 'accepted'; }).map(function (o) { return Object.assign({ _disabled: !o.weighed }, o); });
    if (!list.length) { toast('Nothing is assigned to ' + r.name + ' right now.', 'bad'); return; }
    pickModal('Hand over to ' + r.name, 'Tick the orders being handed over now. They all go out for delivery together. Orders that are not weighed yet cannot be sent.', list,
      function (o) { return asgChip(o) + (o.weighed ? '' : ' <span class="pc-chip">Weigh first</span>'); }, 'Send out for delivery',
      {
        confirm: function (ids) {
          var n = ids.filter(function (id) { var a = S.asg[id]; return !(a && a.accepted); }).length;
          return n === 0 || window.confirm(n + ' order(s) not yet accepted by the rider in the app. Send out anyway?');
        },
        run: function (ids) { return api('POST', C.rider + '/' + rid + '/handover', { orders: ids }); }
      });
  }
  function mdeliverModal(rid) {
    var r = (S.riders || []).filter(function (x) { return +x.id === +rid; })[0]; if (!r) return;
    var list = S.orders.filter(function (o) { return +o.rider_id === +rid && o.stage === 'out'; }).map(function (o) { return Object.assign({ _disabled: false }, o); });
    if (!list.length) { toast('Nothing is out with ' + r.name + ' right now.', 'bad'); return; }
    pickModal('Mark delivered for ' + r.name, 'Use this when the rider phones the hub (for example no network) and says the order has been delivered. Tick only the orders the rider confirmed.', list,
      function (o) { return '<span class="pc-hint">' + (o.since ? esc(dur(now() - o.since)) : '') + '</span>'; }, 'Mark delivered',
      { note: true, run: function (ids, note) { return api('POST', posBase() + '/deliver-many', { orders: ids, note: note }); } });
  }
  document.addEventListener('click', function (e) {
    var t = e.target; if (!t || !t.closest) return;
    var h = t.closest('[data-handover]'); if (h) { handoverModal(h.getAttribute('data-handover')); return; }
    var d = t.closest('[data-mdeliver]'); if (d) { mdeliverModal(d.getAttribute('data-mdeliver')); }
  });
  /* ---- end POS2 ---- */
  function riderStatus(id, action) {
    api('POST', C.rider + '/' + id + '/status', { action: action }).then(function (j) { afterChange(j); }).catch(function () { toast('Network problem. Please try again.', 'bad'); });
  }
  function renderHistory() {
    var h = S.hist;
    if (!h.from) { h.from = today(); h.to = today(); }
    var stages = [['', 'All statuses'], ['new', 'New'], ['accepted', 'Accepted'], ['out', 'Out for delivery'], ['unsettled', 'To settle'], ['delivered', 'Settled'], ['cancelled', 'Cancelled']];
    var bar = '<form class="pc-filter" id="pcHistForm"><input class="pc-input" type="date" name="from" value="' + esc(h.from) + '" max="' + today() + '"><span class="pc-hint">to</span>' +
      '<input class="pc-input" type="date" name="to" value="' + esc(h.to) + '" max="' + today() + '"><select class="pc-input" name="stage">' +
      stages.map(function (s) { return '<option value="' + s[0] + '"' + (h.stage === s[0] ? ' selected' : '') + '>' + s[1] + '</option>'; }).join('') + '</select>' +
      '<button class="pc-btn pri" type="submit">Show</button><a class="pc-btn" id="pcHistCsv" href="' + esc(histUrl('csv')) + '">Download CSV</a>' +
      (h.rows ? '<span class="pc-hint" style="margin-left:auto">' + h.rows.length + ' order(s) · ' + esc(money(h.rows.filter(function (o) { return o.stage !== 'cancelled'; }).reduce(function (s, o) { return s + o.amount; }, 0))) + ' excl. cancelled</span>' : '') + '</form>';
    if (h.rows === null) { loadHistory(); return bar + '<div class="pc-list"><div class="pc-empty" style="padding:40px">Loading…</div></div>'; }
    return bar + renderList(h.rows, 'No orders in this period.');
  }
  function histUrl(fmt) { var h = S.hist; return C.history + '?from=' + encodeURIComponent(h.from) + '&to=' + encodeURIComponent(h.to) + '&stage=' + encodeURIComponent(h.stage) + (fmt ? '&format=' + fmt : ''); }
  function loadHistory() {
    if (S.hist.loading) return;
    S.hist.loading = true;
    api('GET', histUrl()).then(function (j) {
      S.hist.loading = false;
      S.hist.rows = j.ok ? j.orders : [];
      if (j.ok) { S.hist.from = j.from; S.hist.to = j.to; }
      if (S.tab === 'history') render();
    }).catch(function () { S.hist.loading = false; S.hist.rows = []; toast('Could not load history.', 'bad'); });
  }
  function render() {
    renderKpis();
    var v = $('pcView'), html = '';
    if (S.tab === 'board') html = renderBoard();
    else if (S.tab === 'riders') html = renderRiders();
    else if (S.tab === 'sla') html = renderSla();
    else if (S.tab === 'later') html = renderList(S.orders.filter(function (o) { return o.later && o.stage !== 'new'; }), 'No orders scheduled for later days.');
    else if (S.tab === 'history') html = renderHistory();
    else if (S.tab === 'stale') {
      var old = S.orders.filter(function (o) { return o.stale; }).sort(function (a, b) { return (a.placed_at || 0) - (b.placed_at || 0); });
      var cancellable = old.filter(function (o) { return o.stage !== 'unsettled'; }).length;
      html = (old.length ? '<div class="pc-banner is-warn" style="margin-bottom:14px">These orders were due more than 3 days ago and are still open. Open one to finish it properly' +
        (cancellable ? ', or cancel all ' + cancellable + ' that never reached the customer.' : '.') + '<span class="sp"></span>' +
        (cancellable ? '<button type="button" id="pcCloseStale">Cancel ' + cancellable + ' old order(s)</button>' : '') + '</div>' : '') +
        renderList(old, 'No old open orders. Everything is up to date.');
    } else html = renderList(S.orders.filter(function (o) { return o.stage === 'delivered' || o.stage === 'cancelled'; }), 'Nothing settled or cancelled today yet.');
    v.innerHTML = html;
    var hf = $('pcHistForm');
    if (hf) hf.onsubmit = function (e) {
      e.preventDefault();
      S.hist.from = hf.from.value || today(); S.hist.to = hf.to.value || S.hist.from; S.hist.stage = hf.stage.value; S.hist.rows = null;
      render();
    };
  }
  function tick() {   // live countdowns without a full re-render
    document.querySelectorAll('[data-due]').forEach(function (el) {
      var left = +el.getAttribute('data-due') - now();
      el.className = 'pc-chip ' + (left < 0 ? 'bad' : (left < 1800 ? 'warn' : 'ok'));
      el.textContent = left < 0 ? 'Late ' + dur(left) : 'Due in ' + dur(left);
    });
    document.querySelectorAll('[data-ago]').forEach(function (el) { var t = +el.getAttribute('data-ago'); if (t) el.textContent = ago(t); });
  }

  /* ---------------- Order drawer ---------------- */
  function closeDrawer() {
    S.openId = null; S.detail = null;
    var d = document.querySelector('.pc-drawer'), s = document.querySelector('.pc-scrim');
    if (d) d.remove(); if (s) s.remove();
  }
  function openOrder(id) {
    S.openId = id;
    delete S.fresh[id];
    if (!document.querySelector('.pc-drawer')) {
      var scrim = document.createElement('div'); scrim.className = 'pc-scrim'; scrim.onclick = closeDrawer;
      var dr = document.createElement('aside'); dr.className = 'pc-drawer'; dr.setAttribute('role', 'dialog'); dr.setAttribute('aria-label', 'Order ' + id);
      dr.innerHTML = '<div class="pc-dh"><div><h2>' + esc(id) + '</h2><p>Loading…</p></div><button type="button" class="x" aria-label="Close">✕</button></div><div class="pc-db"></div>';
      document.body.appendChild(scrim); document.body.appendChild(dr);
      dr.querySelector('.x').onclick = closeDrawer;
    }
    refreshDrawer();
  }
  function loadDetail(id) {
    return api('GET', orderUrl(id)).then(function (j) { if (!j.ok) throw new Error(j.message || 'Could not load order'); return j.order; });
  }
  function refreshDrawer() {
    var id = S.openId; if (!id) return;
    loadDetail(id).then(function (o) {
      if (S.openId !== id) return;
      S.detail = o; drawDrawer(o);
    }).catch(function (e) { if (S.openId === id) { toast(e.message, 'bad'); closeDrawer(); } });
  }
  var ACT_BTN = {
    accept: ['Accept order', 'pri'], weigh: ['Weigh items', 'pri'], dispatch: ['Out for delivery', 'pri'], deliver: ['Mark delivered', 'ok'],
    dispatch_manual: ['Assign to rider', ''], reassign: ['Change rider', ''],
    settle: ['Settle payment', 'ok'], relocate: ['Move to another hub', ''], reject: ['Reject', 'bad'], cancel: ['Cancel order', 'bad']
  };
  function drawDrawer(o) {
    var dr = document.querySelector('.pc-drawer'); if (!dr) return;
    var lbl = label(o.board === 'ready' ? 'ready' : o.stage);
    var phone = (o.phone || '').replace(/\D/g, '');
    var wa = phone ? 'https://wa.me/' + (phone.length === 10 ? '91' + phone : phone) + '?text=' + encodeURIComponent('Hello ' + o.customer + ', this is Freshful about your order ' + o.order_id + '.') : null;
    dr.querySelector('.pc-dh').innerHTML =
      '<div><h2>' + esc(o.order_id) + '</h2><p>' + esc(lbl) + ' · placed ' + esc(clock(o.placed_at)) + '</p>' +
      '<div class="row" style="display:flex;gap:6px;margin-top:8px;flex-wrap:wrap">' + typeChip(o) + payChip(o) + adjChip(o) + dueChip(o) + '</div></div>' +
      '<button type="button" class="x" aria-label="Close">✕</button>';
    dr.querySelector('.x').onclick = closeDrawer;

    var lines = (o.lines || []).map(function (it) {
      var w = it.weighable ? (it.actual_g != null
        ? '<em class="wt">Weighed ' + esc(fmtG(it.actual_g)) + ' of ' + esc(fmtG(it.expected_g)) + ' · <b class="pc-diff ' + (it.diff > 0 ? 'up' : (it.diff < 0 ? 'down' : 'zero')) + '">' + esc(signed(it.diff)) + '</b></em>'
        : '<em class="wt">To weigh: ' + esc(fmtG(it.expected_g)) + '</em>') : '<em class="wt">Sold per piece</em>';
      return '<div class="pc-item">' + (it.image ? '<img src="' + esc(it.image) + '" alt="" loading="lazy" onerror="this.replaceWith(Object.assign(document.createElement(\'div\'),{className:\'ph\'}))">' : '<div class="ph"></div>') +
        '<div class="nm"><b>' + esc(it.name) + '</b><span>' + esc(it.unit || '') + (it.price ? ' · ' + esc(money(it.price)) + ' each' : '') + '</span>' + w + '</div>' +
        '<span class="q">× ' + esc(+it.qty) + '</span><span class="lt">' + esc(money(it.line)) + '</span></div>';
    }).join('');

    var st = o.settlement;
    var html =
      '<div class="pc-sec"><h4>Customer</h4><div class="pc-box"><div class="pc-cust"><span class="av">' + esc((o.customer || 'C').trim().charAt(0).toUpperCase()) + '</span>' +
      '<div><b>' + esc(o.customer) + '</b><span>' + esc(o.phone || 'no phone') + '</span></div>' +
      '<div class="acts">' + (phone ? '<a class="pc-btn sm" href="tel:' + esc(phone) + '">Call</a>' : '') + (wa ? '<a class="pc-btn sm" href="' + esc(wa) + '" target="_blank" rel="noopener">WhatsApp</a>' : '') + '</div></div>' +
      '<div class="pc-addr"><div style="flex:1">' + esc(o.address || 'No address on file') + (o.address_type ? ' <span class="pc-chip">' + esc(o.address_type) + '</span>' : '') + '</div>' +
      (o.map ? '<a href="' + esc(o.map) + '" target="_blank" rel="noopener">Open map</a>' : '') + '</div></div></div>' +

      '<div class="pc-sec"><h4>Items (' + (o.lines || []).length + ')</h4><div class="pc-box pc-items">' + lines + '</div></div>' +

      '<div class="pc-sec"><h4>Bill</h4><div class="pc-box pc-sum">' +
      (o.discount ? '<div><span>Items before promo</span><span class="num">' + esc(money(o.total_amount + o.discount)) + '</span></div><div><span>Promo ' + esc(o.promo || '') + '</span><span class="num">− ' + esc(money(o.discount)) + '</span></div>' : '') +
      '<div><span>Items</span><span class="num">' + esc(money(o.total_amount)) + '</span></div>' +
      '<div><span>Delivery charge</span><span class="num">' + esc(money(o.delivery_charge)) + '</span></div>' +
      '<div><span>Bill</span><span class="num">' + esc(money(o.bill)) + '</span></div>' +
      (o.adjustment ? '<div><span>Weight adjustment (' + (o.adjustment > 0 ? 'collect extra' : 'return to customer') + ')</span><span class="num">' + esc(signed(o.adjustment)) + '</span></div>' : '') +
      '<div class="tot"><span>' + (o.prepaid ? (o.expected < 0 ? 'Refund to customer' : 'Collect on delivery') : 'Customer pays') + '</span><span class="num">' + esc(money(o.prepaid ? Math.abs(o.expected) : o.amount)) + '</span></div>' +
      (o.prepaid ? '<div class="muted"><span>Paid online before delivery: ' + esc(money(o.bill)) + '</span></div>' : '') +
      (st ? '<div class="muted"><span>Settled ' + esc(clock(sqlTs(st.at || st.created_at))) + (st.by_name ? ' by ' + esc(st.by_name) : '') +
        (st.handover_id ? ' · ' + [st.cash_amount > 0 ? 'cash' : '', st.upi_amount > 0 ? 'UPI' : '', st.card_amount > 0 ? 'card' : ''].filter(Boolean).join(' + ') + (st.orders_count > 1 ? ' (batch of ' + st.orders_count + ')' : '') + (st.reference ? ' · ref ' + esc(st.reference) : '') : ' · nothing to collect') + '</span></div>' : '') +
      '</div></div>' +

      '<div class="pc-sec"><h4>Delivery</h4><div class="pc-meta">' +
      '<div><small>Type</small><b>' + esc(o.delivery_type) + '</b></div>' +
      '<div><small>Slot</small><b>' + esc(o.slot || (o.delivery_type === 'Express' ? 'Within ' + (C.expressMin || 30) + ' min' : '–')) + '</b></div>' +
      '<div><small>Deliver by</small><b>' + esc(o.due_at ? clock(o.due_at) : '–') + '</b></div>' +
      '<div><small>Rider</small><b>' + esc(o.rider || 'Not assigned') + '</b></div></div></div>' +

      '<div class="pc-sec"><h4>History</h4><ul class="pc-tl">' + (o.timeline || []).map(function (t) {
        return '<li><i></i>' + esc(t.label) + (t.by ? ' <span style="color:var(--ink-3)">· ' + esc(t.by) + '</span>' : '') + '<span>' + esc(clock(sqlTs(t.at))) + '</span></li>';
      }).join('') + '</ul></div>' +

      '<div class="pc-sec"><h4>Print</h4><div class="pc-chips">' +
      '<a class="pc-btn sm" target="_blank" href="' + printUrl(o.order_id, 'pick') + '">Packing slip</a>' +
      '<a class="pc-btn sm" target="_blank" href="' + printUrl(o.order_id, 'thermal') + '">Receipt 80 mm</a>' +
      '<a class="pc-btn sm" target="_blank" href="' + printUrl(o.order_id, 'a4') + '">A4 invoice</a></div></div>';
    dr.querySelector('.pc-db').innerHTML = html;

    var foot = dr.querySelector('.pc-df');
    if (!foot) { foot = document.createElement('div'); foot.className = 'pc-df'; dr.appendChild(foot); }
    var order = ['accept', 'weigh', 'dispatch', 'dispatch_manual', 'deliver', 'reassign', 'settle', 'relocate', 'reject', 'cancel'];
    var acts = (o.actions || []).slice().sort(function (a, b) { return order.indexOf(a) - order.indexOf(b); });
    if (o.board === 'ready') acts = acts.filter(function (a) { return a !== 'weigh'; }).concat(['weigh']);   // re-weigh as a secondary action
    if (acts.indexOf('dispatch') >= 0) acts.splice(acts.indexOf('dispatch') + 1, 0, 'dispatch_manual');
    if (o.stage === 'out') acts.splice(1, 0, 'reassign');
    var btns = acts.map(function (a, i) {
      var b = ACT_BTN[a]; if (!b) return '';
      var txt = a === 'weigh' && o.weighed ? 'Re-weigh' : b[0];
      var cls = (a === 'weigh' && o.weighed) ? '' : b[1];
      return '<button type="button" class="pc-btn lg ' + cls + '" data-act="' + a + '" data-id="' + esc(o.order_id) + '">' + txt + '</button>';
    });
    foot.innerHTML = btns.join('') || '<span style="color:var(--ink-3);font-size:13px;padding:8px 0">No further steps for this order.</span>';
  }
  function fmtG(g) { g = Number(g || 0); return g >= 1000 ? (Math.round(g) / 1000).toLocaleString('en-IN', { maximumFractionDigits: 3 }) + ' kg' : Math.round(g) + ' g'; }
  function printUrl(id, fmt) { return orderUrl(id, '/print/' + fmt) + '?auto=1'; }

  /* ---------------- Actions ---------------- */
  function findOrder(id) { return S.orders.filter(function (o) { return o.order_id === id; })[0] || (S.detail && S.detail.order_id === id ? S.detail : null); }
  function afterChange(j, id) {
    if (!j || !j.ok) { toast((j && j.message) || 'Could not update the order.', 'bad'); return false; }
    if (id) delete S.fresh[id];
    toast(j.message || 'Updated', 'ok');
    poll(true);
    if (S.tab === 'history') { S.hist.rows = null; }
    return true;
  }
  function doAction(id, action, extra) {
    if (S.busy) return Promise.resolve();
    S.busy = true;
    return api('POST', orderUrl(id, '/action'), Object.assign({ action: action }, extra || {}))
      .then(function (j) { afterChange(j, id); return j; })
      .catch(function () { toast('Network problem. Please try again.', 'bad'); })
      .then(function (j) { S.busy = false; return j; });
  }
  function startAction(id, action) {
    if (action === 'reject' || action === 'cancel') return reasonModal(id, action);
    if (action === 'dispatch') return dispatchOrder(id);
    if (action === 'dispatch_manual') return assignModal(id);
    if (action === 'reassign') return riderModal(id, true);
    if (action === 'weigh') return weighModal(id);
    if (action === 'relocate') return relocateModal(id);
    if (action === 'settle') return settleModal([id], null);
    if (action === 'deliver') {
      return doAction(id, 'deliver').then(function (j) {
        if (j && j.ok && j.to === 'Order Processed') toast('Money is now with the rider. Settle it when the rider hands it over.');
      });
    }
    return doAction(id, action);
  }

  /* ---------------- Modals ---------------- */
  function modal(html, onReady, wide) {
    var m = document.createElement('div');
    m.className = 'pc-modal'; m.innerHTML = '<div class="pc-mbox' + (wide ? ' wide' : '') + '" role="dialog">' + html + '</div>';
    m.addEventListener('click', function (e) { if (e.target === m) m.remove(); });
    document.body.appendChild(m);
    var close = function () { m.remove(); };
    m.querySelectorAll('[data-close]').forEach(function (b) { b.onclick = close; });
    onReady(m, close);
    var f = m.querySelector('[autofocus]') || m.querySelector('input:not([type=checkbox]),select,textarea'); if (f) setTimeout(function () { f.focus(); }, 30);
    return m;
  }
  function busyBtn(b, on) { b.disabled = on; b.classList.toggle('is-busy', on); }

  function reasonModal(id, action) {
    var reasons = action === 'reject'
      ? ['Item out of stock', 'Address not serviceable', 'Hub closed now', 'Duplicate order', 'Customer asked to cancel']
      : ['Customer asked to cancel', 'Customer not reachable', 'Customer refused at door', 'Item out of stock', 'Quality issue'];
    modal('<div class="pc-mh"><h3>' + (action === 'reject' ? 'Reject' : 'Cancel') + ' order ' + esc(id) + '?</h3><p>Choose a reason. The customer sees this order as cancelled.' + (action === 'reject' ? ' If the address belongs to another hub, use "Move to another hub" instead.' : '') + '</p></div>' +
      '<div class="pc-mb"><div class="pc-chips">' + reasons.map(function (r) { return '<button type="button" class="pc-pick" data-r="' + esc(r) + '">' + esc(r) + '</button>'; }).join('') + '</div>' +
      '<textarea class="pc-input" id="pcReason" rows="2" maxlength="250" placeholder="Or type a reason" style="margin-top:10px"></textarea></div>' +
      '<div class="pc-mf"><button type="button" class="pc-btn lg" data-close>Back</button><button type="button" class="pc-btn lg bad" id="pcReasonGo">' + (action === 'reject' ? 'Reject order' : 'Cancel order') + '</button></div>',
      function (m, close) {
        var ta = m.querySelector('#pcReason');
        m.querySelectorAll('[data-r]').forEach(function (b) {
          b.onclick = function () { m.querySelectorAll('[data-r]').forEach(function (x) { x.classList.remove('is-on'); }); b.classList.add('is-on'); ta.value = b.getAttribute('data-r'); };
        });
        m.querySelector('#pcReasonGo').onclick = function () {
          if (!ta.value.trim()) { toast('Please choose or type a reason.', 'bad'); return; }
          doAction(id, action, { reason: ta.value.trim() }).then(function (j) { if (j && j.ok) { close(); if (action === 'reject') closeDrawer(); } });
        };
      });
  }

  /* Send an order out: smart auto-assign, or choose (or change) the rider by hand */
  function dispatchOrder(id) {
    var o0 = findOrder(id); if (o0 && o0.rider_id && o0.stage === 'accepted') return doAction(id, 'dispatch', { rider_id: o0.rider_id });
    if (!autoOn()) return riderModal(id);
    if (S.busy) return;
    S.busy = true;
    api('POST', orderUrl(id, '/action'), { action: 'dispatch', auto: true }).then(function (j) {
      S.busy = false;
      if (j && !j.ok && j.code === 'no_rider') { toast(j.message, 'bad'); return riderModal(id); }
      afterChange(j, id);
    }).catch(function () { S.busy = false; toast('Network problem. Please try again.', 'bad'); });
  }
  function riderModal(id, change) {
    var list = (S.riders || []).filter(function (r) { return r.id; });
    if (!list.length) {
      return modal('<div class="pc-mh"><h3>Out for delivery · ' + esc(id) + '</h3><p>No riders are set up for this hub. The order will be marked out for delivery without a rider.</p></div>' +
        '<div class="pc-mf"><button type="button" class="pc-btn lg" data-close>Back</button><button type="button" class="pc-btn lg pri" id="pcRiderGo">Send out</button></div>',
        function (m, close) { m.querySelector('#pcRiderGo').onclick = function () { doAction(id, 'dispatch').then(function (j) { if (j && j.ok) close(); }); }; });
    }
    modal('<div class="pc-mh"><h3>' + (change ? 'Change rider · ' : 'Choose rider · ') + esc(id) + '</h3><p>Delivery boys of this hub. The green one is the smart pick (free the longest, or back soonest).</p></div>' +
      '<div class="pc-mb">' + list.map(function (r) {
        var stt = RSTATE[r.state] || ['', ''];
        return '<label class="pc-check" style="' + (r.state === 'off' ? 'opacity:.55' : '') + '"><input type="radio" name="pcRid" value="' + r.id + '"' + (r.smart ? ' checked' : '') + (r.state === 'off' ? ' disabled' : '') + '>' +
          '<span><b>' + esc(r.name) + '</b>' + (r.smart ? ' <span class="pc-chip ok">Smart pick</span>' : '') + '<br><span class="pc-hint">' + esc(stt[0]) + ' · ' + esc(riderLine(r)) + (r.phone ? ' · ' + esc(r.phone) : '') + '</span></span>' +
          '<span class="a" style="font-weight:600;font-size:12px;color:var(--ink-3)">' + (r.delivered_today || 0) + ' today</span></label>';
      }).join('') + '</div>' +
      '<div class="pc-mf"><button type="button" class="pc-btn lg" data-close>Back</button><button type="button" class="pc-btn lg pri" id="pcRiderGo">' + (change ? 'Change rider' : 'Send out') + '</button></div>',
      function (m, close) {
        m.querySelector('#pcRiderGo').onclick = function () {
          var c = m.querySelector('input[name=pcRid]:checked');
          if (!c) { toast('Choose a rider.', 'bad'); return; }
          var b = this; busyBtn(b, true);
          var call = change ? api('POST', orderUrl(id, '/rider'), { rider_id: +c.value }) : api('POST', orderUrl(id, '/action'), { action: 'dispatch', rider_id: +c.value });
          call.then(function (j) { busyBtn(b, false); if (afterChange(j, id)) close(); }).catch(function () { busyBtn(b, false); toast('Network problem. Please try again.', 'bad'); });
        };
      });
  }

  function weighModal(id) {
    loadDetail(id).then(function (o) {
      var lines = o.lines || [];
      var w = lines.filter(function (l) { return l.weighable; });
      if (!w.length) {
        return modal('<div class="pc-mh"><h3>Nothing to weigh · ' + esc(id) + '</h3><p>All items in this order are sold per piece. You can send it out directly.</p></div>' +
          '<div class="pc-mf"><button type="button" class="pc-btn lg" data-close>Close</button><button type="button" class="pc-btn lg pri" id="pcW2R">Out for delivery</button></div>',
          function (m, close) { m.querySelector('#pcW2R').onclick = function () { close(); dispatchOrder(id); }; });
      }
      modal('<div class="pc-mh"><h3>Weigh items · ' + esc(id) + '</h3><p>Put each item on the scale and type the total weight in grams. The bill adjusts for any difference (same rule as before: pack price per gram).</p></div>' +
        '<div class="pc-mb"><table class="pc-wt"><thead><tr><th>Item</th><th class="r">Ordered</th><th class="r">Actual (g)</th><th class="r">Difference</th></tr></thead><tbody>' +
        lines.map(function (l) {
          if (!l.weighable) return '<tr><td><b>' + esc(l.name) + '</b><div class="nw">' + esc(l.unit) + ' × ' + (+l.qty) + '</div></td><td class="r nw" colspan="3">Sold per piece - no weighing</td></tr>';
          return '<tr><td><b>' + esc(l.name) + '</b><div class="nw">' + esc(l.unit) + ' × ' + (+l.qty) + ' · ' + esc(money(l.price)) + ' per pack</div></td>' +
            '<td class="r">' + esc(fmtG(l.expected_g)) + '</td>' +
            '<td class="r"><input class="pc-input" type="number" inputmode="decimal" min="1" step="1" data-row="' + l.row_id + '" data-exp="' + l.expected_g + '" data-pack="' + l.pack_g + '" data-price="' + l.price + '" value="' + (l.actual_g != null ? +l.actual_g : '') + '"></td>' +
            '<td class="r"><span class="pc-diff zero" data-d="' + l.row_id + '">–</span></td></tr>';
        }).join('') + '</tbody></table>' +
        '<div style="margin-top:12px"><div class="pc-sumrow"><span>Bill</span><b>' + esc(money(o.bill)) + '</b></div>' +
        '<div class="pc-sumrow"><span>Weight adjustment</span><b id="pcWAdj">–</b></div>' +
        '<div class="pc-sumrow big"><span id="pcWLbl">' + (o.prepaid ? 'Collect / refund on delivery' : 'Customer pays') + '</span><span id="pcWTot">–</span></div></div></div>' +
        '<div class="pc-mf"><button type="button" class="pc-btn lg" id="pcWExact">Weights exact</button><button type="button" class="pc-btn lg pri" id="pcWGo">Save weights</button><button type="button" class="pc-btn lg ok" id="pcWGoOut">Save and send out</button></div>',
        function (m, close) {
          var inputs = [].slice.call(m.querySelectorAll('input[data-row]'));
          function calc() {
            var total = 0, missing = 0;
            inputs.forEach(function (i) {
              var v = parseFloat(i.value), d = m.querySelector('[data-d="' + i.getAttribute('data-row') + '"]');
              if (isNaN(v) || v <= 0) { missing++; d.textContent = '–'; d.className = 'pc-diff zero'; return; }
              var pack = +i.getAttribute('data-pack'), price = +i.getAttribute('data-price'), exp = +i.getAttribute('data-exp');
              var amt = r2((v - exp) * (price / pack));
              total += amt;
              d.textContent = (v - exp > 0 ? '+' : '') + Math.round(v - exp) + ' g · ' + signed(amt);
              d.className = 'pc-diff ' + (amt > 0 ? 'up' : (amt < 0 ? 'down' : 'zero'));
              i.style.borderColor = (v > exp * 1.5 || v < exp * 0.5) ? 'var(--bad)' : '';
            });
            total = r2(total);
            m.querySelector('#pcWAdj').textContent = missing ? '–' : signed(total);
            var pay = o.prepaid ? total : r2(o.bill + total);
            m.querySelector('#pcWTot').textContent = missing ? 'enter all weights' : (o.prepaid ? (pay < 0 ? 'Refund ' + money(-pay) : (pay > 0 ? 'Collect ' + money(pay) : 'Nothing')) : money(pay));
            return missing;
          }
          inputs.forEach(function (i, k) {
            i.addEventListener('input', calc);
            i.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); (inputs[k + 1] || m.querySelector('#pcWGo')).focus(); } });
          });
          m.querySelector('#pcWExact').onclick = function () { inputs.forEach(function (i) { i.value = Math.round(+i.getAttribute('data-exp')); }); calc(); };
          function save(thenOut, btn) {
            if (calc()) { toast('Enter the weight for every item.', 'bad'); return; }
            var weights = {};
            inputs.forEach(function (i) { weights[i.getAttribute('data-row')] = parseFloat(i.value); });
            busyBtn(btn, true);
            api('POST', orderUrl(id, '/weigh'), { weights: weights }).then(function (j) {
              busyBtn(btn, false);
              if (!afterChange(j, id)) return;
              close();
              if (thenOut) dispatchOrder(id);
            }).catch(function () { busyBtn(btn, false); toast('Network problem. Please try again.', 'bad'); });
          }
          m.querySelector('#pcWGo').onclick = function () { save(false, this); };
          m.querySelector('#pcWGoOut').onclick = function () { save(true, this); };
          calc();
          if (inputs[0]) inputs[0].setAttribute('autofocus', '');
        }, true);
    }).catch(function (e) { toast(e.message, 'bad'); });
  }

  function relocateModal(id) {
    api('GET', C.hubs).then(function (j) {
      var hubs = (j && j.hubs) || [];
      if (!hubs.length) { toast('No other active hub to move to.', 'bad'); return; }
      var groups = {};
      hubs.forEach(function (h) { var k = h.city || 'Other'; (groups[k] = groups[k] || []).push(h); });
      modal('<div class="pc-mh"><h3>Move ' + esc(id) + ' to another hub</h3><p>Use this when the address is outside your delivery area. The other hub gets it as a new order, with sound.</p></div>' +
        '<div class="pc-mb"><label class="pc-hint" for="pcHub">Hub</label><select class="pc-input" id="pcHub"><option value="">Choose a hub</option>' +
        Object.keys(groups).map(function (city) {
          return '<optgroup label="' + esc(city) + (groups[city][0].same_city ? ' (your city)' : '') + '">' + groups[city].map(function (h) { return '<option value="' + h.id + '">' + esc(h.name) + '</option>'; }).join('') + '</optgroup>';
        }).join('') + '</select>' +
        '<label class="pc-hint" for="pcHubNote" style="display:block;margin-top:10px">Note for the other hub (optional)</label><input class="pc-input" id="pcHubNote" maxlength="200" placeholder="e.g. Address is in their area"></div>' +
        '<div class="pc-mf"><button type="button" class="pc-btn lg" data-close>Back</button><button type="button" class="pc-btn lg pri" id="pcHubGo">Move order</button></div>',
        function (m, close) {
          m.querySelector('#pcHubGo').onclick = function () {
            var hub = +m.querySelector('#pcHub').value;
            if (!hub) { toast('Choose a hub.', 'bad'); return; }
            var b = this; busyBtn(b, true);
            api('POST', orderUrl(id, '/relocate'), { hub_id: hub, note: m.querySelector('#pcHubNote').value.trim() }).then(function (r) {
              busyBtn(b, false);
              if (afterChange(r, id)) { close(); closeDrawer(); }
            }).catch(function () { busyBtn(b, false); toast('Network problem. Please try again.', 'bad'); });
          };
        });
    });
  }

  /* Settle one order (counter) or many (rider hands over). */
  function settleModal(ids, rider) {
    var list = S.orders.filter(function (o) { return ids.indexOf(o.order_id) >= 0 || (rider !== null && rider !== undefined && o.stage === 'unsettled' && (+o.rider_id || 0) === +rider); });
    if (!list.length && S.detail && ids.indexOf(S.detail.order_id) >= 0) list = [S.detail];
    if (!list.length) { toast('Nothing to settle.', 'bad'); return; }
    var riderObj = rider !== null && rider !== undefined ? S.riders.filter(function (r) { return +r.id === +rider; })[0] : null;
    var title = riderObj ? 'Collect money from ' + riderObj.name : 'Settle payment · ' + list[0].order_id;
    modal('<div class="pc-mh"><h3>' + esc(title) + '</h3><p>' + (riderObj ? 'Tick the orders the rider is paying for, then enter what you received.' : 'Enter what was received for this order.') + '</p></div>' +
      '<div class="pc-mb"><div class="pc-box" style="padding:4px 12px">' + list.map(function (o) {
        return '<label class="pc-check"><input type="checkbox" data-o="' + esc(o.order_id) + '" data-exp="' + o.expected + '" checked' + (list.length === 1 ? ' disabled' : '') + '>' +
          '<span><b>' + esc(o.order_id) + '</b> · ' + esc(o.customer) + '<br><span class="pc-hint">' + esc(o.payment_type === 'Cash' ? 'COD' : o.payment_type) + (o.adjustment ? ' · weight ' + signed(o.adjustment) : '') + (o.since && o.stage === 'unsettled' ? ' · delivered ' + ago(o.since) : '') + '</span></span>' +
          '<span class="a">' + esc(money(o.expected)) + '</span></label>';
      }).join('') + '</div>' +
      '<div class="pc-sumrow big"><span>Expected</span><span id="pcExp">–</span></div>' +
      '<div class="pc-pay3" style="margin-top:8px"><div><label for="pcCash">Cash</label><input class="pc-input" id="pcCash" type="number" step="0.01" inputmode="decimal" autofocus></div>' +
      '<div><label for="pcUpi">UPI</label><input class="pc-input" id="pcUpi" type="number" min="0" step="0.01" inputmode="decimal" value="0"></div>' +
      '<div><label for="pcCard">Card</label><input class="pc-input" id="pcCard" type="number" min="0" step="0.01" inputmode="decimal" value="0"></div></div>' +
      '<div class="pc-chips" style="margin-top:8px"><button type="button" class="pc-pick" data-all="cash">All cash</button><button type="button" class="pc-pick" data-all="upi">All UPI</button></div>' +
      '<div class="pc-change" id="pcDiff" style="margin-top:8px"></div>' +
      '<input class="pc-input" id="pcRef" maxlength="120" placeholder="UPI / card reference (optional)" style="margin-top:8px">' +
      '<textarea class="pc-input" id="pcNote" rows="2" maxlength="250" placeholder="Note (required if short or excess)" style="margin-top:8px"></textarea></div>' +
      '<div class="pc-mf"><button type="button" class="pc-btn lg" data-close>Later</button><button type="button" class="pc-btn lg ok" id="pcSettleGo">Mark as settled</button></div>',
      function (m, close) {
        var cash = m.querySelector('#pcCash'), upi = m.querySelector('#pcUpi'), card = m.querySelector('#pcCard'), out = m.querySelector('#pcDiff');
        var boxes = [].slice.call(m.querySelectorAll('input[data-o]'));
        function expected() { return r2(boxes.filter(function (b) { return b.checked; }).reduce(function (s, b) { return s + (+b.getAttribute('data-exp')); }, 0)); }
        function calc() {
          var e = expected();
          m.querySelector('#pcExp').textContent = e < 0 ? 'Refund ' + money(-e) : money(e);
          var rec = r2((parseFloat(cash.value) || 0) + (parseFloat(upi.value) || 0) + (parseFloat(card.value) || 0));
          var d = r2(rec - e);
          out.className = 'pc-change' + (d < 0 ? ' is-bad' : '');
          out.textContent = cash.value === '' && !(+upi.value) && !(+card.value) ? '' : (d === 0 ? 'Matches exactly' : (d < 0 ? 'Short by ' + money(-d) : 'Excess ' + money(d)));
        }
        boxes.forEach(function (b) { b.onchange = function () { cash.value = String(Math.max(expected(), 0) - (parseFloat(upi.value) || 0) - (parseFloat(card.value) || 0)); calc(); }; });
        [cash, upi, card].forEach(function (i) { i.addEventListener('input', calc); });
        m.querySelectorAll('[data-all]').forEach(function (b) {
          b.onclick = function () { var e = expected(); if (b.getAttribute('data-all') === 'cash') { cash.value = e; upi.value = 0; } else { upi.value = Math.max(e, 0); cash.value = e < 0 ? e : 0; } card.value = 0; calc(); };
        });
        cash.value = expected();
        calc();
        m.querySelector('#pcSettleGo').onclick = function () {
          var chosen = boxes.filter(function (b) { return b.checked; }).map(function (b) { return b.getAttribute('data-o'); });
          if (!chosen.length) { toast('Tick at least one order.', 'bad'); return; }
          var b = this; busyBtn(b, true);
          api('POST', C.settle, {
            orders: chosen, rider_id: riderObj && riderObj.id ? riderObj.id : null,
            cash: parseFloat(cash.value) || 0, upi: parseFloat(upi.value) || 0, card: parseFloat(card.value) || 0,
            ref: m.querySelector('#pcRef').value.trim(), note: m.querySelector('#pcNote').value.trim()
          }).then(function (r) {
            busyBtn(b, false);
            if (afterChange(r)) close();
          }).catch(function () { busyBtn(b, false); toast('Network problem. Please try again.', 'bad'); });
        };
      }, list.length > 1);
  }

  function closeStaleModal() {
    var n = S.orders.filter(function (o) { return o.stale && o.stage !== 'unsettled'; }).length;
    modal('<div class="pc-mh"><h3>Cancel ' + n + ' old open order(s)?</h3><p>Only orders that never reached the customer (new, accepted or out) are cancelled. Old delivered-but-unsettled orders stay so their money can still be settled. This is recorded and cannot be undone here.</p></div>' +
      '<div class="pc-mb"><textarea class="pc-input" id="pcStaleReason" rows="2" maxlength="250">Old open order closed in cleanup</textarea></div>' +
      '<div class="pc-mf"><button type="button" class="pc-btn" data-close>Keep them</button><button type="button" class="pc-btn bad" id="pcStaleGo">Cancel them</button></div>',
      function (m, close) {
        m.querySelector('#pcStaleGo').onclick = function () {
          var b = this; busyBtn(b, true);
          api('POST', C.staleClose, { confirm: 'yes', reason: m.querySelector('#pcStaleReason').value.trim() }).then(function (j) {
            busyBtn(b, false);
            if (afterChange(j)) { close(); setTab('board'); }
          }).catch(function () { busyBtn(b, false); toast('Network problem. Please try again.', 'bad'); });
        };
      });
  }

  /* ---------------- Search ---------------- */
  var sTimer = null, results = [];
  function runSearch(q) {
    var box = $('pcResults');
    if (q.trim().length < 3) { box.hidden = true; return; }
    api('GET', C.search + '?q=' + encodeURIComponent(q.trim())).then(function (j) {
      results = (j && j.orders) || [];
      box.innerHTML = results.length ? results.map(function (o, i) {
        return '<button type="button" data-open="' + esc(o.order_id) + '"' + (i === 0 ? ' class="is-hl"' : '') + '><b>' + esc(o.order_id) + '</b><span style="color:var(--ink-3)">' + esc(o.customer) + ' · ' + esc(o.phone) + '</span><span style="margin-left:auto" class="pc-chip">' + esc(label(o.board === 'ready' ? 'ready' : o.stage)) + '</span></button>';
      }).join('') : '<div class="empty">No orders found for "' + esc(q) + '" in this hub.</div>';
      box.hidden = false;
    });
  }
  $('pcSearch').addEventListener('input', function (e) { clearTimeout(sTimer); var q = e.target.value; sTimer = setTimeout(function () { runSearch(q); }, 250); });
  $('pcSearch').addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && results[0]) { $('pcResults').hidden = true; openOrder(results[0].order_id); }
    if (e.key === 'Escape') { e.target.value = ''; $('pcResults').hidden = true; e.target.blur(); }
  });

  /* ---------------- Events ---------------- */
  function setTab(tab) {
    S.tab = tab; if (tab === 'riders') loadAsg();
    if (tab === 'history') S.hist.rows = null;
    document.querySelectorAll('.pc-tab[data-tab]').forEach(function (b) { b.classList.toggle('is-on', b.getAttribute('data-tab') === S.tab); });
    store('ff-pos-tab', tab === 'stale' ? 'board' : tab);
    render();
  }
  document.addEventListener('click', function (e) {
    var t = e.target.closest ? e.target : null; if (!t) return;
    var act = t.closest('[data-act]');
    if (act) { e.stopPropagation(); startAction(act.getAttribute('data-id'), act.getAttribute('data-act')); return; }
    var rs = t.closest('[data-rs]');
    if (rs) { riderStatus(rs.getAttribute('data-rid'), rs.getAttribute('data-rs')); return; }
    if (t.id === 'pcAutoToggle') { store('ff-pos-auto', t.checked ? 'on' : 'off'); toast(t.checked ? 'Smart auto-assign is ON: orders go to the best free rider.' : 'Smart auto-assign is OFF: you choose the rider for each order.'); return; }
    var col = t.closest('[data-collect]');
    if (col) { settleModal([], +col.getAttribute('data-collect')); return; }
    var op = t.closest('[data-open]');
    if (op) { $('pcResults').hidden = true; openOrder(op.getAttribute('data-open')); return; }
    var tab = t.closest('[data-tab]');
    if (tab) { setTab(tab.getAttribute('data-tab')); return; }
    if (t.closest('#pcCloseStale')) { closeStaleModal(); return; }
    if (!t.closest('.pc-search') && !t.closest('.pc-results')) $('pcResults').hidden = true;
  });
  document.addEventListener('keydown', function (e) {
    var typing = /INPUT|TEXTAREA|SELECT/.test((e.target && e.target.tagName) || '');
    if (e.key === 'Escape') {
      var m = document.querySelector('.pc-modal'); if (m) { m.remove(); return; }
      if (S.openId) { closeDrawer(); return; }
    }
    if (e.key === '/' && !typing) { e.preventDefault(); $('pcSearch').focus(); }
  });
  $('pcSound').onclick = function () { setSound(!S.sound); if (S.sound) { S.audioOk = true; audio(); chime(1); } };
  $('pcTheme').onclick = function () {
    var cur = root.getAttribute('data-theme') || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    var next = cur === 'dark' ? 'light' : 'dark'; root.setAttribute('data-theme', next); store('ff-theme', next);
  };
  $('pcAlertOpen').onclick = function () {
    var o = S.orders.filter(isNewLive).sort(function (a, b) { return (a.placed_at || 0) - (b.placed_at || 0); })[0];
    if (o) openOrder(o.order_id);
  };
  $('pcAlertSnooze').onclick = function () { S.snoozeUntil = Date.now() + 120000; toast('Sound paused for 2 minutes. New arrivals still ring.'); };
  $('pcRetry').onclick = function () { poll(true); };
  if ('Notification' in window && Notification.permission === 'default') {
    $('pcNotify').hidden = false;
    $('pcNotify').onclick = function () { Notification.requestPermission().then(function () { $('pcNotify').hidden = Notification.permission !== 'default'; }); };
  }
  document.addEventListener('visibilitychange', function () { if (!document.hidden) poll(); });

  /* ---------------- Start ---------------- */
  setSound(read('ff-pos-sound') !== 'off');
  var savedTab = read('ff-pos-tab');
  if (savedTab && ['board', 'sla', 'riders', 'later', 'done', 'history'].indexOf(savedTab) >= 0 && savedTab !== 'board') {
    S.tab = savedTab;
    document.querySelectorAll('.pc-tab[data-tab]').forEach(function (b) { b.classList.toggle('is-on', b.getAttribute('data-tab') === S.tab); });
  }
  function start(withSound) {
    $('pcGate').hidden = true;
    S.audioOk = !!withSound;
    if (withSound) { setSound(true); audio(); chime(1); } else { setSound(false); }
    var want = (location.search.match(/[?&]open=([A-Za-z0-9_\-]+)/) || [])[1];
    if (want) { try { history.replaceState(null, '', location.pathname + location.hash); } catch (e) {} openOrder(want); }
  }
  $('pcGateGo').onclick = function () { start(true); };
  $('pcGateMute').onclick = function () { start(false); };
  $('pcGate').hidden = false;
  poll(true);
})();

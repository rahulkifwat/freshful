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
    $('tnRiders').textContent = S.riders.filter(function (r) { return r.unsettled > 0; }).length;
    $('tnLater').textContent = c.scheduled_later || 0;
    $('tnDone').textContent = (c.delivered || 0) + (c.cancelled || 0);
    $('tnStale').textContent = c.stale || 0;
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
      (p ? '<button type="button" class="pc-btn block ' + p[2] + '" data-act="' + p[0] + '" data-id="' + esc(o.order_id) + '">' + p[1] + '</button>' : '') +
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
  function renderRiders() {
    var list = S.riders || [];
    if (!list.length) return '<div class="pc-list"><div class="pc-empty" style="padding:40px">No riders are set up for this hub yet.</div></div>';
    var total = list.reduce(function (s, r) { return s + (r.cash_due || 0); }, 0);
    return '<div class="pc-banner" style="margin-bottom:14px;background:var(--surface);border:1px solid var(--line)">Money still with riders: <b style="margin-left:6px">' + esc(money(total)) + '</b><span class="sp"></span><span class="pc-hint">When a rider hands over the money, press "Collect money" on their card.</span></div>' +
      '<div class="pc-riders">' + list.map(function (r) {
        return '<div class="pc-rider' + (r.cash_due ? ' is-due' : '') + '"><div class="h"><span class="pc-avatar" aria-hidden="true">' + esc((r.name || 'R').charAt(0).toUpperCase()) + '</span><div><b>' + esc(r.name) + '</b><small>' + esc([r.code, r.phone].filter(Boolean).join(' · ')) + '</small></div>' +
          (r.phone ? '<a class="pc-btn sm" style="margin-left:auto" href="tel:' + esc(r.phone) + '">Call</a>' : '') + '</div>' +
          '<div class="st"><div><small>Out now</small><b>' + r.out + '</b></div><div><small>To settle</small><b>' + r.unsettled + '</b></div><div><small>Amount</small><b>' + esc(money(r.cash_due)) + '</b></div></div>' +
          (r.oldest && r.unsettled ? '<div class="pc-hint">Oldest unsettled: ' + esc(ago(r.oldest)) + '</div>' : '') +
          '<button type="button" class="pc-btn ' + (r.unsettled ? 'ok' : '') + ' block" data-collect="' + r.id + '"' + (r.unsettled ? '' : ' disabled') + '>Collect money</button></div>';
      }).join('') + '</div>';
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
      '<div><small>Slot</small><b>' + esc(o.slot || (o.delivery_type === 'Express' ? 'Within 90 min' : '–')) + '</b></div>' +
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
    var order = ['accept', 'weigh', 'dispatch', 'deliver', 'settle', 'relocate', 'reject', 'cancel'];
    var acts = (o.actions || []).slice().sort(function (a, b) { return order.indexOf(a) - order.indexOf(b); });
    if (o.board === 'ready') acts = acts.filter(function (a) { return a !== 'weigh'; }).concat(['weigh']);   // re-weigh as a secondary action
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
    if (action === 'dispatch') return riderModal(id);
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

  function riderModal(id) {
    var go = function (riders) {
      if (!riders.length) {
        return modal('<div class="pc-mh"><h3>Out for delivery · ' + esc(id) + '</h3><p>No riders are set up for this hub. The order will be marked out for delivery without a rider.</p></div>' +
          '<div class="pc-mf"><button type="button" class="pc-btn lg" data-close>Back</button><button type="button" class="pc-btn lg pri" id="pcRiderGo">Send out</button></div>',
          function (m, close) { m.querySelector('#pcRiderGo').onclick = function () { doAction(id, 'dispatch').then(function (j) { if (j && j.ok) close(); }); }; });
      }
      var busy = {};
      S.riders.forEach(function (r) { busy[r.id] = r.out; });
      modal('<div class="pc-mh"><h3>Out for delivery · ' + esc(id) + '</h3><p>Who is taking this order?</p></div>' +
        '<div class="pc-mb"><div class="pc-chips" id="pcRiders">' + riders.map(function (r) {
          return '<button type="button" class="pc-pick" data-rid="' + r.id + '">' + esc(r.name) + (busy[r.id] ? ' · ' + busy[r.id] + ' out' : ' · free') + '</button>';
        }).join('') + '</div></div>' +
        '<div class="pc-mf"><button type="button" class="pc-btn lg" data-close>Back</button><button type="button" class="pc-btn lg pri" id="pcRiderGo" disabled>Send out</button></div>',
        function (m, close) {
          var chosen = null, goBtn = m.querySelector('#pcRiderGo');
          m.querySelectorAll('[data-rid]').forEach(function (b) {
            b.onclick = function () { m.querySelectorAll('[data-rid]').forEach(function (x) { x.classList.remove('is-on'); }); b.classList.add('is-on'); chosen = +b.getAttribute('data-rid'); goBtn.disabled = false; };
          });
          goBtn.onclick = function () { if (!chosen) return; doAction(id, 'dispatch', { rider_id: chosen }).then(function (j) { if (j && j.ok) close(); }); };
        });
    };
    if (S.detail && S.detail.order_id === id && S.detail.riders) go(S.detail.riders);
    else loadDetail(id).then(function (o) { go(o.riders || []); }).catch(function (e) { toast(e.message, 'bad'); });
  }

  function weighModal(id) {
    loadDetail(id).then(function (o) {
      var lines = o.lines || [];
      var w = lines.filter(function (l) { return l.weighable; });
      if (!w.length) {
        return modal('<div class="pc-mh"><h3>Nothing to weigh · ' + esc(id) + '</h3><p>All items in this order are sold per piece. You can send it out directly.</p></div>' +
          '<div class="pc-mf"><button type="button" class="pc-btn lg" data-close>Close</button><button type="button" class="pc-btn lg pri" id="pcW2R">Out for delivery</button></div>',
          function (m, close) { m.querySelector('#pcW2R').onclick = function () { close(); riderModal(id); }; });
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
              if (thenOut) riderModal(id);
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
    S.tab = tab;
    if (tab === 'history') S.hist.rows = null;
    document.querySelectorAll('.pc-tab[data-tab]').forEach(function (b) { b.classList.toggle('is-on', b.getAttribute('data-tab') === S.tab); });
    store('ff-pos-tab', tab === 'stale' ? 'board' : tab);
    render();
  }
  document.addEventListener('click', function (e) {
    var t = e.target.closest ? e.target : null; if (!t) return;
    var act = t.closest('[data-act]');
    if (act) { e.stopPropagation(); startAction(act.getAttribute('data-id'), act.getAttribute('data-act')); return; }
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
  if (savedTab && ['board', 'riders', 'later', 'done', 'history'].indexOf(savedTab) >= 0 && savedTab !== 'board') {
    S.tab = savedTab;
    document.querySelectorAll('.pc-tab[data-tab]').forEach(function (b) { b.classList.toggle('is-on', b.getAttribute('data-tab') === S.tab); });
  }
  function start(withSound) {
    $('pcGate').hidden = true;
    S.audioOk = !!withSound;
    if (withSound) { setSound(true); audio(); chime(1); } else { setSound(false); }
  }
  $('pcGateGo').onclick = function () { start(true); };
  $('pcGateMute').onclick = function () { start(false); };
  $('pcGate').hidden = false;
  poll(true);
})();

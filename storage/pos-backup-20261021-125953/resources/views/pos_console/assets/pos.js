/* Freshful POS console - live board, new-order sound, actions, settlement (no libraries) */
(function () {
  'use strict';
  var C = window.POSC || {};
  var root = document.documentElement;
  var $ = function (id) { return document.getElementById(id); };
  var S = {
    orders: [], counts: {}, kpis: {}, version: '', offset: 0, tab: 'board',
    known: null, fresh: {}, sound: true, audioOk: false, snoozeUntil: 0,
    fails: 0, openId: null, detail: null, busy: false, lastOk: 0
  };
  var STAGE = { 'new': ['New', '--c-new'], accepted: ['Accepted', '--c-acc'], packing: ['Packing', '--c-pack'], out: ['Out for delivery', '--c-out'] };
  var BASE_TITLE = document.title;

  function store(k, v) { try { localStorage.setItem(k, v); } catch (e) {} }
  function read(k) { try { return localStorage.getItem(k); } catch (e) { return null; } }
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function money(n) { n = Number(n || 0); return '₹' + n.toLocaleString('en-IN', { minimumFractionDigits: n % 1 ? 2 : 0, maximumFractionDigits: 2 }); }
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
  function toast(msg, kind) {
    var t = document.createElement('div');
    t.className = 'pc-toast' + (kind ? ' ' + kind : '');
    t.textContent = msg;
    $('pcToasts').appendChild(t);
    setTimeout(function () { t.remove(); }, kind === 'bad' ? 6000 : 3500);
  }

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
  function isNewLive(o) { return o.stage === 'new' && !o.stale; }
  function countNew() { return S.orders.filter(isNewLive).length; }
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

  /* ---------------- Live feed ---------------- */
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
      S.orders = j.orders; S.counts = j.counts; S.kpis = j.kpis; S.version = j.version;
      if (changed) render(); else { renderKpis(); tick(); }
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
    var k = S.kpis || {}, c = S.counts || {};
    $('pcKpis').innerHTML =
      kpi('Orders today', esc(k.orders_today || 0), (k.cancelled_today || 0) + ' cancelled') +
      kpi('Sales today', esc(money(k.sales_today)), 'excluding cancelled') +
      kpi('Delivered today', esc(k.delivered_today || 0), k.avg_delivery_min != null ? 'avg ' + k.avg_delivery_min + ' min from order' : 'no deliveries yet') +
      kpi('Avg packing time', k.avg_pack_min != null ? esc(k.avg_pack_min) + ' min' : '–', 'accept → out for delivery') +
      kpi('Late now', esc(c.late || 0), 'past their delivery time', (c.late || 0) > 0 ? 'is-bad' : '') +
      kpi('To settle', esc(money(k.unsettled_amount)), (k.unsettled_count || 0) + ' delivered, not marked paid', (k.unsettled_count || 0) > 0 ? 'is-warn' : '');
    var boardN = S.orders.filter(onBoard).length;
    $('tnBoard').textContent = boardN;
    $('tnBoard').classList.toggle('is-alert', countNew() > 0);
    $('tnUnsettled').textContent = c.unsettled || 0;
    $('tnLater').textContent = c.scheduled_later || 0;
    $('tnDone').textContent = (c.delivered || 0) + (c.cancelled || 0);
    $('tnStale').textContent = c.stale || 0;
    $('tabStale').hidden = !(c.stale > 0) && S.tab !== 'stale';
  }
  function onBoard(o) { return !o.stale && (o.stage === 'new' || (['accepted', 'packing', 'out'].indexOf(o.stage) >= 0 && !o.later)); }

  function dueChip(o) {
    if (!o.due_at || ['delivered', 'cancelled'].indexOf(o.stage) >= 0) return '';
    var left = o.due_at - now();
    var cls = left < 0 ? 'bad' : (left < 1800 ? 'warn' : 'ok');
    var txt = left < 0 ? 'Late ' + dur(left) : 'Due in ' + dur(left);
    return '<span class="pc-chip ' + cls + '" data-due="' + o.due_at + '">' + txt + '</span>';
  }
  function payChip(o) {
    if (o.paid) return '<span class="pc-chip ok">Paid</span>';
    var t = (o.payment_type || 'Cash');
    return '<span class="pc-chip ' + (t === 'Cash' ? 'warn' : 'info') + '">' + esc(t === 'Cash' ? 'COD' : t) + '</span>';
  }
  function typeChip(o) {
    if ((o.delivery_type || '').toLowerCase() === 'scheduled') {
      var d = o.schedule_date ? new Date(o.schedule_date + 'T00:00:00') : null;
      var when = d && !isNaN(d) ? d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short' }) : 'date not set';
      return '<span class="pc-chip">Scheduled ' + esc(when + (o.slot ? ' · ' + o.slot : '')) + '</span>';
    }
    return '<span class="pc-chip brand">Express</span>';
  }
  var PRIMARY = { accept: ['Accept order', 'pri'], pack: ['Start packing', ''], dispatch: ['Out for delivery', ''], deliver: ['Mark delivered', 'ok'] };
  function card(o, color) {
    var act = (o.actions || []).filter(function (a) { return PRIMARY[a]; })[0];
    return '<article class="pc-card' + (S.fresh[o.order_id] ? ' is-fresh' : '') + '" style="--c:var(' + color + ')" data-open="' + esc(o.order_id) + '">' +
      '<div class="pc-card-t"><b>' + esc(o.order_id) + '</b>' + dueChip(o) + '<span class="ago" data-ago="' + (o.placed_at || '') + '">' + esc(ago(o.placed_at)) + '</span></div>' +
      '<div class="who">' + esc(o.customer) + '</div>' +
      (o.area ? '<div class="where">' + esc(o.area) + '</div>' : '') +
      '<div class="row">' + typeChip(o) + payChip(o) + (o.rider ? '<span class="pc-chip">' + esc(o.rider) + '</span>' : '') +
      '<span class="amt">' + esc(money(o.amount)) + '</span></div>' +
      '<div class="row" style="color:var(--ink-3);font-size:12px">' + o.items + (o.items === 1 ? ' item' : ' items') + '</div>' +
      (act ? '<button type="button" class="pc-btn block ' + PRIMARY[act][1] + '" data-act="' + act + '" data-id="' + esc(o.order_id) + '">' + PRIMARY[act][0] + '</button>' : '') +
      '</article>';
  }
  function renderBoard() {
    var html = '<div class="pc-board">';
    ['new', 'accepted', 'packing', 'out'].forEach(function (st) {
      var list = S.orders.filter(function (o) { return o.stage === st && onBoard(o); })
        .sort(function (a, b) { return (a.due_at || 9e12) - (b.due_at || 9e12); });
      html += '<section class="pc-col"><div class="pc-col-h" style="--c:var(' + STAGE[st][1] + ')"><i></i>' + STAGE[st][0] + '<span class="n">' + list.length + '</span></div><div class="pc-col-b">' +
        (list.length ? list.map(function (o) { return card(o, STAGE[st][1]); }).join('') :
          '<div class="pc-empty">' + (st === 'new' ? 'No new orders. New orders appear here with a sound.' : 'Nothing here right now.') + '</div>') +
        '</div></section>';
    });
    return html + '</div>';
  }
  function renderList(list, empty) {
    if (!list.length) return '<div class="pc-list"><div class="pc-empty" style="padding:40px">' + esc(empty) + '</div></div>';
    return '<div class="pc-list"><table class="pc-table"><thead><tr><th>Order</th><th>Customer</th><th class="hide-sm">Placed</th><th>Status</th><th class="hide-sm">Payment</th><th class="r">Amount</th></tr></thead><tbody>' +
      list.map(function (o) {
        var lbl = (C.labels && C.labels[o.stage]) || o.status;
        var cls = o.stage === 'cancelled' ? 'bad' : (o.stage === 'delivered' ? 'ok' : 'info');
        return '<tr data-open="' + esc(o.order_id) + '"><td><b>' + esc(o.order_id) + '</b><div style="font-size:12px;color:var(--ink-3)">' + (o.items) + ' item(s)</div></td>' +
          '<td>' + esc(o.customer) + '<div style="font-size:12px;color:var(--ink-3)">' + esc(o.phone) + '</div></td>' +
          '<td class="hide-sm">' + esc(clock(o.placed_at)) + '</td>' +
          '<td><span class="pc-chip ' + cls + '">' + esc(lbl) + '</span> ' + dueChip(o) + '</td>' +
          '<td class="hide-sm">' + payChip(o) + '</td>' +
          '<td class="r"><b>' + esc(money(o.amount)) + '</b></td></tr>';
      }).join('') + '</tbody></table></div>';
  }
  function render() {
    renderKpis();
    var v = $('pcView'), html = '';
    if (S.tab === 'board') {
      var st = (S.counts && S.counts.stale) || 0;
      html = (st ? '<div class="pc-banner is-warn" style="margin-bottom:14px">' + st + ' old open order(s) from earlier days are kept off the live board and do not ring.<span class="sp"></span><button type="button" data-tab="stale">Review</button></div>' : '') + renderBoard();
    } else if (S.tab === 'stale') {
      var old = S.orders.filter(function (o) { return o.stale; }).sort(function (a, b) { return (a.placed_at || 0) - (b.placed_at || 0); });
      html = (old.length ? '<div class="pc-banner is-warn" style="margin-bottom:14px">These orders were due more than 3 days ago and are still open. Open one to mark it delivered, or close them all as cancelled.<span class="sp"></span><button type="button" id="pcCloseStale">Close all ' + old.length + ' as cancelled</button></div>' : '') +
        renderList(old, 'No old open orders. Everything is up to date.');
    }
    else if (S.tab === 'unsettled') html = renderList(S.orders.filter(function (o) { return o.unsettled; }), 'Every delivered order is settled.');
    else if (S.tab === 'later') html = renderList(S.orders.filter(function (o) { return o.later && o.stage !== 'new'; }), 'No orders scheduled for later days.');
    else html = renderList(S.orders.filter(function (o) { return o.stage === 'delivered' || o.stage === 'cancelled'; }), 'Nothing delivered or cancelled today yet.');
    v.innerHTML = html;
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
  function refreshDrawer() {
    var id = S.openId; if (!id) return;
    api('GET', C.order + '/' + encodeURIComponent(id)).then(function (j) {
      if (S.openId !== id) return;
      if (!j.ok) { toast(j.message || 'Could not load order', 'bad'); closeDrawer(); return; }
      S.detail = j.order; drawDrawer(j.order);
    }).catch(function () {});
  }
  var ACT_BTN = {
    accept: ['Accept order', 'pri'], pack: ['Start packing', 'pri'], dispatch: ['Out for delivery', 'pri'],
    deliver: ['Mark delivered', 'ok'], reject: ['Reject', 'bad'], cancel: ['Cancel order', 'bad']
  };
  function drawDrawer(o) {
    var dr = document.querySelector('.pc-drawer'); if (!dr) return;
    var lbl = (C.labels && C.labels[o.stage]) || o.status;
    var phone = (o.phone || '').replace(/\D/g, '');
    var wa = phone ? 'https://wa.me/' + (phone.length === 10 ? '91' + phone : phone) + '?text=' + encodeURIComponent('Hello ' + o.customer + ', this is Freshful about your order ' + o.order_id + '.') : null;
    dr.querySelector('.pc-dh').innerHTML =
      '<div><h2>' + esc(o.order_id) + '</h2><p>' + esc(lbl) + ' · placed ' + esc(clock(o.placed_at)) + '</p>' +
      '<div class="row" style="display:flex;gap:6px;margin-top:8px;flex-wrap:wrap">' + typeChip(o) + payChip(o) + dueChip(o) + '</div></div>' +
      '<button type="button" class="x" aria-label="Close">✕</button>';
    dr.querySelector('.x').onclick = closeDrawer;

    var items = (o.items || []).map(function (it) {
      return '<div class="pc-item">' + (it.image ? '<img src="' + esc(it.image) + '" alt="" loading="lazy" onerror="this.replaceWith(Object.assign(document.createElement(\'div\'),{className:\'ph\'}))">' : '<div class="ph"></div>') +
        '<div class="nm"><b>' + esc(it.name) + '</b><span>' + esc(it.unit || '') + (it.price ? ' · ' + esc(money(it.price)) + ' each' : '') + '</span></div>' +
        '<span class="q">× ' + esc(+it.qty) + '</span><span class="lt">' + esc(money(it.line)) + '</span></div>';
    }).join('');

    var due = Number(o.total_amount || 0) + Number(o.delivery_charge || 0);
    var html =
      '<div class="pc-sec"><h4>Customer</h4><div class="pc-box"><div class="pc-cust"><span class="av">' + esc((o.customer || 'C').trim().charAt(0).toUpperCase()) + '</span>' +
      '<div><b>' + esc(o.customer) + '</b><span>' + esc(o.phone || 'no phone') + '</span></div>' +
      '<div class="acts">' + (phone ? '<a class="pc-btn sm" href="tel:' + esc(phone) + '">Call</a>' : '') + (wa ? '<a class="pc-btn sm" href="' + esc(wa) + '" target="_blank" rel="noopener">WhatsApp</a>' : '') + '</div></div>' +
      '<div class="pc-addr"><div style="flex:1">' + esc(o.address || 'No address on file') + (o.address_type ? ' <span class="pc-chip">' + esc(o.address_type) + '</span>' : '') + '</div>' +
      (o.map ? '<a href="' + esc(o.map) + '" target="_blank" rel="noopener">Open map</a>' : '') + '</div></div></div>' +

      '<div class="pc-sec"><h4>Items (' + (o.items || []).length + ')</h4><div class="pc-box pc-items">' + items + '</div></div>' +

      '<div class="pc-sec"><h4>Bill</h4><div class="pc-box pc-sum">' +
      '<div><span>Items</span><span class="num">' + esc(money(o.total_amount)) + '</span></div>' +
      '<div><span>Delivery charge</span><span class="num">' + esc(money(o.delivery_charge)) + '</span></div>' +
      (o.discount ? '<div><span>Promo ' + esc(o.promo || '') + '</span><span class="num">− ' + esc(money(o.discount)) + '</span></div>' : '') +
      '<div class="tot"><span>To collect</span><span class="num">' + esc(money(due)) + '</span></div>' +
      (Math.abs((o.items_value || 0) - (o.total_amount || 0)) > 0.5 ? '<div class="muted"><span>Note: today\'s prices add up to ' + esc(money(o.items_value)) + '. The order total above is what the customer saw.</span></div>' : '') +
      (o.paid ? '<div class="muted"><span>Settled' + (o.paid_ref ? ' · ' + esc(o.paid_ref) : '') + (o.change > 0 ? ' · change ' + esc(money(o.change)) : '') + '</span></div>' : '') +
      '</div></div>' +

      '<div class="pc-sec"><h4>Delivery</h4><div class="pc-meta">' +
      '<div><small>Type</small><b>' + esc(o.delivery_type) + '</b></div>' +
      '<div><small>Slot</small><b>' + esc(o.slot || (o.delivery_type === 'Express' ? 'Within 90 min' : '–')) + '</b></div>' +
      '<div><small>Deliver by</small><b>' + esc(o.due_at ? clock(o.due_at) : '–') + '</b></div>' +
      '<div><small>Rider</small><b>' + esc(o.rider || 'Not assigned') + '</b></div></div></div>' +

      '<div class="pc-sec"><h4>Timeline</h4><ul class="pc-tl">' + (o.timeline || []).map(function (t) {
        return '<li><i></i>' + esc(t.label) + '<span>' + esc(t.at ? clock(Math.floor(new Date(String(t.at).replace(' ', 'T')).getTime() / 1000)) : '') + '</span></li>';
      }).join('') + '</ul></div>' +

      '<div class="pc-sec"><h4>Print</h4><div class="pc-chips">' +
      '<a class="pc-btn sm" target="_blank" href="' + printUrl(o.order_id, 'thermal') + '">Receipt 80 mm</a>' +
      '<a class="pc-btn sm" target="_blank" href="' + printUrl(o.order_id, 'a4') + '">A4 invoice</a>' +
      '<a class="pc-btn sm" target="_blank" href="' + printUrl(o.order_id, 'pick') + '">Packing list</a></div></div>';
    dr.querySelector('.pc-db').innerHTML = html;

    var foot = dr.querySelector('.pc-df');
    if (!foot) { foot = document.createElement('div'); foot.className = 'pc-df'; dr.appendChild(foot); }
    var btns = (o.actions || []).map(function (a) {
      return '<button type="button" class="pc-btn lg ' + ACT_BTN[a][1] + '" data-act="' + a + '" data-id="' + esc(o.order_id) + '">' + ACT_BTN[a][0] + '</button>';
    });
    if (o.can_settle) btns.push('<button type="button" class="pc-btn lg ok" data-settle="' + esc(o.order_id) + '">Settle payment</button>');
    foot.innerHTML = btns.join('') || '<span style="color:var(--ink-3);font-size:13px;padding:8px 0">No further steps for this order.</span>';
  }
  function printUrl(id, fmt) { return C.order + '/' + encodeURIComponent(id) + '/print/' + fmt + '?auto=1'; }

  /* ---------------- Actions ---------------- */
  function findOrder(id) { return S.orders.filter(function (o) { return o.order_id === id; })[0] || (S.detail && S.detail.order_id === id ? S.detail : null); }
  function doAction(id, action, extra) {
    if (S.busy) return Promise.resolve();
    S.busy = true;
    var body = Object.assign({ action: action }, extra || {});
    return api('POST', C.order + '/' + encodeURIComponent(id) + '/action', body).then(function (j) {
      if (!j.ok) { toast(j.message || 'Could not update the order.', 'bad'); return j; }
      delete S.fresh[id];
      toast(j.message || 'Updated', 'ok');
      return poll(true).then(function () { return j; });
    }).catch(function () { toast('Network problem. Please try again.', 'bad'); })
      .then(function (j) { S.busy = false; return j; });
  }
  function startAction(id, action) {
    var o = findOrder(id);
    if (action === 'reject' || action === 'cancel') return reasonModal(id, action);
    if (action === 'dispatch') return riderModal(id);
    if (action === 'deliver') {
      return doAction(id, 'deliver').then(function (j) {
        if (j && j.ok && o && !o.paid) settleModal(id);
      });
    }
    return doAction(id, action);
  }

  /* ---------------- Modals ---------------- */
  function modal(html, onReady) {
    var m = document.createElement('div');
    m.className = 'pc-modal'; m.innerHTML = '<div class="pc-mbox" role="dialog">' + html + '</div>';
    m.addEventListener('click', function (e) { if (e.target === m) m.remove(); });
    document.body.appendChild(m);
    var close = function () { m.remove(); };
    m.querySelectorAll('[data-close]').forEach(function (b) { b.onclick = close; });
    onReady(m, close);
    var f = m.querySelector('input,select,textarea'); if (f) setTimeout(function () { f.focus(); }, 30);
    return m;
  }
  function reasonModal(id, action) {
    var reasons = action === 'reject'
      ? ['Item out of stock', 'Address not serviceable', 'Hub closed now', 'Duplicate order', 'Customer asked to cancel']
      : ['Customer asked to cancel', 'Item out of stock', 'Customer not reachable', 'Delivery failed', 'Quality issue'];
    modal('<div class="pc-mh"><h3>' + (action === 'reject' ? 'Reject' : 'Cancel') + ' order ' + esc(id) + '?</h3><p>Choose a reason. The customer sees this order as cancelled.</p></div>' +
      '<div class="pc-mb"><div class="pc-chips">' + reasons.map(function (r) { return '<button type="button" class="pc-pick" data-r="' + esc(r) + '">' + esc(r) + '</button>'; }).join('') + '</div>' +
      '<div class="pc-field"><label for="pcReason">Reason</label><textarea id="pcReason" class="pc-textarea" maxlength="250" placeholder="Type or pick a reason"></textarea></div></div>' +
      '<div class="pc-mf"><button type="button" class="pc-btn lg" data-close>Back</button><button type="button" class="pc-btn lg bad" id="pcReasonGo">' + (action === 'reject' ? 'Reject order' : 'Cancel order') + '</button></div>',
      function (m, close) {
        var ta = m.querySelector('#pcReason');
        m.querySelectorAll('[data-r]').forEach(function (b) {
          b.onclick = function () { m.querySelectorAll('[data-r]').forEach(function (x) { x.classList.remove('is-on'); }); b.classList.add('is-on'); ta.value = b.getAttribute('data-r'); };
        });
        m.querySelector('#pcReasonGo').onclick = function () {
          if (!ta.value.trim()) { ta.focus(); toast('Please give a reason.', 'bad'); return; }
          doAction(id, action, { reason: ta.value.trim() }).then(function (j) { if (j && j.ok) close(); });
        };
      });
  }
  function closeStaleModal() {
    var n = (S.counts && S.counts.stale) || 0;
    modal('<div class="pc-mh"><h3>Close ' + n + ' old open order(s)?</h3><p>They will be marked cancelled with the reason below. This is recorded in the audit log and cannot be undone here.</p></div>' +
      '<div class="pc-mb"><textarea class="pc-input" id="pcStaleReason" rows="2" maxlength="250">Old open order closed in cleanup</textarea></div>' +
      '<div class="pc-mf"><button type="button" class="pc-btn" data-close>Keep them</button><button type="button" class="pc-btn bad" id="pcStaleGo">Close all as cancelled</button></div>',
      function (m, close) {
        m.querySelector('#pcStaleGo').onclick = function () {
          var b = this; b.disabled = true;
          api('POST', C.staleClose, { confirm: 'yes', reason: m.querySelector('#pcStaleReason').value.trim() }).then(function (j) {
            toast(j.message || (j.ok ? 'Done.' : 'Could not close.'), j.ok ? 'ok' : 'bad');
            if (j.ok) { close(); S.tab = 'board'; document.querySelectorAll('.pc-tab[data-tab]').forEach(function (x) { x.classList.toggle('is-on', x.getAttribute('data-tab') === 'board'); }); poll(true); }
            else b.disabled = false;
          }).catch(function () { b.disabled = false; toast('Network problem. Please try again.', 'bad'); });
        };
      });
  }
  function riderModal(id) {
    var d = S.detail && S.detail.order_id === id ? S.detail : null;
    var go = function (riders) {
      modal('<div class="pc-mh"><h3>Send ' + esc(id) + ' out for delivery</h3><p>Choose the rider taking it (optional).</p></div>' +
        '<div class="pc-mb"><div class="pc-field"><label for="pcRider">Rider</label><select id="pcRider" class="pc-select"><option value="">Not assigned / own delivery</option>' +
        riders.map(function (r) { return '<option value="' + r.id + '">' + esc(r.name) + (r.phone ? ' · ' + esc(r.phone) : '') + '</option>'; }).join('') +
        '</select></div>' + (riders.length ? '' : '<p style="margin:0;color:var(--ink-3);font-size:12.5px">No riders are set up for this hub yet.</p>') + '</div>' +
        '<div class="pc-mf"><button type="button" class="pc-btn lg" data-close>Back</button><button type="button" class="pc-btn lg pri" id="pcRiderGo">Out for delivery</button></div>',
        function (m, close) {
          m.querySelector('#pcRiderGo').onclick = function () {
            var v = m.querySelector('#pcRider').value;
            doAction(id, 'dispatch', v ? { rider_id: +v } : {}).then(function (j) { if (j && j.ok) close(); });
          };
        });
    };
    if (d) go(d.riders || []);
    else api('GET', C.order + '/' + encodeURIComponent(id)).then(function (j) { go(j.ok ? (j.order.riders || []) : []); });
  }
  function settleModal(id) {
    var load = S.detail && S.detail.order_id === id ? Promise.resolve({ ok: true, order: S.detail }) : api('GET', C.order + '/' + encodeURIComponent(id));
    load.then(function (j) {
      if (!j.ok) return;
      var o = j.order, due = Math.round((Number(o.total_amount || 0) + Number(o.delivery_charge || 0)) * 100) / 100;
      var method = (o.payment_type === 'Online') ? 'UPI' : 'Cash';
      var quick = [due, Math.ceil(due / 50) * 50, Math.ceil(due / 100) * 100, Math.ceil(due / 500) * 500]
        .filter(function (v, i, a) { return a.indexOf(v) === i; });
      modal('<div class="pc-mh"><h3>Settle payment · ' + esc(id) + '</h3><p>' + esc(o.customer) + '</p></div>' +
        '<div class="pc-mb"><div class="pc-due"><span>Amount to collect</span><b>' + esc(money(due)) + '</b></div>' +
        '<div class="pc-seg" id="pcMeth"><button type="button" data-m="Cash">Cash</button><button type="button" data-m="UPI">UPI</button><button type="button" data-m="Card">Card</button></div>' +
        '<div id="pcCashBox"><div class="pc-field"><label for="pcRecv">Cash received</label><input id="pcRecv" class="pc-input big" type="number" inputmode="decimal" min="0" step="0.5"></div>' +
        '<div class="pc-chips" style="margin-top:8px">' + quick.map(function (v) { return '<button type="button" class="pc-pick" data-q="' + v + '">' + esc(money(v)) + '</button>'; }).join('') + '</div>' +
        '<div class="pc-change" id="pcChange" style="margin-top:10px"></div></div>' +
        '<div id="pcRefBox" hidden><div class="pc-field"><label for="pcRef">Reference / UTR number</label><input id="pcRef" class="pc-input" maxlength="120" autocomplete="off"></div></div></div>' +
        '<div class="pc-mf"><button type="button" class="pc-btn lg" data-close>Later</button><button type="button" class="pc-btn lg ok" id="pcSettleGo">Mark as paid</button></div>',
        function (m, close) {
          var recv = m.querySelector('#pcRecv'), ch = m.querySelector('#pcChange');
          function setM(x) {
            method = x;
            m.querySelectorAll('[data-m]').forEach(function (b) { b.classList.toggle('is-on', b.getAttribute('data-m') === x); });
            m.querySelector('#pcCashBox').hidden = x !== 'Cash';
            m.querySelector('#pcRefBox').hidden = x === 'Cash';
            (x === 'Cash' ? recv : m.querySelector('#pcRef')).focus();
          }
          function calc() {
            var r = parseFloat(recv.value);
            if (isNaN(r)) { ch.textContent = ''; return; }
            var c = Math.round((r - due) * 100) / 100;
            ch.className = 'pc-change' + (c < 0 ? ' is-bad' : '');
            ch.textContent = c < 0 ? 'Short by ' + money(-c) : (c === 0 ? 'Exact amount' : 'Give change: ' + money(c));
          }
          m.querySelectorAll('[data-m]').forEach(function (b) { b.onclick = function () { setM(b.getAttribute('data-m')); }; });
          m.querySelectorAll('[data-q]').forEach(function (b) { b.onclick = function () { recv.value = b.getAttribute('data-q'); calc(); }; });
          recv.addEventListener('input', calc);
          setM(method);
          m.querySelector('#pcSettleGo').onclick = function () {
            var body = { method: method };
            if (method === 'Cash') body.received = parseFloat(recv.value);
            else body.ref = m.querySelector('#pcRef').value.trim();
            api('POST', C.order + '/' + encodeURIComponent(id) + '/settle', body).then(function (r) {
              if (!r.ok) { toast(r.message || 'Could not settle.', 'bad'); return; }
              toast(r.message || 'Settled', 'ok'); close(); poll(true);
            });
          };
        });
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
        return '<button type="button" data-open="' + esc(o.order_id) + '"' + (i === 0 ? ' class="is-hl"' : '') + '><b>' + esc(o.order_id) + '</b><span style="color:var(--ink-3)">' + esc(o.customer) + ' · ' + esc(o.phone) + '</span><span style="margin-left:auto" class="pc-chip">' + esc((C.labels && C.labels[o.stage]) || o.status) + '</span></button>';
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
  document.addEventListener('click', function (e) {
    var t = e.target.closest ? e.target : null; if (!t) return;
    var act = t.closest('[data-act]');
    if (act) { e.stopPropagation(); startAction(act.getAttribute('data-id'), act.getAttribute('data-act')); return; }
    var st = t.closest('[data-settle]');
    if (st) { settleModal(st.getAttribute('data-settle')); return; }
    var op = t.closest('[data-open]');
    if (op) { $('pcResults').hidden = true; openOrder(op.getAttribute('data-open')); return; }
    var tab = t.closest('[data-tab]');
    if (tab) {
      S.tab = tab.getAttribute('data-tab');
      document.querySelectorAll('.pc-tab[data-tab]').forEach(function (b) { b.classList.toggle('is-on', b.getAttribute('data-tab') === S.tab); });
      render();
    }
    if (t.closest('#pcCloseStale')) closeStaleModal();
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
    var o = S.orders.filter(function (x) { return x.stage === 'new'; }).sort(function (a, b) { return (a.placed_at || 0) - (b.placed_at || 0); })[0];
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

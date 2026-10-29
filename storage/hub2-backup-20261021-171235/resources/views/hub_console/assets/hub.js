/* Freshful hub console - command centre (22 Oct 2026). No libraries. */
(function () {
  'use strict';
  var C = window.HUBC || {};
  var $ = function (id) { return document.getElementById(id); };
  var root = document.documentElement;
  var S = { view: 'overview', data: {}, timer: null, lastOk: 0, fails: 0, inv: { q: '', cat: '', st: 'all' }, prepDay: 'today',
    ord: { from: '', to: '', stage: '', type: '', pay: '', q: '' } };
  var TITLES = { overview: 'Command center', prep: 'Prep list', inventory: 'Inventory', inward: 'Receive stock', orders: 'Orders' };

  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function money(n) { n = Math.round(Number(n || 0)); return (n < 0 ? '-' : '') + '₹' + Math.abs(n).toLocaleString('en-IN'); }
  function num(n) { n = Number(n || 0); return (Math.round(n * 100) / 100).toLocaleString('en-IN'); }
  function store(k, v) { try { localStorage.setItem(k, v); } catch (e) {} }
  function today() { var d = new Date(); return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); }
  function hourLbl(h) { var s = h % 12 || 12; return s + (h < 12 ? ' AM' : ' PM'); }
  function dur(sec) { sec = Math.abs(sec); var m = Math.floor(sec / 60), h = Math.floor(m / 60); return h ? h + 'h ' + (m % 60) + 'm' : Math.max(1, m) + 'm'; }
  function toast(msg, kind) {
    var t = document.createElement('div'); t.className = 'hc-toast' + (kind ? ' ' + kind : ''); t.textContent = msg;
    $('hcToasts').appendChild(t); setTimeout(function () { t.remove(); }, kind === 'bad' ? 6000 : 3500);
  }
  function api(method, url, body) {
    var opt = { method: method, credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } };
    if (body) { opt.headers['Content-Type'] = 'application/json'; opt.headers['X-CSRF-TOKEN'] = C.csrf; opt.body = JSON.stringify(body); }
    return fetch(url, opt).then(function (r) {
      if (r.status === 401 || r.status === 419) { location.href = C.login; throw new Error('Session ended'); }
      return r.json().catch(function () { return { ok: false, message: 'Unexpected reply (' + r.status + ')' }; }).then(function (j) {
        if (!r.ok && j && j.errors) j.message = Object.values(j.errors)[0][0];
        if (!r.ok) j.ok = false; return j;
      });
    });
  }
  function kpi(label, value, sub, cls) {
    return '<div class="hc-kpi ' + (cls || '') + '"><small>' + esc(label) + '</small><b>' + value + '</b><span>' + (sub || '') + '</span></div>';
  }
  function vs(p) { if (p === null || p === undefined) return '<span class="muted">new vs yesterday</span>'; if (p === 0) return '<span class="muted">same as yesterday</span>'; return '<span class="' + (p > 0 ? 'up' : 'down') + '">' + (p > 0 ? '+' : '') + p + '%</span> vs yesterday'; }
  function chip(txt, cls) { return '<span class="hc-chip ' + (cls || '') + '">' + esc(txt) + '</span>'; }
  function modal(html, ready) {
    var m = document.createElement('div'); m.className = 'hc-modal'; m.innerHTML = '<div class="hc-mbox" role="dialog">' + html + '</div>';
    m.addEventListener('click', function (e) { if (e.target === m) m.remove(); }); document.body.appendChild(m);
    var close = function () { m.remove(); };
    m.querySelectorAll('[data-close]').forEach(function (b) { b.onclick = close; });
    ready(m, close); var f = m.querySelector('input'); if (f) setTimeout(function () { f.focus(); f.select && f.select(); }, 30);
  }
  function posOpen(id) { return C.pos + '?open=' + encodeURIComponent(id); }

  /* ---------------- loading ---------------- */
  function load(force) {
    clearTimeout(S.timer);
    var v = S.view, url = v === 'overview' ? C.overview : (v === 'prep' ? C.prep : (v === 'orders' ? ordersUrl() : C.inventory));
    if (force) $('hcLiveText').textContent = 'Refreshing…';
    return api('GET', url).then(function (j) {
      if (!j.ok) throw new Error(j.message || 'Failed');
      S.data[v === 'inward' ? 'inventory' : v] = j; S.lastOk = Date.now(); S.fails = 0; $('hcLive').classList.remove('is-off');
      if (S.view === v) render();
    }).catch(function (e) {
      S.fails++; if (S.fails >= 2) $('hcLive').classList.add('is-off');
      if (!S.data[v]) $('hcView').innerHTML = '<div class="hc-card hc-empty">Could not load. ' + esc(e.message) + ' <button class="hc-btn sm" id="hcRetry">Retry</button></div>';
    }).then(function () {
      S.timer = setTimeout(function () { load(false); }, S.view === 'overview' ? 30000 : 60000);
    });
  }
  setInterval(function () {
    var s = S.lastOk ? Math.round((Date.now() - S.lastOk) / 1000) : null;
    $('hcLiveText').textContent = s === null ? 'Loading…' : (S.fails >= 2 ? 'Offline' : 'Updated ' + (s < 5 ? 'just now' : (s < 60 ? s + 's ago' : Math.floor(s / 60) + 'm ago')));
  }, 1000);

  function render() {
    var html = '';
    if (S.view === 'overview') html = renderOverview();
    else if (S.view === 'prep') html = renderPrep();
    else if (S.view === 'inventory' || S.view === 'inward') html = renderInventory();
    else if (S.view === 'orders') html = renderOrders();
    $('hcView').innerHTML = html || '<div class="hc-card hc-empty">Loading…</div>';
    bindView();
  }

  /* ---------------- overview ---------------- */
  function renderOverview() {
    var d = S.data.overview; if (!d) return '';
    var k = d.kpis, p = d.pipeline, inv = d.inventory;
    var LV = { bad: '!', warn: '!', info: 'i', ok: '✓' };
    $('nb-overview').hidden = !(p.late || p.new); $('nb-overview').textContent = p.late || p.new;
    $('nb-inward').hidden = !inv.pending_items; $('nb-inward').textContent = inv.pending_items;
    $('nb-inventory').hidden = !(inv.out + inv.short); $('nb-inventory').textContent = inv.out + inv.short;
    var h = '<section class="hc-card" style="margin-bottom:14px"><div class="hc-h">Smart insights <span class="r muted">worked out from your hub\'s live data</span></div><div class="hc-ins">' +
      d.insights.map(function (x) { return '<div class="hc-in ' + x.level + '" data-go="' + esc(x.tab) + '"><span class="ic"><b>' + LV[x.level] + '</b></span><div><b>' + esc(x.title) + '</b><p>' + esc(x.text) + '</p></div></div>'; }).join('') + '</div></section>';
    h += '<div class="hc-grid k4">' +
      kpi('Orders today', num(k.orders), vs(k.orders_vs) + ' · ' + k.express + ' express, ' + k.scheduled + ' scheduled') +
      kpi('Sales today', money(k.sales), vs(k.sales_vs)) +
      kpi('Average order', money(k.aov), k.customers + ' customers · ' + k.new_customers + ' new') +
      kpi('On-time delivery', k.on_time === null ? '–' : k.on_time + '%', k.delivered + ' delivered' + (k.avg_delivery !== null ? ' · avg ' + k.avg_delivery + ' min' : ''), k.on_time === null ? '' : (k.on_time >= 90 ? 'is-ok' : (k.on_time >= 70 ? 'is-warn' : 'is-bad'))) +
      kpi('Cancellations', k.cancel_rate + '%', k.cancelled + ' cancelled today', k.cancel_rate >= 10 ? 'is-bad' : '') +
      kpi('Money with riders', money(k.with_riders), 'collect in POS › Riders & cash', k.with_riders > 0 ? 'is-warn' : '') +
      kpi('Received today', money(k.received), 'cash to hand over ' + money(k.cash_to_handover)) +
      kpi('Stock alerts', num(inv.out + inv.short), inv.out + ' out of stock · ' + inv.short + ' short for orders · ' + inv.low + ' low', (inv.out + inv.short) ? 'is-bad' : 'is-ok') +
      '</div>';
    var P = [['new', 'New', '#EA4A1F'], ['accepted', 'Accepted', '#2563EB'], ['ready', 'Ready', '#7C3AED'], ['out', 'Out for delivery', '#0E9F6E'], ['unsettled', 'To settle', '#B45309']];
    h += '<section class="hc-card" style="margin-bottom:14px"><div class="hc-h">Live order pipeline <span class="r">' + (p.late ? chip(p.late + ' late', 'bad') + ' ' : '') + (p.later ? chip(p.later + ' scheduled later', '') + ' ' : '') + '<a class="hc-btn sm pri" href="' + esc(C.pos) + '" target="_blank" rel="noopener">Open POS</a></span></div><div class="hc-pipe">' +
      P.map(function (x) { return '<a href="' + esc(C.pos) + '" target="_blank" rel="noopener" style="--c:' + x[2] + '"><small>' + x[1] + '</small><b>' + (p[x[0]] || 0) + '</b></a>'; }).join('') + '</div></section>';
    // charts
    var hs = d.hours, from = 6, to = 23, mx = 1;
    for (var i = from; i <= to; i++) mx = Math.max(mx, hs.today[i], hs.yesterday[i]);
    var bars = '', labs = '';
    for (i = from; i <= to; i++) {
      bars += '<div class="col' + (i === hs.now ? ' now' : '') + '" title="' + hourLbl(i) + ': today ' + hs.today[i] + ', yesterday ' + hs.yesterday[i] + '"><i class="y" style="height:' + (hs.yesterday[i] / mx * 100) + '%"></i><i style="height:' + (hs.today[i] / mx * 100) + '%"></i></div>';
      labs += '<span>' + ((i - from) % 3 === 0 ? hourLbl(i) : '') + '</span>';
    }
    var dm = 1; d.days.forEach(function (x) { dm = Math.max(dm, x.sales); });
    var dbars = d.days.map(function (x, n) { var dt = new Date(x.date + 'T00:00:00'); return '<div class="col" title="' + esc(x.date) + ': ' + x.orders + ' orders, ' + money(x.sales) + '"><em>' + (x.sales ? money(x.sales).replace('₹', '₹') : '') + '</em><i style="height:' + (x.sales / dm * 100) + '%;' + (n === d.days.length - 1 ? 'background:var(--brand)' : '') + '"></i></div>'; }).join('');
    var dlabs = d.days.map(function (x, n) { var dt = new Date(x.date + 'T00:00:00'); return '<span>' + (n === d.days.length - 1 ? 'Today' : dt.toLocaleDateString('en-IN', { weekday: 'short' })) + '</span>'; }).join('');
    h += '<div class="hc-grid k2"><section class="hc-card"><div class="hc-h">Orders by hour <span class="r hc-leg"><span><i style="background:var(--c1)"></i>Today</span><span><i style="background:var(--line)"></i>Yesterday</span></span></div><div class="hc-bars">' + bars + '</div><div class="hc-blab">' + labs + '</div></section>' +
      '<section class="hc-card"><div class="hc-h">Sales, last 8 days <span class="r muted">' + money(d.days.reduce(function (s, x) { return s + x.sales; }, 0)) + ' · ' + d.days.reduce(function (s, x) { return s + x.orders; }, 0) + ' orders</span></div><div class="hc-bars hc-days">' + dbars + '</div><div class="hc-blab">' + dlabs + '</div></section></div>';
    // riders, top products, late
    var RS = { available: ['At hub', 'ok'], out: ['On delivery', 'info'], returning: ['Returning', 'warn'], off: ['Off duty', ''] };
    var riders = d.riders.length ? d.riders.map(function (r) {
      var st = RS[r.state] || ['', ''];
      return '<div class="hc-rider"><span class="hc-av" style="background:var(--surface-2);color:var(--ink)">' + esc((r.name || 'R').charAt(0).toUpperCase()) + '</span><div class="nm"><b>' + esc(r.name) + (r.smart ? ' ' + chip('Next in line', 'ok') : '') + '</b><small>' + (r.delivered_today || 0) + ' delivered today' + (r.out ? ' · ' + r.out + ' out now' : '') + (r.cash_due ? ' · ' + money(r.cash_due) + ' to collect' : '') + '</small></div>' + chip(st[0], st[1]) + '</div>';
    }).join('') : '<div class="hc-empty">No riders set up for this hub.</div>';
    var tm = 1; d.top_products.forEach(function (x) { tm = Math.max(tm, x.value); });
    var tops = d.top_products.length ? d.top_products.map(function (x, n) {
      return '<div class="hc-list-row"><div style="flex:1;min-width:0"><b>' + (n + 1) + '. ' + esc(x.name) + '</b> <span class="muted">' + esc(x.pack) + ' · ' + num(x.qty) + ' sold</span><div class="hc-mini"><i style="width:' + (x.value / tm * 100) + '%"></i></div></div><b>' + money(x.value) + '</b></div>';
    }).join('') : '<div class="hc-empty">No orders yet today.</div>';
    h += '<div class="hc-grid k2"><section class="hc-card"><div class="hc-h">Riders right now <span class="r"><a class="hc-btn sm" href="' + esc(C.pos) + '" target="_blank" rel="noopener">Manage in POS</a></span></div>' + riders + '</section>' +
      '<section class="hc-card"><div class="hc-h">Best sellers today</div>' + tops + '</section></div>';
    if (d.late.length) {
      h += '<section class="hc-card"><div class="hc-h">Late orders <span class="r">' + chip(d.late.length + ' late', 'bad') + '</span></div>' + d.late.map(function (o) {
        return '<div class="hc-list-row"><span><b>' + esc(o.order_id) + '</b> · ' + esc(o.customer) + '<br><span class="muted" style="font-size:12px">' + esc(o.delivery_type) + ' · ' + esc(o.status) + (o.rider ? ' · ' + esc(o.rider) : '') + '</span></span><span style="text-align:right">' + chip('Late ' + dur(Date.now() / 1000 - o.due_at), 'bad') + '<br><a class="muted" style="font-size:12px" href="' + esc(posOpen(o.order_id)) + '" target="_blank" rel="noopener">Open in POS</a></span></div>';
      }).join('') + '</section>';
    }
    return h;
  }

  /* ---------------- prep list ---------------- */
  function renderPrep() {
    var d = S.data.prep; if (!d) return '';
    var list = d[S.prepDay] || [];
    var grams = list.reduce(function (s, x) { return s + (x.grams || 0); }, 0);
    var short = list.filter(function (x) { return x.short > 0; }).length;
    var h = '<div class="hc-filter no-print"><div class="hc-seg"><button data-pday="today" class="' + (S.prepDay === 'today' ? 'is-on' : '') + '">Today<span class="n">' + d.today.length + '</span></button><button data-pday="tomorrow" class="' + (S.prepDay === 'tomorrow' ? 'is-on' : '') + '">Tomorrow<span class="n">' + d.tomorrow.length + '</span></button></div>' +
      '<span class="muted">Open orders that still need cutting and packing (new and accepted).</span><span style="flex:1"></span><button class="hc-btn" onclick="window.print()">Print prep sheet</button></div>';
    h += '<div class="hc-grid k4">' + kpi('Products to prepare', list.length, S.prepDay === 'today' ? 'for today' : 'for tomorrow') + kpi('Total weight', (grams / 1000).toLocaleString('en-IN', { maximumFractionDigits: 2 }) + ' kg', 'items sold by weight') +
      kpi('Orders', list.reduce(function (s, x) { return Math.max(s, 0) + x.orders; }, 0), 'order lines in this list') + kpi('Short on stock', short, short ? 'not enough live stock' : 'stock covers everything', short ? 'is-bad' : 'is-ok') + '</div>';
    if (!list.length) return h + '<div class="hc-card hc-empty">Nothing to prepare ' + (S.prepDay === 'today' ? 'right now' : 'for tomorrow yet') + '.</div>';
    return h + '<div class="hc-tablewrap"><table class="hc-table"><thead><tr><th>Product</th><th class="r">Packs</th><th class="r">Total weight</th><th class="r">Orders</th><th>By slot</th><th class="r">Live stock</th><th>Status</th></tr></thead><tbody>' +
      list.map(function (x) {
        return '<tr><td><div class="hc-prod">' + (x.image ? '<img src="' + esc(x.image) + '" alt="" loading="lazy" onerror="this.className=\'ph\';this.removeAttribute(\'src\')">' : '<span class="ph"></span>') + '<div><b>' + esc(x.name) + '</b><span class="sub">' + esc(x.pack) + ' · ' + esc(x.code) + '</span></div></div></td>' +
          '<td class="r"><b style="font-size:16px">' + num(x.qty) + '</b></td><td class="r">' + (x.grams ? (x.grams >= 1000 ? (x.grams / 1000).toLocaleString('en-IN', { maximumFractionDigits: 2 }) + ' kg' : num(x.grams) + ' g') : '<span class="muted">per piece</span>') + '</td>' +
          '<td class="r">' + x.orders + '</td><td>' + Object.keys(x.slots).map(function (s) { return chip(s + ' × ' + num(x.slots[s]), s === 'Express' ? 'info' : ''); }).join(' ') + '</td>' +
          '<td class="r">' + num(x.live) + '</td><td>' + (x.short > 0 ? chip('Short ' + num(x.short), 'bad') : chip('In stock', 'ok')) + '</td></tr>';
      }).join('') + '</tbody></table></div>';
  }

  /* ---------------- inventory ---------------- */
  function invRows() {
    var d = S.data.inventory; if (!d) return [];
    var f = S.inv, q = f.q.trim().toLowerCase();
    return d.rows.filter(function (r) {
      if (S.view === 'inward') return r.pending > 0;
      if (q && (r.name + ' ' + r.code + ' ' + r.category).toLowerCase().indexOf(q) < 0) return false;
      if (f.cat && r.category !== f.cat) return false;
      if (f.st === 'out') return r.status === 'out';
      if (f.st === 'low') return r.status === 'low';
      if (f.st === 'short') return r.short > 0;
      if (f.st === 'pending') return r.pending > 0;
      if (f.st === 'none') return r.status === 'none';
      return true;
    });
  }
  function renderInventory() {
    var d = S.data.inventory; if (!d) return '';
    var rows = invRows(), all = d.rows;
    var cnt = function (fn) { return all.filter(fn).length; };
    var cats = []; all.forEach(function (r) { if (r.category && cats.indexOf(r.category) < 0) cats.push(r.category); }); cats.sort();
    var h = '';
    if (S.view === 'inward') {
      h += '<div class="hc-card" style="margin-bottom:14px"><b>Receive stock sent to this hub.</b> <span class="muted">Count what actually arrived and press Receive. If it differs from what was sent, write why (damaged, short, extra). Every receipt is logged.</span></div>';
    } else {
      h += '<div class="hc-grid k4">' + kpi('Products', cnt(function (r) { return r.inv_id; }), cnt(function (r) { return r.status === 'none'; }) + ' active products not stocked here') +
        kpi('Out of stock', cnt(function (r) { return r.status === 'out'; }), 'live stock is zero', cnt(function (r) { return r.status === 'out'; }) ? 'is-bad' : 'is-ok') +
        kpi('Low stock', cnt(function (r) { return r.status === 'low'; }), 'at or below ' + num(d.low_at) + ' or below open orders', cnt(function (r) { return r.status === 'low'; }) ? 'is-warn' : '') +
        kpi('Stock value', money(all.reduce(function (s, r) { return s + Math.max(0, r.live) * r.price; }, 0)), 'live stock at selling price') + '</div>';
      var segs = [['all', 'All', all.length], ['out', 'Out', cnt(function (r) { return r.status === 'out'; })], ['low', 'Low', cnt(function (r) { return r.status === 'low'; })], ['short', 'Short for orders', cnt(function (r) { return r.short > 0; })], ['pending', 'Incoming', cnt(function (r) { return r.pending > 0; })], ['none', 'Not stocked', cnt(function (r) { return r.status === 'none'; })]];
      h += '<div class="hc-filter"><input class="hc-input" id="invQ" placeholder="Search product, code or category" value="' + esc(S.inv.q) + '" style="min-width:240px;flex:1;max-width:360px">' +
        '<select class="hc-input" id="invCat"><option value="">All categories</option>' + cats.map(function (c) { return '<option' + (S.inv.cat === c ? ' selected' : '') + '>' + esc(c) + '</option>'; }).join('') + '</select>' +
        '<div class="hc-seg">' + segs.map(function (s) { return '<button data-st="' + s[0] + '" class="' + (S.inv.st === s[0] ? 'is-on' : '') + '">' + s[1] + '<span class="n">' + s[2] + '</span></button>'; }).join('') + '</div></div>';
    }
    if (!rows.length) h += '<div class="hc-card hc-empty">' + (S.view === 'inward' ? 'Nothing is waiting to be received. All deliveries are accepted.' : 'No products match.') + '</div>';
    else {
      var ST = { out: ['Out of stock', 'bad'], low: ['Low', 'warn'], ok: ['In stock', 'ok'], none: ['Not stocked', ''] };
      h += '<div class="hc-tablewrap"><table class="hc-table"><thead><tr><th>Product</th><th>Category</th><th class="r">Live</th><th class="r">Fresh</th><th class="r">Older</th><th class="r">For open orders</th><th class="r">Sold today</th><th class="r">Incoming</th><th>Status</th><th></th></tr></thead><tbody>' +
        rows.slice(0, 400).map(function (r) {
          var st = ST[r.status] || ['', ''];
          return '<tr><td><div class="hc-prod">' + (r.image ? '<img src="' + esc(r.image) + '" alt="" loading="lazy" onerror="this.className=\'ph\';this.removeAttribute(\'src\')">' : '<span class="ph"></span>') + '<div><b>' + esc(r.name) + '</b><span class="sub">' + esc(r.pack) + ' · ' + esc(r.code) + (r.active ? '' : ' · inactive') + '</span></div></div></td>' +
            '<td>' + esc(r.category || '–') + '</td><td class="r"><b>' + num(r.live) + '</b></td><td class="r">' + num(r.fresh) + '</td><td class="r">' + (r.old ? num(r.old) : '–') + '</td>' +
            '<td class="r">' + (r.needed ? num(r.needed) : '–') + (r.short ? '<span class="sub" style="color:var(--bad)">short ' + num(r.short) + '</span>' : '') + '</td>' +
            '<td class="r">' + (r.sold_today ? num(r.sold_today) : '–') + '</td><td class="r">' + (r.pending ? '<b style="color:var(--info)">' + num(r.pending) + '</b>' : '–') + '</td>' +
            '<td>' + chip(st[0], st[1]) + '</td><td class="r" style="white-space:nowrap">' +
            (r.pending > 0 && r.inv_id ? '<button class="hc-btn sm ok" data-recv="' + r.inv_id + '">Receive</button> ' : '') +
            '<button class="hc-btn sm" data-count="' + r.id + '">Count</button></td></tr>';
        }).join('') + '</tbody></table></div>';
      if (rows.length > 400) h += '<p class="muted">Showing the first 400 of ' + rows.length + '. Use search to narrow down.</p>';
    }
    if (d.moves && d.moves.length) {
      var names = {}; d.rows.forEach(function (r) { names[r.code] = r.name; });
      h += '<section class="hc-card" style="margin-top:14px"><div class="hc-h">Stock movement log <span class="r muted">latest ' + d.moves.length + '</span></div><div class="hc-tablewrap" style="box-shadow:none"><table class="hc-table"><thead><tr><th>When</th><th>Product</th><th>Type</th><th class="r">Before</th><th class="r">Change</th><th class="r">After</th><th>By / note</th></tr></thead><tbody>' +
        d.moves.map(function (m) {
          var ch = Number(m.change_qty);
          return '<tr><td>' + esc(new Date(String(m.created_at).replace(' ', 'T')).toLocaleString('en-IN', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' })) + '</td><td>' + esc(names[m.product_code] || m.product_code) + '</td><td>' + chip(m.type === 'inward' ? 'Received' : (m.type === 'count' ? 'Stock count' : m.type), m.type === 'inward' ? 'info' : '') + '</td>' +
            '<td class="r">' + num(m.before_qty) + '</td><td class="r ' + (ch > 0 ? 'up' : (ch < 0 ? 'down' : '')) + '">' + (ch > 0 ? '+' : '') + num(ch) + '</td><td class="r"><b>' + num(m.after_qty) + '</b></td>' +
            '<td>' + esc(m.by_name || '') + (m.expected_qty !== null && m.expected_qty !== undefined ? ' <span class="muted">(sent ' + num(m.expected_qty) + ')</span>' : '') + (m.note ? '<span class="sub">' + esc(m.note) + '</span>' : '') + '</td></tr>';
        }).join('') + '</tbody></table></div></section>';
    }
    return h;
  }
  function rowById(fn) { return (S.data.inventory ? S.data.inventory.rows : []).filter(fn)[0]; }
  function receiveModal(invId) {
    var r = rowById(function (x) { return x.inv_id === invId; }); if (!r) return;
    modal('<div class="hc-mh"><h3>Receive ' + esc(r.name) + '</h3><p>Sent to this hub: <b>' + num(r.pending) + '</b> (' + esc(r.pack) + '). Live stock now ' + num(r.live) + '.</p></div>' +
      '<div class="hc-mb"><label>Quantity that actually arrived<input class="hc-input" id="mRecv" type="number" min="0" step="0.01" value="' + r.pending + '"></label><div id="mDiff" class="muted" style="font-size:13px"></div>' +
      '<label>Note (needed if it differs)<input class="hc-input" id="mNote" maxlength="250" placeholder="e.g. 2 damaged in transit"></label></div>' +
      '<div class="hc-mf"><button class="hc-btn" data-close>Cancel</button><button class="hc-btn ok" id="mGo">Receive</button></div>', function (m, close) {
      var inp = m.querySelector('#mRecv'), out = m.querySelector('#mDiff');
      var calc = function () { var v = parseFloat(inp.value), dd = Math.round(((isNaN(v) ? 0 : v) - r.pending) * 100) / 100; out.innerHTML = dd === 0 ? 'Matches what was sent.' : '<b style="color:' + (dd < 0 ? 'var(--bad)' : 'var(--warn)') + '">' + (dd > 0 ? '+' : '') + num(dd) + ' vs sent</b>. Live stock will be ' + num(r.live + (isNaN(v) ? 0 : v)) + '.'; };
      inp.addEventListener('input', calc); calc();
      m.querySelector('#mGo').onclick = function () {
        var b = this; b.disabled = true;
        api('POST', C.inward, { inv_id: invId, received: parseFloat(inp.value), note: m.querySelector('#mNote').value.trim() }).then(function (j) {
          b.disabled = false; toast(j.message || (j.ok ? 'Done' : 'Failed'), j.ok ? 'ok' : 'bad'); if (j.ok) { close(); load(true); }
        }).catch(function () { b.disabled = false; toast('Network problem. Please try again.', 'bad'); });
      };
    });
  }
  function countModal(pid) {
    var r = rowById(function (x) { return x.id === pid; }); if (!r) return;
    modal('<div class="hc-mh"><h3>Stock count · ' + esc(r.name) + '</h3><p>Count what is physically on the shelf. The system shows <b>' + num(r.live) + '</b>.</p></div>' +
      '<div class="hc-mb"><label>Counted quantity (' + esc(r.pack) + ' packs)<input class="hc-input" id="mCnt" type="number" min="0" step="0.01" value="' + r.live + '"></label><div id="mDiff" class="muted" style="font-size:13px"></div>' +
      '<label>Reason / note<input class="hc-input" id="mNote" maxlength="250" placeholder="e.g. daily count, wastage, spoilage"></label></div>' +
      '<div class="hc-mf"><button class="hc-btn" data-close>Cancel</button><button class="hc-btn pri" id="mGo">Save count</button></div>', function (m, close) {
      var inp = m.querySelector('#mCnt'), out = m.querySelector('#mDiff');
      var calc = function () { var v = parseFloat(inp.value), dd = Math.round(((isNaN(v) ? 0 : v) - r.live) * 100) / 100; out.innerHTML = dd === 0 ? 'No change.' : '<b style="color:' + (dd < 0 ? 'var(--bad)' : 'var(--ok)') + '">' + (dd > 0 ? '+' : '') + num(dd) + '</b> compared with the system.'; };
      inp.addEventListener('input', calc); calc();
      m.querySelector('#mGo').onclick = function () {
        var b = this; b.disabled = true;
        api('POST', C.count, { product_id: pid, counted: parseFloat(inp.value), note: m.querySelector('#mNote').value.trim() }).then(function (j) {
          b.disabled = false; toast(j.message || (j.ok ? 'Done' : 'Failed'), j.ok ? 'ok' : 'bad'); if (j.ok) { close(); load(true); }
        }).catch(function () { b.disabled = false; toast('Network problem. Please try again.', 'bad'); });
      };
    });
  }

  /* ---------------- orders ---------------- */
  function ordersUrl() {
    var o = S.ord; if (!o.from) { o.from = today(); o.to = today(); }
    return C.orders + '?from=' + o.from + '&to=' + o.to + '&stage=' + encodeURIComponent(o.stage) + '&type=' + encodeURIComponent(o.type) + '&pay=' + encodeURIComponent(o.pay) + '&q=' + encodeURIComponent(o.q);
  }
  function renderOrders() {
    var d = S.data.orders, o = S.ord; if (!o.from) { o.from = today(); o.to = today(); }
    var sel = function (id, cur, opts) { return '<select class="hc-input" id="' + id + '">' + opts.map(function (x) { return '<option value="' + x[0] + '"' + (cur === x[0] ? ' selected' : '') + '>' + x[1] + '</option>'; }).join('') + '</select>'; };
    var h = '<form class="hc-filter" id="ordForm"><input class="hc-input" type="date" id="oFrom" value="' + o.from + '" max="' + today() + '"><span class="muted">to</span><input class="hc-input" type="date" id="oTo" value="' + o.to + '" max="' + today() + '">' +
      sel('oStage', o.stage, [['', 'All statuses'], ['new', 'New'], ['accepted', 'Accepted'], ['out', 'Out for delivery'], ['unsettled', 'To settle'], ['delivered', 'Settled'], ['cancelled', 'Cancelled']]) +
      sel('oType', o.type, [['', 'Express + scheduled'], ['Express', 'Express'], ['Scheduled', 'Scheduled']]) +
      sel('oPay', o.pay, [['', 'All payments'], ['Cash', 'COD'], ['Online', 'Online'], ['Wallet', 'Wallet']]) +
      '<input class="hc-input" id="oQ" placeholder="Order no. or phone (searches all dates)" value="' + esc(o.q) + '" style="min-width:230px"><button class="hc-btn pri" type="submit">Show</button></form>';
    if (!d) return h + '<div class="hc-card hc-empty">Loading…</div>';
    var s = d.summary;
    h += '<div class="hc-grid k4">' + kpi('Orders', s.orders, d.from === d.to ? d.from : d.from + ' to ' + d.to) + kpi('Value', money(s.value), 'excluding cancelled') + kpi('Average order', money(s.orders - s.cancelled ? s.value / (s.orders - s.cancelled) : 0), '') + kpi('Cancelled', s.cancelled, s.orders ? Math.round(s.cancelled * 100 / s.orders) + '% of orders' : '', s.cancelled ? 'is-warn' : '') + '</div>';
    if (!d.orders.length) return h + '<div class="hc-card hc-empty">No orders found.</div>';
    var SC = { cancelled: 'bad', delivered: 'ok', unsettled: 'warn', out: 'info', 'new': 'warn' };
    var LB = { 'new': 'New', accepted: 'Accepted', ready: 'Ready', out: 'Out for delivery', unsettled: 'Delivered - to settle', delivered: 'Settled', cancelled: 'Cancelled' };
    return h + '<div class="hc-tablewrap"><table class="hc-table"><thead><tr><th>Order</th><th>Placed</th><th>Customer</th><th>Delivery</th><th>Status</th><th>Payment</th><th>Rider</th><th class="r">Amount</th></tr></thead><tbody>' +
      d.orders.map(function (x) {
        var dt = x.placed_at ? new Date(x.placed_at * 1000).toLocaleString('en-IN', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : '';
        return '<tr class="click" data-pos="' + esc(x.order_id) + '"><td><b>' + esc(x.order_id) + '</b><span class="sub">' + x.items + ' item(s)</span></td><td>' + esc(dt) + '</td><td>' + esc(x.customer) + '<span class="sub">' + esc(x.phone) + ' · ' + esc(x.area) + '</span></td>' +
          '<td>' + esc(x.delivery_type) + (x.slot ? '<span class="sub">' + esc(x.slot) + '</span>' : '') + '</td><td>' + chip(LB[x.board] || x.status, SC[x.stage] || '') + (x.late ? ' ' + chip('Late', 'bad') : '') + (x.cancel_reason ? '<span class="sub">' + esc(x.cancel_reason) + '</span>' : '') + '</td>' +
          '<td>' + esc(x.payment_type === 'Cash' ? 'COD' : x.payment_type) + '</td><td>' + esc(x.rider || '–') + '</td><td class="r"><b>' + money(x.amount) + '</b></td></tr>';
      }).join('') + '</tbody></table></div>';
  }

  /* ---------------- events ---------------- */
  function bindView() {
    var q = $('invQ'); if (q) q.addEventListener('input', function () { S.inv.q = q.value; var pos = q.selectionStart; render(); var n = $('invQ'); n.focus(); n.setSelectionRange(pos, pos); });
    var c = $('invCat'); if (c) c.onchange = function () { S.inv.cat = c.value; render(); };
    var f = $('ordForm'); if (f) f.onsubmit = function (e) {
      e.preventDefault(); S.ord = { from: $('oFrom').value || today(), to: $('oTo').value || today(), stage: $('oStage').value, type: $('oType').value, pay: $('oPay').value, q: $('oQ').value.trim() };
      S.data.orders = null; render(); load(true);
    };
  }
  document.addEventListener('click', function (e) {
    var t = e.target; if (!t.closest) return;
    var go = t.closest('[data-go]'); if (go) { var g = go.getAttribute('data-go'); if (g === 'pos') window.open(C.pos, '_blank'); else if (g === 'report') window.open(C.report, '_blank'); else setView(g === 'orders' ? 'orders' : g); return; }
    var st = t.closest('[data-st]'); if (st) { S.inv.st = st.getAttribute('data-st'); render(); return; }
    var pd = t.closest('[data-pday]'); if (pd) { S.prepDay = pd.getAttribute('data-pday'); render(); return; }
    var rv = t.closest('[data-recv]'); if (rv) { receiveModal(+rv.getAttribute('data-recv')); return; }
    var ct = t.closest('[data-count]'); if (ct) { countModal(+ct.getAttribute('data-count')); return; }
    var ps = t.closest('[data-pos]'); if (ps) { window.open(posOpen(ps.getAttribute('data-pos')), '_blank'); return; }
    if (t.closest('#hcRetry')) { load(true); return; }
    var nav = t.closest('[data-view]'); if (nav) { e.preventDefault(); setView(nav.getAttribute('data-view')); $('hcSide').classList.remove('is-open'); }
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { var m = document.querySelector('.hc-modal'); if (m) m.remove(); } });
  function setView(v) {
    if (!TITLES[v]) v = 'overview';
    S.view = v; location.hash = v; store('ff-hub-view', v);
    document.querySelectorAll('[data-view]').forEach(function (a) { a.classList.toggle('is-on', a.getAttribute('data-view') === v); });
    $('hcTitle').textContent = TITLES[v];
    var key = v === 'inward' ? 'inventory' : v;
    if (S.data[key]) render(); else $('hcView').innerHTML = '<div class="hc-card hc-empty">Loading…</div>';
    load(true);
  }
  window.addEventListener('hashchange', function () { var v = location.hash.replace('#', ''); if (v && v !== S.view) setView(v); });
  $('hcRefresh').onclick = function () { load(true); };
  $('hcMenu').onclick = function () { $('hcSide').classList.toggle('is-open'); };
  $('hcTheme').onclick = function () {
    var cur = root.getAttribute('data-theme') || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    var nx = cur === 'dark' ? 'light' : 'dark'; root.setAttribute('data-theme', nx); store('ff-theme', nx);
  };
  document.addEventListener('visibilitychange', function () { if (!document.hidden) load(false); });
  setView(location.hash.replace('#', '') || 'overview');
})();

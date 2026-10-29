/* Freshful hub console - command centre (22 Oct 2026). No libraries. */
(function () {
  'use strict';
  var C = window.HUBC || {};
  var $ = function (id) { return document.getElementById(id); };
  var root = document.documentElement;
  var S = { view: 'overview', data: {}, timer: null, lastOk: 0, fails: 0, inv: { q: '', cat: '', st: 'all' }, prepDay: 'today',
    ord: { from: '', to: '', stage: '', type: '', pay: '', q: '', sched: '', status: '' }, recv: {}, led: { from: '', to: '', type: '', q: '' }, net: { q: '', oos: false },
    tr: { tab: '' }, cash: { from: '', to: '' }, cus: { q: '', segment: '' }, lock: { q: '' }, pg: {}, pgSig: {}, ovTab: 'stage' };
  var TITLES = { overview: 'Command center', prep: 'Prep list', inventory: 'Inventory', inward: 'Receive stock', orders: 'Orders', locked: 'Locked stock',
    transfers: 'Transfers between hubs', network: 'All hubs stock', ledger: 'Stock ledger', cash: 'Cash book', riders: 'Riders', customers: 'Customers' };
  function dataKey(v) { return v === 'inward' ? 'inventory' : v; }
  function viewUrl(v) {
    switch (v) {
      case 'overview': return C.overview; case 'prep': return C.prep; case 'orders': return ordersUrl(); case 'locked': return C.locked;
      case 'transfers': return C.transfers; case 'network': return C.network; case 'riders': return C.riders;
      case 'ledger': if (!S.led.from) { S.led.from = dayAdd(-6); S.led.to = today(); } return C.ledger + '?from=' + S.led.from + '&to=' + S.led.to + '&type=' + encodeURIComponent(S.led.type) + '&q=' + encodeURIComponent(S.led.q);
      case 'cash': if (!S.cash.from) { S.cash.from = dayAdd(-13); S.cash.to = today(); } return C.cash + '?from=' + S.cash.from + '&to=' + S.cash.to;
      case 'customers': return C.customers + '?segment=' + encodeURIComponent(S.cus.segment);
      default: return C.inventory;
    }
  }
  function dayAdd(n) { var d = new Date(); d.setDate(d.getDate() + n); return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); }
  function when(ts) { return ts ? new Date(ts * 1000).toLocaleString('en-IN', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : '–'; }
  function dshort(d) { return d ? new Date(d + 'T00:00:00').toLocaleDateString('en-IN', { weekday: 'short', day: '2-digit', month: 'short' }) : ''; }
  function csv(name, head, rows) {
    var q = function (v) { v = v == null ? '' : String(v); return /[",\n]/.test(v) ? '"' + v.replace(/"/g, '""') + '"' : v; };
    var txt = '\ufeff' + [head].concat(rows).map(function (r) { return r.map(q).join(','); }).join('\r\n');
    var a = document.createElement('a'); a.href = URL.createObjectURL(new Blob([txt], { type: 'text/csv;charset=utf-8' })); a.download = name; document.body.appendChild(a); a.click();
    setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 500);
  }
  function upload(url, fd) {
    return fetch(url, { method: 'POST', credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': C.csrf, 'X-Requested-With': 'XMLHttpRequest' }, body: fd }).then(function (r) {
      if (r.status === 401 || r.status === 419) { location.href = C.login; throw new Error('Session ended'); }
      return r.json().catch(function () { return { ok: false, message: 'Unexpected reply (' + r.status + ')' }; }).then(function (j) { if (!r.ok && j && j.errors) j.message = Object.values(j.errors)[0][0]; if (!r.ok) j.ok = false; return j; });
    });
  }
  function post(url, body, btn, after) {
    if (btn) btn.disabled = true;
    return api('POST', url, body).then(function (j) {
      if (btn) btn.disabled = false; toast(j.message || (j.ok ? 'Done' : 'Failed'), j.ok ? 'ok' : 'bad'); if (j.ok && after) after(j); return j;
    }).catch(function () { if (btn) btn.disabled = false; toast('Network problem. Please try again.', 'bad'); });
  }

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
  function kpi(label, value, sub, cls, go) {
    return '<div class="hc-kpi ' + (cls || '') + (go ? ' is-link" data-go="' + esc(go) + '" role="link" tabindex="0" title="Open the records behind this number' : '') + '"><small>' + esc(label) + '</small><b>' + value + '</b><span>' + (sub || '') + '</span></div>';
  }
  /* client-side pages of 50; the page resets when the filters (sig) change */
  function pager(key, rows, sig, size) {
    size = size || 50; sig = String(sig || '');
    if (S.pgSig[key] !== sig) { S.pgSig[key] = sig; S.pg[key] = 1; }
    var pages = Math.max(1, Math.ceil(rows.length / size)), pg = Math.min(Math.max(1, S.pg[key] || 1), pages); S.pg[key] = pg;
    var a = (pg - 1) * size, html = '';
    if (pages > 1) html = '<div class="hc-pager"><span class="muted">' + (a + 1) + '–' + Math.min(rows.length, a + size) + ' of ' + num(rows.length) + '</span>' +
      '<button class="hc-btn sm" data-pg="' + key + '|' + (pg - 1) + '"' + (pg <= 1 ? ' disabled' : '') + '>Previous</button><span class="muted">Page ' + pg + ' of ' + pages + '</span>' +
      '<button class="hc-btn sm" data-pg="' + key + '|' + (pg + 1) + '"' + (pg >= pages ? ' disabled' : '') + '>Next</button></div>';
    return { rows: rows.slice(a, a + size), html: html };
  }
  function confirmBox(title, text, btn, cb, danger) {
    modal('<div class="hc-mh"><h3>' + esc(title) + '</h3><p>' + text + '</p></div><div class="hc-mf"><button class="hc-btn" data-close>Back</button><button class="hc-btn ' + (danger ? 'bad' : 'pri') + '" id="mGo">' + esc(btn) + '</button></div>', function (m, close) {
      m.querySelector('#mGo').onclick = function () { close(); cb(); };
    });
  }
  function ordPreset(extra) {
    S.ord = { from: today(), to: today(), stage: '', type: '', pay: '', q: '', sched: '', status: '' };
    Object.keys(extra || {}).forEach(function (k) { S.ord[k] = extra[k]; });
    S.data.orders = null; setView('orders');
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
    var v = S.view, url = viewUrl(v);
    if (force) $('hcLiveText').textContent = 'Refreshing…';
    return api('GET', url).then(function (j) {
      if (!j.ok) throw new Error(j.message || 'Failed');
      S.data[dataKey(v)] = j; S.lastOk = Date.now(); S.fails = 0; $('hcLive').classList.remove('is-off');
      if (S.view === v) render();
    }).catch(function (e) {
      S.fails++; if (S.fails >= 2) $('hcLive').classList.add('is-off');
      if (!S.data[dataKey(v)]) $('hcView').innerHTML = '<div class="hc-card hc-empty">Could not load. ' + esc(e.message) + ' <button class="hc-btn sm" id="hcRetry">Retry</button></div>';
    }).then(function () {
      S.timer = setTimeout(function () { load(false); }, S.view === 'overview' ? 30000 : 60000);
    });
  }
  setInterval(function () {
    var s = S.lastOk ? Math.round((Date.now() - S.lastOk) / 1000) : null;
    $('hcLiveText').textContent = s === null ? 'Loading…' : (S.fails >= 2 ? 'Offline' : 'Updated ' + (s < 5 ? 'just now' : (s < 60 ? s + 's ago' : Math.floor(s / 60) + 'm ago')));
  }, 1000);

  function render() {
    var R = { overview: renderOverview, prep: renderPrep, inventory: renderInventory, inward: renderInward, orders: renderOrders, locked: renderLocked,
      transfers: renderTransfers, network: renderNetwork, ledger: renderLedger, cash: renderCash, riders: renderRiders, customers: renderCustomers };
    var html = (R[S.view] || renderOverview)();
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
    var tw = d.transfers ? d.transfers.incoming + d.transfers.requests_to_me : 0; $('nb-transfers').hidden = !tw; $('nb-transfers').textContent = tw;
    var over = d.cash_in_hand !== null && d.cash_in_hand > (d.cash_limit || 20000); $('nb-cash').hidden = !over; $('nb-cash').textContent = over ? '₹' + Math.round(d.cash_in_hand / 1000) + 'k' : '';
    var h = '<section class="hc-card" style="margin-bottom:14px"><div class="hc-h">Smart insights <span class="r muted">worked out from your hub\'s live data</span></div><div class="hc-ins">' +
      d.insights.map(function (x) { return '<div class="hc-in ' + x.level + '" data-go="' + esc(x.tab) + '"><span class="ic"><b>' + LV[x.level] + '</b></span><div><b>' + esc(x.title) + '</b><p>' + esc(x.text) + '</p></div></div>'; }).join('') + '</div></section>';
    h += '<div class="hc-grid k4">' +
      kpi('Orders today', num(k.orders), vs(k.orders_vs) + ' · ' + k.express + ' express, ' + k.scheduled + ' scheduled', '', 'ord:today') +
      kpi('Sales today', money(k.sales), vs(k.sales_vs), '', 'ord:today') +
      kpi('Average order', money(k.aov), k.customers + ' customers · ' + k.new_customers + ' new') +
      kpi('On-time delivery', k.on_time === null ? '–' : k.on_time + '%', k.delivered + ' delivered' + (k.avg_delivery !== null ? ' · avg ' + k.avg_delivery + ' min' : ''), k.on_time === null ? '' : (k.on_time >= 90 ? 'is-ok' : (k.on_time >= 70 ? 'is-warn' : 'is-bad'))) +
      kpi('Cancellations', k.cancel_rate + '%', k.cancelled + ' cancelled today', k.cancel_rate >= 10 ? 'is-bad' : '', 'ord:cancelled') +
      kpi('Money with riders', money(k.with_riders), 'collect in POS › Riders & cash', k.with_riders > 0 ? 'is-warn' : '', 'riders') +
      kpi('Received today', money(k.received), 'cash to hand over ' + money(k.cash_to_handover), '', 'cash') +
      kpi('Stock alerts', num(inv.out + inv.short), inv.out + ' out of stock · ' + inv.short + ' short for orders · ' + inv.low + ' low', (inv.out + inv.short) ? 'is-bad' : 'is-ok', 'inv:alerts') +
      '</div>';
    var P = [['new', 'New', '#EA4A1F'], ['accepted', 'Accepted', '#2563EB'], ['ready', 'Ready', '#7C3AED'], ['out', 'Out for delivery', '#0E9F6E'], ['unsettled', 'To settle', '#B45309']];
    h += '<section class="hc-card" style="margin-bottom:14px"><div class="hc-h">Live order pipeline <span class="r">' + (p.late ? chip(p.late + ' late', 'bad') + ' ' : '') + (p.later ? chip(p.later + ' scheduled later', '') + ' ' : '') + '<a class="hc-btn sm pri" href="' + esc(C.pos) + '" target="_blank" rel="noopener">Open POS</a></span></div><div class="hc-pipe">' +
      P.map(function (x) { return '<a href="' + esc(C.pos) + '" target="_blank" rel="noopener" style="--c:' + x[2] + '"><small>' + x[1] + '</small><b>' + (p[x[0]] || 0) + '</b></a>'; }).join('') + '</div></section>';
    // today by stage, Express vs Scheduled (old dashboard "consolidated numbers")
    var SG = [['new', 'New'], ['accepted', 'Accepted'], ['out', 'Out'], ['unsettled', 'To settle'], ['delivered', 'Settled'], ['cancelled', 'Cancelled']];
    var g = d.status_grid || {}, sc = d.scheduled || {}, lf = d.lifetime || {};
    var cell = function (n, st, ty, raw) { return '<td class="r"><a href="#" class="hc-cell" data-ocell="' + esc(JSON.stringify({ stage: st || '', type: ty || '', status: raw || '' })) + '">' + n + '</a></td>'; };
    var grow = function (name) { var r = g[name] || {}, t = 0; SG.forEach(function (x) { t += r[x[0]] || 0; }); return '<tr><td><b>' + name + '</b></td>' + SG.map(function (x) { return cell(r[x[0]] || 0, x[0], name); }).join('') + cell('<b>' + t + '</b>', '', name) + '</tr>'; };
    var tot = {}; SG.forEach(function (x) { tot[x[0]] = ((g.Express || {})[x[0]] || 0) + ((g.Scheduled || {})[x[0]] || 0); });
    var RAW = [['Order Pending', 'Pending'], ['Order Placed', 'Placed'], ['Order Processed', 'Processed'], ['Order Shipped', 'Shipped'], ['Order Dispatched', 'Dispatched'], ['Order Delivered', 'Delivered'], ['Order Cancel', 'Cancelled']], rs = d.raw_status || {};
    var table = S.ovTab === 'raw'
      ? '<table class="hc-mini-table tight"><thead><tr><th>Hub</th>' + RAW.map(function (x) { return '<th class="r">' + x[1] + '</th>'; }).join('') + '<th class="r">Total</th></tr></thead><tbody><tr><td><b>' + esc(C.hub.split(' · ')[0]) + '</b></td>' +
          RAW.map(function (x) { return cell(rs[x[0]] || 0, '', '', x[0]); }).join('') + cell('<b>' + RAW.reduce(function (a, x) { return a + (rs[x[0]] || 0); }, 0) + '</b>') + '</tr></tbody></table>' +
          '<p class="muted" style="font-size:12px;margin:8px 0 0">Raw status values, same columns as the old dashboard. In this system Processed = delivered, cash not yet settled; Delivered = settled.</p>'
      : '<table class="hc-mini-table"><thead><tr><th></th>' + SG.map(function (x) { return '<th class="r">' + x[1] + '</th>'; }).join('') + '<th class="r">Total</th></tr></thead><tbody>' +
          grow('Express') + grow('Scheduled') + '<tr><td><b>All</b></td>' + SG.map(function (x) { return cell('<b>' + tot[x[0]] + '</b>', x[0]); }).join('') + cell('<b>' + SG.reduce(function (s, x) { return s + tot[x[0]]; }, 0) + '</b>') + '</tr></tbody></table>';
    var gv = d.grievances || {};
    var gvHtml = gv.available ? '<div class="hc-list-row" data-griev="1" style="cursor:pointer"><span>Customer grievances<br><span class="muted" style="font-size:12px">' + gv.last30 + ' in the last 30 days' + (gv.open !== null ? ' · ' + gv.open + ' open' : '') + '</span></span><b style="font-size:20px">' + gv.today + '<span class="muted" style="font-size:12px;font-weight:600"> today</span></b></div>'
      : '<div class="muted" style="font-size:12px;margin-top:8px">Grievances: ' + esc(gv.reason || 'not available') + '</div>';
    h += '<div class="hc-grid k32"><section class="hc-card"><div class="hc-h">Today\'s orders by status <span class="r"><span class="hc-seg"><button data-ovtab="stage" class="' + (S.ovTab !== 'raw' ? 'is-on' : '') + '">By stage</button><button data-ovtab="raw" class="' + (S.ovTab === 'raw' ? 'is-on' : '') + '">Old status names</button></span></span></div><div style="overflow:auto">' + table + '</div><p class="muted" style="font-size:12px;margin:8px 0 0">Orders placed today. Click any number to open those orders.</p></section>' +
      '<section class="hc-card"><div class="hc-h">Scheduled deliveries</div>' +
      '<div class="hc-list-row" data-sched="today" style="cursor:pointer"><span>For today<br><span class="muted" style="font-size:12px">' + (sc.today_open || 0) + ' still open</span></span><b style="font-size:20px">' + (sc.today || 0) + '</b></div>' +
      '<div class="hc-list-row" data-sched="tomorrow" style="cursor:pointer"><span>For tomorrow</span><b style="font-size:20px">' + (sc.tomorrow || 0) + '</b></div>' +
      '<div class="hc-list-row" data-sched="upcoming" style="cursor:pointer"><span>Later this month</span><b style="font-size:20px">' + (sc.later || 0) + '</b></div>' +
      gvHtml + '<div class="muted" style="font-size:12px;margin-top:8px">All time at this hub: ' + num(lf.orders) + ' orders (' + num(lf.express) + ' express, ' + num(lf.scheduled) + ' scheduled)</div></section></div>';
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
        (S._pgI = pager('inv', rows, [S.inv.q, S.inv.cat, S.inv.st].join('|'))).rows.map(function (r) {
          var st = ST[r.status] || ['', ''];
          return '<tr><td><div class="hc-prod">' + (r.image ? '<img src="' + esc(r.image) + '" alt="" loading="lazy" onerror="this.className=\'ph\';this.removeAttribute(\'src\')">' : '<span class="ph"></span>') + '<div><b>' + esc(r.name) + '</b><span class="sub">' + esc(r.pack) + ' · ' + esc(r.code) + (r.active ? '' : ' · inactive') + '</span></div></div></td>' +
            '<td>' + esc(r.category || '–') + '</td><td class="r"><b>' + num(r.live) + '</b></td><td class="r">' + num(r.fresh) + '</td><td class="r">' + (r.old ? num(r.old) : '–') + '</td>' +
            '<td class="r">' + (r.needed ? num(r.needed) : '–') + (r.short ? '<span class="sub" style="color:var(--bad)">short ' + num(r.short) + '</span>' : '') + '</td>' +
            '<td class="r">' + (r.sold_today ? num(r.sold_today) : '–') + '</td><td class="r">' + (r.pending ? '<b style="color:var(--info)">' + num(r.pending) + '</b>' : '–') + '</td>' +
            '<td>' + chip(st[0], st[1]) + '</td><td class="r" style="white-space:nowrap">' +
            (r.pending > 0 && r.inv_id ? '<button class="hc-btn sm ok" data-recv="' + r.inv_id + '">Receive</button> ' : '') +
            (r.old > 0 && r.live > 0 ? '<button class="hc-btn sm" data-move="' + r.id + '" title="Send older stock to another hub (old Transfer Danger Stock)">Move older</button> ' : '') +
            '<button class="hc-btn sm" data-count="' + r.id + '">Count</button></td></tr>';
        }).join('') + '</tbody></table></div>';
      h += S._pgI.html;
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
    return C.orders + '?from=' + o.from + '&to=' + o.to + '&stage=' + encodeURIComponent(o.stage) + '&type=' + encodeURIComponent(o.type) + '&pay=' + encodeURIComponent(o.pay) + '&q=' + encodeURIComponent(o.q) + '&sched=' + encodeURIComponent(o.sched) + '&status=' + encodeURIComponent(o.status || '');
  }
  function renderOrders() {
    var d = S.data.orders, o = S.ord; if (!o.from) { o.from = today(); o.to = today(); }
    var sel = function (id, cur, opts) { return '<select class="hc-input" id="' + id + '">' + opts.map(function (x) { return '<option value="' + x[0] + '"' + (cur === x[0] ? ' selected' : '') + '>' + x[1] + '</option>'; }).join('') + '</select>'; };
    var isToday = !o.sched && o.from === today() && o.to === today() && !o.q && !o.type && !o.pay && !o.status;
    var PR = [['p:today', 'Today', isToday && !o.stage], ['p:cancel', 'Today cancelled', isToday && o.stage === 'cancelled'], ['', 'By date placed', !o.sched && !(isToday && (!o.stage || o.stage === 'cancelled'))],
      ['today', 'Scheduled for today', o.sched === 'today'], ['tomorrow', 'Scheduled for tomorrow', o.sched === 'tomorrow'], ['upcoming', 'All upcoming scheduled', o.sched === 'upcoming']];
    var h = '<div class="hc-seg hc-subtabs">' + PR.map(function (x) { return '<button data-osched="' + x[0] + '" class="' + (x[2] ? 'is-on' : '') + '">' + x[1] + '</button>'; }).join('') + '</div>';
    if (o.status) h += '<div class="hc-filter"><span class="hc-chip info">Status: ' + esc(o.status) + '</span><a href="#" data-oclear="1" class="muted">clear</a></div>';
    h += '<form class="hc-filter" id="ordForm"' + (o.sched ? ' style="display:none"' : '') + '><input class="hc-input" type="date" id="oFrom" value="' + o.from + '" max="' + today() + '"><span class="muted">to</span><input class="hc-input" type="date" id="oTo" value="' + o.to + '" max="' + today() + '">' +
      sel('oStage', o.stage, [['', 'All statuses'], ['new', 'New'], ['accepted', 'Accepted'], ['out', 'Out for delivery'], ['unsettled', 'To settle'], ['delivered', 'Settled'], ['cancelled', 'Cancelled']]) +
      sel('oType', o.type, [['', 'Express + scheduled'], ['Express', 'Express'], ['Scheduled', 'Scheduled']]) +
      sel('oPay', o.pay, [['', 'All payments'], ['Cash', 'COD'], ['Online', 'Online'], ['Wallet', 'Wallet']]) +
      '<input class="hc-input" id="oQ" placeholder="Order no. or phone (searches all dates)" value="' + esc(o.q) + '" style="min-width:230px"><button class="hc-btn pri" type="submit">Show</button></form>';
    if (!d) return h + '<div class="hc-card hc-empty">Loading…</div>';
    var s = d.summary;
    h += '<div class="hc-grid k4">' + kpi('Orders', s.orders, o.sched ? 'by delivery date' : (d.from === d.to ? d.from : d.from + ' to ' + d.to)) + kpi('Value', money(s.value), 'excluding cancelled') + kpi('Average order', money(s.orders - s.cancelled ? s.value / (s.orders - s.cancelled) : 0), '') + kpi('Cancelled', s.cancelled, s.orders ? Math.round(s.cancelled * 100 / s.orders) + '% of orders' : '', s.cancelled ? 'is-warn' : '') + '</div>';
    if (!d.orders.length) return h + '<div class="hc-card hc-empty">No orders found.</div>';
    var SC = { cancelled: 'bad', delivered: 'ok', unsettled: 'warn', out: 'info', 'new': 'warn' };
    var LB = { 'new': 'New', accepted: 'Accepted', ready: 'Ready', out: 'Out for delivery', unsettled: 'Delivered - to settle', delivered: 'Settled', cancelled: 'Cancelled' };
    var pgO = pager('orders', d.orders, ordersUrl());
    return h + '<div class="hc-tablewrap"><table class="hc-table"><thead><tr><th>Order</th><th>Placed</th><th>Customer</th><th>Delivery</th><th>Status</th><th>Payment</th><th>Rider</th><th class="r">Amount</th></tr></thead><tbody>' +
      pgO.rows.map(function (x) {
        var dt = x.placed_at ? new Date(x.placed_at * 1000).toLocaleString('en-IN', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : '';
        return '<tr class="click" data-pos="' + esc(x.order_id) + '"><td><b>' + esc(x.order_id) + '</b><span class="sub">' + x.items + ' item(s)</span></td><td>' + esc(dt) + '</td><td>' + esc(x.customer) + '<span class="sub">' + esc(x.phone) + ' · ' + esc(x.area) + '</span></td>' +
          '<td>' + esc(x.delivery_type) + (x.slot ? '<span class="sub">' + (x.schedule_date && x.delivery_type === 'Scheduled' ? esc(dshort(x.schedule_date)) + ' · ' : '') + esc(x.slot) + '</span>' : '') + '</td><td>' + chip(LB[x.board] || x.status, SC[x.stage] || '') + (x.late ? ' ' + chip('Late', 'bad') : '') + (x.cancel_reason ? '<span class="sub">' + esc(x.cancel_reason) + '</span>' : '') + '</td>' +
          '<td>' + esc(x.payment_type === 'Cash' ? 'COD' : x.payment_type) + '</td><td>' + esc(x.rider || '–') + '</td><td class="r"><b>' + money(x.amount) + '</b></td></tr>';
      }).join('') + '</tbody></table></div>' + pgO.html + '<p class="muted" style="font-size:12px">Click an order to open it in the POS: details, status change, hub move, rejection reason and rider assignment are done there.</p>';
  }


  /* ---------------- receive stock (bulk, old "Accept Inward Stocks" + Excel) ---------------- */
  function pendingRows() { return S.data.inventory ? S.data.inventory.rows.filter(function (r) { return r.pending > 0 && r.inv_id; }) : []; }
  function renderInward() {
    var d = S.data.inventory; if (!d) return '';
    var rows = pendingRows();
    var h = '<div class="hc-card" style="margin-bottom:14px"><b>Stock sent to this hub.</b> <span class="muted">Count what arrived, correct the quantity if it differs, then press Receive all. A difference needs a short note (damaged, short, extra). You can also download the sheet, fill it in Excel and upload it back.</span></div>';
    if (!rows.length) return h + '<div class="hc-card hc-empty">Nothing is waiting to be received. All deliveries are accepted.</div>' + movesCard(d);
    h += '<div class="hc-filter"><button class="hc-btn" id="rcvCsv">Download sheet (CSV)</button><label class="hc-btn" style="cursor:pointer">Upload filled sheet<input type="file" id="rcvFile" accept=".csv,text/csv" hidden></label><span style="flex:1"></span><span class="muted" id="rcvSum"></span><button class="hc-btn ok" id="rcvAll">Receive all</button></div>';
    h += '<div class="hc-tablewrap"><table class="hc-table"><thead><tr><th>Product</th><th>Category</th><th class="r">Live now</th><th class="r">Sent</th><th class="r">Received</th><th class="r">Difference</th><th>Note</th></tr></thead><tbody>' +
      rows.map(function (r) {
        var v = S.recv[r.inv_id] || { q: r.pending, n: '' };
        return '<tr><td><div class="hc-prod">' + (r.image ? '<img src="' + esc(r.image) + '" alt="" loading="lazy" onerror="this.className=\'ph\';this.removeAttribute(\'src\')">' : '<span class="ph"></span>') + '<div><b>' + esc(r.name) + '</b><span class="sub">' + esc(r.pack) + ' · ' + esc(r.code) + '</span></div></div></td>' +
          '<td>' + esc(r.category || '–') + '</td><td class="r">' + num(r.live) + '</td><td class="r"><b>' + num(r.pending) + '</b></td>' +
          '<td class="r"><input class="hc-input hc-qty" type="number" min="0" step="0.01" data-rq="' + r.inv_id + '" value="' + esc(v.q) + '"></td><td class="r" id="rd-' + r.inv_id + '"></td>' +
          '<td><input class="hc-input" style="height:34px;min-width:180px" maxlength="250" data-rn="' + r.inv_id + '" placeholder="Only if different" value="' + esc(v.n) + '"></td></tr>';
      }).join('') + '</tbody></table></div>';
    return h + movesCard(d);
  }
  function recvRefresh() {
    var diff = 0, n = 0;
    pendingRows().forEach(function (r) {
      var v = S.recv[r.inv_id] || { q: r.pending }, q = parseFloat(v.q), dd = Math.round(((isNaN(q) ? 0 : q) - r.pending) * 100) / 100, el = $('rd-' + r.inv_id);
      if (el) el.innerHTML = dd === 0 ? '<span class="muted">–</span>' : '<b style="color:' + (dd < 0 ? 'var(--bad)' : 'var(--warn)') + '">' + (dd > 0 ? '+' : '') + num(dd) + '</b>';
      if (dd !== 0) diff++; n++;
    });
    if ($('rcvSum')) $('rcvSum').textContent = n + ' product(s)' + (diff ? ', ' + diff + ' with a difference' : ', all as sent');
  }
  function movesCard(d) {
    if (!d.moves || !d.moves.length) return '';
    var names = {}; d.rows.forEach(function (r) { names[r.code] = r.name; });
    return '<section class="hc-card" style="margin-top:14px"><div class="hc-h">Latest stock changes <span class="r"><a href="#ledger" data-view="ledger" class="hc-btn sm">Full stock ledger</a></span></div><div style="overflow:auto"><table class="hc-mini-table"><thead><tr><th>When</th><th>Product</th><th>Type</th><th class="r">Change</th><th class="r">After</th><th>By / note</th></tr></thead><tbody>' +
      d.moves.slice(0, 12).map(function (m) { var ch = Number(m.change_qty);
        return '<tr><td>' + esc(new Date(String(m.created_at).replace(' ', 'T')).toLocaleString('en-IN', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' })) + '</td><td>' + esc(names[m.product_code] || m.product_code) + '</td><td>' + typeChip(m.type) + '</td><td class="r ' + (ch > 0 ? 'up' : 'down') + '">' + (ch > 0 ? '+' : '') + num(ch) + '</td><td class="r">' + num(m.after_qty) + '</td><td>' + esc(m.by_name || '') + (m.note ? '<span class="sub muted">' + esc(m.note) + '</span>' : '') + '</td></tr>'; }).join('') + '</tbody></table></div></section>';
  }
  var MT = { inward: ['Received', 'info'], count: ['Stock count', ''], sale: ['Order packed', 'warn'], 'return': ['Order returned', 'ok'], transfer_out: ['Sent to hub', 'warn'], transfer_in: ['From hub', 'info'], transfer_back: ['Transfer cancelled', 'ok'] };
  function typeChip(t) { var x = MT[t] || [t, '']; return chip(x[0], x[1]); }

  /* ---------------- locked stock ---------------- */
  function renderLocked() {
    var d = S.data.locked; if (!d) return '';
    var rows = d.rows, q = S.lock.q.trim().toLowerCase();
    if (q) rows = rows.filter(function (r) { return (r.name + ' ' + r.code + ' ' + r.orders.map(function (o) { return o.order_id; }).join(' ')).toLowerCase().indexOf(q) >= 0; });
    var res = 0, pk = 0, sh = 0; d.rows.forEach(function (r) { res += r.reserved; pk += r.packed + r.out; if (r.short > 0) sh++; });
    var h = '<div class="hc-grid k4">' + kpi('Products in open orders', d.rows.length, 'new, accepted or out') + kpi('Reserved by new orders', num(res), 'still on the shelf') + kpi('Packed / out', num(pk), 'already taken off the shelf') +
      kpi('Not enough stock', sh, sh ? 'new orders need more than live stock' : 'stock covers every new order', sh ? 'is-bad' : 'is-ok') + '</div>';
    h += '<div class="hc-filter"><input class="hc-input" id="lkQ" placeholder="Search product or order" value="' + esc(S.lock.q) + '" style="min-width:260px"><span class="muted">' + (d.deducts ? 'Live stock drops when the POS accepts an order. "Available" = live stock minus what new orders still need.' : 'Stock deduction is switched off (HUB_STOCK_DEDUCT=false).') + '</span></div>';
    if (!rows.length) return h + '<div class="hc-card hc-empty">No stock is locked in open orders.</div>';
    var pgL = pager('locked', rows, S.lock.q);
    return h + '<div class="hc-tablewrap"><table class="hc-table"><thead><tr><th>Product</th><th class="r">Live</th><th class="r">Reserved (new)</th><th class="r">Packed</th><th class="r">Out</th><th class="r">Available</th><th>Orders</th></tr></thead><tbody>' +
      pgL.rows.map(function (r) {
        return '<tr><td><b>' + esc(r.name) + '</b><span class="sub">' + esc(r.pack) + ' · ' + esc(r.code) + '</span></td><td class="r">' + num(r.live) + '</td><td class="r"><b>' + num(r.reserved) + '</b></td><td class="r">' + num(r.packed) + '</td><td class="r">' + num(r.out) + '</td>' +
          '<td class="r">' + (r.short > 0 ? chip('Short ' + num(r.short), 'bad') : '<b>' + num(r.available) + '</b>') + '</td><td>' +
          r.orders.slice(0, 8).map(function (o) { return '<a class="hc-chip ' + (o.stage === 'new' ? 'warn' : (o.stage === 'out' ? 'info' : '')) + '" style="text-decoration:none;margin:2px" href="' + esc(posOpen(o.order_id)) + '" target="_blank" rel="noopener" title="' + esc(o.status + ' · ' + o.slot) + '">' + esc(o.order_id) + ' × ' + num(o.qty) + '</a>'; }).join('') + (r.orders.length > 8 ? ' <span class="muted">+' + (r.orders.length - 8) + '</span>' : '') + '</td></tr>';
      }).join('') + '</tbody></table></div>' + pgL.html;
  }

  /* ---------------- stock ledger ---------------- */
  function renderLedger() {
    var d = S.data.ledger, f = S.led;
    var h = '<form class="hc-filter" id="ledForm"><input class="hc-input" type="date" id="lFrom" value="' + f.from + '" max="' + today() + '"><span class="muted">to</span><input class="hc-input" type="date" id="lTo" value="' + f.to + '" max="' + today() + '">' +
      '<select class="hc-input" id="lType">' + [['', 'All changes'], ['in', 'Stock in'], ['out', 'Stock out'], ['inward', 'Received from store'], ['sale', 'Orders packed'], ['return', 'Orders returned'], ['transfer_out', 'Sent to hubs'], ['transfer_in', 'Received from hubs'], ['count', 'Stock counts']].map(function (x) { return '<option value="' + x[0] + '"' + (f.type === x[0] ? ' selected' : '') + '>' + x[1] + '</option>'; }).join('') + '</select>' +
      '<input class="hc-input" id="lQ" placeholder="Product, order or transfer no." value="' + esc(f.q) + '" style="min-width:220px"><button class="hc-btn pri" type="submit">Show</button><span style="flex:1"></span><button class="hc-btn" type="button" id="lCsv">Download CSV</button></form>';
    if (!d) return h + '<div class="hc-card hc-empty">Loading…</div>';
    var s = d.summary || {}, inQ = 0, outQ = 0; Object.keys(s).forEach(function (k) { if (s[k] > 0) inQ += s[k]; else outQ += s[k]; });
    h += '<div class="hc-grid k4">' + kpi('Changes', num(d.count), d.from + ' to ' + d.to) + kpi('Stock in', '+' + num(inQ), 'received, returned, from hubs') + kpi('Stock out', num(outQ), 'orders and transfers') + kpi('Stock counts', num(s.count || 0), 'net change from counts', (s.count || 0) < 0 ? 'is-warn' : '') + '</div>';
    if (!d.moves.length) return h + '<div class="hc-card hc-empty">No stock changes in this period.</div>';
    return h + '<div class="hc-tablewrap"><table class="hc-table"><thead><tr><th>When</th><th>Product</th><th>Type</th><th>Reference</th><th class="r">Before</th><th class="r">Change</th><th class="r">After</th><th>By / note</th></tr></thead><tbody>' +
      (S._pgLd = pager('ledger', d.moves, viewUrl('ledger'))).rows.map(function (m) {
        return '<tr><td>' + esc(when(m.at)) + '</td><td><b>' + esc(m.name) + '</b><span class="sub">' + esc(m.code) + '</span></td><td>' + typeChip(m.type) + '</td><td>' + (m.ref ? (/^IH/.test(m.ref) ? esc(m.ref) : '<a href="' + esc(posOpen(m.ref)) + '" target="_blank" rel="noopener">' + esc(m.ref) + '</a>') : '–') + '</td>' +
          '<td class="r">' + num(m.before) + '</td><td class="r ' + (m.change > 0 ? 'up' : 'down') + '"><b>' + (m.change > 0 ? '+' : '') + num(m.change) + '</b>' + (m.expected !== null ? '<span class="sub">sent ' + num(m.expected) + '</span>' : '') + '</td><td class="r">' + num(m.after) + '</td>' +
          '<td>' + esc(m.by || '') + (m.note ? '<span class="sub">' + esc(m.note) + '</span>' : '') + '</td></tr>';
      }).join('') + '</tbody></table></div>' + S._pgLd.html;
  }

  /* ---------------- all hubs stock ---------------- */
  function renderNetwork() {
    var d = S.data.network; if (!d) return '';
    var q = S.net.q.trim().toLowerCase(), me = C.hubId;
    var rows = d.rows.filter(function (r) { if (q && (r.name + ' ' + r.code).toLowerCase().indexOf(q) < 0) return false; if (S.net.oos && !(r.by[me] === null || r.by[me] <= 0)) return false; return true; });
    var h = '<div class="hc-filter"><input class="hc-input" id="nQ" placeholder="Search product" value="' + esc(S.net.q) + '" style="min-width:240px"><label class="muted" style="display:flex;gap:6px;align-items:center"><input type="checkbox" id="nOos"' + (S.net.oos ? ' checked' : '') + '> Only what is out of stock here</label>' +
      '<span style="flex:1"></span><button class="hc-btn" id="nCsv">Download CSV</button><button class="hc-btn pri" data-tnew="request">Request stock</button></div>';
    h += '<p class="muted" style="margin:0 0 10px">Live stock in every active hub. Use it to see who can send you stock, then press Request stock.</p>';
    if (!rows.length) return h + '<div class="hc-card hc-empty">No products match.</div>';
    return h + '<div class="hc-tablewrap"><table class="hc-table hc-sticky"><thead><tr><th>Product</th>' + d.hubs.map(function (x) { return '<th class="r' + (x.me ? ' hc-me-col' : '') + '">' + esc(x.hub) + '</th>'; }).join('') + '<th class="r">Total</th></tr></thead><tbody>' +
      (S._pgN = pager('net', rows, S.net.q + '|' + S.net.oos)).rows.map(function (r) {
        return '<tr><td><b>' + esc(r.name) + '</b><span class="sub">' + esc(r.pack) + ' · ' + esc(r.code) + '</span></td>' + d.hubs.map(function (x) { var v = r.by[x.id];
          return '<td class="r' + (x.me ? ' hc-me-col' : '') + '">' + (v === null || v === undefined ? '<span class="muted">–</span>' : (v <= 0 ? '<span style="color:var(--bad)">0</span>' : num(v))) + '</td>'; }).join('') + '<td class="r"><b>' + num(r.total) + '</b></td></tr>';
      }).join('') + '</tbody></table></div>' + S._pgN.html;
  }

  /* ---------------- transfers ---------------- */
  var TS = { requested: ['Requested', 'warn'], declined: ['Declined', ''], in_transit: ['On the way', 'info'], received: ['Received', 'ok'], cancelled: ['Cancelled', ''] };
  function renderTransfers() {
    var d = S.data.transfers; if (!d) return '';
    if (!d.ok) return '<div class="hc-card hc-empty">' + esc(d.message) + '</div>';
    var c = d.counts, list = d.transfers;
    var tabNow = S.tr.tab || (c.incoming + c.requests_to_me ? 'todo' : 'all');
    var tabs = [['todo', 'Needs action', c.incoming + c.requests_to_me], ['in', 'Coming in', list.filter(function (t) { return t.direction === 'in'; }).length], ['out', 'Going out', list.filter(function (t) { return t.direction === 'out'; }).length], ['all', 'All', list.length]];
    var f = { todo: function (t) { return t.can.receive || t.can.fulfil; }, 'in': function (t) { return t.direction === 'in'; }, out: function (t) { return t.direction === 'out'; }, all: function () { return true; } }[tabNow];
    var rows = list.filter(f);
    var h = '<div class="hc-grid k4">' + kpi('To receive', c.incoming, 'stock on the way to you', c.incoming ? 'is-warn' : '') + kpi('Requests for you', c.requests_to_me, 'other hubs asking for stock', c.requests_to_me ? 'is-warn' : '') +
      kpi('Your requests', c.my_requests, 'waiting for the other hub') + kpi('Sent, not yet received', c.outgoing, 'on the road') + '</div>';
    h += '<div class="hc-filter"><div class="hc-seg">' + tabs.map(function (t) { return '<button data-ttab="' + t[0] + '" class="' + (tabNow === t[0] ? 'is-on' : '') + '">' + t[1] + '<span class="n">' + t[2] + '</span></button>'; }).join('') + '</div><span style="flex:1"></span>' +
      '<button class="hc-btn" data-tnew="request">Request stock</button><button class="hc-btn pri" data-tnew="send">Send stock</button></div>';
    if (!rows.length) return h + '<div class="hc-card hc-empty">' + (tabNow === 'todo' ? 'Nothing needs your action right now.' : 'No transfers in the last 30 days.') + '</div>';
    return h + '<div class="hc-tablewrap"><table class="hc-table"><thead><tr><th>Transfer</th><th>Direction</th><th>Products</th><th>Status</th><th>Rider</th><th>Created</th><th></th></tr></thead><tbody>' +
      (S._pgT = pager('tr', rows, tabNow)).rows.map(function (t) {
        var st = TS[t.status] || [t.status, ''];
        var act = (t.can.receive ? '<button class="hc-btn sm ok" data-trecv="' + t.id + '">Receive</button> ' : '') + (t.can.fulfil ? '<button class="hc-btn sm pri" data-tful="' + t.id + '">Send</button> <button class="hc-btn sm" data-tdec="' + t.id + '">Decline</button> ' : '') +
          (t.can.cancel ? '<button class="hc-btn sm" data-tcan="' + t.id + '">Cancel</button> ' : '');
        return '<tr class="click" data-tview="' + t.id + '"><td><b>' + esc(t.ref) + '</b><span class="sub">' + esc(t.kind === 'request' ? 'Request' : (t.reason_label || 'Transfer')) + (t.outward_ref ? ' · outward ' + esc(t.outward_ref) : '') + '</span></td>' +
          '<td>' + (t.direction === 'in' ? chip('In', 'info') + ' from <b>' + esc(t.from) + '</b>' : chip('Out', 'warn') + ' to <b>' + esc(t.to) + '</b>') + '</td>' +
          '<td>' + t.items + ' product(s)<span class="sub">' + esc(t.lines.slice(0, 2).map(function (l) { return l.name; }).join(', ')) + (t.lines.length > 2 ? '…' : '') + '</span></td>' +
          '<td>' + chip(st[0], st[1]) + (t.status === 'received' && t.difference ? ' ' + chip((t.difference > 0 ? '+' : '') + num(t.difference), 'bad') : '') + '</td><td>' + esc(t.rider || '–') + '</td><td>' + esc(when(t.created_at)) + '<span class="sub">' + esc(t.created_by || '') + '</span></td>' +
          '<td class="r" style="white-space:nowrap">' + act + '</td></tr>';
      }).join('') + '</tbody></table></div>' + S._pgT.html;
  }
  function trById(id) { return (S.data.transfers ? S.data.transfers.transfers : []).filter(function (t) { return t.id === id; })[0]; }
  function trDetail(id) {
    var t = trById(id); if (!t) return; var st = TS[t.status] || [t.status, ''];
    modal('<div class="hc-mh"><h3>' + esc(t.ref) + ' ' + chip(st[0], st[1]) + '</h3><p>' + esc(t.from) + ' → ' + esc(t.to) + (t.reason_label ? ' · ' + esc(t.reason_label) : '') + '</p></div><div class="hc-mb">' +
      '<div class="hc-kv"><span>Created</span><b>' + esc(when(t.created_at)) + ' · ' + esc(t.created_by || '') + '</b><span>Sent</span><b>' + (t.sent_at ? esc(when(t.sent_at)) + ' · ' + esc(t.sent_by || '') : '–') + '</b>' +
      (t.outward_ref ? '<span>Outward / challan no.</span><b>' + esc(t.outward_ref) + '</b>' : '') + '<span>Rider</span><b>' + esc(t.rider || '–') + '</b><span>Received</span><b>' + (t.received_at ? esc(when(t.received_at)) + ' · ' + esc(t.received_by || '') : '–') + '</b>' +
      (t.instruction ? '<span>Instruction</span><b>' + esc(t.instruction) + '</b>' : '') + (t.receive_note ? '<span>Receiving note</span><b>' + esc(t.receive_note) + '</b>' : '') + (t.close_note ? '<span>' + (t.status === 'declined' ? 'Declined because' : 'Cancelled because') + '</span><b>' + esc(t.close_note) + '</b>' : '') + '</div>' +
      '<table class="hc-mini-table"><thead><tr><th>Product</th><th class="r">Asked</th><th class="r">Sent</th><th class="r">Received</th><th class="r">Difference</th></tr></thead><tbody>' +
      t.lines.map(function (l) { var df = l.received !== null && l.sent !== null ? l.received - l.sent : null;
        return '<tr><td><b>' + esc(l.name) + '</b><span class="muted"> ' + esc(l.pack || '') + '</span></td><td class="r">' + (l.requested !== null ? num(l.requested) : '–') + '</td><td class="r">' + (l.sent !== null ? num(l.sent) : '–') + '</td><td class="r">' + (l.received !== null ? num(l.received) : '–') + '</td><td class="r">' + (df ? '<b style="color:var(--bad)">' + (df > 0 ? '+' : '') + num(df) + '</b>' : '–') + '</td></tr>'; }).join('') + '</tbody></table></div>' +
      '<div class="hc-mf"><button class="hc-btn" data-close>Close</button><button class="hc-btn" id="mPrint">Print delivery note</button></div>', function (m) {
      m.querySelector('.hc-mbox').classList.add('mid');
      m.querySelector('#mPrint').onclick = function () { printNote(t); };
    });
  }
  function printNote(t) {
    var w = window.open('', '_blank'); if (!w) return;
    w.document.write('<!doctype html><meta charset="utf-8"><title>' + esc(t.ref) + '</title><style>body{font:14px system-ui;margin:24px;color:#111}table{width:100%;border-collapse:collapse;margin-top:14px}td,th{border:1px solid #999;padding:6px 8px;text-align:left}th{background:#eee}.r{text-align:right}</style>' +
      '<h2 style="margin:0">Freshful inter-hub delivery note</h2><p><b>' + esc(t.ref) + '</b>' + (t.outward_ref ? ' · Outward no. ' + esc(t.outward_ref) : '') + ' · ' + esc(t.from) + ' → ' + esc(t.to) + '<br>Sent: ' + esc(when(t.sent_at)) + ' by ' + esc(t.sent_by || '') + ' · Rider: ' + esc(t.rider || '-') + (t.instruction ? '<br>Instruction: ' + esc(t.instruction) : '') + '</p>' +
      '<table><tr><th>Product</th><th>Pack</th><th class="r">Sent</th><th class="r">Received</th></tr>' + t.lines.map(function (l) { return '<tr><td>' + esc(l.name) + '</td><td>' + esc(l.pack || '') + '</td><td class="r">' + (l.sent !== null ? num(l.sent) : '') + '</td><td class="r">' + (l.received !== null ? num(l.received) : '') + '</td></tr>'; }).join('') + '</table>' +
      '<p style="margin-top:40px">Sent by: ____________________ &nbsp;&nbsp; Rider: ____________________ &nbsp;&nbsp; Received by: ____________________</p><script>print()<\/script>');
    w.document.close();
  }
  function needStock(cb) { if (S.data.inventory) return cb(); api('GET', C.inventory).then(function (j) { if (j.ok) { S.data.inventory = j; cb(); } }); }
  function needNet(cb) { if (S.data.network) return cb(); api('GET', C.network).then(function (j) { if (j.ok) { S.data.network = j; cb(); } }); }
  function needTr(cb) { if (S.data.transfers && S.data.transfers.hubs) return cb(); api('GET', C.transfers).then(function (j) { if (j.ok) { S.data.transfers = j; cb(); } }); }
  function transferNew(kind, preset) {
    needTr(function () { (kind === 'send' ? needStock : needNet)(function () {
      var T = S.data.transfers, send = kind === 'send';
      var prods = send ? S.data.inventory.rows.filter(function (r) { return r.live > 0; }).map(function (r) { return { id: r.id, name: r.name, pack: r.pack, have: r.live, old: r.old }; })
        : S.data.network.rows.map(function (r) { return { id: r.id, name: r.name, pack: r.pack, have: r.by[C.hubId] || 0, by: r.by }; });
      var hubOpts = T.hubs.map(function (x) { return '<option value="' + x.id + '">' + esc(x.hub) + (x.city ? ' · ' + esc(x.city) : '') + '</option>'; }).join('');
      modal('<div class="hc-mh"><h3>' + (send ? 'Send stock to another hub' : 'Request stock from another hub') + '</h3><p>' + (send ? 'Stock leaves this hub as soon as you press Send. The other hub adds it when they receive it.' : 'The other hub sees your request in their Transfers page and sends it or declines with a reason.') + '</p></div>' +
        '<div class="hc-mb"><div class="hc-row2"><label>' + (send ? 'Send to' : 'Ask which hub') + '<select class="hc-input" id="tHub"><option value="">Choose a hub</option>' + hubOpts + '</select></label>' +
        (send ? '<label>Why<select class="hc-input" id="tWhy">' + Object.keys(T.reasons).filter(function (k) { return k !== 'request'; }).map(function (k) { return '<option value="' + k + '">' + esc(T.reasons[k]) + '</option>'; }).join('') + '</select></label>' : '<label>Note for them<input class="hc-input" id="tNote" maxlength="300" placeholder="e.g. weekend demand"></label>') + '</div>' +
        (send ? '<div class="hc-row2"><label>Rider taking it<select class="hc-input" id="tRider"><option value="">Not assigned</option>' + T.riders.map(function (r) { return '<option value="' + r.id + '">' + esc(r.name) + '</option>'; }).join('') + '</select></label><label>Instruction<input class="hc-input" id="tIns" maxlength="300" placeholder="e.g. keep on ice"></label></div>' +
          '<label>Outward / challan no. (optional)<input class="hc-input" id="tOut" maxlength="60" placeholder="Paper outward number, if any"></label>' : '') +
        '<label>Products <input class="hc-input" id="tFind" placeholder="Search product"></label><div class="hc-pick" id="tList"></div><div class="muted" id="tCount" style="font-size:13px"></div></div>' +
        '<div class="hc-mf"><button class="hc-btn" data-close>Cancel</button><button class="hc-btn pri" id="mGo">' + (send ? 'Send' : 'Send request') + '</button></div>', function (m, close) {
        m.querySelector('.hc-mbox').classList.add('wide');
        var qty = {}, list = m.querySelector('#tList'), hubSel = m.querySelector('#tHub');
        if (preset && preset.id) { qty[preset.id] = preset.qty; if (send && preset.reason) m.querySelector('#tWhy').value = preset.reason; var pp = prods.filter(function (x) { return x.id === preset.id; })[0]; if (pp) m.querySelector('#tFind').value = pp.name; }
        var draw = function () {
          var f = m.querySelector('#tFind').value.trim().toLowerCase(), hub = +hubSel.value;
          var items = prods.filter(function (p) { return !f || p.name.toLowerCase().indexOf(f) >= 0; });
          if (!send && hub) items.sort(function (a, b) { return (b.by[hub] || 0) - (a.by[hub] || 0); });
          list.innerHTML = items.slice(0, 120).map(function (p) {
            var info = send ? 'have ' + num(p.have) + (p.old > 0 ? ' · older ' + num(p.old) : '') : (hub ? 'they have ' + (p.by[hub] === null || p.by[hub] === undefined ? '–' : num(p.by[hub])) + ' · ' : '') + 'you have ' + num(p.have);
            return '<label><span style="flex:1">' + esc(p.name) + ' <span class="muted">' + esc(p.pack) + '</span></span><small>' + info + '</small><input class="hc-input hc-qty" type="number" min="0" step="0.01" data-tp="' + p.id + '" value="' + (qty[p.id] || '') + '" placeholder="0"></label>';
          }).join('') || '<div class="hc-empty">No products' + (send ? ' in stock' : '') + '.</div>';
        };
        var count = function () { var n = Object.keys(qty).filter(function (k) { return qty[k] > 0; }).length; m.querySelector('#tCount').textContent = n ? n + ' product(s) selected' : 'Type a quantity next to each product.'; };
        list.addEventListener('input', function (e) { var id = e.target.getAttribute('data-tp'); if (id) { qty[id] = parseFloat(e.target.value) || 0; count(); } });
        m.querySelector('#tFind').addEventListener('input', draw); hubSel.addEventListener('change', draw); draw(); count();
        m.querySelector('#mGo').onclick = function () {
          var lines = Object.keys(qty).filter(function (k) { return qty[k] > 0; }).map(function (k) { return { product_id: +k, qty: qty[k] }; });
          if (!hubSel.value) return toast('Choose the hub.', 'bad'); if (!lines.length) return toast('Enter at least one quantity.', 'bad');
          var body = send ? { to_hub: +hubSel.value, lines: lines, reason: m.querySelector('#tWhy').value, instruction: m.querySelector('#tIns').value, rider_id: +m.querySelector('#tRider').value || null, outward_ref: m.querySelector('#tOut').value }
            : { from_hub: +hubSel.value, lines: lines, note: m.querySelector('#tNote').value };
          post(send ? C.tSend : C.tRequest, body, this, function () { close(); S.tr.tab = 'all'; S.data.inventory = null; S.data.network = null; if (S.view !== 'transfers') setView('transfers'); else load(true); });
        };
      });
    }); });
  }
  function transferLines(t, mode) {   // mode: receive | fulfil
    return '<table class="hc-mini-table"><thead><tr><th>Product</th><th class="r">' + (mode === 'receive' ? 'Sent' : 'Asked') + '</th>' + (mode === 'fulfil' ? '<th class="r">You have</th>' : '') + '<th class="r">' + (mode === 'receive' ? 'Received' : 'Send') + '</th></tr></thead><tbody>' +
      t.lines.map(function (l) { var base = mode === 'receive' ? l.sent : l.requested; var have = mode === 'fulfil' && S.data.inventory ? (S.data.inventory.rows.filter(function (r) { return r.code === l.code; })[0] || { live: 0 }).live : null;
        return '<tr><td><b>' + esc(l.name) + '</b> <span class="muted">' + esc(l.pack || '') + '</span></td><td class="r">' + num(base) + '</td>' + (have !== null ? '<td class="r">' + num(have) + '</td>' : '') +
          '<td class="r"><input class="hc-input hc-qty" type="number" min="0" step="0.01" data-lc="' + esc(l.code) + '" value="' + (mode === 'fulfil' && have !== null ? Math.min(base, Math.max(0, have)) : base) + '"></td></tr>'; }).join('') + '</tbody></table>';
  }
  function readLines(m) { return Array.prototype.map.call(m.querySelectorAll('[data-lc]'), function (i) { return { code: i.getAttribute('data-lc'), qty: parseFloat(i.value) || 0 }; }); }
  function transferReceive(id) {
    var t = trById(id); if (!t) return;
    modal('<div class="hc-mh"><h3>Receive ' + esc(t.ref) + '</h3><p>From ' + esc(t.from) + (t.rider ? ' · rider ' + esc(t.rider) : '') + '. Count each product and correct it if it differs.</p></div><div class="hc-mb">' + transferLines(t, 'receive') +
      '<label>Note (needed if anything differs)<input class="hc-input" id="mNote" maxlength="300" placeholder="e.g. 1 pack damaged"></label></div><div class="hc-mf"><button class="hc-btn" data-close>Cancel</button><button class="hc-btn ok" id="mGo">Receive into stock</button></div>', function (m, close) {
      m.querySelector('.hc-mbox').classList.add('mid');
      m.querySelector('#mGo').onclick = function () { post(C.tBase + '/' + id + '/receive', { lines: readLines(m), note: m.querySelector('#mNote').value }, this, function () { close(); S.data.inventory = null; load(true); }); };
    });
  }
  function transferFulfil(id) {
    needStock(function () { var t = trById(id); if (!t) return; var T = S.data.transfers;
      modal('<div class="hc-mh"><h3>Send ' + esc(t.ref) + ' to ' + esc(t.to) + '</h3><p>' + (t.instruction ? 'Their note: ' + esc(t.instruction) + '. ' : '') + 'Set 0 for anything you cannot send.</p></div><div class="hc-mb">' + transferLines(t, 'fulfil') +
        '<div class="hc-row2"><label>Rider taking it<select class="hc-input" id="tRider"><option value="">Not assigned</option>' + T.riders.map(function (r) { return '<option value="' + r.id + '">' + esc(r.name) + '</option>'; }).join('') + '</select></label><label>Note<input class="hc-input" id="tIns" maxlength="300"></label></div><label>Outward / challan no. (optional)<input class="hc-input" id="tOut" maxlength="60"></label></div>' +
        '<div class="hc-mf"><button class="hc-btn" data-close>Cancel</button><button class="hc-btn pri" id="mGo">Send now</button></div>', function (m, close) {
        m.querySelector('.hc-mbox').classList.add('mid');
        m.querySelector('#mGo').onclick = function () { post(C.tBase + '/' + id + '/fulfil', { lines: readLines(m), rider_id: +m.querySelector('#tRider').value || null, instruction: m.querySelector('#tIns').value, outward_ref: m.querySelector('#tOut').value }, this, function () { close(); S.data.inventory = null; load(true); }); };
      });
    });
  }
  function reasonModal(title, text, btn, url, after) {
    modal('<div class="hc-mh"><h3>' + esc(title) + '</h3><p>' + esc(text) + '</p></div><div class="hc-mb"><label>Reason<input class="hc-input" id="mNote" maxlength="250"></label></div><div class="hc-mf"><button class="hc-btn" data-close>Back</button><button class="hc-btn pri" id="mGo">' + esc(btn) + '</button></div>', function (m, close) {
      m.querySelector('#mGo').onclick = function () { var n = m.querySelector('#mNote').value.trim(); if (!n) return toast('Write the reason.', 'bad'); post(url, { note: n }, this, function () { close(); if (after) after(); load(true); }); };
    });
  }

  /* ---------------- cash book ---------------- */
  function renderCash() {
    var d = S.data.cash, f = S.cash;
    var h = '<form class="hc-filter" id="cashForm"><input class="hc-input" type="date" id="cFrom" value="' + f.from + '" max="' + today() + '"><span class="muted">to</span><input class="hc-input" type="date" id="cTo" value="' + f.to + '" max="' + today() + '"><button class="hc-btn" type="submit">Show</button>' +
      '<span style="flex:1"></span><button class="hc-btn" type="button" id="cCsv">Excel (CSV)</button><button class="hc-btn" type="button" id="cPdf">PDF / Print</button><button class="hc-btn" type="button" data-cadd="adjustment">Adjustment</button><button class="hc-btn pri" type="button" data-cadd="bank">Add deposit receipt</button></form>';
    if (!d) return h + '<div class="hc-card hc-empty">Loading…</div>';
    if (!d.ok) return h + '<div class="hc-card hc-empty">' + esc(d.message) + '</div>';
    var lim = d.cash_limit || 20000, age = d.cash_age_days;
    h += '<div class="hc-grid k4">' + kpi('Cash at the hub now', money(d.in_hand), d.in_hand > lim ? 'above the ₹' + num(lim) + ' limit: deposit it' : 'closing balance today', d.in_hand > lim ? 'is-warn' : '') + kpi('Cash sale', money(d.summary.cash_sale), 'collected from riders in this period') +
      kpi('Deposited', money(d.summary.deposits), 'bank and CMS in this period') +
      kpi('Cash held since', d.cash_since ? (age === 0 ? 'Today' : age + ' day' + (age > 1 ? 's' : '')) : '–', d.cash_since ? 'oldest cash not yet deposited: ' + dshort(d.cash_since) : 'everything is deposited', age >= 2 ? 'is-warn' : (d.cash_since ? '' : 'is-ok')) + '</div>';
    return h + '<div class="hc-tablewrap"><table class="hc-table"><thead><tr><th>Date</th><th class="r">Opening</th><th class="r">Cash sale</th><th class="r">Deposits</th><th class="r">Adjustments</th><th class="r">Closing</th><th>Entries</th><th>Day-end count</th></tr></thead><tbody>' +
      d.rows.map(function (r) {
        return '<tr><td><b>' + esc(dshort(r.date)) + '</b></td><td class="r">' + money(r.opening) + '</td><td class="r">' + money(r.cash_sale) + '</td><td class="r">' + (r.deposits ? '<b>' + money(r.deposits) + '</b>' : '–') + '</td>' +
          '<td class="r">' + (r.adjustments ? money(r.adjustments) : '–') + '</td><td class="r"><b>' + money(r.closing) + '</b></td><td>' +
          (r.entries.length ? r.entries.map(function (e) {
            return '<div class="' + (e.voided ? 'is-void' : '') + '" style="font-size:12.5px;margin:2px 0">' + chip(e.type_label, e.type === 'adjustment' ? 'warn' : (e.type === 'opening' ? '' : 'ok')) + ' <b>' + money(e.amount) + '</b>' + (e.reference ? ' · ' + esc(e.reference) : '') +
              (e.receipt ? ' · <a href="' + esc(C.receiptBase + '/' + e.receipt) + '" target="_blank" rel="noopener">receipt</a>' : '') + (e.note ? '<span class="sub">' + esc(e.note) + '</span>' : '') + (e.voided ? '<span class="sub">cancelled: ' + esc(e.void_note || '') + '</span>' : (!e.voided && e.at && Date.now() / 1000 - e.at < 7 * 86400 ? ' <a href="#" data-cvoid="' + e.id + '" class="muted" style="font-size:11.5px">cancel</a>' : '')) + '</div>';
          }).join('') : '') + (r.undeposited && r.date < today() ? chip('Cash not deposited yet', 'warn') : (r.entries.length ? '' : '<span class="muted">–</span>')) + '</td>' +
          '<td>' + (r.day_closed ? chip('Submitted', 'ok') + (r.count_diff ? '<span class="sub" style="color:var(--bad)">counted ' + money(r.counted) + ' (' + money(r.count_diff) + ')</span>' : '') : '<span class="muted">–</span>') + '</td></tr>';
      }).join('') + '</tbody></table></div>';
  }
  function cashAdd(type) {
    var dep = type !== 'adjustment';
    var row = function (n) {
      return '<div class="hc-deprow" data-dep="' + n + '"><div class="hc-row2"><label>Deposit amount (₹)<input class="hc-input" type="number" step="0.01" min="0" data-f="amount"></label>' +
        '<label>Deposit type<select class="hc-input" data-f="type"><option value="bank">Bank deposit</option><option value="cms">CMS cash pickup</option></select></label></div>' +
        '<div class="hc-row2"><label>Slip / receipt no.<input class="hc-input" maxlength="120" data-f="reference"></label><label>Receipt photo or PDF<input class="hc-input" style="padding-top:7px" type="file" accept="image/*,application/pdf" data-f="receipt"></label></div>' +
        '<label>Comments<input class="hc-input" maxlength="500" data-f="note" placeholder="Optional"></label>' + (n > 0 ? '<a href="#" class="muted" data-deprm="' + n + '" style="font-size:12px">Remove this deposit</a>' : '') + '</div>';
    };
    modal('<div class="hc-mh"><h3>' + (dep ? 'Add deposit receipt' : 'Cash adjustment') + '</h3><p>Entries are kept for audit. A wrong entry can be cancelled (with a reason) within 7 days.</p></div>' +
      '<div class="hc-mb"><label>Date<input class="hc-input" type="date" id="cDate" value="' + today() + '" max="' + today() + '" min="' + dayAdd(-60) + '"></label>' +
      '<div class="hc-daybox" id="cDay"><span class="muted">Loading the day…</span></div>' +
      (dep ? '<div id="cRows">' + row(0) + '</div><button type="button" class="hc-btn sm" id="cMore">Add another deposit</button>'
        : '<div class="hc-row2"><label>Type<select class="hc-input" id="cType"><option value="adjustment">Adjustment</option><option value="opening">Opening balance (once)</option></select></label>' +
          '<label>Amount (₹)<input class="hc-input" type="number" step="0.01" id="cAmt" placeholder="-50 for short, 50 for extra"></label></div><label>Reason<input class="hc-input" id="cNote" maxlength="500" placeholder="Required"></label>') +
      '</div><div class="hc-mf"><button class="hc-btn" data-close>Cancel</button><button class="hc-btn pri" id="mGo">Save</button></div>', function (m, close) {
      m.querySelector('.hc-mbox').classList.add('mid');
      var day = null, n = 1, box = m.querySelector('#cDay');
      var paint = function () {
        if (!day) return;
        var extra = 0;
        if (dep) m.querySelectorAll('[data-f="amount"]').forEach(function (i) { extra -= parseFloat(i.value) || 0; });
        else { var a = parseFloat(m.querySelector('#cAmt').value) || 0; extra = m.querySelector('#cType').value === 'opening' ? a - day.opening : a; }
        box.innerHTML = '<div class="hc-kv"><span>Opening balance</span><b>' + money(day.opening) + '</b><span>Cash sale</span><b>' + money(day.cash_sale) + '</b>' +
          '<span>Already deposited</span><b>' + money(day.deposits) + '</b>' + (day.adjustments ? '<span>Adjustments</span><b>' + money(day.adjustments) + '</b>' : '') +
          '<span>Closing balance now</span><b>' + money(day.closing) + '</b><span>Closing after this entry</span><b style="color:' + (day.closing + extra < 0 ? 'var(--bad)' : 'var(--ok)') + '">' + money(day.closing + extra) + '</b></div>' +
          (day.closing + extra < -0.5 ? '<p style="color:var(--bad);font-size:12.5px;margin:6px 0 0">This is more than the cash held for this day. Check the amount.</p>' : '');
      };
      var loadDay = function () {
        var dt = m.querySelector('#cDate').value || today(); box.innerHTML = '<span class="muted">Loading the day…</span>';
        api('GET', C.cash + '?from=' + dt + '&to=' + dt).then(function (j) { day = j.ok && j.rows[0] ? j.rows[0] : null; if (!day) box.innerHTML = '<span class="muted">Could not load this day.</span>'; paint(); });
      };
      m.querySelector('#cDate').onchange = loadDay; m.addEventListener('input', paint); m.addEventListener('change', function (e) { if (e.target.id === 'cType') paint(); }); loadDay();
      if (dep) {
        m.querySelector('#cMore').onclick = function () { if (m.querySelectorAll('[data-dep]').length >= 5) return toast('Up to 5 deposits at a time.', 'bad'); m.querySelector('#cRows').insertAdjacentHTML('beforeend', row(n++)); };
        m.addEventListener('click', function (e) { var r = e.target.closest && e.target.closest('[data-deprm]'); if (r) { e.preventDefault(); var el = m.querySelector('[data-dep="' + r.getAttribute('data-deprm') + '"]'); if (el) el.remove(); paint(); } });
      }
      m.querySelector('#mGo').onclick = function () {
        var b = this, dt = m.querySelector('#cDate').value, jobs = [];
        if (dep) {
          var bad = '';
          m.querySelectorAll('[data-dep]').forEach(function (r) {
            var g = function (f) { return r.querySelector('[data-f="' + f + '"]'); }, amt = parseFloat(g('amount').value) || 0, file = g('receipt').files[0];
            if (amt <= 0) bad = bad || 'Enter the amount of every deposit.'; else if (!g('reference').value.trim()) bad = bad || 'Enter the slip / receipt number of every deposit.'; else if (!file) bad = bad || 'Attach the receipt photo or PDF for every deposit.';
            var fd = new FormData(); fd.append('date', dt); fd.append('type', g('type').value); fd.append('amount', amt); fd.append('reference', g('reference').value); fd.append('note', g('note').value); if (file) fd.append('receipt', file);
            jobs.push(fd);
          });
          if (bad) return toast(bad, 'bad');
        } else {
          var fd = new FormData(); fd.append('date', dt); fd.append('type', m.querySelector('#cType').value); fd.append('amount', m.querySelector('#cAmt').value || '0'); fd.append('note', m.querySelector('#cNote').value); jobs.push(fd);
        }
        b.disabled = true; var saved = 0;
        var next = function () {
          if (!jobs.length) { b.disabled = false; close(); toast(saved + ' entr' + (saved === 1 ? 'y' : 'ies') + ' saved.', 'ok'); load(true); return; }
          upload(C.cashAdd, jobs.shift()).then(function (j) {
            if (!j.ok) { b.disabled = false; toast((saved ? saved + ' saved, then: ' : '') + (j.message || 'Failed'), 'bad'); if (saved) load(true); return; }
            saved++; next();
          }).catch(function () { b.disabled = false; toast('Network problem. ' + saved + ' saved.', 'bad'); });
        };
        next();
      };
    });
  }
  function cashPrint() {
    var d = S.data.cash; if (!d || !d.ok) return; var w = window.open('', '_blank'); if (!w) return toast('Allow pop-ups to print.', 'bad');
    w.document.write('<!doctype html><meta charset="utf-8"><title>Cash deposits ' + esc(C.hub) + ' ' + esc(d.from) + ' to ' + esc(d.to) + '</title><style>body{font:13px system-ui;margin:24px;color:#111}table{width:100%;border-collapse:collapse;margin-top:12px}td,th{border:1px solid #999;padding:5px 7px;text-align:left;vertical-align:top}th{background:#eee}.r{text-align:right}</style>' +
      '<h2 style="margin:0">Freshful · Cash deposits</h2><p>' + esc(C.hub) + ' · ' + esc(dshort(d.from)) + ' to ' + esc(dshort(d.to)) + '<br>Cash sale ' + money(d.summary.cash_sale) + ' · Deposited ' + money(d.summary.deposits) + ' · Adjustments ' + money(d.summary.adjustments) + ' · Cash at hub ' + money(d.in_hand) + '</p>' +
      '<table><tr><th>Date</th><th class="r">Opening</th><th class="r">Cash sale</th><th class="r">Deposits</th><th class="r">Adjustments</th><th class="r">Closing</th><th>Entries</th><th>Hub</th></tr>' +
      d.rows.map(function (r) { return '<tr><td>' + esc(r.date) + '</td><td class="r">' + money(r.opening) + '</td><td class="r">' + money(r.cash_sale) + '</td><td class="r">' + money(r.deposits) + '</td><td class="r">' + money(r.adjustments) + '</td><td class="r">' + money(r.closing) + '</td><td>' +
        r.entries.filter(function (e) { return !e.voided; }).map(function (e) { return esc(e.type_label) + ' ' + money(e.amount) + (e.reference ? ' (' + esc(e.reference) + ')' : '') + (e.note ? ' - ' + esc(e.note) : ''); }).join('<br>') + '</td><td>' + esc(C.hub.split(' · ')[0]) + '</td></tr>'; }).join('') +
      '</table><p style="margin-top:30px">Printed ' + esc(new Date().toLocaleString('en-IN')) + ' by ' + esc(C.actor) + '</p><script>print()<\/script>');
    w.document.close();
  }


  /* ---------------- riders ---------------- */
  var RS2 = { available: ['At hub', 'ok'], out: ['On delivery', 'info'], returning: ['Returning', 'warn'], off: ['Off duty', ''] };
  function renderRiders() {
    var d = S.data.riders; if (!d) return '';
    var on = d.riders.filter(function (r) { return r.active; }), atHub = on.filter(function (r) { return r.state === 'available'; }).length;
    var h = '<div class="hc-grid k4">' + kpi('Active riders', on.length, (d.riders.length - on.length) + ' switched off') + kpi('At the hub now', atHub, 'ready for the next order', atHub ? 'is-ok' : 'is-warn') +
      kpi('Deliveries today', d.riders.reduce(function (s, r) { return s + r.today; }, 0), 'by all riders') + kpi('Cash with riders', money(d.riders.reduce(function (s, r) { return s + r.cash_due; }, 0)), 'collect in POS › Riders & cash') + '</div>';
    h += '<div class="hc-filter"><span class="muted">Riders log in to the rider app with their employee ID or mobile number and the password set here. Live status is changed in the POS.</span><span style="flex:1"></span><button class="hc-btn pri" data-redit="0">Add rider</button></div>';
    if (!d.riders.length) return h + '<div class="hc-card hc-empty">No riders yet. Add the first one.</div>';
    return h + '<div class="hc-tablewrap"><table class="hc-table"><thead><tr><th>Rider</th><th>Employee ID</th><th>Status now</th><th class="r">Today</th><th class="r">Last 7 days</th><th class="r">Last 30 days</th><th class="r">Cash with rider</th><th>Account</th><th></th></tr></thead><tbody>' +
      d.riders.map(function (r) { var st = r.active ? (RS2[r.state] || ['', '']) : ['Switched off', 'bad'];
        return '<tr' + (r.active ? '' : ' style="opacity:.6"') + '><td><b>' + esc(r.name) + '</b><span class="sub"><a href="tel:' + esc(r.phone) + '">' + esc(r.phone) + '</a>' + (r.gender ? ' · ' + esc(r.gender) : '') + '</span></td><td>' + esc(r.employee_id || '–') + '</td>' +
          '<td>' + chip(st[0], st[1]) + (r.out ? '<span class="sub">' + r.out + ' order(s) out</span>' : '') + '</td><td class="r"><b>' + r.today + '</b></td><td class="r">' + r.week + '</td><td class="r">' + r.month + '</td><td class="r">' + (r.cash_due ? '<b style="color:var(--warn)">' + money(r.cash_due) + '</b>' : '–') + '</td>' +
          '<td>' + (r.app ? chip('App linked', 'info') : '<span class="muted">App not used yet</span>') + '</td><td class="r" style="white-space:nowrap"><button class="hc-btn sm" data-redit="' + r.id + '">Edit</button> <button class="hc-btn sm' + (r.active ? '' : ' ok') + '" data-rtog="' + r.id + '">' + (r.active ? 'Switch off' : 'Switch on') + '</button></td></tr>';
      }).join('') + '</tbody></table></div>';
  }
  function riderEdit(id) {
    var r = id ? (S.data.riders.riders.filter(function (x) { return x.id === id; })[0] || {}) : {};
    modal('<div class="hc-mh"><h3>' + (id ? 'Edit ' + esc(r.name) : 'Add a rider') + '</h3><p>' + (id ? 'Leave the password empty to keep the current one.' : 'The rider will belong to this hub.') + '</p></div><div class="hc-mb">' +
      '<label>Full name<input class="hc-input" id="rName" maxlength="60" value="' + esc(r.name || '') + '"></label><div class="hc-row2"><label>Mobile number<input class="hc-input" id="rPhone" inputmode="numeric" maxlength="14" value="' + esc(r.phone || '') + '"></label>' +
      '<label>Gender<select class="hc-input" id="rGender">' + ['', 'Male', 'Female', 'Other'].map(function (g) { return '<option' + ((r.gender || '') === g ? ' selected' : '') + ' value="' + g + '">' + (g || 'Not set') + '</option>'; }).join('') + '</select></label></div>' +
      (id ? '' : '<label>Status<select class="hc-input" id="rStatus"><option value="active">Active: can take orders</option><option value="inactive">Inactive: not yet working</option></select></label>') +
      '<div class="hc-row2"><label>Employee ID<input class="hc-input" id="rEmp" maxlength="20" value="' + esc(r.employee_id || '') + '"' + (id && r.employee_id ? ' readonly' : '') + '></label><label>' + (id ? 'New app password' : 'App password') + '<input class="hc-input" id="rPass" type="text" maxlength="60" autocomplete="new-password" placeholder="' + (id ? 'Keep current' : 'At least 6 characters') + '"></label></div></div>' +
      '<div class="hc-mf"><button class="hc-btn" data-close>Cancel</button><button class="hc-btn pri" id="mGo">' + (id ? 'Save' : 'Add rider') + '</button></div>', function (m, close) {
      m.querySelector('#mGo').onclick = function () {
        post(C.riderSave, { id: id || null, name: m.querySelector('#rName').value, phone: m.querySelector('#rPhone').value, gender: m.querySelector('#rGender').value, employee_id: m.querySelector('#rEmp').value, password: m.querySelector('#rPass').value, status: id ? undefined : m.querySelector('#rStatus').value }, this, function () { close(); load(true); });
      };
    });
  }

  /* ---------------- customers ---------------- */
  var SEG = { all: 'All', 'new': 'New (1 order)', repeat: 'Repeat (2-4)', loyal: 'Loyal (5+)', at_risk: 'Not ordered in 30 days', no_order: 'Never ordered' };
  function cusRows() { var d = S.data.customers, q = S.cus.q.trim().toLowerCase(); return d ? d.customers.filter(function (c) { return !q || (c.name + ' ' + c.phone + ' ' + c.email).toLowerCase().indexOf(q) >= 0; }) : []; }
  function renderCustomers() {
    var d = S.data.customers; if (!d) return '';
    var rows = cusRows(), sg = d.segments, spend = 0, ord = 0; d.customers.forEach(function (c) { spend += c.spend; ord += c.orders; });
    var h = '<div class="hc-grid k4">' + kpi('Customers', num(sg.all), 'address in this hub area or ordered here') + kpi('Repeat customers', num(sg.repeat + sg.loyal), sg.all ? Math.round((sg.repeat + sg.loyal + sg.at_risk) * 100 / Math.max(1, sg.all - sg.no_order)) + '% of buyers came back' : '') +
      kpi('Not ordered in 30 days', num(sg.at_risk), 'win them back with an offer', sg.at_risk ? 'is-warn' : '') + kpi('Average spend', money(ord ? spend / Math.max(1, d.customers.filter(function (c) { return c.orders; }).length) : 0), 'per customer (in this list)') + '</div>';
    h += '<div class="hc-filter"><input class="hc-input" id="cuQ" placeholder="Search name, mobile or email" value="' + esc(S.cus.q) + '" style="min-width:240px"><div class="hc-seg">' +
      Object.keys(SEG).map(function (k) { return '<button data-cseg="' + k + '" class="' + ((S.cus.segment || 'all') === k ? 'is-on' : '') + '">' + SEG[k] + '<span class="n">' + num(sg[k]) + '</span></button>'; }).join('') + '</div><span style="flex:1"></span><button class="hc-btn" id="cuCsv">Download CSV</button></div>';
    if (!rows.length) return h + '<div class="hc-card hc-empty">No customers match.</div>';
    var SC = { 'new': 'info', repeat: '', loyal: 'ok', at_risk: 'warn', no_order: '' };
    return h + '<div class="hc-tablewrap"><table class="hc-table"><thead><tr><th>Customer</th><th>Type</th><th class="r">Orders</th><th class="r">Spent here</th><th>Last order</th><th class="r">Wallet</th><th>Joined</th></tr></thead><tbody>' +
      (S._pgC = pager('cus', rows, S.cus.segment + '|' + S.cus.q)).rows.map(function (c) {
        return '<tr class="click" data-cus="' + c.id + '"><td><b>' + esc(c.name) + '</b><span class="sub">' + esc(c.phone) + (c.email ? ' · ' + esc(c.email) : '') + '</span></td><td>' + chip(SEG[c.segment].replace(/ \(.*\)/, ''), SC[c.segment]) + '</td>' +
          '<td class="r">' + c.orders + '</td><td class="r"><b>' + money(c.spend) + '</b></td><td>' + (c.last ? esc(new Date(c.last * 1000).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' })) : '–') + '</td>' +
          '<td class="r">' + (c.wallet ? money(c.wallet) : '–') + '</td><td>' + (c.joined ? esc(new Date(c.joined * 1000).toLocaleDateString('en-IN', { month: 'short', year: 'numeric' })) : '–') + '</td></tr>';
      }).join('') + '</tbody></table></div>' + S._pgC.html + (d.total > d.customers.length ? '<p class="muted">The 1,000 biggest customers are listed. Search to find others.</p>' : '');
  }
  function customerView(id) {
    api('GET', C.customerBase + '/' + id).then(function (j) {
      if (!j.ok) return toast(j.message || 'Not found', 'bad');
      var c = j.customer, s = j.stats;
      var SC = { cancelled: 'bad', delivered: 'ok', unsettled: 'warn', out: 'info', 'new': 'warn' };
      modal('<div class="hc-mh"><h3>' + esc(c.name || 'Customer') + '</h3><p><a href="tel:' + esc(c.phone) + '">' + esc(c.phone) + '</a>' + (c.email ? ' · ' + esc(c.email) : '') + (c.joined ? ' · joined ' + esc(new Date(c.joined * 1000).toLocaleDateString('en-IN', { month: 'short', year: 'numeric' })) : '') + '</p></div><div class="hc-mb">' +
        '<div class="hc-grid k4" style="margin:0">' + kpi('Orders here', s.orders, '') + kpi('Spent here', money(s.spend), '') + kpi('Average order', money(s.aov), '') + kpi('Wallet', money(c.wallet), '') + '</div>' +
        (j.addresses.length ? '<div><b style="font-size:13px">Addresses</b>' + j.addresses.map(function (a) { return '<div class="muted" style="font-size:13px;margin-top:4px">' + (a.type ? chip(a.type, '') + ' ' : '') + esc(a.text) + (a.here === false ? ' ' + chip('Other hub', '') : '') + '</div>'; }).join('') + '</div>' : '') +
        '<div class="hc-filter" style="margin:0"><b style="font-size:13px;flex:1">Orders</b><input class="hc-input" type="date" id="cuF" style="height:32px"><span class="muted">to</span><input class="hc-input" type="date" id="cuT" style="height:32px"><a href="#" id="cuClr" class="muted">all dates</a></div>' +
        (j.orders.length ? '<table class="hc-mini-table" id="cuOrd"><thead><tr><th>Order</th><th>Date</th><th>Type</th><th>Hub</th><th>Status</th><th>Payment</th><th class="r">Amount</th></tr></thead><tbody>' + j.orders.map(function (o) {
          return '<tr data-at="' + (o.at || 0) + '"><td><a href="' + esc(posOpen(o.order_id)) + '" target="_blank" rel="noopener"><b>' + esc(o.order_id) + '</b></a><span class="muted"> · ' + o.items + ' item(s)</span></td><td>' + esc(when(o.at)) + '</td><td>' + esc(o.type || '') + '</td><td>' + esc(C.hub.split(' · ')[0]) + '</td><td>' + chip(o.status, SC[o.stage] || '') + '</td><td>' + esc(o.pay === 'Cash' ? 'COD' : (o.pay || '')) + '</td><td class="r">' + money(o.amount) + '</td></tr>'; }).join('') + '</tbody></table><div class="muted" id="cuNone" hidden>No orders in these dates.</div>' : '<div class="muted">No orders at this hub.</div>') +
        '<b style="font-size:13px">Wallet and payment history</b>' + (j.wallet.length ? '<div style="overflow:auto"><table class="hc-mini-table"><thead><tr><th>Date</th><th>What</th><th>Order</th><th class="r">Order amount</th><th class="r">Wallet used</th><th class="r">Credit</th><th>Payment</th><th class="r">Balance</th></tr></thead><tbody>' + j.wallet.map(function (w) {
          return '<tr><td>' + esc(when(w.at)) + '</td><td>' + esc(w.what) + '</td><td>' + (w.ref ? '<a href="' + esc(posOpen(w.ref)) + '" target="_blank" rel="noopener">' + esc(w.ref) + '</a>' : '–') + '</td>' +
            '<td class="r">' + (w.order_amount ? money(w.order_amount) : '–') + '</td><td class="r">' + (w.wallet_amount ? '<span class="down">' + money(w.wallet_amount) + '</span>' : '–') + '</td><td class="r">' + (w.credit_amount ? '<span class="up">+' + money(w.credit_amount) + '</span>' : '–') + '</td>' +
            '<td>' + esc(w.payment_type === 'Cash' ? 'COD' : (w.payment_type || '–')) + '</td><td class="r">' + (w.balance !== null && w.balance !== undefined ? money(w.balance) : '–') + '</td></tr>'; }).join('') + '</tbody></table></div>' : '<div class="muted">No wallet activity.</div>') +
        '</div><div class="hc-mf"><button class="hc-btn" data-close>Close</button><a class="hc-btn pri" style="flex:1;display:grid;place-items:center;text-decoration:none;height:44px" href="https://wa.me/91' + esc(String(c.phone).slice(-10)) + '" target="_blank" rel="noopener">WhatsApp</a></div>', function (m) {
        m.querySelector('.hc-mbox').classList.add('wide');
        var f = m.querySelector('#cuF'), t2 = m.querySelector('#cuT');
        var apply = function () {
          var a = f.value ? new Date(f.value + 'T00:00:00').getTime() / 1000 : 0, b = t2.value ? new Date(t2.value + 'T23:59:59').getTime() / 1000 : 1e12, shown = 0;
          m.querySelectorAll('#cuOrd tbody tr').forEach(function (tr) { var at = +tr.getAttribute('data-at'); var on = at >= a && at <= b; tr.hidden = !on; if (on) shown++; });
          var none = m.querySelector('#cuNone'); if (none) none.hidden = shown > 0;
        };
        f.onchange = apply; t2.onchange = apply; m.querySelector('#cuClr').onclick = function (e) { e.preventDefault(); f.value = ''; t2.value = ''; apply(); };
      });
    });
  }

  /* ---------------- grievances ---------------- */
  function grievanceModal() {
    var from = dayAdd(-29), to = today();
    var draw = function (m) {
      var box = m.querySelector('#gvList'); box.innerHTML = '<div class="hc-empty">Loading…</div>';
      api('GET', C.grievances + '?from=' + from + '&to=' + to).then(function (j) {
        if (!j.ok || !j.available) { box.innerHTML = '<div class="hc-empty">' + esc(j.reason || j.message || 'Not available') + '</div>'; return; }
        var pg = pager('griev', j.rows, from + to, 25);
        box.innerHTML = j.rows.length ? '<table class="hc-mini-table"><thead><tr><th>Date</th><th>Order</th><th>Customer</th><th>Category</th><th>Details</th><th>Status</th></tr></thead><tbody>' + pg.rows.map(function (g) {
          return '<tr><td>' + esc(when(g.at)) + '</td><td>' + (g.order_id ? '<a href="' + esc(posOpen(g.order_id)) + '" target="_blank" rel="noopener">' + esc(g.order_id) + '</a>' : '–') + '</td><td>' + esc(g.customer || '–') + '</td><td>' + esc(g.category || '–') + '</td><td>' + esc(g.text || '') + '</td><td>' + (g.status ? chip(g.status, g.open ? 'warn' : 'ok') : '–') + '</td></tr>'; }).join('') + '</tbody></table>' + pg.html
          : '<div class="hc-empty">No grievances in these dates.</div>';
      });
    };
    modal('<div class="hc-mh"><h3>Customer grievances</h3><p>Grievances about this hub\'s orders. Replies and closing are handled by customer care.</p></div><div class="hc-mb"><div class="hc-filter" style="margin:0"><input class="hc-input" type="date" id="gF" value="' + from + '" max="' + today() + '"><span class="muted">to</span><input class="hc-input" type="date" id="gT" value="' + to + '" max="' + today() + '"><button class="hc-btn sm" id="gGo">Show</button></div><div id="gvList"></div></div><div class="hc-mf"><button class="hc-btn" data-close>Close</button></div>', function (m) {
      m.querySelector('.hc-mbox').classList.add('wide');
      m.querySelector('#gGo').onclick = function () { from = m.querySelector('#gF').value || from; to = m.querySelector('#gT').value || to; draw(m); };
      m.addEventListener('click', function (e) { var p2 = e.target.closest && e.target.closest('[data-pg]'); if (p2) { e.stopPropagation(); var a = p2.getAttribute('data-pg').split('|'); S.pg[a[0]] = +a[1]; draw(m); } }, true);
      draw(m);
    });
  }

  /* ---------------- events ---------------- */
  function bindView() {
    var q = $('invQ'); if (q) q.addEventListener('input', function () { S.inv.q = q.value; var pos = q.selectionStart; render(); var n = $('invQ'); n.focus(); n.setSelectionRange(pos, pos); });
    var c = $('invCat'); if (c) c.onchange = function () { S.inv.cat = c.value; render(); };
    var f = $('ordForm'); if (f) f.onsubmit = function (e) {
      e.preventDefault(); S.ord = { from: $('oFrom').value || today(), to: $('oTo').value || today(), stage: $('oStage').value, type: $('oType').value, pay: $('oPay').value, q: $('oQ').value.trim(), sched: '' };
      S.data.orders = null; render(); load(true);
    };
    live('lkQ', function (v) { S.lock.q = v; }); live('nQ', function (v) { S.net.q = v; }); live('cuQ', function (v) { S.cus.q = v; });
    var oos = $('nOos'); if (oos) oos.onchange = function () { S.net.oos = oos.checked; render(); };
    var lf = $('ledForm'); if (lf) lf.onsubmit = function (e) { e.preventDefault(); S.led = { from: $('lFrom').value || today(), to: $('lTo').value || today(), type: $('lType').value, q: $('lQ').value.trim() }; S.data.ledger = null; render(); load(true); };
    var cf = $('cashForm'); if (cf) cf.onsubmit = function (e) { e.preventDefault(); S.cash = { from: $('cFrom').value || dayAdd(-13), to: $('cTo').value || today() }; S.data.cash = null; render(); load(true); };
    if ($('rcvAll')) recvRefresh();
    var rf = $('rcvFile'); if (rf) rf.onchange = function () { var file = rf.files[0]; if (!file) return; var rd = new FileReader(); rd.onload = function () { importSheet(String(rd.result)); }; rd.readAsText(file); };
  }
  function live(id, set) { var el = $(id); if (!el) return; el.addEventListener('input', function () { set(el.value); var p = el.selectionStart; render(); var n = $(id); n.focus(); n.setSelectionRange(p, p); }); }
  function importSheet(txt) {
    var lines = txt.replace(/^﻿/, '').split(/\r?\n/).filter(function (l) { return l.trim(); }), n = 0, byCode = {};
    pendingRows().forEach(function (r) { byCode[r.code] = r; });
    lines.slice(1).forEach(function (l) { var c = l.split(',').map(function (x) { return x.replace(/^"|"$/g, '').trim(); }); var r = byCode[c[0]]; if (!r) return; var q = parseFloat(c[4]); if (isNaN(q)) return; S.recv[r.inv_id] = { q: q, n: c[5] || '' }; n++; });
    render(); toast(n ? n + ' line(s) filled from the sheet. Check and press Receive all.' : 'No matching products found in the sheet.', n ? 'ok' : 'bad');
  }
  document.addEventListener('input', function (e) {
    var t = e.target, id;
    if ((id = t.getAttribute && t.getAttribute('data-rq'))) { S.recv[id] = S.recv[id] || { q: '', n: '' }; S.recv[id].q = t.value; recvRefresh(); }
    if ((id = t.getAttribute && t.getAttribute('data-rn'))) { S.recv[id] = S.recv[id] || { q: '', n: '' }; S.recv[id].n = t.value; if (S.recv[id].q === '') S.recv[id].q = (pendingRows().filter(function (r) { return r.inv_id === +id; })[0] || {}).pending; }
  });
  document.addEventListener('click', function (e) {
    var t = e.target; if (!t.closest) return; var x;
    var go = t.closest('[data-go]'); if (go) { var g = go.getAttribute('data-go');
      if (g === 'pos') window.open(C.pos, '_blank'); else if (g === 'report') window.open(C.report, '_blank');
      else if (g === 'ord:today') ordPreset({}); else if (g === 'ord:cancelled') ordPreset({ stage: 'cancelled' });
      else if (g === 'inv:alerts') { S.inv.st = 'out'; setView('inventory'); } else setView(g); return; }
    if ((x = t.closest('[data-sched]'))) { S.ord.sched = x.getAttribute('data-sched'); S.data.orders = null; setView('orders'); return; }
    if ((x = t.closest('[data-osched]'))) { var os = x.getAttribute('data-osched');
      if (os === 'p:today') ordPreset({}); else if (os === 'p:cancel') ordPreset({ stage: 'cancelled' });
      else { S.ord.sched = os; S.ord.status = ''; S.data.orders = null; render(); load(true); } return; }
    var st = t.closest('[data-st]'); if (st) { S.inv.st = st.getAttribute('data-st'); render(); return; }
    var pd = t.closest('[data-pday]'); if (pd) { S.prepDay = pd.getAttribute('data-pday'); render(); return; }
    var rv = t.closest('[data-recv]'); if (rv) { receiveModal(+rv.getAttribute('data-recv')); return; }
    var ct = t.closest('[data-count]'); if (ct) { countModal(+ct.getAttribute('data-count')); return; }
    if ((x = t.closest('[data-trecv]'))) { e.stopPropagation(); transferReceive(+x.getAttribute('data-trecv')); return; }
    if ((x = t.closest('[data-tful]'))) { e.stopPropagation(); transferFulfil(+x.getAttribute('data-tful')); return; }
    if ((x = t.closest('[data-tdec]'))) { var tid = +x.getAttribute('data-tdec'); reasonModal('Decline this request', 'The other hub will see your reason.', 'Decline', C.tBase + '/' + tid + '/decline'); return; }
    if ((x = t.closest('[data-tcan]'))) { var cid = +x.getAttribute('data-tcan'), tt = trById(cid); reasonModal('Cancel ' + (tt ? tt.ref : 'transfer'), tt && tt.status === 'in_transit' ? 'The stock comes back into this hub.' : 'The request is withdrawn.', 'Cancel transfer', C.tBase + '/' + cid + '/cancel', function () { S.data.inventory = null; }); return; }
    if ((x = t.closest('[data-tview]'))) { trDetail(+x.getAttribute('data-tview')); return; }
    if ((x = t.closest('[data-tnew]'))) { transferNew(x.getAttribute('data-tnew')); return; }
    if ((x = t.closest('[data-ttab]'))) { S.tr.tab = x.getAttribute('data-ttab'); render(); return; }
    if ((x = t.closest('[data-cadd]'))) { cashAdd(x.getAttribute('data-cadd')); return; }
    if ((x = t.closest('[data-cvoid]'))) { e.preventDefault(); reasonModal('Cancel this cash entry', 'It stays in the book, struck through, with your reason.', 'Cancel entry', C.cashBase + '/' + x.getAttribute('data-cvoid') + '/void'); return; }
    if ((x = t.closest('[data-redit]'))) { riderEdit(+x.getAttribute('data-redit')); return; }
    if ((x = t.closest('[data-rtog]'))) {
      var rid = +x.getAttribute('data-rtog'), rr = (S.data.riders ? S.data.riders.riders : []).filter(function (y) { return y.id === rid; })[0] || {}, btn = x;
      confirmBox(rr.active ? 'Switch off ' + rr.name + '?' : 'Switch on ' + rr.name + '?', rr.active ? 'They will not be given new orders and cannot be chosen in the POS until switched on again.' : 'They can be given orders again.', rr.active ? 'Switch off' : 'Switch on',
        function () { post(C.riderBase + '/' + rid + '/toggle', {}, btn, function () { load(true); }); }, rr.active);
      return;
    }
    if ((x = t.closest('[data-pg]'))) { var pp = x.getAttribute('data-pg').split('|'); S.pg[pp[0]] = +pp[1]; render(); var tw = document.querySelector('.hc-tablewrap'); if (tw) tw.scrollIntoView({ block: 'start' }); return; }
    if ((x = t.closest('[data-ovtab]'))) { S.ovTab = x.getAttribute('data-ovtab'); render(); return; }
    if ((x = t.closest('[data-ocell]'))) { e.preventDefault(); var f = JSON.parse(x.getAttribute('data-ocell')); ordPreset({ stage: f.stage, type: f.type, status: f.status }); return; }
    if ((x = t.closest('[data-oclear]'))) { e.preventDefault(); S.ord.status = ''; S.data.orders = null; render(); load(true); return; }
    if ((x = t.closest('[data-griev]'))) { grievanceModal(); return; }
    if ((x = t.closest('[data-move]'))) { var mr = (S.data.inventory ? S.data.inventory.rows : []).filter(function (y) { return y.id === +x.getAttribute('data-move'); })[0]; if (mr) transferNew('send', { id: mr.id, qty: Math.min(mr.old, mr.live), reason: 'older' }); return; }
    if (t.closest('#cPdf')) { cashPrint(); return; }
    if ((x = t.closest('[data-cseg]'))) { S.cus.segment = x.getAttribute('data-cseg') === 'all' ? '' : x.getAttribute('data-cseg'); load(true); return; }
    if ((x = t.closest('[data-cus]'))) { customerView(+x.getAttribute('data-cus')); return; }
    if (t.closest('#rcvAll')) {
      var lines = pendingRows().map(function (r) { var v = S.recv[r.inv_id] || { q: r.pending, n: '' }; return { inv_id: r.inv_id, received: parseFloat(v.q === '' ? r.pending : v.q) || 0, note: v.n || '' }; });
      var bad = lines.filter(function (l, i) { return Math.abs(l.received - pendingRows()[i].pending) > 0.0001 && !l.note.trim(); });
      if (bad.length) return toast(bad.length + ' product(s) differ from what was sent. Write a note for each.', 'bad');
      var rb = t.closest('#rcvAll'), dif = lines.filter(function (l, i) { return Math.abs(l.received - pendingRows()[i].pending) > 0.0001; }).length;
      confirmBox('Receive ' + lines.length + ' product(s) into stock?', (dif ? dif + ' with a difference (your notes are saved). ' : 'Everything as sent. ') + 'Live stock goes up by what you entered and every line is logged in the stock ledger.', 'Receive all',
        function () { post(C.inwardMany, { lines: lines }, rb, function () { S.recv = {}; load(true); }); });
      return;
    }
    if (t.closest('#rcvCsv')) { csv('receive-sheet-' + today() + '.csv', ['Code', 'Product', 'Pack', 'Sent', 'Received', 'Note'], pendingRows().map(function (r) { return [r.code, r.name, r.pack, r.pending, r.pending, '']; })); return; }
    if (t.closest('#lCsv') && S.data.ledger) { csv('stock-ledger-' + S.led.from + '-to-' + S.led.to + '.csv', ['Date', 'Code', 'Product', 'Type', 'Reference', 'Before', 'Change', 'After', 'Sent', 'By', 'Note'], S.data.ledger.moves.map(function (m) { return [when(m.at), m.code, m.name, (MT[m.type] || [m.type])[0], m.ref || '', m.before, m.change, m.after, m.expected === null ? '' : m.expected, m.by || '', m.note || '']; })); return; }
    if (t.closest('#nCsv') && S.data.network) { var nd = S.data.network; csv('all-hubs-stock-' + today() + '.csv', ['Code', 'Product', 'Pack'].concat(nd.hubs.map(function (h2) { return h2.hub; })).concat(['Total']), nd.rows.map(function (r) { return [r.code, r.name, r.pack].concat(nd.hubs.map(function (h2) { var v = r.by[h2.id]; return v === null || v === undefined ? '' : v; })).concat([r.total]); })); return; }
    if (t.closest('#cCsv') && S.data.cash && S.data.cash.ok) { csv('cash-book-' + S.cash.from + '-to-' + S.cash.to + '.csv', ['Date', 'Opening', 'Cash sale', 'Deposits', 'Adjustments', 'Closing', 'Entries'], S.data.cash.rows.map(function (r) { return [r.date, r.opening, r.cash_sale, r.deposits, r.adjustments, r.closing, r.entries.filter(function (x2) { return !x2.voided; }).map(function (x2) { return x2.type_label + ' ' + x2.amount + (x2.reference ? ' ' + x2.reference : ''); }).join('; ')]; })); return; }
    if (t.closest('#cuCsv') && S.data.customers) { csv('customers-' + today() + '.csv', ['Name', 'Mobile', 'Email', 'Type', 'Orders', 'Spent', 'Last order', 'Wallet'], cusRows().map(function (c2) { return [c2.name, c2.phone, c2.email, SEG[c2.segment], c2.orders, c2.spend, c2.last ? new Date(c2.last * 1000).toISOString().slice(0, 10) : '', c2.wallet]; })); return; }
    var ps = t.closest('[data-pos]'); if (ps) { window.open(posOpen(ps.getAttribute('data-pos')), '_blank'); return; }
    if (t.closest('#hcRetry')) { load(true); return; }
    var nav = t.closest('[data-view]'); if (nav) { e.preventDefault(); setView(nav.getAttribute('data-view')); $('hcSide').classList.remove('is-open'); }
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { var m = document.querySelector('.hc-modal'); if (m) m.remove(); } });
  function setView(v) {
    if (!TITLES[v]) v = 'overview';
    S.view = v; if (location.hash !== '#' + v) history.replaceState(null, '', '#' + v); store('ff-hub-view', v);
    document.querySelectorAll('[data-view]').forEach(function (a) { a.classList.toggle('is-on', a.getAttribute('data-view') === v); });
    $('hcTitle').textContent = TITLES[v]; document.title = TITLES[v] + ' · Hub · Freshful';
    if (S.data[dataKey(v)]) render(); else $('hcView').innerHTML = '<div class="hc-card hc-empty">Loading…</div>';
    window.scrollTo(0, 0);
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
  // badges for transfers/cash also when the first screen is not the overview
  if (S.view !== 'overview') api('GET', C.overview).then(function (j) { if (j.ok) { S.data.overview = j; var sv = S.view; S.view = 'overview'; renderOverview(); S.view = sv; } });
})();

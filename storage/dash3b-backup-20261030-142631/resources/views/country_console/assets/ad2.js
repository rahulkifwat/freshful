/* Freshful admin console: dashboard 2 and the hub / delivery boy drawer (30 Oct 2026).
   Plugs into cc.js through window.CCX (after ad.js and dr.js). Replaces the Admin dashboard page; the old numbers stay in the API. */
(function () {
  'use strict';
  var prev = window.CCX;
  window.CCX = function (K) {
    if (typeof prev === 'function') { try { prev(K); } catch (e) { if (window.console) console.error(e); } }
    var V = K.V, S = K.S, $ = K.$, esc = K.esc, money = K.money, num = K.num, dt = K.dt, ago = K.ago, pill = K.pill, api = K.api,
      card = K.card, kpi = K.kpi, slot = K.slot, mount = K.mount, T = K.T, openDrawer = K.openDrawer, closeDrawer = K.closeDrawer;

    var PAL = ['#F70202', '#FF5500', '#2563EB', '#7C3AED', '#F59E0B', '#0EA5E9', '#DB2777', '#475569'];
    var css = document.createElement('style');
    css.textContent =
      '.d2-hero{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}' +
      '.d2-h{position:relative;overflow:hidden;border-radius:16px;padding:18px 20px;color:#fff;min-height:124px;cursor:default}' +
      '.d2-h:after{content:"";position:absolute;top:-24px;right:-24px;width:96px;height:96px;border-radius:50%;background:rgba(255,255,255,.13)}' +
      '.d2-h.a{background:linear-gradient(135deg,#F70202,#FF5500)}.d2-h.b{background:#1E293B}.d2-h.c{background:#4338CA}.d2-h.d{background:#B45309}' +
      '.d2-h b{display:block;font-size:26px;line-height:1.1;font-weight:800;position:relative;z-index:1}.d2-h span{display:block;font-size:12.5px;font-weight:600;opacity:.93;margin-top:6px;position:relative;z-index:1}' +
      '.d2-h small{display:block;font-size:11.5px;opacity:.85;margin-top:10px;position:relative;z-index:1}' +
      '.d2-g{display:inline-block;font-size:11px;font-weight:700;padding:2px 8px;border-radius:99px;background:rgba(255,255,255,.2)}' +
      '.d2-3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}.d2-2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}' +
      '.d2-cv{position:relative;height:250px}.d2-cv.s{height:190px}.d2-cv.l{height:300px}' +
      '.d2-leg{display:flex;flex-direction:column;gap:6px;margin-top:12px;font-size:12.5px}.d2-leg div{display:flex;align-items:center;gap:8px}.d2-leg i{width:10px;height:10px;border-radius:50%;flex:none}.d2-leg em{margin-left:auto;font-style:normal;font-weight:700}' +
      '.d2-prog{height:7px;border-radius:99px;background:rgba(148,163,184,.25);overflow:hidden}.d2-prog i{display:block;height:100%;border-radius:99px;background:linear-gradient(90deg,#F70202,#FF5500)}' +
      '.d2-rd{border:1px solid var(--line,#e2e8f0);border-radius:14px;padding:14px;margin-bottom:12px}' +
      '.d2-rd h4{margin:0;font-size:15px}.d2-rd small{color:var(--ink-3,#64748b)}' +
      '.d2-st{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;margin:12px 0}.d2-st div{background:rgba(148,163,184,.12);border-radius:10px;padding:8px 10px}.d2-st small{display:block;font-size:10.5px;text-transform:uppercase;letter-spacing:.3px}.d2-st b{font-size:16px}' +
      '.d2-ch{display:inline-block;margin:2px 4px 2px 0;padding:3px 9px;border-radius:99px;border:1px solid var(--line,#e2e8f0);font-size:12px;font-weight:600;cursor:pointer;background:transparent;color:inherit}' +
      '.d2-row{display:flex;align-items:center;gap:10px;flex-wrap:wrap}' +
      '@media(max-width:1100px){.d2-hero{grid-template-columns:repeat(2,minmax(0,1fr))}.d2-3{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:700px){.d2-hero,.d2-2,.d2-3{grid-template-columns:minmax(0,1fr)}}';
    document.head.appendChild(css);

    var pct = function (n) { return (Math.round(n * 10) / 10) + '%'; };
    var short = function (v) { v = Number(v) || 0; return v >= 10000000 ? (v / 10000000).toFixed(1) + 'Cr' : v >= 100000 ? (v / 100000).toFixed(1) + 'L' : v >= 1000 ? Math.round(v / 1000) + 'k' : String(Math.round(v)); };
    var growth = function (g, what) { return g == null ? '' : '<span class="d2-g">' + (g >= 0 ? '▲ ' : '▼ ') + Math.abs(g) + '% ' + (what || 'vs previous period') + '</span>'; };

    function loadChart() {
      if (window.Chart) return Promise.resolve();
      return new Promise(function (res, rej) {
        var s = document.createElement('script'); s.src = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js'; s.onload = res; s.onerror = rej; document.head.appendChild(s);
      });
    }
    function killCharts() { (S.p.d2c || []).forEach(function (c) { try { c.destroy(); } catch (e) {} }); S.p.d2c = []; }
    function chart(root, id, cfg) {
      var cv = root.querySelector('#' + id); if (!cv || !window.Chart) return;
      var ink = getComputedStyle(root).color || '#334155';
      cfg.options = cfg.options || {}; cfg.options.responsive = true; cfg.options.maintainAspectRatio = false;
      window.Chart.defaults.color = ink; window.Chart.defaults.font.family = 'inherit';
      var c = new window.Chart(cv, cfg); (S.p.d2c = S.p.d2c || []).push(c);
    }
    var gridC = 'rgba(148,163,184,.22)';
    function axes(horizontal, fmt) {
      var v = { grid: { color: gridC }, ticks: { font: { size: 10 }, callback: fmt || function (x) { return short(x); } }, beginAtZero: true };
      var l = { grid: { display: false }, ticks: { font: { size: 10 } } };
      return horizontal ? { x: v, y: l } : { x: l, y: v };
    }
    function legend(items) { return '<div class="d2-leg">' + items.map(function (i) { return '<div><i style="background:' + i.c + '"></i><span>' + esc(i.l) + '</span><em>' + esc(i.v) + '</em></div>'; }).join('') + '</div>'; }
    var STAGE_L = { 'new': 'New', accepted: 'Accepted', out: 'Out for delivery', unsettled: 'Delivered, to settle', delivered: 'Settled', cancelled: 'Cancelled' };
    var STAGE_C = { 'new': '#2563EB', accepted: '#F59E0B', out: '#7C3AED', unsettled: '#FF5500', delivered: '#475569', cancelled: '#F70202' };

    /* ------------------------------------------------------------------ dashboard */
    V['ad-home'] = { t: 'Admin dashboard', dates: true, auto: 120, run: function (root) {
      return Promise.all([api('/ad/dash2/home'), loadChart().catch(function () {})]).then(function (x) {
        var d = x[0], r = d.range, o = d.open, g = d.growth;
        var hero = '<div class="d2-hero">' +
          '<div class="d2-h a"><b>' + esc(money(r.sales)) + '</b><span>Sales (delivered orders)</span><small>' + num(r.delivered, 0) + ' delivered ' + growth(g.sales) + '</small></div>' +
          '<div class="d2-h b"><b>' + num(r.orders, 0) + '</b><span>Orders placed</span><small>' + num(r.cancelled, 0) + ' cancelled (' + pct(r.cancel_rate) + ') ' + growth(g.orders) + '</small></div>' +
          '<div class="d2-h c"><b>' + esc(money(r.aov)) + '</b><span>Average order value</span><small>' + (r.fees ? 'Delivery fees ' + esc(money(r.fees)) : 'Per delivered order') + '</small></div>' +
          '<div class="d2-h d"><b>' + esc(money(d.cash.total)) + '</b><span>Money with riders now</span><small>' + num(d.cash.riders.reduce(function (s, z) { return s + z.orders; }, 0), 0) + ' delivered orders to settle</small></div></div>';
        var live = '<div class="ad-sec">Right now</div><div class="cc-kpis">' +
          kpi('Pending to accept', num(o['new'], 0), 'waiting at hubs', o['new'] ? 'warn' : 'k-indigo', 'ad-dispatch') + kpi('Accepted', num(o.accepted, 0), 'packing or waiting for a rider', 'k-amber', 'ad-dispatch') +
          kpi('Out for delivery', num(o.out, 0), 'with delivery boys', 'k-purple', 'ad-dispatch') + kpi('Late now', num(o.late, 0), 'past their due time', o.late ? 'bad' : 'k-slate', 'ad-dispatch') +
          kpi('Delivered today', num(o.delivered_today, 0), num(d.totals.riders_today, 0) + ' delivery boys', 'k-blue', 'orders') + (o.tickets == null ? '' : kpi('Open tickets', num(o.tickets, 0), 'support', o.tickets ? 'warn' : 'k-slate', 'ad-tickets')) + '</div>';
        var per = '<div class="ad-sec">In the chosen dates · ' + esc(d.range_label) + '</div><div class="cc-kpis">' +
          kpi('Settled', money(r.settled), 'money received from riders', 'k-blue', 'reports') + kpi('To settle', money(r.unsettled), 'delivered, money still with riders', r.unsettled ? 'warn' : 'k-slate', 'ad-riders-live') +
          kpi('On-time delivery', r.ontime == null ? '–' : r.ontime + '%', r.avg_min == null ? 'no timed deliveries' : 'average ' + r.avg_min + ' min to deliver', r.ontime != null && r.ontime < 80 ? 'warn' : 'k-indigo', 'league') +
          kpi('Cancelled', num(r.cancelled, 0), pct(r.cancel_rate) + ' of orders', r.cancelled ? 'warn' : 'k-slate', 'cancels') +
          kpi('New customers', r.new_customers == null ? '–' : num(r.new_customers, 0), (g.customers == null ? 'signed up' : (g.customers >= 0 ? '▲ ' : '▼ ') + Math.abs(g.customers) + '% vs previous'), 'k-pink', 'ad-buyers') +
          kpi('Repeat customers', num(r.repeat, 0), r.buyers ? pct(r.repeat * 100 / r.buyers) + ' of ' + num(r.buyers, 0) + ' who ordered' : '', 'k-purple', 'ad-buyers') +
          (r.refunds == null ? '' : kpi('Refunds', money(r.refunds), 'paid back', r.refunds ? 'warn' : 'k-slate', 'ad-refunds')) + kpi('All customers', num(d.totals.customers, 0), 'registered accounts', 'k-slate', 'ad-buyers') + kpi('Express orders', num(exp, 0), 'in the chosen dates', 'k-amber', 'orders') + kpi('Scheduled orders', num(sch, 0), 'in the chosen dates', 'k-purple', 'orders') + '</div>';
        var tc = function (n) { var v = 0; d.types.forEach(function (x) { if (String(x.label).toLowerCase() === n) v += x.orders; }); return v; };
        var exp = d.types.reduce(function (s, x) { return s + (String(x.label).toLowerCase() === 'scheduled' ? 0 : x.orders); }, 0), sch = tc('scheduled'), mo = d.month;
        var perOrd = '<div class="ad-sec">This month \u00b7 ' + esc(mo.label) + '</div><div class="cc-kpis">' +
          kpi('Total orders this month', num(mo.orders, 0), num(mo.delivered, 0) + ' delivered', 'k-indigo', 'orders') + kpi('Sales this month', money(mo.sales), 'delivered orders', 'k-blue', 'reports') +
          kpi('Express this month', num(mo.express, 0), 'delivered the same day', 'k-amber', 'orders') + kpi('Scheduled this month', num(mo.scheduled, 0), 'booked for a later slot', 'k-purple', 'orders') +
          kpi('Cancelled this month', num(mo.cancelled, 0), 'orders', mo.cancelled ? 'warn' : 'k-slate', 'cancels') + '</div>';
        var att = d.attention && d.attention.length ? card('Needs attention', '<ul class="ad-att">' + d.attention.map(function (z) { return '<li class="t-' + z.tone + '"' + (z.go ? ' data-go="' + esc(z.go) + '"' : '') + '><b>' + (z.n == null ? '!' : num(z.n, 0)) + '</b><span>' + esc(z.text) + '</span>' + (z.go ? '<em>Fix ›</em>' : '') + '</li>'; }).join('') + '</ul>', { flush: true, sub: 'Found in your data. Click a line to open the screen that fixes it.' }) : '';
        var sumSt = Object.keys(d.status).reduce(function (s, k) { return s + d.status[k]; }, 0);
        var stItems = Object.keys(STAGE_L).filter(function (k) { return d.status[k]; }).map(function (k) { return { l: STAGE_L[k], v: num(d.status[k], 0), c: STAGE_C[k] }; });
        var payItems = d.payments.map(function (p, i) { return { l: p.label, v: num(p.orders, 0) + ' · ' + short(p.value), c: PAL[i % PAL.length] }; });
        var tyItems = d.types.map(function (p, i) { return { l: p.label, v: num(p.orders, 0), c: PAL[(i + 2) % PAL.length] }; });
        var h = '<div class="cc-grid">' + hero + live + perOrd + per + att +
          '<div class="d2-2">' + card('Sales and orders, last 12 months', '<div class="d2-cv"><canvas id="d2m"></canvas></div>') + card('Sales by day, last 30 days', '<div class="d2-cv"><canvas id="d2d"></canvas></div>') + '</div>' +
          card('Delivered sales vs money settled, last 12 months', '<div class="d2-cv l"><canvas id="d2s"></canvas></div>') +
          '<div class="d2-3">' + card('Order status', '<div class="d2-cv s"><canvas id="d2st"></canvas></div>' + legend(stItems)) + card('Payment type', '<div class="d2-cv s"><canvas id="d2p"></canvas></div>' + (payItems.length ? legend(payItems) : '<p class="cc-note">No orders in these dates.</p>')) +
          card('Delivery type', '<div class="d2-cv s"><canvas id="d2t"></canvas></div>' + (tyItems.length ? legend(tyItems) : '<p class="cc-note">No orders in these dates.</p>')) + '</div>' +
          '<div class="d2-2">' + card('When customers order (hour of day)', '<div class="d2-cv"><canvas id="d2h"></canvas></div>') + card('Sales by hub', d.by_hub.length ? '<div class="d2-cv"><canvas id="d2hub"></canvas></div>' : '<div class="cc-empty">No sales in these dates.</div>') + '</div>' +
          card('Top delivery boys this month', slot('trid'), { flush: true, sub: 'Ranked by deliveries. Click a name to open his details.' }) + '<div class="d2-2">' + card('Hub league', slot('hubs'), { flush: true }) + card('Money with delivery boys', slot('cash'), { flush: true, sub: 'Delivered orders whose money has not been settled at the hub yet.' }) + '</div>' +
          '<div class="d2-2">' + card('Top customers in these dates', slot('cust'), { flush: true }) + card('Top products in these dates', slot('items'), { flush: true }) + '</div>' +
          (d.reasons.length ? card('Why orders were cancelled', '<div class="d2-cv s"><canvas id="d2r"></canvas></div>') : '') +
          card('Latest orders', slot('recent'), { flush: true }) + '</div>';
        root.innerHTML = h; killCharts();
        var months = d.months.map(function (m) { return m.m; });
        chart(root, 'd2m', { type: 'bar', data: { labels: months, datasets: [{ type: 'bar', label: 'Sales', data: d.months.map(function (m) { return m.sales; }), backgroundColor: '#FF5500', borderRadius: 6, yAxisID: 'y' }, { type: 'line', label: 'Orders', data: d.months.map(function (m) { return m.orders; }), borderColor: '#2563EB', backgroundColor: '#2563EB', tension: .35, pointRadius: 3, yAxisID: 'y1' }] },
          options: { plugins: { legend: { display: true, position: 'top', labels: { usePointStyle: true, font: { size: 11 } } }, tooltip: { callbacks: { label: function (c) { return c.dataset.label + ': ' + (c.dataset.label === 'Sales' ? money(c.raw) : num(c.raw, 0)); } } } }, scales: { x: { grid: { display: false }, ticks: { font: { size: 10 } } }, y: { grid: { color: gridC }, beginAtZero: true, ticks: { font: { size: 10 }, callback: short } }, y1: { position: 'right', grid: { display: false }, beginAtZero: true, ticks: { font: { size: 10 } } } } } });
        chart(root, 'd2d', { type: 'line', data: { labels: d.days.map(function (x) { return x.d.slice(8, 10) + '/' + x.d.slice(5, 7); }), datasets: [{ label: 'Sales', data: d.days.map(function (x) { return x.sales; }), borderColor: '#F70202', backgroundColor: 'rgba(247,2,2,.10)', fill: true, tension: .35, pointRadius: 2 }] },
          options: { plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return money(c.raw) + ' · ' + d.days[c.dataIndex].orders + ' orders'; } } } }, scales: axes(false) } });
        chart(root, 'd2s', { type: 'bar', data: { labels: months, datasets: [{ label: 'Delivered sales', data: d.months.map(function (m) { return m.sales; }), backgroundColor: '#FF5500', borderRadius: 5 }, { label: 'Settled', data: d.months.map(function (m) { return m.settled; }), backgroundColor: '#2563EB', borderRadius: 5 }] },
          options: { plugins: { legend: { display: true, position: 'top', labels: { usePointStyle: true, font: { size: 11 } } }, tooltip: { callbacks: { label: function (c) { return c.dataset.label + ': ' + money(c.raw); } } } }, scales: axes(false) } });
        var dn = function (id, labels, data, colors) { chart(root, id, { type: 'doughnut', data: { labels: labels, datasets: [{ data: data.length ? data : [1], backgroundColor: data.length ? colors : ['rgba(148,163,184,.3)'], borderWidth: 0 }] }, options: { cutout: '68%', plugins: { legend: { display: false } } } }); };
        dn('d2st', Object.keys(STAGE_L).filter(function (k) { return d.status[k]; }).map(function (k) { return STAGE_L[k]; }), Object.keys(STAGE_L).filter(function (k) { return d.status[k]; }).map(function (k) { return d.status[k]; }), Object.keys(STAGE_L).filter(function (k) { return d.status[k]; }).map(function (k) { return STAGE_C[k]; }));
        dn('d2p', d.payments.map(function (p) { return p.label; }), d.payments.map(function (p) { return p.orders; }), PAL);
        dn('d2t', d.types.map(function (p) { return p.label; }), d.types.map(function (p) { return p.orders; }), PAL.slice(2));
        chart(root, 'd2h', { type: 'bar', data: { labels: d.hours.map(function (v, i) { return (i % 12 || 12) + (i < 12 ? 'a' : 'p'); }), datasets: [{ data: d.hours, backgroundColor: '#FF5500', borderRadius: 4 }] }, options: { plugins: { legend: { display: false } }, scales: axes(false, function (x) { return x; }) } });
        if (d.by_hub.length) chart(root, 'd2hub', { type: 'bar', data: { labels: d.by_hub.slice(0, 8).map(function (x) { return x.name; }), datasets: [{ data: d.by_hub.slice(0, 8).map(function (x) { return x.sales; }), backgroundColor: PAL, borderRadius: 5 }] }, options: { indexAxis: 'y', plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return money(c.raw) + ' · ' + d.by_hub[c.dataIndex].orders + ' orders'; } } } }, scales: axes(true) } });
        if (d.reasons.length) chart(root, 'd2r', { type: 'bar', data: { labels: d.reasons.map(function (x) { return x.label; }), datasets: [{ data: d.reasons.map(function (x) { return x.value; }), backgroundColor: '#F70202', borderRadius: 5 }] }, options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: axes(true, function (x) { return x; }) } });

        mount(root, 'hubs', T([{ k: 'name', t: 'Hub', f: function (x) { return '<b>' + esc(x.name) + '</b>'; } }, { k: 'orders', t: 'Orders', n: 1 }, { k: 'sales', t: 'Sales', n: 1, f: function (x) { return money(x.sales); } },
          { k: 'ontime', t: 'On time', n: 1, f: function (x) { return x.ontime == null ? '<span class="muted">–</span>' : '<div class="d2-row"><div class="d2-prog" style="width:60px"><i style="width:' + x.ontime + '%"></i></div>' + x.ontime + '%</div>'; } }],
          d.by_hub, { noPage: 1, empty: 'No orders in these dates.', click: function (x) { if (K.hubDrawer) K.hubDrawer({ id: x.id, name: x.name }); } }));
        mount(root, 'trid', T([{ k: 'rank', t: '#', n: 1 }, { k: 'name', t: 'Delivery boy', f: function (x) { return '<b>' + esc(x.name) + '</b><small>' + esc(x.hub) + '</small>'; } }, { k: 'deliveries', t: 'Deliveries', n: 1 }, { k: 'value', t: 'Sales', n: 1, f: function (x) { return money(x.value); } },
          { k: 'ontime', t: 'On time', n: 1, f: function (x) { return x.ontime == null ? '<span class="muted">\u2013</span>' : '<div class="d2-row"><div class="d2-prog" style="width:60px"><i style="width:' + x.ontime + '%"></i></div>' + x.ontime + '%</div>'; } },
          { k: 'avg_min', t: 'Avg minutes', n: 1, f: function (x) { return x.avg_min == null ? '\u2013' : x.avg_min; } }, { k: 'cash', t: 'Cash with him', n: 1, f: function (x) { return x.cash ? '<b>' + money(x.cash) + '</b>' : '\u2013'; } }],
          d.top_riders, { noPage: 1, empty: 'No deliveries this month yet.', click: function (x) { if (K.riderDrawer) K.riderDrawer(x.id); } }));
        mount(root, 'cash', T([{ k: 'name', t: 'Delivery boy', f: function (x) { return '<b>' + esc(x.name) + '</b><small>' + esc(x.hub) + '</small>'; } }, { k: 'orders', t: 'Orders', n: 1 }, { k: 'amount', t: 'Money', n: 1, f: function (x) { return '<b>' + money(x.amount) + '</b>'; } }, { k: 'since', t: 'Oldest', n: 1, f: function (x) { return ago(x.since); } }],
          d.cash.riders.slice(0, 10), { noPage: 1, empty: 'Nothing is waiting to be settled.', click: function (x) { if (x.id && K.riderDrawer) K.riderDrawer(x.id); } }));
        mount(root, 'cust', T([{ k: 'name', t: 'Customer', f: function (x) { return '<b>' + esc(x.name) + '</b><small>' + esc(x.phone) + '</small>'; } }, { k: 'orders', t: 'Orders', n: 1 }, { k: 'value', t: 'Spent', n: 1, f: function (x) { return money(x.value); } }],
          d.top_customers, { noPage: 1, empty: 'No orders in these dates.', click: function (x) { if (K.customerDrawer) K.customerDrawer(x.id); } }));
        mount(root, 'items', T([{ k: 'name', t: 'Product', f: function (x) { return '<b>' + esc(x.name) + '</b><small>' + esc(x.pack) + '</small>'; } }, { k: 'orders', t: 'Orders', n: 1 }, { k: 'value', t: 'Sales', n: 1, f: function (x) { return money(x.value); } }],
          d.top_items, { noPage: 1, empty: 'No sales in these dates.' }));
        mount(root, 'recent', T([{ k: 'id', t: 'Order' }, { k: 'at', t: 'Placed', n: 1, f: function (x) { return dt(x.at); } }, { k: 'customer', t: 'Customer' }, { k: 'hub', t: 'Hub' }, { k: 'stage', t: 'Status', f: function (x) { return K.stagePill(x.stage); } }, { k: 'amount', t: 'Amount', n: 1, f: function (x) { return money(x.amount); } }],
          d.recent, { noPage: 1, click: function (x) { K.orderDrawer(x.id); } }));
        K.wireGo(root);
      });
    } };

    /* ------------------------------------------------------------------ hub and delivery boy drawer */
    function riderBlock(r) {
      var ph = String(r.phone || '').replace(/\D/g, '');
      var wa = ph ? 'https://wa.me/' + (ph.length === 10 ? '91' + ph : ph) : '';
      var chips = function (list, tone) { return list.length ? list.map(function (id) { return '<button type="button" class="d2-ch" data-o="' + esc(id) + '">' + esc(id) + '</button>'; }).join('') : '<span class="muted">none</span>'; };
      var extra = Object.keys(r.extra || {}).map(function (k) { return '<div><small>' + esc(k) + '</small><b style="font-size:13px">' + esc(r.extra[k]) + '</b></div>'; }).join('');
      return '<div class="d2-rd"><div class="d2-row"><div><h4>' + esc(r.name) + '</h4><small>' + esc([r.code, r.phone].filter(Boolean).join(' · ')) + (r.hub ? ' · ' + esc(r.hub) : '') + '</small></div>' +
        '<span style="margin-left:auto" class="d2-row">' + (r.active ? pill('Active', 'p-info') : pill('Deactivated', 'p-bad')) + (r.on_duty ? pill('On duty', 'p-info') : pill('Off duty')) + '</span></div>' +
        '<div class="d2-st"><div><small>Delivered today</small><b>' + num(r.today, 0) + '</b></div><div><small>Out for delivery</small><b>' + r.out.length + '</b></div><div><small>Waiting hand-over</small><b>' + r.assigned.length + '</b></div>' +
        '<div><small>To settle</small><b>' + r.unsettled + ' · ' + esc(money(r.cash)) + '</b></div><div><small>Last delivery</small><b style="font-size:13px">' + (r.last ? esc(ago(r.last)) : '–') + '</b></div>' + extra + '</div>' +
        '<div style="margin-bottom:8px"><small>Out for delivery</small><br>' + chips(r.out) + '</div><div style="margin-bottom:10px"><small>Assigned, waiting for hand-over</small><br>' + chips(r.assigned) + '</div>' +
        '<div class="d2-row">' + (ph ? '<a class="cc-btn sm" href="tel:' + esc(ph) + '">Call</a>' : '') + (wa ? '<a class="cc-btn sm" target="_blank" rel="noopener" href="' + esc(wa) + '">WhatsApp</a>' : '') +
        (r.regid && K.driverDrawer ? '<button type="button" class="cc-btn sm" data-dr="' + r.regid + '">Open driver file</button>' : '') + '</div></div>';
    }
    function wireDrawer(box) {
      box.querySelectorAll('[data-o]').forEach(function (b) { b.addEventListener('click', function () { var id = b.getAttribute('data-o'); closeDrawer(); setTimeout(function () { K.orderDrawer(id); }, 150); }); });
      box.querySelectorAll('[data-dr]').forEach(function (b) { b.addEventListener('click', function () { var id = +b.getAttribute('data-dr'); closeDrawer(); setTimeout(function () { K.driverDrawer(id); }, 150); }); });
    }
    K.hubDrawer = function (h) {
      openDrawer(h.name || 'Hub', '<div class="cc-load"><i></i>Loading…</div>');
      api('/ad/dash2/hub/' + h.id, {}, false).then(function (d) {
        var box = $('ccDBody'); if (!box) return;
        if (!d.ok) { box.innerHTML = '<div class="cc-empty">' + esc(d.message || 'Could not load.') + '</div>'; return; }
        var s = d.stats;
        box.innerHTML = '<div class="d2-row" style="margin-bottom:10px">' + (d.hub.active ? pill('Open', 'p-info') : pill('Closed')) + (d.hub.city ? '<span class="muted">' + esc(d.hub.city) + '</span>' : '') +
          (d.hub.kind ? '<span class="muted">Area: ' + esc(d.hub.kind === 'circle' ? 'circle' + (d.hub.radius_m ? ' ' + num(d.hub.radius_m / 1000, 1) + ' km' : '') : d.hub.kind) + '</span>' : '<span class="down">No delivery area drawn</span>') + '</div>' +
          '<div class="d2-st"><div><small>Orders today</small><b>' + s.orders_today + '</b></div><div><small>Sales today</small><b>' + esc(money(s.sales_today)) + '</b></div><div><small>Delivered today</small><b>' + s.delivered_today + '</b></div>' +
          '<div><small>New</small><b>' + s['new'] + '</b></div><div><small>Accepted</small><b>' + s.accepted + '</b></div><div><small>Out for delivery</small><b>' + s.out + '</b></div></div>' +
          (s.late ? '<p class="down" style="margin:0 0 10px"><b>' + s.late + '</b> order(s) late right now.</p>' : '') +
          '<h4 style="margin:14px 0 8px">Delivery boys (' + d.riders.length + ')</h4>' + (d.riders.length ? d.riders.map(riderBlock).join('') : '<div class="cc-empty">No delivery boys at this hub.</div>');
        wireDrawer(box);
      }).catch(function () { var box = $('ccDBody'); if (box) box.innerHTML = '<div class="cc-empty">Could not load. Try again.</div>'; });
    };
    K.riderDrawer = function (boyId) {
      openDrawer('Delivery boy', '<div class="cc-load"><i></i>Loading…</div>');
      api('/ad/dash2/rider/' + boyId, {}, false).then(function (d) {
        var box = $('ccDBody'); if (!box) return;
        if (!d.ok) { box.innerHTML = '<div class="cc-empty">' + esc(d.message || 'Could not load.') + '</div>'; return; }
        box.innerHTML = riderBlock(d.rider); wireDrawer(box);
      }).catch(function () { var box = $('ccDBody'); if (box) box.innerHTML = '<div class="cc-empty">Could not load. Try again.</div>'; });
    };

    /* Live locations page: a clickable list of hubs under the map (pins open the same drawer). */
    var live = V['ad-riders-live'];
    if (live && typeof live.run === 'function') {
      var run0 = live.run;
      live.run = function (root) {
        return run0.call(this, root).then(function (r) {
          api('/ad/live/god', {}, false).then(function (d) {
            var grid = root.querySelector('.cc-grid'); if (!grid || !d || !d.hubs) return;
            var rows = d.hubs.map(function (h) { var n = d.riders.filter(function (x) { return x.hub === h.id; }); return { id: h.id, name: h.name, city: h.city, active: h.active, area: h.kind, riders: n.length, out: n.filter(function (x) { return x.orders.length; }).length }; });
            var wrap = document.createElement('div');
            wrap.innerHTML = card('Hubs and their delivery boys', slot('hl'), { flush: true, sub: 'Click a hub to see every delivery boy, what each is carrying and the money with them.' });
            grid.appendChild(wrap.firstChild);
            mount(root, 'hl', T([{ k: 'name', t: 'Hub', f: function (x) { return '<b>' + esc(x.name) + '</b><small>' + esc(x.city) + '</small>'; } }, { k: 'active', t: 'Status', f: function (x) { return x.active ? pill('Open', 'p-info') : pill('Closed'); } },
              { k: 'area', t: 'Delivery area', f: function (x) { return x.area ? esc(x.area) : '<span class="down">none drawn</span>'; } }, { k: 'riders', t: 'Delivery boys', n: 1 }, { k: 'out', t: 'Carrying orders', n: 1 }], rows, { noPage: 1, click: function (x) { K.hubDrawer(x); } }));
          }).catch(function () {});
          return r;
        });
      };
    }
  };
})();

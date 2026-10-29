/* Freshful production console (production update 1, 28 Oct 2026). Production screens for the production manager.
   Plugs into cc.js (window.CCX) and uses its helpers, styles and dialogs. No browser default pop-ups. */
(function () {
  'use strict';
  var prev = window.CCX;
  window.CCX = function (K) {
    if (typeof prev === 'function') { try { prev(K); } catch (e) { if (window.console) console.error(e); } }
    var C = K.C, V = K.V, S = K.S, $ = K.$, esc = K.esc, money = K.money, money2 = K.money2, num = K.num, pct = K.pct, dt = K.dt, dOnly = K.dOnly,
      pill = K.pill, api = K.api, post = K.post, act = K.act, ask = K.ask, toast = K.toast, card = K.card, kpi = K.kpi, slot = K.slot, mount = K.mount,
      T = K.T, el = K.el, openDrawer = K.openDrawer, openModal = K.openModal, closeModal = K.closeModal, closeDrawer = K.closeDrawer, wireGo = K.wireGo;
    var P = window.PRC || {};
    var can = P.can || {};
    C.home = 'pr-home';

    /* ------------------------------------------------------------------ menu: groups with coloured tiles, find, collapse */
    (function menu() {
      var side = $('ccSide'), nav = side && side.querySelector('.cc-nav'); if (!nav) return;
      var colors = P.colors || {}, closed = K.store.get('pr-closed') || {};
      var groups = [], cur = null;
      [].slice.call(nav.children).forEach(function (n) {
        if (n.classList.contains('cc-sep')) { cur = { name: n.textContent.trim(), links: [] }; groups.push(cur); }
        else if (n.tagName === 'A' && cur) cur.links.push(n);
      });
      nav.innerHTML = '';
      groups.forEach(function (g) {
        var sec = el('<section class="pr-grp" data-group="' + esc(g.name) + '" style="--c:' + esc(colors[g.name] || '#64748B') + '"><button type="button" class="pr-grp-h" aria-expanded="true"><span>' + esc(g.name) + '</span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg></button><div class="pr-list"></div></section>');
        var list = sec.querySelector('.pr-list');
        g.links.forEach(function (a) {
          var svg = a.querySelector('svg'); var tile = document.createElement('span'); tile.className = 'pr-tile';
          if (svg) { svg.setAttribute('width', '16'); svg.setAttribute('height', '16'); tile.appendChild(svg); }
          a.insertBefore(tile, a.firstChild);
          a.setAttribute('data-label', (a.textContent + ' ' + g.name).toLowerCase());
          list.appendChild(a);
        });
        var h = sec.querySelector('.pr-grp-h');
        if (closed[g.name]) { sec.classList.add('is-closed'); h.setAttribute('aria-expanded', 'false'); }
        h.addEventListener('click', function () { var c = sec.classList.toggle('is-closed'); h.setAttribute('aria-expanded', c ? 'false' : 'true'); closed[g.name] = c; K.store.set('pr-closed', closed); });
        nav.appendChild(sec);
      });
      var nores = el('<p class="pr-nores" hidden>No page matches.</p>'); nav.appendChild(nores);
      var find = el('<label class="pr-find"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/></svg><input type="search" placeholder="Find a page" aria-label="Find a page in the menu" autocomplete="off"><kbd>/</kbd></label>');
      nav.parentNode.insertBefore(find, nav);
      var inp = find.querySelector('input');
      inp.addEventListener('input', function () {
        var q = inp.value.trim().toLowerCase(), any = false;
        nav.querySelectorAll('.pr-grp').forEach(function (s) {
          var vis = 0; s.querySelectorAll('a[data-label]').forEach(function (a) { var on = !q || a.getAttribute('data-label').indexOf(q) >= 0; a.hidden = !on; if (on) vis++; });
          s.hidden = !vis; if (q && vis) s.classList.remove('is-closed'); else if (!q && closed[s.getAttribute('data-group')]) s.classList.add('is-closed'); if (vis) any = true;
        });
        nores.hidden = any;
      });
      document.addEventListener('keydown', function (e) { var t = (e.target && e.target.tagName) || ''; if (e.key === '/' && !/INPUT|TEXTAREA|SELECT/.test(t)) { e.preventDefault(); inp.focus(); } });
      window.addEventListener('hashchange', function () { var a = nav.querySelector('a.is-on'); var s = a && a.closest('.pr-grp'); if (s) s.classList.remove('is-closed'); });
    })();

    /* screens this role may not open are removed, so the address bar cannot reach them */
    var allowed = { 'pr-home': true }; (P.screens || []).forEach(function (s) { allowed[s] = true; });

    /* ------------------------------------------------------------------ helpers */
    function amt(q, r) {
      q = +q || 0; if (!r || !r.per) return num(q) + ' pk';
      var v = q * r.per;
      if (r.kind === 'count') return num(v, 0) + ' pcs';
      if (r.kind === 'volume') return Math.abs(v) >= 1000 ? num(v / 1000) + ' L' : num(v, 0) + ' ml';
      return Math.abs(v) >= 1000 ? num(v / 1000) + ' kg' : num(v, 0) + ' g';
    }
    function amtSum(list) { // list of [packs, row]
      var t = { weight: 0, count: 0, volume: 0 };
      list.forEach(function (x) { var q = +x[0] || 0, r = x[1]; if (!q || !r || !r.per) return; t[r.kind || 'count'] += r.kind === 'count' ? q * r.per : q * r.per / 1000; });
      var o = []; if (t.weight) o.push(num(t.weight) + ' kg'); if (t.count) o.push(num(t.count, 0) + ' pcs'); if (t.volume) o.push(num(t.volume) + ' L');
      return o.join(' + ') || '0';
    }
    function hubById(id) { return (K.M.hubs || []).filter(function (h) { return String(h.id) === String(id); })[0] || { name: 'Hub ' + id, city: '' }; }
    function stPill(s) { var t = { sent: 'Waiting at hub', partly: 'Partly received', received: 'Received', cancelled: 'Cancelled' }[s] || s; return '<span class="pr-st ' + esc(s) + '"><i></i>' + esc(t) + '</span>'; }
    function att(list) {
      if (!list.length) return '<div class="cc-empty">Nothing needs attention right now.</div>';
      return '<ul class="pr-att">' + list.map(function (a) { return '<li class="t-' + esc(a.tone) + '"' + (a.go && V[a.go] ? ' data-go="' + esc(a.go) + '"' : '') + '><b>' + (a.tone === 'bad' ? '!' : a.tone === 'warn' ? '!' : 'i') + '</b><span>' + esc(a.text) + '</span>' + (a.go && V[a.go] ? '<em>Open</em>' : '') + '</li>'; }).join('') + '</ul>';
    }
    function dayTabs(dates, cur) { return '<div class="pr-days" role="tablist">' + dates.map(function (d) { return '<button type="button" role="tab" data-d="' + esc(d.date) + '" class="' + (d.date === cur ? 'is-on' : '') + '" aria-selected="' + (d.date === cur) + '">' + esc(d.label) + '<small>' + esc(dOnly(Date.parse(d.date + 'T12:00:00') / 1000)) + '</small></button>'; }).join('') + '</div>'; }
    function oneKey() { var a = new Uint8Array(12); (window.crypto || window.msCrypto).getRandomValues(a); return 'pr-' + [].map.call(a, function (b) { return ('0' + b.toString(16)).slice(-2); }).join(''); }
    var tomorrowStr = (function () { var d = new Date(C.today + 'T12:00:00'); d.setDate(d.getDate() + 1); return K.ymd(d); })();

    /* ------------------------------------------------------------------ dashboard */
    V['pr-home'] = { t: 'Production dashboard', dates: false, auto: 180, run: function (root) {
      return api('/pr/home', {}, false).then(function (d) {
        if (!d.ok) { root.innerHTML = card('Production dashboard', '<div class="cc-empty">' + esc(d.message || 'Could not load.') + '</div>'); return; }
        var k = d.kpi, cats = d.by_category || [];
        var kg = 0, pcs = 0, L = 0; cats.forEach(function (c) { kg += c.kg; pcs += c.pcs; L += c.L; });
        var big = [kg ? num(kg) + ' kg' : '', pcs ? num(pcs, 0) + ' pcs' : '', L ? num(L) + ' L' : ''].filter(Boolean).join(' + ') || 'Nothing planned';
        var maxV = Math.max.apply(null, cats.map(function (c) { return c.value; }).concat([1]));
        var steps = [
          { go: 'pr-plan', done: k.to_make_tomorrow > 0, t: 'Check the plan for tomorrow', s: k.products_tomorrow ? k.products_tomorrow + ' products, ' + num(k.to_make_tomorrow, 0) + ' packs suggested or planned' : 'No demand found for tomorrow yet' },
          { go: 'pr-sheet', done: false, t: 'Print the production sheet', s: 'Cutting and packing list for the floor' },
          { go: 'pr-plan', done: k.to_make_tomorrow > 0 && k.to_send_tomorrow <= 0, t: 'Send to the hubs', s: k.to_send_tomorrow > 0 ? num(k.to_send_tomorrow, 0) + ' packs still to send for tomorrow' : 'Everything planned has been sent' },
          { go: 'pr-batches', done: k.waiting_lines === 0, t: 'Hubs receive the stock', s: k.waiting_lines ? k.waiting_lines + ' lines waiting at hubs' + (k.oldest_waiting ? ' (oldest ' + K.ago(k.oldest_waiting) + ')' : '') : 'Nothing waiting' }
        ].filter(function (x) { return V[x.go]; });
        root.innerHTML = '<section class="pr-run"><div class="pr-run-l"><h2>Tomorrow\'s production run · ' + esc(dOnly(Date.parse(d.tomorrow + 'T12:00:00') / 1000)) + '</h2>' +
            '<div class="pr-big"><b>' + esc(big) + '</b><span>' + num(k.to_make_tomorrow, 0) + ' packs · ' + money(k.value_tomorrow) + '</span></div><p>Planned where you saved a plan, otherwise the suggestion from sales, bookings and stock in the hubs.</p>' +
            (cats.length ? '<ul class="pr-cats">' + cats.slice(0, 8).map(function (c) { var a = [c.kg ? num(c.kg) + ' kg' : '', c.pcs ? num(c.pcs, 0) + ' pcs' : '', c.L ? num(c.L) + ' L' : ''].filter(Boolean).join(' + '); return '<li><span title="' + esc(c.name) + '">' + esc(c.name) + '</span><i><em style="width:' + Math.max(2, c.value * 100 / maxV) + '%"></em></i><b>' + esc(a) + '</b></li>'; }).join('') + '</ul>' : '') +
          '</div><div class="pr-run-r"><h2>Today\'s steps</h2><div class="pr-steps">' + steps.map(function (s, i) { return '<a class="pr-step' + (s.done ? ' done' : '') + '" href="#' + s.go + '"><b>' + (s.done ? '✓' : i + 1) + '</b><span>' + esc(s.t) + '<small>' + esc(s.s) + '</small></span></a>'; }).join('') + '</div></div></section>' +
          '<div class="cc-kpis">' +
            kpi('Sent to hubs today', num(k.sent_today.packs || 0, 0) + ' pk', money(k.sent_today.value || 0) + ' · ' + (k.sent_today.batches || 0) + ' dispatch' + ((k.sent_today.batches || 0) === 1 ? '' : 'es'), '', V['pr-batches'] ? 'pr-batches' : '') +
            kpi('Still to send today', num(k.to_send_today, 0) + ' pk', 'for the rest of today', k.to_send_today > 0 ? 'warn' : '', V['pr-plan'] ? 'pr-plan' : '') +
            kpi('Waiting at hubs', num(k.waiting_packs, 0) + ' pk', k.waiting_lines + ' lines · ' + money(k.waiting_value), k.stale_lines ? 'warn' : '', V.inward ? 'inward' : '') +
            kpi('Stock in hubs', money(k.stock_value), 'fresh today ' + money(k.fresh_value), '', V.sheet ? 'sheet' : '') +
            kpi('Danger stock', money(k.danger_value), 'must sell today', k.danger_value > 0 ? 'warn' : '', V.danger ? 'danger' : '') +
            kpi('Expired stock', money(k.expired_value), 'older than 2 days', k.expired_value > 0 ? 'bad' : '', V.danger ? 'danger' : '') +
            kpi('Sellers out of stock', num(k.stockouts, 0), 'sold in 14 days, nothing usable now', k.stockouts ? 'bad' : '', V['pr-board'] ? 'pr-board' : '') +
            kpi('Wastage, 7 days', k.waste7_pct == null ? money(k.waste7) : pct(k.waste7_pct), money(k.waste7) + ' of ' + money(k.sold7) + ' sales', k.waste7_pct != null && k.waste7_pct > d.wastage_limit ? 'bad' : '', V.wastage ? 'wastage' : '') +
            kpi('Short receipts, 7 days', num(k.short7.lines, 0), money(k.short7.value) + ' less than sent', k.short7.lines ? 'warn' : '', V['pr-receipts'] ? 'pr-receipts' : '') +
          '</div>' +
          '<div class="cc-grid g32">' + card('Sent, sold and wasted · last 14 days', slot('ch'), { sub: 'Value at selling price. Sent = production dispatches; sold = customer orders; wasted = write-offs in the hubs.' }) + card('Needs attention', att(d.attention || []), { flush: 1 }) + '</div>' +
          '<div class="cc-grid g2">' + card('To make and send for tomorrow', slot('top'), { flush: 1, actions: V['pr-plan'] ? '<a class="cc-btn sm" href="#pr-plan">Open the plan</a>' : '' }) + card('Hubs: stock to watch', slot('hubs'), { flush: 1 }) + '</div>' +
          (d.stockouts.length ? card('Regular sellers with no usable stock', slot('out'), { flush: 1, sub: 'Sold in the hub in the last 14 days; no fresh, danger or recently sent stock there now.' }) : '') +
          (d.recent.length ? card('Latest dispatches', slot('rec'), { flush: 1, actions: V['pr-batches'] ? '<a class="cc-btn sm" href="#pr-batches">All dispatches</a>' : '' }) : '');
        wireGo(root);
        root.querySelectorAll('.pr-att [data-go]').forEach(function (li) { li.addEventListener('click', function () { location.hash = li.getAttribute('data-go'); }); });
        var lab = d.days.map(function (x) { return new Date(x.date + 'T12:00:00').toLocaleDateString('en-IN', { day: '2-digit', month: 'short' }); });
        var kv = function (k) { return d.days.map(function (x) { return Math.round(x[k] / 100) / 10; }); };
        mount(root, 'ch', K.chartGroups(lab, [{ name: 'Sent to hubs (₹ thousand)', values: kv('sent'), color: 'var(--pr)' }, { name: 'Sold (₹ thousand)', values: kv('sold'), color: 'var(--c1)' }, { name: 'Wasted (₹ thousand)', values: kv('wasted'), color: 'var(--bad)' }], { h: 230 }));
        mount(root, 'top', T([{ k: 'name', t: 'Product', f: function (r) { return '<b>' + esc(r.name) + '</b><small>' + esc(r.pack) + ' · ' + esc(r.category) + '</small>'; } },
          { k: 'demand', t: 'Demand', n: 1, f: function (r) { return amt(r.demand, r); } }, { k: 'usable', t: 'Usable', n: 1, f: function (r) { return amt(r.usable, r); } },
          { k: 'planned', t: 'To make', n: 1, f: function (r) { return '<b>' + amt(r.planned, r) + '</b><small>' + num(r.planned, 0) + ' pk · ' + r.hubs + ' hub' + (r.hubs === 1 ? '' : 's') + '</small>'; } },
          { k: 'to_send', t: 'Still to send', n: 1, f: function (r) { return '<b style="color:var(--pr-ink)">' + num(r.to_send, 0) + ' pk</b>'; } }], d.top, { noPage: 1, empty: 'Nothing left to send for tomorrow.' }));
        mount(root, 'hubs', T([{ k: 'hub', t: 'Hub', f: function (r) { return esc(r.hub) + (r.active ? '' : ' ' + pill('off')) + '<small>' + esc(r.city) + '</small>'; } },
          { k: 'value', t: 'Stock', n: 1, f: function (r) { return money(r.value); } }, { k: 'danger', t: 'Danger', n: 1, f: function (r) { return r.danger ? '<b style="color:var(--warn)">' + money(r.danger) + '</b>' : '–'; } },
          { k: 'expired', t: 'Expired', n: 1, f: function (r) { return r.expired ? '<b style="color:var(--bad)">' + money(r.expired) + '</b>' : '–'; } }, { k: 'waiting', t: 'Waiting in', n: 1, f: function (r) { return r.waiting ? money(r.waiting) : '–'; } },
          { k: 'out', t: 'Out', n: 1 }], d.hubs, { per: 10 }));
        if (d.stockouts.length) mount(root, 'out', T([{ k: 'name', t: 'Product', f: function (r) { return '<b>' + esc(r.name) + '</b><small>' + esc(r.pack) + '</small>'; } }, { k: 'hub', t: 'Hub' }, { k: 'per_day', t: 'Sells / day', n: 1, f: function (r) { return num(r.per_day, 1) + ' pk'; } }], d.stockouts, { per: 10 }));
        if (d.recent.length) mount(root, 'rec', T([{ k: 'no', t: 'Dispatch', f: function (r) { return '<b>' + esc(r.no) + '</b><small>' + dt(r.at) + '</small>'; } }, { k: 'hub_names', t: 'Hubs' }, { k: 'packs', t: 'Packs', n: 1, f: function (r) { return num(r.packs, 0); } }, { k: 'value', t: 'Value', n: 1, f: function (r) { return money(r.value); } }, { k: 'status', t: 'Status', f: function (r) { return stPill(r.status); } }], d.recent, { noPage: 1, click: function (r) { batchDrawer(r.id); } }));
        var waitingB = (d.recent || []).filter(function (r) { return r.status === 'sent' || r.status === 'partly'; }).length;
        K.badge('pr-batches', waitingB);
      });
    } };

    /* ------------------------------------------------------------------ production plan (editable matrix) */
    var PL = { dirty: {}, data: null };
    window.addEventListener('beforeunload', function (e) { if (Object.keys(PL.dirty).length) { e.preventDefault(); e.returnValue = ''; } });
    V['pr-plan'] = { t: 'Production plan', dates: false, run: function (root) {
      var st = S.p.pl = Object.assign({ date: tomorrowStr, q: '', cat: '', need: true }, S.p.pl || {});
      return api('/pr/plan', { date: st.date, q: st.q, category: st.cat, need: st.need ? 1 : '' }, false).then(function (d) {
        if (!d.ok) { root.innerHTML = card('Production plan', '<div class="cc-empty">' + esc(d.message || 'Could not load.') + '</div>'); return; }
        PL.data = d; PL.dirty = {}; st.date = d.date;
        var ro = !can.plan;
        var hubs = d.hubs;
        var head = '<thead><tr><th class="pc">Product <small style="font-weight:600">(packs per hub)</small></th><th>Total</th>' + hubs.map(function (h) { return '<th class="hb" title="' + esc(h.name + (h.city ? ', ' + h.city : '')) + '">' + esc(h.name) + '</th>'; }).join('') + '</tr></thead>';
        var body = d.rows.map(function (r, i) {
          var flags = (r.suspect ? '<span class="pr-flag" title="The pack size looks wrong; kg figures will be off">pack size?</span>' : '') + (!r.active ? '<span class="pr-flag o">off</span>' : '');
          return '<tr data-i="' + i + '"><td class="pc"><b data-p="' + i + '">' + esc(r.name) + '</b>' + flags + '<small>' + esc(r.pack) + ' · ' + esc(r.category) + '</small></td><td class="tot" data-tot="' + i + '"></td>' +
            hubs.map(function (h) {
              var c = r.hubs[h.id] || { suggested: 0, planned: 0, sent: 0, daily: 0, booked: 0, demand: 0, usable: 0, fresh: 0, danger: 0, pending: 0 };
              var tip = 'Sells ' + num(c.daily, 1) + '/day · booked ' + num(c.booked, 0) + ' · demand ' + num(c.demand, 1) + ' · usable ' + num(c.usable, 1) + ' (fresh ' + num(c.fresh, 0) + ', danger ' + num(c.danger, 0) + ', waiting ' + num(c.pending, 0) + ')' + (c.note ? ' · note: ' + c.note : '');
              return '<td><div class="pr-cell' + (c.planned ? '' : ' z') + '" title="' + esc(tip) + '"><input inputmode="numeric" data-h="' + h.id + '" data-r="' + i + '" value="' + (c.planned || 0) + '" aria-label="' + esc(r.name + ' for ' + h.name) + '"' + (c.saved ? ' class="sv"' : '') + (ro ? ' disabled' : '') + '><small>' + (c.sent ? '<span class="snt">sent ' + num(c.sent, 0) + '</span>' : c.suggested ? 'sug ' + num(c.suggested, 0) : '&nbsp;') + '</small></div></td>';
            }).join('') + '</tr>';
        }).join('');
        var foot = '<tfoot><tr><td class="pc">Total</td><td class="tot" id="plGT"></td>' + hubs.map(function (h) { return '<td data-ht="' + h.id + '"></td>'; }).join('') + '</tr></tfoot>';
        var tt = d.total;
        root.innerHTML = '<section class="cc-card"><div class="pr-bar">' + dayTabs(d.dates, d.date) +
            '<input class="cc-in" id="plQ" type="search" placeholder="Search product or code" value="' + esc(st.q) + '" style="width:200px">' +
            '<select class="cc-sel" id="plC"><option value="">All categories</option>' + d.categories.map(function (c) { return '<option' + (c === st.cat ? ' selected' : '') + '>' + esc(c) + '</option>'; }).join('') + '</select>' +
            '<label class="cc-chip" style="cursor:pointer"><input type="checkbox" id="plN"' + (st.need ? ' checked' : '') + '> Only products to make</label>' +
            '<span style="flex:1"></span><span class="pr-dirty" id="plDirty" hidden></span>' +
            (ro ? '' : '<button type="button" class="cc-btn" id="plReset" title="Put the suggestion back in every box">Use suggestions</button><button type="button" class="cc-btn pri" id="plSave">Save plan</button>') +
            (can.send ? '<button type="button" class="cc-btn pr-go" id="plSend">Send to hubs</button>' : '') +
          '</div>' +
          '<div class="pr-sum" id="plSum"></div>' +
          (d.rows.length && hubs.length ? '<div class="pr-mx"><table>' + head + '<tbody>' + body + '</tbody>' + foot + '</table></div>' : '<div class="cc-empty">' + (hubs.length ? 'No product needs making for these filters. Untick "Only products to make" to plan any product.' : 'No active hub in these filters.') + '</div>') +
          '<div class="pr-legend"><span><i style="background:var(--surface)"></i>Box = packs planned for that hub</span><span><i style="border-color:var(--pr)"></i>Saved plan</span><span><i style="background:var(--warn-soft);border-color:var(--warn)"></i>Changed, not saved</span><span>"sug" = suggestion · "sent" = already sent for this day · Enter or arrow keys move between boxes</span></div></section>' +
          '<section class="cc-card"><div class="cc-body"><details class="pr-why"><summary>How the suggestion is worked out</summary><ul>' +
            '<li>Daily sales = ' + Math.round((1 - d.rule.weekday_weight) * 100) + '% the average of the last ' + d.rule.history_days + ' full days + ' + Math.round(d.rule.weekday_weight * 100) + '% the same weekday of the last 4 weeks. Orders count on their delivery day; cancelled orders are left out.</li>' +
            '<li>Demand = daily sales, or the scheduled orders already booked for that day if more. For today only what is still to come is counted (the hub\'s usual share of orders after this time), at least the new orders not yet accepted.</li>' +
            '<li>Usable stock follows the 2-day rule. Today: fresh + danger + stock sent in the last ' + d.rule.pending_max_days + ' days and not yet received. Tomorrow: what will still be fresh after today\'s demand uses the danger stock first. Later days: nothing in the hubs now will still be sellable.</li>' +
            '<li>Suggestion = demand + ' + d.rule.safety_pct + '% safety on daily sales − usable stock, rounded up to whole packs. Stock is counted in packs, so you plan and send whole packs.</li></ul></details></div></section>';
        var inputs = [].slice.call(root.querySelectorAll('.pr-cell input'));
        function val(inp) { var v = inp.value.trim(); return v === '' ? 0 : +v; }
        function totals() {
          var gt = 0, gtList = [], ht = {}; var sum = { planned: 0, sent: 0, toSend: 0 };
          d.rows.forEach(function (r, i) {
            var rowIn = root.querySelectorAll('input[data-r="' + i + '"]'), t = 0, ts = 0;
            rowIn.forEach(function (inp) { var v = val(inp); if (!(v >= 0)) v = 0; t += v; var h = inp.getAttribute('data-h'); ht[h] = (ht[h] || 0) + v; var c = r.hubs[h]; ts += Math.max(0, v - (c ? c.sent : 0)); });
            r._p = t; r._ts = ts; gt += t; gtList.push([t, r]); sum.planned += t; sum.sent += r.sent; sum.toSend += ts;
            var cell = root.querySelector('[data-tot="' + i + '"]'); if (cell) cell.innerHTML = num(t, 0) + ' pk<small>' + amt(t, r) + (r.sent ? ' · sent ' + num(r.sent, 0) : '') + '</small>';
          });
          var g = $('plGT'); if (g) g.innerHTML = num(gt, 0) + ' pk<small>' + esc(amtSum(gtList)) + '</small>';
          d.hubs.forEach(function (h) { var c = root.querySelector('[data-ht="' + h.id + '"]'); if (c) c.textContent = num(ht[h.id] || 0, 0); });
          var n = Object.keys(PL.dirty).length;
          var dd = $('plDirty'); if (dd) { dd.hidden = !n; dd.textContent = n + ' change' + (n === 1 ? '' : 's') + ' not saved'; }
          $('plSum').innerHTML = '<span>Demand <b>' + num(tt.demand, 0) + ' pk</b></span><span>Usable in hubs <b>' + num(tt.usable, 0) + ' pk</b></span><span>Suggested <b>' + num(tt.suggested, 0) + ' pk</b></span><span>Planned <b>' + num(sum.planned, 0) + ' pk · ' + esc(amtSum(gtList)) + '</b></span><span>Sent <b>' + num(sum.sent, 0) + ' pk</b></span><span>Still to send <b>' + num(sum.toSend, 0) + ' pk</b></span>' + (tt.stale ? '<span>Old inward not counted <b>' + num(tt.stale, 0) + ' pk</b></span>' : '');
          PL.toSend = sum.toSend;
        }
        inputs.forEach(function (inp, idx) {
          inp.addEventListener('focus', function () { inp.select(); });
          inp.addEventListener('input', function () {
            inp.value = inp.value.replace(/[^\d]/g, '').slice(0, 5);
            var r = d.rows[+inp.getAttribute('data-r')], h = inp.getAttribute('data-h'), c = r.hubs[h];
            var orig = c ? c.planned : 0, key = h + '|' + r.code;
            if (val(inp) !== orig) PL.dirty[key] = { hub_id: +h, code: r.code, planned: val(inp), suggested: c ? c.suggested : 0 }; else delete PL.dirty[key];
            inp.classList.toggle('ch', !!PL.dirty[key]); inp.parentNode.classList.toggle('z', !val(inp));
            totals();
          });
          inp.addEventListener('keydown', function (e) {
            var cols = d.hubs.length, go = null;
            if (e.key === 'Enter' || e.key === 'ArrowDown') go = idx + cols; else if (e.key === 'ArrowUp') go = idx - cols;
            else if (e.key === 'ArrowRight' && inp.selectionStart === inp.value.length) go = idx + 1; else if (e.key === 'ArrowLeft' && inp.selectionStart === 0) go = idx - 1;
            if (go != null && inputs[go]) { e.preventDefault(); inputs[go].focus(); }
          });
        });
        totals();
        root.querySelectorAll('.pr-days [data-d]').forEach(function (b) { b.addEventListener('click', function () { leave(function () { st.date = b.getAttribute('data-d'); K.render(); }); }); });
        var qt; $('plQ').addEventListener('input', function () { clearTimeout(qt); var v = this.value; qt = setTimeout(function () { leave(function () { st.q = v; K.render(); }); }, 450); });
        $('plC').addEventListener('change', function () { var v = this.value; leave(function () { st.cat = v; K.render(); }); });
        $('plN').addEventListener('change', function () { var v = this.checked; leave(function () { st.need = v; K.render(); }); });
        root.querySelectorAll('[data-p]').forEach(function (b) { b.addEventListener('click', function () { planDrawer(d, d.rows[+b.getAttribute('data-p')]); }); });
        var rs = $('plReset'); if (rs) rs.addEventListener('click', function () {
          inputs.forEach(function (inp) { var r = d.rows[+inp.getAttribute('data-r')], c = r.hubs[inp.getAttribute('data-h')]; var s = c ? c.suggested : 0; if (val(inp) !== s) { inp.value = s; inp.dispatchEvent(new Event('input')); } });
          toast('Suggestions put back. Press Save plan to keep them.', 'ok');
        });
        var sv = $('plSave'); if (sv) sv.addEventListener('click', function () { save(sv).then(function (ok) { if (ok) K.render(true); }); });
        var sd = $('plSend'); if (sd) sd.addEventListener('click', function () {
          var go = function () { sendModal(d, root); };
          if (Object.keys(PL.dirty).length && can.plan) save(sd).then(function (ok) { if (ok) go(); }); else go();
        });
      });
    } };
    function save(btn) {
      var cells = Object.keys(PL.dirty).map(function (k) { return PL.dirty[k]; });
      if (!cells.length) { toast('Nothing changed.', 'ok'); return Promise.resolve(true); }
      return act(btn, '/pr/plan', { date: PL.data.date, cells: cells }).then(function (r) {
        if (r.ok) {
          PL.dirty = {}; var dd = $('plDirty'); if (dd) dd.hidden = true;
          document.querySelectorAll('.pr-cell input.ch').forEach(function (i) { i.classList.remove('ch'); i.classList.add('sv'); });
          cells.forEach(function (c) { PL.data.rows.forEach(function (r2) { if (r2.code === c.code && r2.hubs[c.hub_id]) { r2.hubs[c.hub_id].planned = c.planned; r2.hubs[c.hub_id].saved = true; } }); });
        }
        return !!r.ok;
      });
    }
    function leave(fn) {
      if (!Object.keys(PL.dirty).length) return fn();
      ask({ title: 'Leave without saving?', text: 'You changed ' + Object.keys(PL.dirty).length + ' box(es) in the plan. They are lost if you leave now.', ok: 'Leave without saving', cancel: 'Stay', tone: 'warn' }).then(function (y) { if (y) { PL.dirty = {}; fn(); } });
    }
    window.addEventListener('hashchange', function () { if (S.view === 'pr-plan' && Object.keys(PL.dirty).length) toast('Plan changes were not saved.', 'bad'); }, true);

    function planDrawer(d, r) {
      var rows = d.hubs.map(function (h) { var c = r.hubs[h.id]; return c ? Object.assign({ hub: h.name, city: h.city, hub_id: h.id }, c) : null; }).filter(Boolean);
      openDrawer(r.name, '');
      var b = $('ccDBody');
      b.innerHTML = '<div class="cc-chips">' + pill(r.pack || 'pack') + pill(r.category, 'p-info') + (r.active ? '' : pill('Switched off', 'p-bad')) + (r.suspect ? pill('Check the pack size', 'p-warn') : '') + '</div>' +
        '<div class="cc-kv"><div><small>Sells per day</small><b>' + amt(r.daily, r) + '</b></div><div><small>Booked</small><b>' + amt(r.booked, r) + '</b></div><div><small>Demand</small><b>' + amt(r.demand, r) + '</b></div><div><small>Usable stock</small><b>' + amt(r.usable, r) + '</b></div><div><small>Suggested</small><b>' + num(r.suggested, 0) + ' pk</b></div><div><small>Planned</small><b>' + num(r.planned, 0) + ' pk</b></div><div><small>Sent</small><b>' + num(r.sent, 0) + ' pk</b></div><div><small>Price</small><b>' + money2(r.price) + '</b></div></div>' + slot('t') +
        '<p class="cc-note">Packs per hub. Usable = fresh + danger + waiting (today) or what stays fresh into tomorrow. Click a hub row to see that hub\'s stock of every product.</p>';
      mount(b, 't', T([{ k: 'hub', t: 'Hub', f: function (x) { return esc(x.hub) + '<small>' + esc(x.city) + '</small>'; } },
        { k: 'daily', t: 'Per day', n: 1, f: function (x) { return num(x.daily, 1); } }, { k: 'booked', t: 'Booked', n: 1, f: function (x) { return num(x.booked, 0); } }, { k: 'demand', t: 'Demand', n: 1, f: function (x) { return num(x.demand, 1); } },
        { k: 'fresh', t: 'Fresh', n: 1, f: function (x) { return num(x.fresh, 0); } }, { k: 'danger', t: 'Danger', n: 1, f: function (x) { return x.danger ? '<b style="color:var(--warn)">' + num(x.danger, 0) + '</b>' : '0'; } },
        { k: 'pending', t: 'Waiting', n: 1, f: function (x) { return num(x.pending, 0) + (x.stale ? '<small>' + num(x.stale, 0) + ' old</small>' : ''); } }, { k: 'usable', t: 'Usable', n: 1, f: function (x) { return num(x.usable, 1); } },
        { k: 'suggested', t: 'Sugg.', n: 1 }, { k: 'planned', t: 'Plan', n: 1, f: function (x) { return '<b>' + num(x.planned, 0) + '</b>'; } }, { k: 'sent', t: 'Sent', n: 1 }], rows, { noPage: 1, csv: 'plan-' + r.code + '-' + d.date + '.csv' }));
    }

    function sendModal(d, root) {
      var byHub = {}, lines = [], packs = 0, value = 0;
      root.querySelectorAll('.pr-cell input').forEach(function (inp) {
        var r = d.rows[+inp.getAttribute('data-r')], h = inp.getAttribute('data-h'), c = r.hubs[h], v = +inp.value || 0, q = Math.max(0, v - (c ? c.sent : 0));
        if (q <= 0) return;
        (byHub[h] = byHub[h] || []).push({ r: r, q: q });
        lines.push({ hub_id: +h, code: r.code, qty: q }); packs += q; value += q * r.price;
      });
      if (!lines.length) { toast('Nothing left to send for ' + (d.date === tomorrowStr ? 'tomorrow' : d.date) + '. Every planned pack has been sent.', 'ok'); return; }
      var hubs = Object.keys(byHub);
      var staleN = 0; hubs.forEach(function (h) { byHub[h].forEach(function (x) { var c = x.r.hubs[h]; if (c && c.untracked > 0) staleN++; }); });
      var warn = staleN ? '<p class="pr-dirty" style="margin:0 0 10px">' + staleN + ' of these lines go to a hub that still has old waiting stock for the same product that no production dispatch explains. The hub will see one combined number to receive. ' + (V['pr-batches'] ? 'You can clear old waiting stock in Dispatches to hubs first.' : '') + '</p>' : '';
      var tbl = '<div class="pr-send"><table class="cc-t"><thead><tr><th>Product</th><th class="n">Packs</th><th class="n">Weight / pcs</th></tr></thead><tbody>' + hubs.map(function (h) {
        var hb = hubById(h), list = byHub[h];
        return '<tr class="pr-hubhead"><td colspan="3">' + esc(hb.name) + (hb.city ? ' · ' + esc(hb.city) : '') + ' — ' + list.length + ' product' + (list.length === 1 ? '' : 's') + ', ' + num(list.reduce(function (a, x) { return a + x.q; }, 0), 0) + ' packs</td></tr>' +
          list.map(function (x) { return '<tr><td>' + esc(x.r.name) + '<small>' + esc(x.r.pack) + '</small></td><td class="n"><b>' + num(x.q, 0) + '</b></td><td class="n">' + amt(x.q, x.r) + '</td></tr>'; }).join('');
      }).join('') + '</tbody></table></div>';
      var key = oneKey();
      openModal('Send to hubs · ' + (d.date === tomorrowStr ? 'for tomorrow' : 'for ' + d.date), '<p class="cc-note">' + num(packs, 0) + ' packs worth ' + money(value) + ' to ' + hubs.length + ' hub' + (hubs.length === 1 ? '' : 's') + '. Each hub sees it in Accept inward stocks and counts it in when it arrives; any difference is recorded there.</p>' + warn + tbl +
        '<div class="cc-row" style="margin-top:10px"><label class="cc-f" style="flex:1"><span>Vehicle / driver (optional)</span><input class="cc-in" id="sdV" maxlength="60" placeholder="e.g. KA05 AB 1234 · Ravi"></label><label class="cc-f" style="flex:2"><span>Note (optional)</span><input class="cc-in" id="sdN" maxlength="250" placeholder="Shown on the dispatch note"></label></div>',
        [{ t: 'Send ' + num(packs, 0) + ' packs', cls: 'pri', fn: function (b) {
          act(b, '/pr/send', { date: d.date, lines: lines, vehicle: $('sdV').value, note: $('sdN').value, client_ref: key }, function (r) { closeModal(); K.render(); if (r.batch) setTimeout(function () { batchDrawer(r.batch.id); }, 350); });
        } }]);
    }

    /* ------------------------------------------------------------------ production sheet (print) */
    V['pr-sheet'] = { t: 'Production sheet', dates: false, nofilter: false, run: function (root) {
      var st = S.p.ps = Object.assign({ date: tomorrowStr }, S.p.ps || {});
      return api('/pr/sheet', { date: st.date }, false).then(function (d) {
        if (!d.ok) { root.innerHTML = card('Production sheet', '<div class="cc-empty">' + esc(d.message || '') + '</div>'); return; }
        st.date = d.date;
        var hubs = {}; d.hubs.forEach(function (h) { hubs[h.id] = h.name; });
        var rows = [], cat = null;
        d.rows.forEach(function (r) { if (r.category !== cat) { cat = r.category; var c = d.by_category[cat] || {}; rows.push({ _cat: cat, _amt: [c.kg ? num(c.kg) + ' kg' : '', c.pcs ? num(c.pcs, 0) + ' pcs' : '', c.L ? num(c.L) + ' L' : ''].filter(Boolean).join(' + ') }); } rows.push(r); });
        var label = (d.dates.filter(function (x) { return x.date === d.date; })[0] || {}).label || d.date;
        root.innerHTML = '<div class="pr-print-head"><h1>Freshful production sheet · ' + esc(label) + ' ' + esc(dOnly(Date.parse(d.date + 'T12:00:00') / 1000)) + '</h1><p>Printed ' + esc(new Date().toLocaleString('en-IN')) + ' by ' + esc(C.actor) + ' · ' + d.rows.length + ' products · ' + num(d.total.planned, 0) + ' packs</p></div>' +
          '<section class="cc-card"><div class="pr-bar pr-noprint">' + dayTabs(d.dates, d.date) + '<span style="flex:1"></span><span class="cc-note">From the saved plan; the suggestion where nothing is saved.</span><button type="button" class="cc-btn pri" id="psPrint">Print</button></div>' +
          '<div class="cc-body flush"><div class="cc-tw"><table class="cc-t"><thead><tr><th>Product</th><th>Pack</th><th class="n">Packs</th><th class="n">Total to make</th><th>Per hub (packs)</th><th class="n">Sent</th><th>Done</th></tr></thead><tbody>' +
          (rows.length ? rows.map(function (r) {
            if (r._cat) return '<tr class="pr-cat-h"><td colspan="3">' + esc(r._cat) + '</td><td class="n">' + esc(r._amt) + '</td><td colspan="3"></td></tr>';
            return '<tr><td><b>' + esc(r.name) + '</b><small>' + esc(r.code) + '</small></td><td>' + esc(r.pack) + '</td><td class="n"><b>' + num(r.packs, 0) + '</b></td><td class="n"><b>' + esc(r.amount_text) + '</b></td><td style="white-space:normal;font-size:12px">' + r.hubs.map(function (h) { return esc(hubs[h.hub_id] || ('Hub ' + h.hub_id)) + ' <b>' + num(h.planned, 0) + '</b>'; }).join(' · ') + '</td><td class="n">' + (r.sent ? num(r.sent, 0) : '–') + '</td><td>☐</td></tr>';
          }).join('') : '<tr><td colspan="7"><div class="cc-empty">Nothing to make for this day.</div></td></tr>') +
          '</tbody></table></div></div></section><div class="pr-sign"><div>Prepared by</div><div>Checked by (quality)</div><div>Dispatched by</div></div>';
        root.querySelectorAll('.pr-days [data-d]').forEach(function (b) { b.addEventListener('click', function () { st.date = b.getAttribute('data-d'); K.render(); }); });
        $('psPrint').addEventListener('click', function () { window.print(); });
      });
    } };

    /* ------------------------------------------------------------------ dispatches */
    V['pr-batches'] = { t: 'Dispatches to hubs', dates: true, run: function (root) {
      var st = S.p.pb = Object.assign({ status: '' }, S.p.pb || {});
      return api('/pr/batches', { status: st.status }).then(function (d) {
        var t = d.total || {};
        root.innerHTML = '<div class="cc-kpis">' + kpi('Dispatches', num(t.batches || 0, 0), K.S.from === K.S.to ? dOnly(Date.parse(K.S.from + 'T12:00:00') / 1000) : 'in these dates') + kpi('Packs sent', num(t.packs || 0, 0), money(t.value || 0)) +
          kpi('Lines waiting at hubs', num(t.waiting || 0, 0), 'sent, not received yet', t.waiting ? 'warn' : '') + kpi('Received short', num(t.short || 0) + ' pk', 'less than sent', t.short ? 'bad' : '') + '</div>' +
          card('Dispatches', '<div class="pr-bar pr-noprint"><div class="cc-tabs" id="pbS">' + [['', 'All'], ['sent', 'Waiting'], ['partly', 'Partly received'], ['received', 'Received'], ['cancelled', 'Cancelled']].map(function (x) { return '<button type="button" data-s="' + x[0] + '" class="' + (st.status === x[0] ? 'is-on' : '') + '">' + x[1] + '</button>'; }).join('') + '</div><span style="flex:1"></span>' + (can.send ? '<button type="button" class="cc-btn pri" id="pbNew">New dispatch</button>' : '') + '</div>' + slot('t'), { flush: 1, sub: 'Every send to the hubs, newest first. Click one for its lines, receipts and the printable dispatch note.' });
        if (d.installed === false) { root.querySelector('[data-slot="t"]').outerHTML = '<div class="cc-empty">The production tables are not installed yet.</div>'; return; }
        mount(root, 't', T([{ k: 'no', t: 'Dispatch', f: function (r) { return '<b>' + esc(r.no) + '</b><small>' + dt(r.at) + ' · ' + esc(r.by) + '</small>'; }, c: function (r) { return r.no; } },
          { k: 'plan_date', t: 'For', f: function (r) { return r.plan_date ? dOnly(Date.parse(r.plan_date + 'T12:00:00') / 1000) : '–'; } },
          { k: 'hub_names', t: 'Hubs', f: function (r) { return esc(r.hub_names) + '<small>' + r.hubs + ' hub' + (r.hubs === 1 ? '' : 's') + ' · ' + r.lines + ' lines</small>'; } },
          { k: 'packs', t: 'Packs', n: 1, f: function (r) { return num(r.packs, 0); } }, { k: 'value', t: 'Value', n: 1, f: function (r) { return money(r.value); } },
          { k: 'status', t: 'Status', f: function (r) { return stPill(r.status) + '<small>' + r.received + ' received · ' + r.waiting + ' waiting' + (r.cancelled ? ' · ' + r.cancelled + ' cancelled' : '') + '</small>'; }, c: function (r) { return r.status; } },
          { k: 'short', t: 'Short', n: 1, f: function (r) { return r.short ? '<b style="color:var(--bad)">' + num(r.short) + ' pk</b>' : '–'; } },
          { k: 'vehicle', t: 'Vehicle', f: function (r) { return esc(r.vehicle || '–'); } }], d.rows, { per: 50, csv: 'dispatches-' + S.from + '-' + S.to + '.csv', click: function (r) { batchDrawer(r.id); }, empty: 'No dispatch in these dates.' }));
        root.querySelectorAll('#pbS [data-s]').forEach(function (b) { b.addEventListener('click', function () { st.status = b.getAttribute('data-s'); K.render(); }); });
        var nb = $('pbNew'); if (nb) nb.addEventListener('click', manualSend);
        return staleCard(root);
      });
    } };

    /* Old stock waiting at hubs: review, clear (kept, can be put back) */
    function staleCard(root) {
      return api('/pr/stale', {}, false).then(function (d) {
        if (!d.ok || (!d.total.lines && !d.cleared.length)) return;
        var box = el('<div></div>'); root.appendChild(box);
        var t = d.total;
        box.innerHTML = card('Old stock waiting at hubs', (t.lines ? '<div class="pr-bar"><span class="cc-note" style="flex:1">' + num(t.lines, 0) + ' lines (' + num(t.packs, 0) + ' packs, ' + money(t.value) + ') were sent more than ' + d.days + ' days ago and never received. The plan does not count them as stock. If they never arrived, clear them so hubs only see real inward stock. Every cleared line is kept and can be put back.</span>' + (can.stale ? '<button type="button" class="cc-btn danger" id="stClr">Review and clear</button>' : '') + '</div>' + slot('h') : '<div class="cc-empty">No old waiting stock.</div>') + (d.cleared.length ? '<div class="ad-pad" style="padding:10px 14px 4px"><b>Cleared in the last 60 days</b></div>' + slot('c') : ''), { flush: 1 });
        if (t.lines) mount(box, 'h', T([{ k: 'hub', t: 'Hub', f: function (r) { return esc(r.hub) + '<small>' + esc(r.city) + '</small>'; } }, { k: 'lines', t: 'Lines', n: 1 }, { k: 'packs', t: 'Packs', n: 1, f: function (r) { return num(r.packs, 0); } }, { k: 'value', t: 'Value', n: 1, f: function (r) { return money(r.value); } },
          { k: 'oldest', t: 'Waiting since', n: 1, f: function (r) { return r.oldest ? dOnly(r.oldest) : '–'; } }, { k: 'unknown', t: 'Deleted products', n: 1, f: function (r) { return r.unknown ? '<b style="color:var(--warn)">' + r.unknown + '</b>' : '0'; } }], d.hubs, { per: 20, csv: 'old-waiting-stock.csv' }));
        if (d.cleared.length) mount(box, 'c', T([{ k: 'at', t: 'Cleared', n: 1, f: function (r) { return dt(r.at) + '<small>' + esc(r.by) + '</small>'; } }, { k: 'hub', t: 'Hub' }, { k: 'name', t: 'Product', f: function (r) { return esc(r.name) + '<small>' + esc(r.code) + '</small>'; } }, { k: 'qty', t: 'Packs', n: 1, f: function (r) { return num(r.qty, 0); } }, { k: 'note', t: 'Reason' },
          { k: 'restored', t: '', nosort: 1, csv: false, f: function (r) { return r.restored ? '<small>Put back ' + dt(r.restored) + '</small>' : (can.stale ? '<button type="button" class="cc-btn sm" data-rs="' + r.id + '">Put back</button>' : ''); } }], d.cleared, { per: 20, after: function (w) {
            w.querySelectorAll('[data-rs]').forEach(function (b) { b.addEventListener('click', function () { act(b, '/pr/stale/' + b.getAttribute('data-rs') + '/restore', {}, function () { K.render(true); }); }); });
          } }));
        var cb = box.querySelector('#stClr'); if (cb) cb.addEventListener('click', function () {
          openModal('Clear old waiting stock', '<p class="cc-note">Only lines waiting more than ' + d.days + ' days. Live stock is not touched. Choose the hubs:</p><div class="pr-send" style="padding:8px 12px">' + d.hubs.map(function (h) { return '<label class="cc-chip" style="display:flex;margin:4px 0;cursor:pointer"><input type="checkbox" data-hb="' + h.hub_id + '" checked> ' + esc(h.hub) + ' · ' + h.lines + ' lines, ' + money(h.value) + '</label>'; }).join('') + '</div><label class="cc-f" style="margin-top:10px"><span>Reason (kept with every line)</span><input class="cc-in" id="stN" maxlength="250" placeholder="e.g. old test data from 2023, never physically sent"></label>',
            [{ t: 'Clear', cls: 'pri', fn: function (b) {
              var hubs = [].slice.call($('ccMBody').querySelectorAll('[data-hb]:checked')).map(function (i) { return +i.getAttribute('data-hb'); });
              if (!hubs.length) { toast('Choose at least one hub.', 'bad'); return; }
              var n = $('stN').value.trim(); if (n.length < 4) { toast('Write the reason.', 'bad'); return; }
              act(b, '/pr/stale/clear', { hubs: hubs, note: n }, function () { closeModal(); K.render(); });
            } }]);
        });
      });
    }

    function batchDrawer(id) {
      openDrawer('Dispatch', '<div class="cc-load"><i></i>Loading…</div>');
      api('/pr/batch/' + id, {}, false).then(function (d) {
        var b = $('ccDBody');
        if (!d.ok) { b.innerHTML = '<div class="cc-empty">' + esc(d.message) + '</div>'; return; }
        var x = d.batch; $('ccDTitle').textContent = 'Dispatch ' + x.no;
        var byHub = {}; d.lines.forEach(function (l) { (byHub[l.hub_id] = byHub[l.hub_id] || []).push(l); });
        b.innerHTML = '<div class="cc-chips">' + stPill(x.status) + pill(num(x.packs, 0) + ' packs') + pill(money(x.value)) + (x.plan_date ? pill('For ' + dOnly(Date.parse(x.plan_date + 'T12:00:00') / 1000), 'p-info') : '') + '</div>' +
          '<dl class="cc-dl"><dt>Sent</dt><dd>' + dt(x.at) + ' by ' + esc(x.by) + '</dd>' + (x.vehicle ? '<dt>Vehicle</dt><dd>' + esc(x.vehicle) + '</dd>' : '') + (x.note ? '<dt>Note</dt><dd>' + esc(x.note) + '</dd>' : '') + '</dl>' +
          '<div class="cc-row pr-noprint"><button type="button" class="cc-btn" id="bdPrint">Print dispatch notes</button></div>' +
          Object.keys(byHub).map(function (h) {
            var list = byHub[h], hb = list[0];
            return '<div class="cc-card"><header><h2>' + esc(hb.hub) + (hb.city ? ' · ' + esc(hb.city) : '') + '</h2><p>' + list.length + ' line' + (list.length === 1 ? '' : 's') + ' · ' + num(list.reduce(function (a, l) { return a + (l.status === 'cancelled' ? 0 : l.qty); }, 0), 0) + ' packs</p></header><div class="cc-body flush"><div class="cc-tw"><table class="cc-t"><thead><tr><th>Product</th><th class="n">Sent</th><th class="n">Received</th><th>Status</th><th class="pr-noprint"></th></tr></thead><tbody>' +
              list.map(function (l) {
                var diff = l.received != null ? l.received - l.qty : null;
                return '<tr><td><b>' + esc(l.name) + '</b><small>' + esc(l.pack) + ' · ' + esc(l.code) + (l.pending_before ? ' · ' + num(l.pending_before, 0) + ' already waiting' : '') + '</small></td><td class="n"><b>' + num(l.qty, 0) + '</b><small>' + esc(l.amount_text) + '</small></td>' +
                  '<td class="n">' + (l.status === 'received' ? (l.received == null ? '<span class="muted">old panel</span>' : '<b>' + num(l.received) + '</b>' + (diff && Math.abs(diff) > 0.0005 ? '<small style="color:' + (diff < 0 ? 'var(--bad)' : 'var(--warn)') + '">' + (diff > 0 ? '+' : '') + num(diff) + '</small>' : '')) : '–') + '</td>' +
                  '<td>' + stPill(l.status) + (l.received_at ? '<small>' + dt(l.received_at) + '</small>' : '') + (l.cancel_note ? '<small>' + esc(l.cancel_note) + '</small>' : '') + '</td>' +
                  '<td class="pr-noprint">' + (l.status === 'sent' && can.cancel ? '<button type="button" class="cc-btn sm danger" data-cx="' + l.id + '">Cancel</button>' : '') + '</td></tr>';
              }).join('') + '</tbody></table></div></div></div>';
          }).join('') + '<div class="pr-sign"><div>Dispatched by</div><div>Driver</div><div>Received by (hub)</div></div>';
        b.querySelectorAll('[data-cx]').forEach(function (bt) { bt.addEventListener('click', function () {
          ask({ title: 'Cancel this line?', text: 'The packs will no longer be expected at the hub. Only possible while the hub has not received them.', ok: 'Cancel line', cancel: 'Keep', tone: 'danger', input: { label: 'Reason', placeholder: 'e.g. not loaded on the vehicle' } }).then(function (why) {
            if (!why) return; act(bt, '/pr/line/' + bt.getAttribute('data-cx') + '/cancel', { note: why }, function () { batchDrawer(id); if (S.view === 'pr-batches') K.render(true); });
          });
        }); });
        $('bdPrint').addEventListener('click', function () { document.body.classList.add('pr-printing-drawer'); window.print(); setTimeout(function () { document.body.classList.remove('pr-printing-drawer'); }, 500); });
      });
    }

    /* Send something outside the plan: one hub, any products. */
    function manualSend() {
      var today = C.today;
      openModal('New dispatch', '<div class="cc-load"><i></i>Loading products…</div>');
      api('/pr/plan', { date: today }, false).then(function (d) {
        if (!d.ok) { $('ccMBody').innerHTML = '<div class="cc-empty">' + esc(d.message || '') + '</div>'; return; }
        var hubs = d.hubs;
        if (!hubs.length) { $('ccMBody').innerHTML = '<div class="cc-empty">No active hub in your filters.</div>'; return; }
        var body = el('<div class="pr-form"><div class="cc-row"><label class="cc-f" style="flex:1"><span>Hub</span><select class="cc-sel" id="msH">' + hubs.map(function (h) { return '<option value="' + h.id + '">' + esc(h.name + (h.city ? ' · ' + h.city : '')) + '</option>'; }).join('') + '</select></label>' +
          '<label class="cc-f" style="flex:1"><span>Find product</span><input class="cc-in" id="msQ" type="search" placeholder="Name or code"></label></div>' +
          '<div class="pr-send" id="msL"></div><div class="cc-row" style="margin-top:10px"><label class="cc-f" style="flex:1"><span>Vehicle / driver</span><input class="cc-in" id="msV" maxlength="60"></label><label class="cc-f" style="flex:2"><span>Note</span><input class="cc-in" id="msN" maxlength="250" placeholder="Why this extra dispatch"></label></div><p class="cc-note" id="msT"></p></div>');
        $('ccMBody').innerHTML = ''; $('ccMBody').appendChild(body);
        var qty = {}, mkey = oneKey();
        function draw() {
          var h = $('msH').value, q = $('msQ').value.trim().toLowerCase();
          var list = d.rows.filter(function (r) { return r.active && (!q || (r.name + ' ' + r.code).toLowerCase().indexOf(q) >= 0); }).slice(0, 150);
          $('msL').innerHTML = '<table class="cc-t"><thead><tr><th>Product</th><th class="n">In hub (usable)</th><th class="n">Packs to send</th></tr></thead><tbody>' + list.map(function (r) { var c = r.hubs[h] || {}; var k = h + '|' + r.code; return '<tr><td>' + esc(r.name) + '<small>' + esc(r.pack) + '</small></td><td class="n">' + num(c.usable || 0, 0) + '</td><td class="n"><input class="cc-in" style="width:80px;text-align:right" inputmode="numeric" data-k="' + esc(k) + '" value="' + (qty[k] || '') + '"></td></tr>'; }).join('') + '</tbody></table>';
          $('msL').querySelectorAll('[data-k]').forEach(function (i) { i.addEventListener('input', function () { i.value = i.value.replace(/[^\d]/g, '').slice(0, 5); if (+i.value > 0) qty[i.getAttribute('data-k')] = +i.value; else delete qty[i.getAttribute('data-k')]; tot(); }); });
        }
        function tot() { var n = 0, p = 0; Object.keys(qty).forEach(function (k) { n++; p += qty[k]; }); $('msT').textContent = n ? n + ' line(s), ' + p + ' packs ready to send.' : 'Enter packs for the products to send.'; }
        $('msH').addEventListener('change', draw); $('msQ').addEventListener('input', draw); draw(); tot();
        var f = $('ccMFoot'); var bt = document.createElement('button'); bt.type = 'button'; bt.className = 'cc-btn pri'; bt.textContent = 'Send'; f.insertBefore(bt, f.firstChild);
        bt.addEventListener('click', function () {
          var lines = Object.keys(qty).map(function (k) { var p = k.split('|'); return { hub_id: +p[0], code: p.slice(1).join('|'), qty: qty[k] }; });
          if (!lines.length) { toast('Enter packs for at least one product.', 'bad'); return; }
          act(bt, '/pr/send', { date: today, lines: lines, vehicle: $('msV').value, note: $('msN').value, client_ref: mkey }, function (r) { closeModal(); K.render(); if (r.batch) setTimeout(function () { batchDrawer(r.batch.id); }, 350); });
        });
      });
    }

    /* ------------------------------------------------------------------ receipts */
    V['pr-receipts'] = { t: 'Hub receipts & differences', dates: true, run: function (root) {
      return api('/pr/receipts').then(function (d) {
        var t = d.total || {};
        root.innerHTML = '<div class="cc-kpis">' + kpi('Receipts', num(t.receipts || 0, 0), 'stock counted in by hubs') + kpi('Exactly as sent', t.accuracy == null ? '–' : pct(t.accuracy), 'of receipts', t.accuracy != null && t.accuracy < 90 ? 'warn' : '') +
          kpi('Received short', money(t.short_value || 0), 'less than sent', t.short_value ? 'bad' : '') + kpi('Received extra', money(t.over_value || 0), 'more than sent', t.over_value ? 'warn' : '') + '</div>' +
          card('By hub', slot('h'), { flush: 1, sub: 'Every time a hub accepts inward stock it enters what really arrived; the difference against what was sent is kept.' }) + card('Every receipt', slot('r'), { flush: 1 });
        mount(root, 'h', T([{ k: 'hub', t: 'Hub', f: function (r) { return esc(r.hub) + '<small>' + esc(r.city) + '</small>'; } }, { k: 'receipts', t: 'Receipts', n: 1 }, { k: 'exact', t: 'Exact', n: 1 },
          { k: 'accuracy', t: 'Accuracy', n: 1, f: function (r) { return r.accuracy == null ? '–' : pill(r.accuracy + '%', r.accuracy >= 95 ? 'p-ok' : r.accuracy >= 85 ? 'p-warn' : 'p-bad'); } }, { k: 'short', t: 'Short', n: 1 },
          { k: 'short_value', t: 'Short value', n: 1, f: function (r) { return r.short_value ? '<b style="color:var(--bad)">' + money(r.short_value) + '</b>' : '–'; } }, { k: 'over', t: 'Extra', n: 1 }, { k: 'over_value', t: 'Extra value', n: 1, f: function (r) { return r.over_value ? money(r.over_value) : '–'; } }], d.by_hub, { noPage: 1, empty: 'No receipts in these dates.' }));
        mount(root, 'r', T([{ k: 'at', t: 'When', n: 1, f: function (r) { return dt(r.at); }, c: function (r) { return dt(r.at); } }, { k: 'hub', t: 'Hub' }, { k: 'name', t: 'Product', f: function (r) { return '<b>' + esc(r.name) + '</b><small>' + esc(r.pack) + '</small>'; } },
          { k: 'sent', t: 'Sent', n: 1, f: function (r) { return r.sent == null ? '–' : num(r.sent); } }, { k: 'received', t: 'Received', n: 1, f: function (r) { return '<b>' + num(r.received) + '</b>'; } },
          { k: 'diff', t: 'Difference', n: 1, f: function (r) { return Math.abs(r.diff) < 0.0005 ? '<span class="muted">0</span>' : '<b style="color:' + (r.diff < 0 ? 'var(--bad)' : 'var(--warn)') + '">' + (r.diff > 0 ? '+' : '') + num(r.diff) + '</b><small>' + money(r.diff_value) + '</small>'; } },
          { k: 'note', t: 'Hub note', f: function (r) { return esc(r.note || '–') + (r.by ? '<small>' + esc(r.by) + '</small>' : ''); } }], d.rows, { per: 50, csv: 'receipts-' + S.from + '-' + S.to + '.csv', empty: 'No receipts in these dates.' }));
      });
    } };

    /* ------------------------------------------------------------------ production stock board (the old production page) */
    V['pr-board'] = { t: 'Production stock board', dates: false, run: function (root) {
      var st = S.p.bd = Object.assign({ q: '', cat: '', show: 'stock' }, S.p.bd || {});
      return api('/pr/board', { q: st.q, category: st.cat, show: st.show }, false).then(function (d) {
        root.innerHTML = card('Stock of every product in every hub', '<div class="pr-bar"><input class="cc-in" id="bdQ" type="search" placeholder="Product, code or hub" value="' + esc(st.q) + '" style="width:220px"><select class="cc-sel" id="bdC"><option value="">All categories</option>' + d.categories.map(function (c) { return '<option' + (c === st.cat ? ' selected' : '') + '>' + esc(c) + '</option>'; }).join('') + '</select>' +
          '<select class="cc-sel" id="bdS">' + [['', 'Every line'], ['stock', 'With stock or waiting'], ['danger', 'Danger or expired'], ['out', 'Selling but out of stock'], ['waiting', 'Waiting to be received']].map(function (x) { return '<option value="' + x[0] + '"' + (st.show === x[0] ? ' selected' : '') + '>' + x[1] + '</option>'; }).join('') + '</select><span style="flex:1"></span><span class="cc-note">' + num(d.total.rows, 0) + ' lines · ' + money(d.total.value) + ' live</span></div>' + slot('t'),
          { flush: 1, sub: 'Fresh = received today; danger = received yesterday (sell today); expired = older. Cover = days the usable stock lasts at the 7-day sales rate. Click a line for that product in every hub.' });
        mount(root, 't', T([{ k: 'name', t: 'Product', f: function (r) { return '<b>' + esc(r.name) + '</b>' + (r.known ? '' : ' <span class="pr-flag">no product</span>') + (r.active || !r.known ? '' : ' <span class="pr-flag o">off</span>') + '<small>' + esc(r.code) + ' · ' + esc(r.pack) + '</small>'; }, c: function (r) { return r.name; } },
          { k: 'category', t: 'Category' }, { k: 'hub', t: 'Hub', f: function (r) { return esc(r.hub) + '<small>' + esc(r.city) + '</small>'; } },
          { k: 'live', t: 'Live', n: 1, f: function (r) { return '<b>' + amt(r.live, r) + '</b><small>' + num(r.live, 0) + ' pk</small>'; }, c: function (r) { return r.live; } },
          { k: 'fresh', t: 'Fresh', n: 1, f: function (r) { return num(r.fresh, 0); } }, { k: 'danger', t: 'Danger', n: 1, f: function (r) { return r.danger ? '<b style="color:var(--warn)">' + num(r.danger, 0) + '</b>' : '0'; } },
          { k: 'expired', t: 'Expired', n: 1, f: function (r) { return r.expired ? '<b style="color:var(--bad)">' + num(r.expired, 0) + '</b>' : '0'; } },
          { k: 'pending', t: 'Waiting in', n: 1, f: function (r) { return r.pending ? num(r.pending, 0) + (r.stale ? '<small style="color:var(--warn)">old</small>' : '') : '0'; } },
          { k: 'sold_today', t: 'Sold today', n: 1, f: function (r) { return num(r.sold_today, 0); } }, { k: 'per_day', t: 'Per day', n: 1, f: function (r) { return num(r.per_day, 1); } },
          { k: 'cover', t: 'Cover', n: 1, f: function (r) { return r.cover == null ? '–' : r.cover < 1 ? '<b style="color:var(--bad)">' + num(r.cover, 1) + ' d</b>' : num(r.cover, 1) + ' d'; } }],
          d.rows, { per: 100, csv: 'production-stock-board.csv', click: function (r) { if (r.id && V.stock) K.productDrawer(r.id, r.name); }, empty: 'No lines match.' }));
        var qt; $('bdQ').addEventListener('input', function () { clearTimeout(qt); var v = this.value; qt = setTimeout(function () { st.q = v; K.render(); }, 450); });
        $('bdC').addEventListener('change', function () { st.cat = this.value; K.render(); });
        $('bdS').addEventListener('change', function () { st.show = this.value; K.render(); });
      });
    } };

    /* ------------------------------------------------------------------ products */
    V['pr-products'] = { t: 'Products', dates: false, nofilter: false, run: function (root) {
      var st = S.p.pp = Object.assign({ q: '', cat: '', on: '' }, S.p.pp || {});
      return api('/pr/products', {}, false).then(function (d) {
        var t = d.total;
        root.innerHTML = '<div class="cc-kpis">' + kpi('Products', num(t.products, 0), num(t.on, 0) + ' on sale') + kpi('Pack size to check', num(t.suspect, 0), 'kg figures are off for these', t.suspect ? 'warn' : '') + kpi('No cost price', num(t.no_cost, 0), 'on sale without a cost; margin unknown', t.no_cost ? 'warn' : '') + '</div>' +
          card('Products', '<div class="pr-bar"><input class="cc-in" id="ppQ" type="search" placeholder="Name or code" value="' + esc(st.q) + '" style="width:220px"><select class="cc-sel" id="ppC"><option value="">All categories</option>' + d.categories.map(function (c) { return '<option' + (c === st.cat ? ' selected' : '') + '>' + esc(c) + '</option>'; }).join('') + '</select>' +
            '<select class="cc-sel" id="ppO"><option value="">On and off</option><option value="1"' + (st.on === '1' ? ' selected' : '') + '>On sale</option><option value="0"' + (st.on === '0' ? ' selected' : '') + '>Off</option></select></div>' + slot('t'),
            { flush: 1, sub: 'Stock, sales and wastage are for the hubs in your filters. Prices and pack sizes are set by the admin in Products; switching a product off hides it from customers.' });
        var rows = d.rows.filter(function (r) { var q = st.q.toLowerCase(); return (!q || (r.name + ' ' + r.code).toLowerCase().indexOf(q) >= 0) && (!st.cat || r.category === st.cat) && (st.on === '' || (st.on === '1') === r.active); });
        mount(root, 't', T([{ k: 'name', t: 'Product', f: function (r) { return '<div style="display:flex;gap:10px;align-items:center">' + (r.image ? '<img src="' + esc(r.image) + '" alt="" loading="lazy" width="40" height="40" style="border-radius:8px;object-fit:cover;border:1px solid var(--line)" onerror="this.style.visibility=\'hidden\'">' : '') + '<div><b>' + esc(r.name) + '</b><small>' + esc(r.code) + ' · ' + esc(r.category) + '</small></div></div>'; }, c: function (r) { return r.name; } },
          { k: 'pack', t: 'Pack', f: function (r) { return esc(r.pack) + (r.unit_bad ? ' <span class="pr-flag" title="The pack unit is not one the system knows (g, kg, ml, L, pcs, dozen, tray...). It is counted as pieces.">unit?</span>' : r.suspect ? ' <span class="pr-flag">check</span>' : '') + '<small>' + esc(r.uom) + '</small>'; } },
          { k: 'price', t: 'Price', n: 1, f: function (r) { return money2(r.price) + (r.mrp && r.mrp > r.price ? '<small>MRP ' + money2(r.mrp) + '</small>' : ''); } },
          { k: 'cost', t: 'Cost', n: 1, f: function (r) { return r.cost == null ? '<span class="muted">–</span>' : money2(r.cost); } },
          { k: 'margin', t: 'Margin', n: 1, f: function (r) { return r.margin == null ? '–' : pill(r.margin + '%', r.margin >= 25 ? 'p-ok' : r.margin >= 10 ? 'p-warn' : 'p-bad'); } },
          { k: 'live', t: 'In hubs', n: 1, f: function (r) { return '<b>' + amt(r.live, r) + '</b><small>' + r.hubs_with + ' hub' + (r.hubs_with === 1 ? '' : 's') + (r.pending ? ' · ' + num(r.pending, 0) + ' pk coming' : '') + '</small>'; }, c: function (r) { return r.live; } },
          { k: 'expired', t: 'Danger / expired', n: 1, f: function (r) { return (r.danger ? '<b style="color:var(--warn)">' + num(r.danger, 0) + '</b>' : '0') + ' / ' + (r.expired ? '<b style="color:var(--bad)">' + num(r.expired, 0) + '</b>' : '0'); } },
          { k: 'sold30', t: 'Sold 30 days', n: 1, f: function (r) { return num(r.sold30, 0) + ' pk<small>' + num(r.per_day, 1) + ' / day</small>'; } },
          { k: 'waste_pct', t: 'Wasted', n: 1, f: function (r) { return r.waste_pct == null ? '–' : (r.waste_pct > 5 ? '<b style="color:var(--bad)">' + r.waste_pct + '%</b>' : r.waste_pct + '%'); } },
          { k: 'active', t: 'On sale', nosort: 1, csv: false, f: function (r) { return can.toggle ? '<button type="button" class="pr-sw' + (r.active ? ' is-on' : '') + '" role="switch" aria-checked="' + r.active + '" aria-label="On sale" data-tg="' + r.id + '"><i></i></button>' : (r.active ? pill('On', 'p-ok') : pill('Off')); } }],
          rows, { per: 50, csv: 'products.csv', empty: 'No product matches.', after: function (w, list) {
            w.querySelectorAll('[data-tg]').forEach(function (b) { b.addEventListener('click', function () {
              var r = list.filter(function (x) { return String(x.id) === b.getAttribute('data-tg'); })[0]; if (!r) return;
              var go = function () { act(b, '/pr/products/' + r.id + '/toggle', {}, function (res) { r.active = !!res.on; b.classList.toggle('is-on', r.active); b.setAttribute('aria-checked', r.active); }); };
              if (r.active) ask({ title: 'Take ' + r.name + ' off sale?', text: 'Customers will no longer see it in the app or website. Stock in the hubs stays.', ok: 'Take off sale', tone: 'warn' }).then(function (y) { if (y) go(); }); else go();
            }); });
          } }));
        var qt; $('ppQ').addEventListener('input', function () { clearTimeout(qt); var v = this.value; qt = setTimeout(function () { st.q = v; K.render(); }, 300); });
        $('ppC').addEventListener('change', function () { st.cat = this.value; K.render(); });
        $('ppO').addEventListener('change', function () { st.on = this.value; K.render(); });
      });
    } };

    /* remove screens this role may not open */
    Object.keys(V).forEach(function (k) { if (!allowed[k]) delete V[k]; });
  };
})();

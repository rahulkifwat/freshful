/* Freshful admin console (admin update 1, 22 Oct 2026). Head-office screens for the admin role.
   Plugs into cc.js (window.CCX) and uses its helpers, styles and dialogs. No browser default pop-ups. */
window.CCX = function (K) {
  'use strict';
  var C = K.C, V = K.V, S = K.S, $ = K.$, esc = K.esc, money = K.money, money2 = K.money2, num = K.num, pct = K.pct, dt = K.dt, dOnly = K.dOnly, dStr = K.dStr,
    pill = K.pill, api = K.api, post = K.post, act = K.act, ask = K.ask, toast = K.toast, card = K.card, kpi = K.kpi, slot = K.slot, mount = K.mount,
    hbars = K.hbars, T = K.T, el = K.el, openDrawer = K.openDrawer, openModal = K.openModal, closeModal = K.closeModal, closeDrawer = K.closeDrawer;

  /* ------------------------------------------------------------------ shared bits */
  var META = null;
  function meta(force) { if (META && !force) return Promise.resolve(META); return api('/ad/meta', {}, false).then(function (d) { META = d; return d; }); }
  function hashParams() { var h = location.hash.split('?')[1] || ''; var o = {}; h.split('&').forEach(function (kv) { if (!kv) return; var p = kv.split('='); o[decodeURIComponent(p[0])] = decodeURIComponent(p[1] || ''); }); return o; }
  function modal(title, body, buttons, wide) { openModal(title, body, buttons); $('ccModal').classList.toggle('ad-wide', !!wide); }
  function sw(on, attrs) { return '<button type="button" class="ad-sw' + (on ? ' is-on' : '') + '" role="switch" aria-checked="' + (on ? 'true' : 'false') + '" ' + (attrs || '') + '><i></i></button>'; }
  function thumb(url, big) { return url ? '<img class="ad-thumb' + (big ? ' big' : '') + '" src="' + esc(url) + '" alt="" loading="lazy" onerror="this.classList.add(\'is-bad\')">' : '<span class="ad-thumb none">No image</span>'; }
  function cut(s, n) { s = String(s == null ? '' : s).replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim(); return s.length > n ? esc(s.slice(0, n)) + '…' : esc(s); }
  function download(path, extra) { var q = Object.keys(extra || {}).map(function (k) { return encodeURIComponent(k) + '=' + encodeURIComponent(extra[k]); }).join('&'); location.href = C.base + path + (q ? '?' + q : ''); }
  function help(t) { return t ? '<p class="cc-note ad-help">' + esc(t) + '</p>' : ''; }
  function upload(file, folder) {
    var fd = new FormData(); fd.append('file', file); fd.append('folder', folder || '');
    return fetch(C.base + '/ad/upload', { method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': C.csrf, 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
      .then(function (r) { if (r.status === 413) return { ok: false, message: 'The image is too large for the server.' }; return r.json().catch(function () { return { ok: false, message: 'Upload failed (' + r.status + ').' }; }); })
      .catch(function () { return { ok: false, message: 'No connection. Try again.' }; });
  }
  function postFile(path, file, fields) {
    var fd = new FormData(); fd.append('file', file); Object.keys(fields || {}).forEach(function (k) { fd.append(k, fields[k]); });
    return fetch(C.base + path, { method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': C.csrf, 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
      .then(function (r) { if (r.status === 419) return { ok: false, message: 'This page has expired. Reload the page and try again.' }; return r.json().catch(function () { return { ok: false, message: 'Server error (' + r.status + ').' }; }); })
      .catch(function () { return { ok: false, message: 'No connection. Try again.' }; });
  }
  /** Image field: preview + choose file; uploads at once and keeps the file name in a hidden input. */
  function imageField(name, label, folder, value, url, req) {
    return '<div class="cc-f ad-img" data-img="' + esc(name) + '" data-folder="' + esc(folder || '') + '"><span>' + esc(label) + (req ? ' *' : '') + '</span><div class="ad-imgrow">' + thumb(url, true) +
      '<div><input type="hidden" name="' + esc(name) + '" value="' + esc(value || '') + '"><label class="cc-btn sm">Choose image<input type="file" accept="image/jpeg,image/png,image/webp,image/gif" hidden></label>' +
      (value ? ' <button type="button" class="cc-btn sm" data-clear>Remove</button>' : '') + '<small class="ad-imgmsg">JPG, PNG, WEBP or GIF, up to 4 MB.</small></div></div></div>';
  }
  function wireImages(root) {
    root.querySelectorAll('.ad-img').forEach(function (box) {
      var inp = box.querySelector('input[type=hidden]'), f = box.querySelector('input[type=file]'), msg = box.querySelector('.ad-imgmsg'), clr = box.querySelector('[data-clear]');
      f.addEventListener('change', function () {
        if (!f.files[0]) return; msg.textContent = 'Uploading…';
        upload(f.files[0], box.getAttribute('data-folder')).then(function (r) {
          if (!r.ok) { msg.textContent = r.message; msg.classList.add('bad'); return; }
          inp.value = r.file; msg.textContent = 'Uploaded. Save to keep it.'; msg.classList.remove('bad');
          var old = box.querySelector('.ad-thumb'); old.outerHTML = thumb(r.url, true);
        });
      });
      if (clr) clr.addEventListener('click', function () { inp.value = ''; var old = box.querySelector('.ad-thumb'); old.outerHTML = thumb('', true); msg.textContent = 'Removed. Save to keep it.'; });
    });
  }

  /* ------------------------------------------------------------------ generic master lists (AdminRecords) */
  var RES = { 'ad-maincat': 'maincat', 'ad-category': 'category', 'ad-items': 'items', 'ad-units': 'units', 'ad-tags': 'tags', 'ad-filters': 'filters', 'ad-buyers': 'buyers',
    'ad-cities': 'cities', 'ad-delivery': 'delivery', 'ad-banners': 'banners', 'ad-appbanners': 'appbanners', 'ad-slider': 'slider', 'ad-offers': 'offers', 'ad-promos': 'promos',
    'ad-coupons': 'coupons', 'ad-certs': 'certs', 'ad-policies': 'policies', 'ad-grievcat': 'grievcat', 'ad-newsletter': 'newsletter', 'ad-contact': 'contact', 'ad-franchise': 'franchise' };

  function fieldHtml(f, v, row) {
    var ro = f.ro ? ' disabled' : '', req = f.req ? ' *' : '', val = v == null ? '' : v;
    if (f.t === 'image') return imageField(f.name, f.label, f.folder, val, row ? row[f.name + '_url'] : '', f.req);
    if (f.t === 'status') return '<label class="ad-check"><input type="checkbox" name="' + f.name + '"' + (row ? (row[f.name] ? ' checked' : '') : ' checked') + '> On (visible / active)</label>';
    if (f.t === 'select') {
      var o = f.opts || {}, keys = Object.keys(o);
      return '<label class="cc-f"><span>' + esc(f.label) + req + '</span><select class="cc-sel" name="' + f.name + '"' + ro + '><option value="">Choose…</option>' + keys.map(function (k) { return '<option value="' + esc(k) + '"' + (String(val) === String(k) ? ' selected' : '') + '>' + esc(o[k]) + '</option>'; }).join('') + '</select></label>';
    }
    if (f.t === 'textarea' || f.t === 'html' || f.t === 'csv') {
      var tv = f.t === 'csv' ? String(val).split(',').join('\n') : val;
      return '<label class="cc-f' + (f.t === 'html' ? ' ad-html' : '') + '"><span>' + esc(f.label) + req + (f.t === 'html' ? ' <small class="muted">(simple HTML allowed: &lt;h3&gt; &lt;p&gt; &lt;ul&gt; &lt;li&gt; &lt;b&gt; &lt;a&gt;)</small>' : '') + '</span><textarea name="' + f.name + '"' + ro + ' maxlength="' + (f.max || 2000) + '" rows="' + (f.t === 'html' ? 14 : 4) + '">' + esc(tv) + '</textarea></label>';
    }
    var type = { number: 'number', money: 'number', date: 'date', email: 'email' }[f.t] || 'text';
    return '<label class="cc-f"><span>' + esc(f.label) + req + '</span><input class="cc-in" type="' + type + '" name="' + f.name + '" value="' + esc(val) + '"' + ro + (f.max && type === 'text' ? ' maxlength="' + f.max + '"' : '') +
      (f.min != null && type === 'number' ? ' min="' + f.min + '"' : '') + (f.max != null && type === 'number' ? ' max="' + f.max + '"' : '') + (type === 'number' ? ' step="' + (f.step || (f.t === 'money' ? '0.01' : 'any')) + '"' : '') + (f.t === 'code' ? ' style="text-transform:uppercase"' : '') + '></label>';
  }
  function readForm(box, fields) {
    var v = {};
    fields.forEach(function (f) {
      if (f.ro) return;
      var e = box.querySelector('[name="' + f.name + '"]'); if (!e) return;
      v[f.name] = f.t === 'status' ? e.checked : e.value;
    });
    return v;
  }
  function recordForm(d, res, row, after) {
    var editable = d.edit;
    var body = el('<div class="ad-form">' + help(row ? '' : d.help) + d.fields.map(function (f) { return fieldHtml(f, row ? row[f.name] : '', row); }).join('') + '</div>');
    if (!editable) body.querySelectorAll('input,select,textarea').forEach(function (e) { e.disabled = true; });
    wireImages(body);
    var btns = [];
    if (editable) btns.push({ t: row ? 'Save' : 'Add', cls: 'pri', fn: function (b) {
      act(b, '/ad/r/' + res + '/save', { id: row ? row.id : '', values: readForm(body, d.fields) }, function () { closeModal(); after(); });
    } });
    if (row && d.delete) btns.unshift({ t: 'Delete', cls: 'danger', fn: function (b) {
      ask({ title: 'Delete this ' + d.title.toLowerCase() + '?', text: 'It is removed from the website and app. A copy is kept in the change log.', tone: 'danger', ok: 'Delete' }).then(function (y) { if (y) act(b, '/ad/r/' + res + '/' + row.id + '/delete', {}, function () { closeModal(); after(); }); });
    } });
    modal((row ? (editable ? 'Edit ' : '') : 'Add ') + d.title.toLowerCase(), body, btns, d.fields.some(function (f) { return f.t === 'html'; }));
  }
  function recordView(key, res) {
    V[key] = { t: '…', dates: false, nofilter: true, run: function (root) {
      return meta().then(function (m) {
        var d = (m.records || {})[res];
        if (!d || d.missing) { root.innerHTML = card('Not available', '<div class="cc-empty">The table ' + esc(d ? d.table : res) + ' is not in this database.</div>'); return; }
        V[key].t = d.plural; $('ccTitle').textContent = d.plural;
        return api('/ad/r/' + res, {}, false).then(function (r) {
          var st = { q: '', status: '' };
          var cols = d.list.map(function (c) {
            var f = d.fields.filter(function (x) { return x.name === c; })[0];
            if (c === 'used') return { k: 'used', t: 'Used', n: 1, f: function (x) { return num(x.used, 0) + (x.used_value ? '<small>' + money(x.used_value) + ' off</small>' : '') + (x.expired ? '<small class="down">expired</small>' : ''); } };
            if (c === 'created_at') return { k: c, t: 'Added', f: function (x) { return dStr(x.created_at); } };
            if (!f) return null;
            if (f.t === 'image') return { k: c, t: f.label, nosort: 1, csv: false, f: function (x) { return thumb(x[c + '_url']); } };
            if (f.t === 'status') return { k: c, t: 'On / off', v: function (x) { return x[c] ? 1 : 0; }, c: function (x) { return x[c] ? 'On' : 'Off'; }, f: function (x) { return d.edit ? sw(x[c], 'data-tg="' + x.id + '"') : (x[c] ? pill('On', 'p-ok') : pill('Off')); } };
            if (f.t === 'select') return { k: c, t: f.label, v: function (x) { return x[c + '_label'] || x[c]; }, f: function (x) { return esc(x[c + '_label'] || x[c] || '–') + (x[c] && !x[c + '_label'] ? ' <small class="down">not in list</small>' : ''); } };
            if (f.t === 'money') return { k: c, t: f.label, n: 1, v: function (x) { return +x[c]; }, f: function (x) { return money2(x[c]); } };
            if (f.t === 'number') return { k: c, t: f.label, n: 1, v: function (x) { return +x[c]; }, f: function (x) { return num(x[c]); } };
            if (f.t === 'date') return { k: c, t: f.label, f: function (x) { return x[c] ? esc(x[c]) : '<span class="muted">never</span>'; } };
            if (f.t === 'textarea' || f.t === 'html' || f.t === 'csv') return { k: c, t: f.label, f: function (x) { return cut(x[c], 90); } };
            return { k: c, t: f.label, f: function (x) { return cut(x[c], 60) || '–'; } };
          }).filter(Boolean);
          if (res === 'items') cols.push({ k: 'products', t: 'Products', n: 1 });
          if (res === 'maincat') cols.push({ k: 'categories', t: 'Categories', n: 1 });
          var head = '<div class="ad-tools"><input class="cc-in" id="adQ" placeholder="Search ' + esc(d.plural.toLowerCase()) + '…" style="flex:1;min-width:180px">' +
            (d.has_status ? '<select class="cc-sel" id="adSt"><option value="">On and off</option><option value="on">On only</option><option value="off">Off only</option></select>' : '') +
            (d.add ? '<button type="button" class="cc-btn pri" id="adAdd">+ Add ' + esc(d.title.toLowerCase()) + '</button>' : '') + '</div>';
          root.innerHTML = '<div class="cc-grid">' + (d.help ? '<div class="cc-card"><div class="cc-body">' + help(d.help) + '</div></div>' : '') + card(d.plural + ' (' + num(r.total, 0) + ')', head + slot('t'), { flush: true }) + '</div>';
          var reload = function () { V[key].run(root); };
          function draw() {
            var q = st.q.toLowerCase();
            var rows = r.rows.filter(function (x) {
              if (st.status === 'on' && !x.status) return false; if (st.status === 'off' && x.status) return false;
              if (!q) return true;
              return d.list.some(function (c) { return String(x[c + '_label'] || x[c] || '').toLowerCase().indexOf(q) >= 0; }) || String(x.id) === q;
            });
            var node = T(cols, rows, { csv: 'freshful-' + res + '.csv', click: function (x) { recordForm(d, res, x, reload); }, per: 50, after: function (w) {
              w.querySelectorAll('[data-tg]').forEach(function (b) { b.addEventListener('click', function (e) { e.stopPropagation(); var id = b.getAttribute('data-tg');
                post('/ad/r/' + res + '/' + id + '/toggle', {}).then(function (x) { toast(x.message, x.ok ? 'ok' : 'bad'); if (x.ok) { r.rows.forEach(function (y) { if (String(y.id) === id) y.status = x.on; }); b.classList.toggle('is-on', x.on); b.setAttribute('aria-checked', x.on); } }); }); });
            } });
            var s = root.querySelector('[data-slot="t"]') || root.querySelector('.ad-tbl'); if (!s) return;
            node.classList.add('ad-tbl'); s.replaceWith(node);
          }
          draw();
          var qi = $('adQ'), si = $('adSt'), add = $('adAdd'), tm;
          if (qi) qi.addEventListener('input', function () { clearTimeout(tm); tm = setTimeout(function () { st.q = qi.value.trim(); draw(); }, 200); });
          if (si) si.addEventListener('change', function () { st.status = si.value; draw(); });
          if (add) add.addEventListener('click', function () { recordForm(d, res, null, function () { META = null; reload(); }); });
        });
      });
    } };
  }
  Object.keys(RES).forEach(function (k) { recordView(k, RES[k]); });

  /* ------------------------------------------------------------------ dashboard */
  V['ad-home'] = { t: 'Admin dashboard', dates: true, auto: 120, run: function (root) {
    return api('/ad/home').then(function (d) {
      var a = d.all, g = d.range;
      var h = '<div class="cc-grid">' +
        '<div class="ad-sec">All time</div><div class="cc-kpis">' +
        kpi('Customers', num(a.customers, 0), '', '', 'customers') + kpi('Revenue (delivered)', money(a.revenue), num(a.delivered, 0) + ' delivered orders', '', 'reports') +
        kpi('Total orders', num(a.orders, 0), '', '', 'orders') + kpi('Products', num(a.products_on, 0) + ' on sale', num(a.products, 0) + ' in total', '', 'ad-products') +
        kpi('Pending to accept', num(a['new'], 0), 'waiting at hubs', a['new'] ? 'warn' : '', 'live') + kpi('Ongoing', num(a.ongoing, 0), 'accepted or out for delivery', '', 'live') +
        kpi('Completed', num(a.delivered, 0), '', '', 'orders') + kpi('Cancelled', num(a.cancelled, 0), a.orders ? pct(a.cancelled * 100 / a.orders) + ' of all orders' : '', '', 'cancels') + '</div>' +
        '<div class="ad-sec">In the chosen dates</div><div class="cc-kpis">' +
        kpi('Sales', money(g.sales), num(g.orders, 0) + ' orders', '', 'reports') + kpi('Average order', money(g.aov), '') + kpi('Cancelled', num(g.cancelled, 0), '', g.cancelled ? 'warn' : '', 'cancels') +
        kpi('New customers', g.new_customers == null ? '–' : num(g.new_customers, 0), 'signed up', '', 'ad-buyers') + '</div>' +
        (d.attention.length ? card('Needs attention', '<ul class="ad-att">' + d.attention.map(function (x) { return '<li class="t-' + x.tone + '"' + (x.go ? ' data-go="' + esc(x.go) + '"' : '') + '><b>' + (x.n == null ? '!' : num(x.n, 0)) + '</b><span>' + esc(x.text) + '</span>' + (x.go ? '<em>Fix ›</em>' : '') + '</li>'; }).join('') + '</ul>', { flush: true, sub: 'Found in your data. Click a line to open the screen that fixes it.' }) : '') +
        card('Orders and sales by day', slot('trend')) +
        '<div class="ad-2">' + card('Orders by hub', hbars(d.by_hub, '', function (v, i) { return num(v, 0) + ' · ' + money(i.sales); })) + card('Customers by hub', hbars(d.users)) + '</div>' +
        card('Sales by top items', hbars(d.items, 'b2', function (v, i) { return money(v) + ' · ' + num(i.orders, 0) + ' orders'; })) +
        card('Latest orders', slot('recent'), { flush: true }) + card('Newest customers', slot('newc'), { flush: true }) + '</div>';
      root.innerHTML = h;
      var days = d.by_day;
      mount(root, 'trend', K.chartBarLine(days.map(function (x) { return x.d; }), days.map(function (x) { return x.orders; }), days.map(function (x) { return x.sales; }), { xl: function (s) { return s.slice(8, 10) + '/' + s.slice(5, 7); }, fl: function (v) { return v >= 1000 ? Math.round(v / 1000) + 'k' : Math.round(v); }, legend: '<span><i style="background:var(--c1-soft)"></i>Orders</span><span><i style="background:var(--brand)"></i>Sales (₹)</span>', tip: function (i) { return dOnly(Date.parse(days[i].d + 'T12:00:00') / 1000) + '<br>' + num(days[i].orders, 0) + ' orders · ' + money(days[i].sales); } }));
      mount(root, 'recent', T([{ k: 'id', t: 'Order' }, { k: 'at', t: 'Placed', n: 1, f: function (x) { return dt(x.at); } }, { k: 'customer', t: 'Customer' }, { k: 'hub', t: 'Hub' }, { k: 'stage', t: 'Status', f: function (x) { return K.stagePill(x.stage); } }, { k: 'amount', t: 'Amount', n: 1, f: function (x) { return money(x.amount); } }], d.recent, { noPage: 1, click: function (x) { K.orderDrawer(x.id); } }));
      mount(root, 'newc', T([{ k: 'name', t: 'Name' }, { k: 'phone', t: 'Mobile' }, { k: 'at', t: 'Joined', n: 1, f: function (x) { return dt(x.at); } }], d.new_customers, { noPage: 1, click: function (x) { K.customerDrawer(x.id); } }));
      K.wireGo(root);
    });
  } };

  /* ------------------------------------------------------------------ products */
  var PM = null;
  function pmeta(force) { if (PM && !force) return Promise.resolve(PM); return api('/ad/products/meta', {}, false).then(function (d) { PM = d; return d; }); }
  function productForm(id, preset, after) {
    Promise.all([pmeta(true), id ? api('/ad/products/' + id, {}, false) : Promise.resolve(null)]).then(function (x) {
      var m = x[0], r = x[1], p = r && r.ok ? r.product : (preset || {});
      if (r && !r.ok) { toast(r.message, 'bad'); return; }
      var mains = m.mains, cats = m.categories;
      var catOpts = function (main) { return cats.filter(function (c) { return !main || c.main === main; }).map(function (c) { return '<option value="' + c.id + '"' + (String(c.id) === String(p.category_id) ? ' selected' : '') + '>' + esc(c.name + (c.active ? '' : ' (off)')) + '</option>'; }).join(''); };
      var tags = String(p.tag || '').split(',').map(function (s) { return s.trim(); });
      var props = String(p.property || '').split(',').map(function (s) { return s.trim(); });
      var body = el('<div class="ad-form ad-pf">' +
        (p.product_id ? '<div class="cc-chips">' + pill(p.product_id, 'p-info') + (p.live != null ? pill('Live stock: ' + num(p.live) + ' packs', p.live > 0 ? 'p-ok' : 'p-mute') : '') + '</div>' : (preset && preset.restore_code ? '<p class="cc-note"><b>Recreating product code ' + esc(preset.restore_code) + '</b>: its hub stock will be linked to this product again. It is saved switched off; check it, then switch it on.</p>' : '<p class="cc-note">The product code is given automatically (next: <b>' + esc(m.next_code) + '</b>).</p>')) +
        '<div class="ad-3"><label class="cc-f"><span>Main category *</span><select class="cc-sel" name="main"><option value="">Choose…</option>' + mains.map(function (n) { return '<option' + (n === p.main ? ' selected' : '') + '>' + esc(n) + '</option>'; }).join('') + '</select></label>' +
        '<label class="cc-f"><span>Category *</span><select class="cc-sel" name="category_id"><option value="">Choose…</option>' + catOpts(p.main) + '</select></label>' +
        '<label class="cc-f"><span>Item *</span><select class="cc-sel" name="item_id"><option value="">Choose the category first</option></select></label></div>' +
        '<div class="ad-3"><label class="cc-f"><span>Unit *</span><select class="cc-sel" name="product_unit"><option value="">Choose…</option>' + m.units.map(function (u) { return '<option' + (u === p.product_unit ? ' selected' : '') + '>' + esc(u) + '</option>'; }).join('') + '</select></label>' +
        '<label class="cc-f"><span>Pack size * <small class="muted">(e.g. 500 with Gram)</small></span><input class="cc-in" type="number" step="any" min="0" name="unit_quantity" value="' + esc(p.unit_quantity || '') + '"></label>' +
        '<label class="cc-f"><span>Gross quantity <small class="muted">(before cleaning)</small></span><input class="cc-in" name="gross_quantity" value="' + esc(p.gross_quantity || '') + '"></label></div>' +
        '<div class="ad-3"><label class="cc-f"><span>MRP (Rs.) *</span><input class="cc-in" type="number" step="0.01" min="0" name="MRP" value="' + esc(p.MRP || '') + '"></label>' +
        '<label class="cc-f"><span>Selling price (Rs.) *</span><input class="cc-in" type="number" step="0.01" min="0" name="main_price" value="' + esc(p.main_price || '') + '"></label>' +
        '<label class="cc-f"><span>Cost price (Rs.) <small class="muted">(your cost)</small></span><input class="cc-in" type="number" step="0.01" min="0" name="cost_price" value="' + esc(p.cost_price || '') + '"></label></div>' +
        '<div class="ad-calc" id="pfCalc"></div>' +
        '<div class="ad-4"><label class="cc-f"><span>No. of pieces</span><input class="cc-in" name="pieces" value="' + esc(p.pieces || '') + '"></label><label class="cc-f"><span>Serves</span><input class="cc-in" name="serves" value="' + esc(p.serves || '') + '"></label>' +
        '<label class="cc-f"><span>Cooking time (min)</span><input class="cc-in" name="cooking_time" value="' + esc(p.cooking_time || '') + '"></label><label class="cc-f"><span>Rank (order shown)</span><input class="cc-in" type="number" min="0" step="1" name="ranking" value="' + esc(p.ranking || '') + '"></label></div>' +
        '<div class="cc-f"><span>Tags</span><div class="ad-checks">' + (m.tags.length ? m.tags.map(function (t) { return '<label class="ad-check"><input type="checkbox" name="tag" value="' + esc(t) + '"' + (tags.indexOf(t) >= 0 ? ' checked' : '') + '> ' + esc(t) + '</label>'; }).join('') : '<span class="muted">No tags yet (Catalogue › Product tags).</span>') + '</div></div>' +
        '<div class="cc-f"><span>Product filters</span><div class="ad-checks" id="pfProps"></div></div>' +
        '<label class="cc-f"><span>Description</span><textarea name="description" rows="4" maxlength="5000">' + esc(p.description || '') + '</textarea></label>' +
        imageField('product_image', 'Product photo (leave empty to use the item photo)', 'products', p.product_image === 'false' ? '' : (p.product_image || ''), p.image_url || '', false) +
        '<div class="ad-checks"><label class="ad-check"><input type="checkbox" name="active"' + (id ? (p.active ? ' checked' : '') : '') + '> On sale</label><label class="ad-check"><input type="checkbox" name="confirm_pack"> This pack size is correct</label><label class="ad-check"><input type="checkbox" name="confirm_cost"> Sell below cost</label></div>' +
        (r && r.hubs && r.hubs.length ? '<details class="ad-det"><summary>Stock in hubs (' + r.hubs.length + ')</summary>' + r.hubs.map(function (h) { return '<div>' + esc(h.hub) + ': <b>' + num(h.live) + '</b> live' + (h.incoming ? ', ' + num(h.incoming) + ' incoming' : '') + '</div>'; }).join('') + '</details>' : '') +
        (p.stock !== undefined && p.stock !== '' && id ? '<input type="hidden" name="stock" value="' + esc(p.stock) + '">' : '') + '</div>');
      var q = function (n) { return body.querySelector('[name="' + n + '"]'); };
      function loadItems(keep) {
        var c = q('category_id').value; var it = q('item_id');
        if (!c) { it.innerHTML = '<option value="">Choose the category first</option>'; return; }
        it.innerHTML = '<option value="">Loading…</option>';
        api('/ad/products/items/' + c, {}, false).then(function (d) {
          it.innerHTML = '<option value="">Choose…</option>' + d.items.map(function (i) { return '<option value="' + i.id + '"' + (String(i.id) === String(keep) ? ' selected' : '') + '>' + esc(i.name + (i.active ? '' : ' (off)')) + '</option>'; }).join('');
          if (!d.items.length) it.innerHTML = '<option value="">No items in this category: add one under Catalogue › Items</option>';
        });
        var fs = m.filters.filter(function (f) { return String(f.cat) === String(c); });
        body.querySelector('#pfProps').innerHTML = fs.length ? fs.map(function (f) { return '<div class="ad-prop"><b>' + esc(f.name) + '</b>' + f.values.map(function (v) { return '<label class="ad-check"><input type="checkbox" name="property" value="' + esc(v) + '"' + (props.indexOf(v) >= 0 ? ' checked' : '') + '> ' + esc(v) + '</label>'; }).join('') + '</div>'; }).join('') : '<span class="muted">No filters for this category.</span>';
      }
      function calc() {
        var mrp = +q('MRP').value || 0, pr = +q('main_price').value || 0, co = +q('cost_price').value || 0, uq = +q('unit_quantity').value || 0, u = (q('product_unit').value || '').toLowerCase();
        var t = [];
        if (mrp && pr) t.push(pr > mrp ? '<span class="down">Price is above MRP</span>' : (Math.round((mrp - pr) * 100 / mrp) + '% off MRP'));
        if (pr && co) t.push(co > pr ? '<span class="down">Below cost: you lose ' + money2(co - pr) + ' per pack</span>' : 'Margin ' + money2(pr - co) + ' (' + (Math.round((pr - co) * 1000 / pr) / 10) + '%)');
        var g = /^(g|gm|gms|gram|grams)$/.test(u) ? uq : (/^(kg|kgs|kilo|kilogram)/.test(u) ? uq * 1000 : 0);
        if (g && pr) t.push(money2(pr * 1000 / g) + ' per kg');
        if (g && (g < 50 || g > 25000)) t.push('<span class="down">' + uq + ' ' + esc(q('product_unit').value) + ' looks wrong for one pack</span>');
        body.querySelector('#pfCalc').innerHTML = t.join(' · ');
      }
      q('main').addEventListener('change', function () { q('category_id').innerHTML = '<option value="">Choose…</option>' + catOpts(this.value); loadItems(''); });
      q('category_id').addEventListener('change', function () { loadItems(''); });
      ['MRP', 'main_price', 'cost_price', 'unit_quantity', 'product_unit'].forEach(function (n) { q(n).addEventListener('input', calc); q(n).addEventListener('change', calc); });
      wireImages(body); loadItems(p.item_id); calc();
      modal(id ? 'Edit product' : (preset && preset.restore_code ? 'Recreate product ' + preset.restore_code : 'Add product'), body, [{ t: id ? 'Save product' : 'Add product', cls: 'pri', fn: function (b) {
        var v = {}; ['category_id', 'item_id', 'product_unit', 'unit_quantity', 'gross_quantity', 'MRP', 'main_price', 'cost_price', 'pieces', 'serves', 'cooking_time', 'ranking', 'description', 'product_image', 'stock'].forEach(function (n) { var e = q(n); if (e) v[n] = e.value; });
        v.tag = [].map.call(body.querySelectorAll('[name=tag]:checked'), function (e) { return e.value; });
        v.property = [].map.call(body.querySelectorAll('[name=property]:checked'), function (e) { return e.value; });
        v.active = q('active').checked; v.confirm_pack = q('confirm_pack').checked ? 1 : 0; v.confirm_cost = q('confirm_cost').checked ? 1 : 0;
        if (preset && preset.restore_code) v.restore_code = preset.restore_code;
        act(b, '/ad/products/save', { id: id || '', values: v }, function () { closeModal(); if (after) after(); });
      } }], true);
    });
  }
  V['ad-products'] = { t: 'Products', dates: false, nofilter: true, run: function (root) {
    var hp = hashParams();
    return Promise.all([api('/ad/products', {}, false), pmeta()]).then(function (x) {
      var r = x[0], m = x[1], sel = {};
      var st = { q: '', main: '', cat: '', status: '', issue: hp.issue || '' };
      var issues = {}; r.rows.forEach(function (p) { p.issues.forEach(function (i) { issues[i] = (issues[i] || 0) + 1; }); });
      var on = r.rows.filter(function (p) { return p.active; }).length;
      root.innerHTML = '<div class="cc-grid"><div class="cc-kpis">' + kpi('Products', num(r.rows.length, 0), num(on, 0) + ' on sale') + kpi('Off', num(r.rows.length - on, 0), 'hidden from customers') +
        kpi('Out of stock (on sale)', num(r.rows.filter(function (p) { return p.active && p.live <= 0; }).length, 0), 'no live stock in any hub', '', '') + kpi('Need fixing', num(r.rows.filter(function (p) { return p.issues.length; }).length, 0), 'see the issue filter', r.rows.some(function (p) { return p.issues.length; }) ? 'warn' : '') + '</div>' +
        card('All products', '<div class="ad-tools"><input class="cc-in" id="pQ" placeholder="Search name or code…" style="flex:1;min-width:180px">' +
          '<select class="cc-sel" id="pMain"><option value="">All main categories</option>' + m.mains.map(function (n) { return '<option>' + esc(n) + '</option>'; }).join('') + '</select>' +
          '<select class="cc-sel" id="pCat"><option value="">All categories</option></select>' +
          '<select class="cc-sel" id="pSt"><option value="">On and off</option><option value="on">On sale</option><option value="off">Off</option><option value="nostock">On sale, no stock</option></select>' +
          '<select class="cc-sel" id="pIs"><option value="">Any issue or none</option><option value="any">Has an issue</option>' + Object.keys(issues).map(function (i) { return '<option value="' + esc(i) + '"' + (st.issue === i ? ' selected' : '') + '>' + esc(i) + ' (' + issues[i] + ')</option>'; }).join('') + '</select>' +
          '<button type="button" class="cc-btn pri" id="pAdd">+ Add product</button><button type="button" class="cc-btn" id="pExp">Export Excel</button><a class="cc-btn" href="#ad-import">Import / bulk price update</a></div>' +
          '<div class="ad-bulk" id="pBulk" hidden><b id="pBulkN"></b> selected <button type="button" class="cc-btn sm" data-bulk="1">Switch on</button><button type="button" class="cc-btn sm" data-bulk="0">Switch off</button><button type="button" class="cc-btn sm" data-bulk="x">Clear selection</button></div>' + slot('t'), { flush: true }) + '</div>';
      var fillCats = function () { $('pCat').innerHTML = '<option value="">All categories</option>' + m.categories.filter(function (c) { return !st.main || c.main === st.main; }).map(function (c) { return '<option value="' + esc(c.name) + '">' + esc(c.name) + '</option>'; }).join(''); };
      fillCats();
      var reload = function () { V['ad-products'].run(root); };
      function bulkBar() { var n = Object.keys(sel).length; $('pBulk').hidden = !n; $('pBulkN').textContent = n; }
      function draw() {
        var q = st.q.toLowerCase();
        var rows = r.rows.filter(function (p) {
          if (st.main && p.main !== st.main) return false; if (st.cat && p.category !== st.cat) return false;
          if (st.status === 'on' && !p.active) return false; if (st.status === 'off' && p.active) return false; if (st.status === 'nostock' && (!p.active || p.live > 0)) return false;
          if (st.issue === 'any' && !p.issues.length) return false; if (st.issue && st.issue !== 'any' && p.issues.indexOf(st.issue) < 0) return false;
          return !q || (p.name + ' ' + p.code + ' ' + p.category + ' ' + p.pack).toLowerCase().indexOf(q) >= 0;
        });
        var node = T([
          { k: 'sel', t: '', nosort: 1, csv: false, f: function (p) { return '<input type="checkbox" data-sel="' + p.id + '"' + (sel[p.id] ? ' checked' : '') + ' aria-label="Select">'; } },
          { k: 'image', t: '', nosort: 1, csv: false, f: function (p) { return thumb(p.image); } },
          { k: 'rank', t: 'Rank', n: 1 }, { k: 'code', t: 'Code' },
          { k: 'name', t: 'Product', v: function (p) { return p.name; }, f: function (p) { return '<b>' + esc(p.name) + '</b><small>' + esc(p.pack) + (p.tags ? ' · ' + esc(p.tags) : '') + '</small>'; } },
          { k: 'category', t: 'Category', f: function (p) { return esc(p.category || '–') + '<small>' + esc(p.main) + '</small>'; } },
          { k: 'mrp', t: 'MRP', n: 1, f: function (p) { return money2(p.mrp); } },
          { k: 'price', t: 'Price', n: 1, f: function (p) { return '<b>' + money2(p.price) + '</b>' + (p.off ? '<small>' + p.off + '% off</small>' : ''); } },
          { k: 'margin', t: 'Margin', n: 1, v: function (p) { return p.margin == null ? -999 : p.margin; }, f: function (p) { return p.margin == null ? '<span class="muted">no cost</span>' : '<span class="' + (p.margin < 0 ? 'down' : '') + '">' + p.margin + '%</span><small>cost ' + money2(p.cost) + '</small>'; } },
          { k: 'live', t: 'Live stock', n: 1, f: function (p) { return p.live > 0 ? '<b>' + esc(S.units === 'p' ? num(p.live) + ' pk' : p.live_text) + '</b><small>' + num(p.hubs, 0) + ' hub(s)</small>' : '<span class="down">0</span>'; } },
          { k: 'active', t: 'On sale', v: function (p) { return p.active ? 1 : 0; }, c: function (p) { return p.active ? 'On' : 'Off'; }, f: function (p) { return sw(p.active, 'data-tg="' + p.id + '"'); } },
          { k: 'issues', t: 'Check', v: function (p) { return p.issues.length; }, c: function (p) { return p.issues.join('; '); }, f: function (p) { return p.issues.map(function (i) { return pill(i, /price|MRP|missing/i.test(i) ? 'p-bad' : 'p-warn'); }).join(' ') || '<span class="muted">ok</span>'; } }
        ], rows, { csv: 'freshful-products.csv', per: 50, click: function (p) { productForm(p.id, null, reload); }, after: function (w) {
          w.querySelectorAll('[data-sel]').forEach(function (c) { c.addEventListener('change', function () { var id = c.getAttribute('data-sel'); if (c.checked) sel[id] = 1; else delete sel[id]; bulkBar(); }); c.addEventListener('click', function (e) { e.stopPropagation(); }); });
          w.querySelectorAll('[data-tg]').forEach(function (b) { b.addEventListener('click', function (e) { e.stopPropagation(); var id = b.getAttribute('data-tg');
            post('/ad/products/' + id + '/toggle', {}).then(function (x) { toast(x.message, x.ok ? 'ok' : 'bad'); if (x.ok) { r.rows.forEach(function (p) { if (String(p.id) === id) p.active = x.on; }); b.classList.toggle('is-on', x.on); } }); }); });
        } });
        node.classList.add('ad-tbl');
        var s = root.querySelector('[data-slot="t"]') || root.querySelector('.ad-tbl'); s.replaceWith(node);
      }
      draw();
      var tm;
      $('pQ').addEventListener('input', function () { clearTimeout(tm); var v = this.value; tm = setTimeout(function () { st.q = v.trim(); draw(); }, 200); });
      $('pMain').addEventListener('change', function () { st.main = this.value; st.cat = ''; fillCats(); draw(); });
      $('pCat').addEventListener('change', function () { st.cat = this.value; draw(); });
      $('pSt').addEventListener('change', function () { st.status = this.value; draw(); });
      $('pIs').addEventListener('change', function () { st.issue = this.value; draw(); });
      $('pAdd').addEventListener('click', function () { productForm(null, null, reload); });
      $('pExp').addEventListener('click', function () { download('/ad/products/export'); });
      root.querySelectorAll('[data-bulk]').forEach(function (b) { b.addEventListener('click', function () {
        var v = b.getAttribute('data-bulk'); if (v === 'x') { sel = {}; bulkBar(); draw(); return; }
        var ids = Object.keys(sel);
        ask({ title: (v === '1' ? 'Switch on ' : 'Switch off ') + ids.length + ' product(s)?', text: v === '1' ? 'They become visible to customers.' : 'Customers will no longer see them.', ok: v === '1' ? 'Switch on' : 'Switch off', tone: v === '1' ? 'info' : 'warn' })
          .then(function (y) { if (y) act(b, '/ad/products/bulk-status', { ids: ids, on: v === '1' }, function () { sel = {}; reload(); }); });
      }); });
    });
  } };

  V['ad-missing'] = { t: 'Stock without a product', dates: false, nofilter: true, run: function (root) {
    return api('/ad/products/missing', {}, false).then(function (d) {
      var t = d.total || { codes: 0, live: 0, incoming: 0 };
      root.innerHTML = '<div class="cc-grid"><div class="cc-card"><div class="cc-body"><p class="cc-note ad-help"><b>What this is:</b> these product codes have stock in your hubs, but the product itself was deleted in the old admin. Nobody can sell this stock and it is not in the reports. ' +
        'For each code you can <b>recreate the product with the same code</b>: the stock is linked again at once. It is saved switched off, so you can check the price and photo first. If the stock is not real any more, recreate the product and then write the stock off under Wastage.</p></div></div>' +
        '<div class="cc-kpis">' + kpi('Codes', num(t.codes, 0), 'with no product', t.codes ? 'bad' : '') + kpi('Live packs', num(t.live), 'sitting in hubs') + kpi('Incoming packs', num(t.incoming), 'waiting to be accepted') + '</div>' + card('Codes', slot('t'), { flush: true }) + '</div>';
      mount(root, 't', T([{ k: 'code', t: 'Code' }, { k: 'live', t: 'Live packs', n: 1, f: function (x) { return '<b>' + num(x.live) + '</b>'; } }, { k: 'incoming', t: 'Incoming', n: 1 }, { k: 'hubs', t: 'Hubs with stock', n: 1 },
        { k: 'updated', t: 'Last change', n: 1, f: function (x) { return x.updated ? dt(x.updated) : '–'; } }, { k: 'go', t: '', nosort: 1, csv: false, f: function (x) { return '<button type="button" class="cc-btn sm pri" data-re="' + esc(x.code) + '">Recreate product</button>'; } }],
        d.rows, { csv: 'freshful-stock-without-product.csv', sort: 1, after: function (w) {
          w.querySelectorAll('[data-re]').forEach(function (b) { b.addEventListener('click', function (e) { e.stopPropagation(); productForm(null, { restore_code: b.getAttribute('data-re') }, function () { V['ad-missing'].run(root); }); }); });
        }, click: function (x) {
          openDrawer('Code ' + x.code, '<dl class="cc-dl"><dt>Live packs</dt><dd>' + num(x.live) + '</dd><dt>Incoming</dt><dd>' + num(x.incoming) + '</dd><dt>Inventory rows</dt><dd>' + num(x.rows, 0) + '</dd></dl>' +
            '<div class="cc-card"><div class="cc-body flush">' + (x.by_hub.length ? '<div class="cc-tw"><table class="cc-t"><thead><tr><th>Hub</th><th class="n">Live</th><th class="n">Incoming</th></tr></thead><tbody>' + x.by_hub.map(function (h) { return '<tr><td>' + esc(h.hub) + '</td><td class="n">' + num(h.live) + '</td><td class="n">' + num(h.incoming) + '</td></tr>'; }).join('') + '</tbody></table></div>' : '<div class="cc-empty">No stock in any hub.</div>') + '</div></div>' +
            '<button type="button" class="cc-btn pri" id="mRe">Recreate product ' + esc(x.code) + '</button>');
          $('mRe').addEventListener('click', function () { closeDrawer(); productForm(null, { restore_code: x.code }, function () { V['ad-missing'].run(root); }); });
        } }));
    });
  } };

  V['ad-import'] = { t: 'Excel import / bulk update', dates: false, nofilter: true, run: function (root) {
    root.innerHTML = '<div class="cc-grid ad-2">' +
      card('1. Bulk price update', '<ol class="ad-steps"><li><button type="button" class="cc-btn sm" id="iExp">Download the product list</button> (Excel).</li><li>Change <b>MRP</b>, <b>Selling price</b> or <b>Cost price</b> in that file. Do not change the Product ID column.</li><li>Upload it here. You will see every change before anything is saved.</li></ol>' +
        '<div class="ad-tools"><input type="file" id="iUpd" accept=".xlsx,.csv" class="cc-in"><button type="button" class="cc-btn pri" id="iUpdGo">Check file</button></div><div id="iUpdOut"></div>') +
      card('2. Add new products', '<ol class="ad-steps"><li><button type="button" class="cc-btn sm" id="iTpl">Download the template</button>.</li><li>One row per pack size. Category must already exist; a new item name is created in that category.</li><li>Upload it. New products are added <b>switched off</b>: add photos, check them, then switch them on.</li></ol>' +
        '<div class="ad-tools"><input type="file" id="iImp" accept=".xlsx,.csv" class="cc-in"><button type="button" class="cc-btn pri" id="iImpGo">Check file</button></div><div id="iImpOut"></div>') + '</div>';
    $('iExp').addEventListener('click', function () { download('/ad/products/export'); });
    $('iTpl').addEventListener('click', function () { download('/ad/products/template'); });
    function run(mode, input, out, btn) {
      var f = $(input).files[0]; if (!f) { toast('Choose the file first.', 'bad'); return; }
      var b = $(btn); b.disabled = true; b.textContent = 'Checking…';
      postFile('/ad/products/excel', f, { mode: mode, apply: 0 }).then(function (r) {
        b.disabled = false; b.textContent = 'Check file';
        var o = $(out);
        if (!r.ok) { o.innerHTML = '<p class="ad-err">' + esc(r.message) + '</p>'; return; }
        var errs = (r.errors || []).length ? '<details class="ad-det" open><summary>' + r.errors.length + ' row(s) will be skipped</summary>' + r.errors.map(function (e) { return '<div class="down">' + esc(e) + '</div>'; }).join('') + '</details>' : '';
        var list = mode === 'import' ? r.rows : r.changes, h = '<p class="cc-note"><b>' + esc(r.message) + '</b></p>' + errs;
        o.innerHTML = h + '<div class="ad-prev"></div>' + (list.length ? '<button type="button" class="cc-btn pri" data-apply>' + (mode === 'import' ? 'Add these ' + list.length + ' products' : 'Save these ' + list.length + ' changes') + '</button>' : '');
        var tbl = mode === 'import' ? T([{ k: 'line', t: 'Row', n: 1 }, { k: 'category', t: 'Category' }, { k: 'item', t: 'Item', f: function (x) { return esc(x.item) + (x.new_item ? ' ' + pill('new item', 'p-info') : ''); } }, { k: 'pack', t: 'Pack', f: function (x) { return esc(x.pack) + (x.suspect ? ' ' + pill('check', 'p-warn') : ''); } }, { k: 'mrp', t: 'MRP', n: 1 }, { k: 'price', t: 'Price', n: 1 }], list, { per: 20 })
          : T([{ k: 'code', t: 'Code' }, { k: 'name', t: 'Product' }, { k: 'mrp', t: 'MRP', f: function (x) { return x.to.MRP != null ? money2(x.from.MRP) + ' → <b>' + money2(x.to.MRP) + '</b>' : '<span class="muted">same</span>'; } },
            { k: 'price', t: 'Selling price', f: function (x) { return x.to.main_price != null ? money2(x.from.main_price) + ' → <b>' + money2(x.to.main_price) + '</b>' : '<span class="muted">same</span>'; } },
            { k: 'cost', t: 'Cost', f: function (x) { return x.to.cost_price != null ? money2(x.from.cost_price) + ' → <b>' + money2(x.to.cost_price) + '</b>' : '<span class="muted">same</span>'; } }], list, { per: 20 });
        o.querySelector('.ad-prev').appendChild(tbl);
        var ap = o.querySelector('[data-apply]');
        if (ap) ap.addEventListener('click', function () {
          ask({ title: mode === 'import' ? 'Add ' + list.length + ' products?' : 'Save ' + list.length + ' price changes?', text: mode === 'import' ? 'They are added switched off.' : 'Customers see the new prices at once.', ok: 'Yes, save' }).then(function (y) {
            if (!y) return; ap.disabled = true; ap.textContent = 'Saving…';
            postFile('/ad/products/excel', f, { mode: mode, apply: 1 }).then(function (x) { toast(x.message, x.ok ? 'ok' : 'bad'); if (x.ok) o.innerHTML = '<p class="ad-ok">' + esc(x.message) + '</p>' + (x.added ? '<p class="cc-note">New codes: ' + esc(x.added.join(', ')) + '</p>' : ''); else { ap.disabled = false; ap.textContent = 'Try again'; } });
          });
        });
      });
    }
    $('iUpdGo').addEventListener('click', function () { run('update', 'iUpd', 'iUpdOut', 'iUpdGo'); });
    $('iImpGo').addEventListener('click', function () { run('import', 'iImp', 'iImpOut', 'iImpGo'); });
    return Promise.resolve();
  } };

  /* ------------------------------------------------------------------ staff and roles */
  V['ad-staff'] = { t: 'Staff users', dates: false, nofilter: true, run: function (root) {
    return api('/ad/staff', {}, false).then(function (m) {
      var cur = (S.p.staffType && m.types.some(function (t) { return t.key === S.p.staffType; })) ? S.p.staffType : (m.types[0] || {}).key;
      root.innerHTML = '<div class="cc-grid"><div class="cc-tabs" id="sTabs">' + m.types.map(function (t) { return '<button type="button" data-k="' + t.key + '"' + (t.key === cur ? ' class="is-on"' : '') + '>' + esc(t.label) + ' <small>' + t.count + '</small></button>'; }).join('') + '</div><div id="sBody"></div></div>';
      function show(k) {
        S.p.staffType = k; cur = k;
        root.querySelectorAll('#sTabs button').forEach(function (b) { b.classList.toggle('is-on', b.getAttribute('data-k') === k); });
        var t = m.types.filter(function (x) { return x.key === k; })[0];
        $('sBody').innerHTML = '<div class="cc-load"><i></i>Loading…</div>';
        api('/ad/staff/' + k, {}, false).then(function (r) {
          $('sBody').innerHTML = card(t.label, '<div class="ad-tools"><input class="cc-in" id="sQ" placeholder="Search name, email or mobile…" style="flex:1;min-width:180px"><button type="button" class="cc-btn pri" id="sAdd">+ Add ' + esc(t.one.toLowerCase()) + '</button></div>' +
            '<p class="cc-note ad-pad">Login page: <a href="' + esc(t.login) + '" target="_blank" rel="noopener">' + esc(t.login) + '</a> · Passwords here are saved ' + (r.passwords === 'hash' ? '<b>secure (encrypted)</b>' : 'in the <b>same plain form the old login reads</b> (they will be secured when this login moves to the new one)') + '.</p>' + slot('t'), { flush: true });
          var cols = [{ k: 'name', t: 'Name', f: function (u) { return '<b>' + esc(u.name) + '</b>'; } }, { k: 'email', t: 'Email (login)' }, { k: 'phone', t: 'Mobile' }];
          if (t.hub) cols.push({ k: 'hubs', t: t.hub === 'many' ? 'Hubs' : 'Hub', f: function (u) { return esc(u.hubs || '–') + (u.city ? '<small>' + esc(u.city) + '</small>' : ''); } });
          cols.push({ k: 'secure', t: 'Password', v: function (u) { return u.secure ? 1 : 0; }, c: function (u) { return !u.has_password ? 'none' : u.secure ? 'secure' : 'plain'; }, f: function (u) { return !u.has_password ? pill('not set', 'p-bad') : u.secure ? pill('secure', 'p-ok') : pill('plain text', 'p-warn'); } });
          cols.push({ k: 'active', t: 'Can log in', v: function (u) { return u.active ? 1 : 0; }, c: function (u) { return u.active ? 'Yes' : 'No'; }, f: function (u) { return sw(u.active, 'data-tg="' + u.id + '"'); } });
          var q = '';
          function draw() {
            var rows = r.rows.filter(function (u) { return !q || (u.name + ' ' + u.email + ' ' + u.phone + ' ' + u.hubs).toLowerCase().indexOf(q) >= 0; });
            var n = T(cols, rows, { csv: 'freshful-' + k + '-users.csv', click: function (u) { staffForm(t, u); }, after: function (w) {
              w.querySelectorAll('[data-tg]').forEach(function (b) { b.addEventListener('click', function (e) { e.stopPropagation(); var id = b.getAttribute('data-tg'), u = r.rows.filter(function (x) { return String(x.id) === id; })[0];
                ask({ title: (u.active ? 'Block login for ' : 'Allow login for ') + u.name + '?', text: u.active ? 'They will not be able to log in until you switch them on again.' : '', ok: u.active ? 'Block' : 'Allow', tone: u.active ? 'warn' : 'info' }).then(function (y) {
                  if (y) post('/ad/staff/' + k + '/' + id + '/toggle', {}).then(function (x) { toast(x.message, x.ok ? 'ok' : 'bad'); if (x.ok) { u.active = x.on; b.classList.toggle('is-on', x.on); } }); }); }); });
            } });
            n.classList.add('ad-tbl'); var s = $('sBody').querySelector('[data-slot="t"]') || $('sBody').querySelector('.ad-tbl'); s.replaceWith(n);
          }
          draw();
          $('sQ').addEventListener('input', function () { q = this.value.trim().toLowerCase(); draw(); });
          $('sAdd').addEventListener('click', function () { staffForm(t, null); });
        });
      }
      function staffForm(t, u) {
        var hubs = K.M.hubs;
        var body = el('<div class="ad-form"><div class="ad-2"><label class="cc-f"><span>Name *</span><input class="cc-in" name="name" maxlength="100" value="' + esc(u ? u.name : '') + '"></label>' +
          '<label class="cc-f"><span>Email (login) *</span><input class="cc-in" type="email" name="email" maxlength="150" value="' + esc(u ? u.email : '') + '"></label></div>' +
          '<div class="ad-2"><label class="cc-f"><span>Mobile</span><input class="cc-in" name="phone" maxlength="13" value="' + esc(u ? u.phone : '') + '"></label>' +
          '<label class="cc-f"><span>' + (u ? 'New password <small class="muted">(leave empty to keep)</small>' : 'Password *') + '</span><input class="cc-in" type="text" name="password" autocomplete="new-password" minlength="8" maxlength="72" placeholder="at least 8 characters"></label></div>' +
          (t.hub === 'one' ? '<label class="cc-f"><span>Hub *</span><select class="cc-sel" name="hub_id"><option value="">Choose…</option>' + K.hubOptions(u && u.hub_ids[0]) + '</select></label>' : '') +
          (t.hub === 'many' ? '<div class="cc-f"><span>Hubs this manager looks after *</span><div class="ad-checks ad-hubpick">' + hubs.map(function (h) { return '<label class="ad-check"><input type="checkbox" name="hub_ids" value="' + h.id + '"' + (u && u.hub_ids.indexOf(h.id) >= 0 ? ' checked' : '') + '> ' + esc(h.name) + (h.city ? ' <small class="muted">' + esc(h.city) + '</small>' : '') + '</label>'; }).join('') + '</div></div>' : '') +
          '<label class="cc-f"><span>Address</span><input class="cc-in" name="address" maxlength="255" value="' + esc(u ? u.address : '') + '"></label>' +
          '<label class="ad-check"><input type="checkbox" name="active"' + (!u || u.active ? ' checked' : '') + '> Can log in</label>' +
          '<button type="button" class="cc-btn sm" id="sGen">Make a strong password</button></div>');
        body.querySelector('#sGen').addEventListener('click', function () { var c = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789', s = ''; var a = new Uint32Array(12); (window.crypto || window.msCrypto).getRandomValues(a); for (var i = 0; i < 12; i++) s += c[a[i] % c.length]; body.querySelector('[name=password]').value = s; toast('Password filled in. Copy it for the user before saving.', 'ok'); });
        modal((u ? 'Edit ' : 'Add ') + t.one.toLowerCase(), body, [{ t: u ? 'Save' : 'Add user', cls: 'pri', fn: function (b) {
          var g = function (n) { var e = body.querySelector('[name="' + n + '"]'); return e ? e.value : ''; };
          var v = { name: g('name'), email: g('email'), phone: g('phone'), password: g('password'), address: g('address'), active: body.querySelector('[name=active]').checked };
          if (t.hub === 'one') v.hub_id = g('hub_id');
          if (t.hub === 'many') v.hub_ids = [].map.call(body.querySelectorAll('[name=hub_ids]:checked'), function (e) { return e.value; });
          act(b, '/ad/staff/' + t.key + '/save', { id: u ? u.id : '', values: v }, function () { closeModal(); show(t.key); });
        } }], t.hub === 'many');
      }
      root.querySelectorAll('#sTabs button').forEach(function (b) { b.addEventListener('click', function () { show(b.getAttribute('data-k')); }); });
      if (cur) show(cur); else $('sBody').innerHTML = card('Staff', '<div class="cc-empty">No staff tables found.</div>');
    });
  } };

  V['ad-roles'] = { t: 'Roles & permissions', dates: false, nofilter: true, run: function (root) {
    return api('/ad/roles', {}, false).then(function (d) {
      if (!d.ok) { root.innerHTML = card('Roles', '<div class="cc-empty">' + esc(d.message) + '</div>'); return; }
      var total = 0; d.groups.forEach(function (g) { total += g.perms.length; });
      root.innerHTML = '<div class="cc-grid"><div class="cc-card"><div class="cc-body"><p class="cc-note ad-help">Each role type decides which menus the managers of that panel see, and their dashboard. Same permissions as the old admin; they are stored the same way, so the panels read them unchanged.</p></div></div>' +
        card('Roles', '<div class="ad-tools"><span style="flex:1"></span><button type="button" class="cc-btn pri" id="rAdd">+ Add role</button></div>' + slot('t'), { flush: true }) + '</div>';
      mount(root, 't', T([{ k: 'type', t: 'Role', f: function (x) { return '<b>' + esc(x.type) + '</b>'; } }, { k: 'dashboard', t: 'Dashboard', f: function (x) { return esc(d.dashboards[x.dashboard] || x.dashboard || '–'); } },
        { k: 'n', t: 'Permissions', n: 1, v: function (x) { return x.permissions.length; }, f: function (x) { return num(x.permissions.length, 0) + ' of ' + total; } }, { k: 'updated', t: 'Last changed', f: function (x) { return dStr(x.updated); } }],
        d.rows, { noPage: 1, click: function (x) { roleForm(x); } }));
      $('rAdd').addEventListener('click', function () { roleForm(null); });
      function roleForm(x) {
        var used = d.rows.map(function (r) { return r.type; });
        var types = d.types.filter(function (t) { return !x ? used.indexOf(t) < 0 : true; });
        if (!x && !types.length) { toast('Every role type already exists. Edit one instead.', 'bad'); return; }
        var have = x ? x.permissions : [];
        var body = el('<div class="ad-form"><div class="ad-2"><label class="cc-f"><span>Role *</span><select class="cc-sel" name="type">' + types.map(function (t) { return '<option' + (x && x.type === t ? ' selected' : '') + '>' + esc(t) + '</option>'; }).join('') + '</select></label>' +
          '<label class="cc-f"><span>Dashboard</span><select class="cc-sel" name="dashboard">' + Object.keys(d.dashboards).map(function (k) { return '<option value="' + k + '"' + (x && x.dashboard === k ? ' selected' : '') + '>' + esc(d.dashboards[k]) + '</option>'; }).join('') + '</select></label></div>' +
          '<div class="ad-tools"><button type="button" class="cc-btn sm" data-all="1">Tick all</button><button type="button" class="cc-btn sm" data-all="0">Untick all</button></div>' +
          '<div class="ad-perms">' + d.groups.map(function (g) { return '<fieldset><legend><label class="ad-check"><input type="checkbox" data-grp="' + g.key + '"> ' + esc(g.label) + '</label></legend>' + g.perms.map(function (p) { return '<label class="ad-check"><input type="checkbox" name="perm" data-g="' + g.key + '" value="' + p.key + '"' + (have.indexOf(p.key) >= 0 ? ' checked' : '') + '> ' + esc(p.label) + '</label>'; }).join('') + '</fieldset>'; }).join('') + '</div></div>');
        var sync = function () { body.querySelectorAll('[data-grp]').forEach(function (c) { var all = body.querySelectorAll('[data-g="' + c.getAttribute('data-grp') + '"]'), on = [].filter.call(all, function (e) { return e.checked; }).length; c.checked = on === all.length && on > 0; c.indeterminate = on > 0 && on < all.length; }); };
        body.querySelectorAll('[data-grp]').forEach(function (c) { c.addEventListener('change', function () { body.querySelectorAll('[data-g="' + c.getAttribute('data-grp') + '"]').forEach(function (e) { e.checked = c.checked; }); sync(); }); });
        body.querySelectorAll('[name=perm]').forEach(function (c) { c.addEventListener('change', sync); });
        body.querySelectorAll('[data-all]').forEach(function (b) { b.addEventListener('click', function () { body.querySelectorAll('[name=perm]').forEach(function (e) { e.checked = b.getAttribute('data-all') === '1'; }); sync(); }); });
        sync();
        modal(x ? 'Permissions: ' + x.type : 'Add role', body, [{ t: 'Save permissions', cls: 'pri', fn: function (b) {
          act(b, '/ad/roles/save', { id: x ? x.id : '', values: { type: body.querySelector('[name=type]').value, dashboard: body.querySelector('[name=dashboard]').value, permissions: [].map.call(body.querySelectorAll('[name=perm]:checked'), function (e) { return e.value; }) } }, function () { closeModal(); V['ad-roles'].run(root); });
        } }], true);
      }
    });
  } };

  /* ------------------------------------------------------------------ hubs and delivery areas (map) */
  var LEAF = null;
  function leaflet() {
    if (window.L && window.L.map) return Promise.resolve(window.L);
    if (LEAF) return LEAF;
    LEAF = new Promise(function (ok, bad) {
      var css = document.createElement('link'); css.rel = 'stylesheet'; css.href = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css'; document.head.appendChild(css);
      var s = document.createElement('script'); s.src = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js'; s.onload = function () { ok(window.L); }; s.onerror = function () { LEAF = null; bad(new Error('map')); };
      document.head.appendChild(s); setTimeout(function () { if (!window.L) { LEAF = null; bad(new Error('map')); } }, 9000);
    });
    return LEAF;
  }
  V['ad-hubs'] = { t: 'Hubs & delivery areas', dates: false, nofilter: true, run: function (root) {
    return api('/ad/hubs', {}, false).then(function (d) {
      var on = d.rows.filter(function (h) { return h.active; }).length;
      root.innerHTML = '<div class="cc-grid"><div class="cc-kpis">' + kpi('Hubs', num(d.rows.length, 0), num(on, 0) + ' open') + kpi('Need fixing', num(d.rows.filter(function (h) { return h.issues.length; }).length, 0), 'see the Check column', d.rows.some(function (h) { return h.issues.length; }) ? 'warn' : '') + '</div>' +
        card('Hubs', '<div class="ad-tools"><input class="cc-in" id="hQ" placeholder="Search hub or city…" style="flex:1;min-width:180px"><button type="button" class="cc-btn pri" id="hAdd">+ Add hub</button></div>' + slot('t'), { flush: true }) + '</div>';
      var reload = function () { V['ad-hubs'].run(root); };
      var q = '';
      function draw() {
        var rows = d.rows.filter(function (h) { return !q || (h.name + ' ' + h.city).toLowerCase().indexOf(q) >= 0; });
        var n = T([{ k: 'name', t: 'Hub', f: function (h) { return '<b>' + esc(h.name) + '</b><small>#' + h.id + '</small>'; } }, { k: 'city', t: 'City' },
          { k: 'kind', t: 'Delivery area', f: function (h) { return h.kind === 'polygon' ? 'Drawn area · ' + h.points.length + ' corners' : h.kind === 'circle' ? 'Circle · ' + d.circle_km + ' km' : '<span class="down">none</span>'; } },
          { k: 'hub_users', t: 'Staff', n: 1, v: function (h) { return h.hub_users + h.pos_users; }, f: function (h) { return num(h.hub_users, 0) + ' hub · ' + num(h.pos_users, 0) + ' POS'; } }, { k: 'riders', t: 'Riders', n: 1 }, { k: 'addresses', t: 'Customer addresses', n: 1 },
          { k: 'active', t: 'Open', v: function (h) { return h.active ? 1 : 0; }, c: function (h) { return h.active ? 'Open' : 'Closed'; }, f: function (h) { return sw(h.active, 'data-tg="' + h.id + '"'); } },
          { k: 'issues', t: 'Check', v: function (h) { return h.issues.length; }, c: function (h) { return h.issues.join('; '); }, f: function (h) { return h.issues.map(function (i) { return pill(i, /No delivery area|No hub/.test(i) ? 'p-bad' : 'p-warn'); }).join(' ') || '<span class="muted">ok</span>'; } }],
          rows, { csv: 'freshful-hubs.csv', click: function (h) { hubEditor(h, d, reload); }, after: function (w) {
            w.querySelectorAll('[data-tg]').forEach(function (b) { b.addEventListener('click', function (e) { e.stopPropagation(); var id = b.getAttribute('data-tg'), h = d.rows.filter(function (x) { return String(x.id) === id; })[0];
              ask({ title: (h.active ? 'Close ' : 'Open ') + h.name + '?', text: h.active ? 'New orders will not go to this hub. Orders already placed stay with it.' : 'The hub starts receiving orders from its delivery area.', ok: h.active ? 'Close hub' : 'Open hub', tone: h.active ? 'danger' : 'info' })
                .then(function (y) { if (y) post('/ad/hubs/' + id + '/toggle', {}).then(function (x) { toast(x.message, x.ok ? 'ok' : 'bad'); if (x.ok) { h.active = x.on; b.classList.toggle('is-on', x.on); } }); }); }); });
          } });
        n.classList.add('ad-tbl'); var s = root.querySelector('[data-slot="t"]') || root.querySelector('.ad-tbl'); s.replaceWith(n);
      }
      draw();
      $('hQ').addEventListener('input', function () { q = this.value.trim().toLowerCase(); draw(); });
      $('hAdd').addEventListener('click', function () { hubEditor(null, d, reload); });
    });
  } };
  function hubEditor(h, d, after) {
    var st = { kind: h ? (h.kind || 'polygon') : 'polygon', points: h ? h.points.slice() : [], lat: h ? h.lat : null, lng: h ? h.lng : null };
    if (h && h.centre && h.issues.join(' ').indexOf('corner of the area') >= 0) { st.lat = +h.centre[0].toFixed(7); st.lng = +h.centre[1].toFixed(7); }
    var body = el('<div class="ad-form"><div class="ad-3"><label class="cc-f"><span>Hub name *</span><input class="cc-in" name="name" maxlength="100" value="' + esc(h ? h.name : '') + '"></label>' +
      '<label class="cc-f"><span>City *</span><select class="cc-sel" name="city_id"><option value="">Choose…</option>' + d.cities.map(function (c) { return '<option value="' + c.id + '"' + (h && h.city_id === c.id ? ' selected' : '') + '>' + esc(c.name) + '</option>'; }).join('') + '</select></label>' +
      '<label class="cc-f"><span>Delivery area</span><select class="cc-sel" name="kind"><option value="polygon"' + (st.kind === 'polygon' ? ' selected' : '') + '>Drawn area (exact streets)</option><option value="circle"' + (st.kind === 'circle' ? ' selected' : '') + '>Circle around the hub (' + d.circle_km + ' km)</option></select></label></div>' +
      '<div class="ad-maptools"><span id="hmHelp"></span><button type="button" class="cc-btn sm" id="hmUndo">Undo last corner</button><button type="button" class="cc-btn sm" id="hmClear">Clear area</button><button type="button" class="cc-btn sm" id="hmMid">Put hub pin in the middle</button></div>' +
      '<div class="ad-map" id="hmMap"><div class="cc-load"><i></i>Loading map…</div></div>' +
      '<details class="ad-det"><summary>Type the points instead (one "latitude, longitude" per line)</summary><label class="cc-f"><span>Hub pin</span><input class="cc-in" id="hmPin" placeholder="12.9716, 77.5946"></label><label class="cc-f"><span>Area corners</span><textarea id="hmPts" rows="6" placeholder="12.97, 77.59&#10;12.98, 77.60&#10;12.96, 77.61"></textarea></label><button type="button" class="cc-btn sm" id="hmApply">Use these points</button></details>' +
      '<div class="ad-checks"><label class="ad-check"><input type="checkbox" name="active"' + (h && h.active ? ' checked' : '') + '> Open for orders</label><label class="ad-check"><input type="checkbox" name="confirm_outside"> The hub is outside its area</label></div>' +
      (h && h.issues.length ? '<p class="cc-note">' + h.issues.map(function (i) { return pill(i, 'p-warn'); }).join(' ') + '</p>' : '') + '</div>');
    var map = null, layer = null, pin = null, Lr = null;
    function txt() { body.querySelector('#hmPin').value = st.lat != null ? st.lat + ', ' + st.lng : ''; body.querySelector('#hmPts').value = st.points.map(function (p) { return p[0] + ', ' + p[1]; }).join('\n'); }
    function helpTxt() { body.querySelector('#hmHelp').textContent = st.kind === 'polygon' ? 'Click the map to add corners of the delivery area (' + st.points.length + ' so far). Drag the orange pin to the hub.' : 'Drag the pin to the hub. Customers within ' + d.circle_km + ' km are served.'; body.querySelector('#hmUndo').hidden = body.querySelector('#hmClear').hidden = st.kind !== 'polygon'; }
    function centre() { var a = 0, cx = 0, cy = 0, n = st.points.length; if (n < 3) return null; for (var i = 0; i < n; i++) { var p = st.points[i], q = st.points[(i + 1) % n], f = p[1] * q[0] - q[1] * p[0]; a += f; cx += (p[1] + q[1]) * f; cy += (p[0] + q[0]) * f; } if (Math.abs(a) < 1e-12) return [st.points.reduce(function (s, p) { return s + p[0]; }, 0) / n, st.points.reduce(function (s, p) { return s + p[1]; }, 0) / n]; return [cy / (3 * a), cx / (3 * a)]; }
    function drawMap() {
      if (!map) { txt(); return; }
      if (layer) { map.removeLayer(layer); layer = null; }
      if (st.kind === 'polygon' && st.points.length) layer = st.points.length >= 3 ? Lr.polygon(st.points, { color: '#EA4A1F', weight: 2, fillOpacity: .12 }).addTo(map) : Lr.polyline(st.points, { color: '#EA4A1F', weight: 2, dashArray: '4 4' }).addTo(map);
      if (st.kind === 'circle' && st.lat != null) layer = Lr.circle([st.lat, st.lng], { radius: d.circle_km * 1000, color: '#EA4A1F', weight: 2, fillOpacity: .1 }).addTo(map);
      if (st.lat != null) { if (!pin) { pin = Lr.marker([st.lat, st.lng], { draggable: true, title: 'Hub' }).addTo(map); pin.on('dragend', function () { var p = pin.getLatLng(); st.lat = +p.lat.toFixed(7); st.lng = +p.lng.toFixed(7); drawMap(); }); } else pin.setLatLng([st.lat, st.lng]); }
      txt(); helpTxt();
    }
    body.querySelector('[name=kind]').addEventListener('change', function () { st.kind = this.value; if (st.kind === 'circle' && st.lat == null && st.points.length) { var c = centre() || st.points[0]; st.lat = c[0]; st.lng = c[1]; } drawMap(); helpTxt(); });
    body.querySelector('#hmUndo').addEventListener('click', function () { st.points.pop(); drawMap(); });
    body.querySelector('#hmClear').addEventListener('click', function () { st.points = []; drawMap(); });
    body.querySelector('#hmMid').addEventListener('click', function () { var c = centre(); if (!c) { toast('Draw the area first (at least 3 corners).', 'bad'); return; } st.lat = +c[0].toFixed(7); st.lng = +c[1].toFixed(7); drawMap(); });
    body.querySelector('#hmApply').addEventListener('click', function () {
      var pts = body.querySelector('#hmPts').value.split(/\n+/).map(function (l) { var m = l.match(/(-?\d+(?:\.\d+)?)\s*[, ]\s*(-?\d+(?:\.\d+)?)/); return m ? [+m[1], +m[2]] : null; }).filter(Boolean);
      var pm = body.querySelector('#hmPin').value.match(/(-?\d+(?:\.\d+)?)\s*[, ]\s*(-?\d+(?:\.\d+)?)/);
      st.points = pts; if (pm) { st.lat = +pm[1]; st.lng = +pm[2]; }
      drawMap(); if (map && layer && layer.getBounds) try { map.fitBounds(layer.getBounds(), { padding: [20, 20] }); } catch (e) {}
      toast('Points applied.', 'ok');
    });
    modal(h ? 'Hub: ' + h.name : 'Add hub', body, [{ t: h ? 'Save hub' : 'Add hub', cls: 'pri', fn: function (b) {
      var v = { name: body.querySelector('[name=name]').value, city_id: body.querySelector('[name=city_id]').value, kind: st.kind, points: st.points, lat: st.lat, lng: st.lng, active: body.querySelector('[name=active]').checked, confirm_outside: body.querySelector('[name=confirm_outside]').checked ? 1 : 0 };
      act(b, '/ad/hubs/save', { id: h ? h.id : '', values: v }, function () { closeModal(); after(); });
    } }], true);
    helpTxt(); txt();
    leaflet().then(function (L) {
      Lr = L; var box = body.querySelector('#hmMap'); box.innerHTML = '';
      var c0 = st.lat != null ? [st.lat, st.lng] : (st.points[0] || [12.97, 77.59]);
      map = L.map(box, { zoomControl: true }).setView(c0, 13);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
      map.on('click', function (e) {
        if (st.kind === 'polygon') { st.points.push([+e.latlng.lat.toFixed(7), +e.latlng.lng.toFixed(7)]); if (st.lat == null && st.points.length >= 3) { var c = centre(); st.lat = +c[0].toFixed(7); st.lng = +c[1].toFixed(7); } }
        else { st.lat = +e.latlng.lat.toFixed(7); st.lng = +e.latlng.lng.toFixed(7); }
        drawMap();
      });
      drawMap();
      if (layer && layer.getBounds) try { map.fitBounds(layer.getBounds(), { padding: [20, 20] }); } catch (e) {}
      setTimeout(function () { map.invalidateSize(); }, 120);
    }).catch(function () { body.querySelector('#hmMap').innerHTML = '<div class="cc-empty">The map could not load (no internet to the map service). Use "Type the points instead" below.</div>'; body.querySelector('details').open = true; });
  }

  /* ------------------------------------------------------------------ push notifications */
  V['ad-push'] = { t: 'Push notifications', dates: false, nofilter: true, run: function (root) {
    return api('/ad/push', {}, false).then(function (d) {
      root.innerHTML = '<div class="cc-grid">' + (d.configured ? '' : card('Set up push notifications first', '<p class="cc-note ad-help">The old admin used Google\'s old notification service, which Google switched off in 2024, so pushes have not reached customers since then. To send again:</p>' +
        '<ol class="ad-steps"><li>Open <b>Firebase console</b> › your Freshful app project › <b>Project settings</b> › <b>Service accounts</b> › <b>Generate new private key</b>. A .json file downloads.</li>' +
        '<li>Upload it to your hosting <b>outside public_html</b>, e.g. <code>/home/u182416408/firebase-service-account.json</code>.</li>' +
        '<li>Add to <code>.env</code>: <code>FCM_SERVICE_ACCOUNT=/home/u182416408/firebase-service-account.json</code> and <code>FCM_PROJECT_ID=&lt;your firebase project id&gt;</code>, then run <code>php artisan config:clear</code>.</li></ol>')) +
        card('Send to every app user', '<div class="ad-form" id="pf"><label class="cc-f"><span>Title *</span><input class="cc-in" name="title" maxlength="80" placeholder="e.g. Fresh catch today"></label><label class="cc-f"><span>Message *</span><textarea name="body" maxlength="400" rows="3" placeholder="e.g. Seer fish and prawns just arrived. Order in 30 minutes."></textarea></label>' +
          imageField('image', 'Image (optional)', '', '', '', false) + '<div><button type="button" class="cc-btn pri" id="pSend"' + (d.configured ? '' : ' disabled') + '>Send now</button></div></div>') +
        card('Sent before', slot('t'), { flush: true }) + '</div>';
      wireImages(root);
      mount(root, 't', T([{ k: 'at', t: 'Sent', n: 1, f: function (x) { return x.at ? dt(x.at) : '–'; } }, { k: 'image', t: '', nosort: 1, csv: false, f: function (x) { return thumb(x.image); } }, { k: 'title', t: 'Title' }, { k: 'body', t: 'Message', f: function (x) { return cut(x.body, 100); } }], d.rows, { csv: 'freshful-push.csv' }));
      var b = $('pSend');
      if (b) b.addEventListener('click', function () {
        var f = $('pf'), t = f.querySelector('[name=title]').value.trim(), m = f.querySelector('[name=body]').value.trim();
        if (!t || !m) { toast('Write the title and the message.', 'bad'); return; }
        ask({ title: 'Send to every app user?', text: '"' + t + '" goes to all customers with the app. It cannot be taken back.', ok: 'Send', tone: 'warn' }).then(function (y) { if (y) act(b, '/ad/push', { title: t, body: m, image: f.querySelector('[name=image]').value }, function () { V['ad-push'].run(root); }); });
      });
    });
  } };

  /* ------------------------------------------------------------------ inventory report detail */
  V['ad-invreport'] = { t: 'Inventory report detail', dates: true, run: function (root) {
    return api('/ad/reports/inventory').then(function (d) {
      var conv = function (q, r) { if (S.units === 'p' || !r.per) return num(q); return K.qtyText(q, r); };
      var sum = function (k) { return d.rows.reduce(function (s, r) { return s + (+r[k] || 0); }, 0); };
      root.innerHTML = '<div class="cc-grid"><div class="cc-kpis">' + kpi('Sold', money(sum('sold_amt')), num(sum('orders'), 0) + ' order lines') + kpi('Live stock value', money(sum('live_value')), num(d.rows.filter(function (r) { return r.live > 0; }).length, 0) + ' products in stock') +
        kpi('Wastage', money(sum('waste_value')), '', sum('waste_value') > 0 ? 'warn' : '') + kpi('Products', num(d.rows.length, 0), esc(d.label)) + '</div>' +
        (d.moves_from ? '<p class="cc-note">Received, transfers and wastage are recorded from ' + esc(d.moves_from) + ' (when the hub console went live). Sales are complete for any dates.</p>' : '<p class="cc-note">No stock movements recorded yet; received, transfers and wastage show 0.</p>') +
        card('Per product · ' + esc(d.label), '<div class="ad-tools"><span style="flex:1"></span><button type="button" class="cc-btn" id="irX">Download Excel</button></div>' + slot('t'), { flush: true }) + '</div>';
      mount(root, 't', T([{ k: 'code', t: 'Product ID' }, { k: 'name', t: 'Product', f: function (r) { return '<b>' + esc(r.name) + '</b><small>' + esc(r.pack) + '</small>'; } }, { k: 'category', t: 'Category' },
        { k: 'cost', t: 'Cost', n: 1, f: function (r) { return r.cost ? money2(r.cost) : '–'; } }, { k: 'mrp', t: 'MRP', n: 1, f: function (r) { return money2(r.mrp); } },
        { k: 'sold_amt', t: 'Sold amount', n: 1, f: function (r) { return money(r.sold_amt); } }, { k: 'sold', t: 'Sold', n: 1, f: function (r) { return conv(r.sold, r); } },
        { k: 'received', t: 'Received', n: 1, f: function (r) { return conv(r.received, r); } }, { k: 'tin', t: 'Transfer in', n: 1, f: function (r) { return conv(r.tin, r); } }, { k: 'tout', t: 'Transfer out', n: 1, f: function (r) { return conv(r.tout, r); } },
        { k: 'wastage', t: 'Wastage', n: 1, f: function (r) { return r.wastage ? '<span class="down">' + conv(r.wastage, r) + '</span>' : '0'; } }, { k: 'live', t: 'Live now', n: 1, f: function (r) { return r.live > 0 ? '<b>' + conv(r.live, r) + '</b>' : '<span class="down">0</span>'; } }, { k: 'live_value', t: 'Live value', n: 1, f: function (r) { return money(r.live_value); } }],
        d.rows, { csv: 'freshful-inventory-report.csv', per: 100 }));
      $('irX').addEventListener('click', function () { var q = {}; Object.keys(S.f).forEach(function (k) { if (S.f[k] !== '' && !(k === 'hstatus' && S.f[k] === 'all')) q[k] = S.f[k]; }); q.from = S.from; q.to = S.to; q.format = 'xlsx'; q.units = S.units; download('/ad/reports/inventory', q); });
    });
  } };

  /* ------------------------------------------------------------------ change log */
  V['ad-audit'] = { t: 'Change log', dates: true, nofilter: false, run: function (root) {
    var st = S.p.audit || (S.p.audit = { q: '', module: '' });
    return api('/ad/audit', { q: st.q, module: st.module }).then(function (d) {
      if (d.off) { root.innerHTML = card('Change log', '<div class="cc-empty">The change log table is not installed yet.</div>'); return; }
      root.innerHTML = '<div class="cc-grid">' + card('Who changed what (' + num(d.rows.length, 0) + ')', '<div class="ad-tools"><input class="cc-in" id="aQ" placeholder="Search text, person or record id…" value="' + esc(st.q) + '" style="flex:1;min-width:180px"><select class="cc-sel" id="aM"><option value="">All modules</option>' + d.modules.map(function (m) { return '<option' + (m === st.module ? ' selected' : '') + '>' + esc(m) + '</option>'; }).join('') + '</select><button type="button" class="cc-btn" id="aGo">Search</button></div>' + slot('t'), { flush: true, sub: 'Every change made in the new admin. Passwords are never shown.' }) + '</div>';
      mount(root, 't', T([{ k: 'at', t: 'When', n: 1, f: function (x) { return dt(x.at); } }, { k: 'actor', t: 'Who', f: function (x) { return esc(x.actor) + '<small>' + esc(x.role) + '</small>'; } }, { k: 'action', t: 'Action', f: function (x) { return pill(x.action, /delete|off/.test(x.action) ? 'p-warn' : 'p-info'); } }, { k: 'module', t: 'Module' }, { k: 'summary', t: 'What' }],
        d.rows, { csv: 'freshful-change-log.csv', click: function (x) {
          var keys = Object.keys(Object.assign({}, x.before || {}, x.after || {}));
          openDrawer(x.summary, '<dl class="cc-dl"><dt>When</dt><dd>' + dt(x.at) + '</dd><dt>Who</dt><dd>' + esc(x.actor) + ' (' + esc(x.role) + ')</dd><dt>Table</dt><dd>' + esc(x.table) + ' #' + esc(x.record) + '</dd><dt>From</dt><dd>' + esc(x.ip) + '</dd></dl>' +
            (keys.length ? '<div class="cc-card"><div class="cc-body flush"><div class="cc-tw"><table class="cc-t"><thead><tr><th>Field</th><th>Before</th><th>After</th></tr></thead><tbody>' + keys.map(function (k) { var b = (x.before || {})[k], a = (x.after || {})[k]; return '<tr><td><b>' + esc(k) + '</b></td><td>' + cut(b == null ? '–' : b, 200) + '</td><td>' + cut(a == null ? '–' : a, 200) + '</td></tr>'; }).join('') + '</tbody></table></div></div></div>' : '<p class="cc-note">No field changes recorded.</p>'));
        } }));
      var go = function () { st.q = $('aQ').value.trim(); st.module = $('aM').value; V['ad-audit'].run(root); };
      $('aGo').addEventListener('click', go); $('aQ').addEventListener('keydown', function (e) { if (e.key === 'Enter') go(); });
    });
  } };

  /* ------------------------------------------------------------------ my profile */
  V['ad-profile'] = { t: 'My profile & password', dates: false, nofilter: true, run: function (root) {
    return api('/ad/profile', {}, false).then(function (d) {
      if (!d.ok) { root.innerHTML = card('Profile', '<div class="cc-empty">' + esc(d.message) + '</div>'); return; }
      var p = d.profile;
      root.innerHTML = '<div class="cc-grid ad-2">' + card('My profile', '<div class="ad-form" id="prf"><label class="cc-f"><span>Name *</span><input class="cc-in" name="name" value="' + esc(p.name) + '" maxlength="100"></label><label class="cc-f"><span>Email (login) *</span><input class="cc-in" type="email" name="email" value="' + esc(p.email) + '" maxlength="150"></label>' +
          '<label class="cc-f"><span>Address</span><input class="cc-in" name="address" value="' + esc(p.address) + '" maxlength="255"></label>' + imageField('image', 'Photo', 'users', p.image, p.image_url, false) + '<div><button type="button" class="cc-btn pri" id="prS">Save profile</button></div></div>') +
        card('Change password', '<div class="ad-form" id="pwf"><label class="cc-f"><span>Current password</span><input class="cc-in" type="password" name="current" autocomplete="current-password"></label><label class="cc-f"><span>New password <small class="muted">(at least 10 characters, letters and numbers)</small></span><input class="cc-in" type="password" name="new" autocomplete="new-password"></label>' +
          '<label class="cc-f"><span>New password again</span><input class="cc-in" type="password" name="confirm" autocomplete="new-password"></label><div><button type="button" class="cc-btn pri" id="pwS">Change password</button></div><p class="cc-note">Your password is stored ' + (p.secure ? '<b>secure (encrypted)</b>' : 'as <b>plain text</b> by the old login') + '.</p></div>') +
        card('System checks', '<dl class="cc-dl"><dt>Image folder</dt><dd>' + (d.uploads ? pill('found', 'p-ok') : pill('not found: set ADMIN_UPLOADS_ROOT', 'p-bad')) + '</dd><dt>Push notifications</dt><dd>' + (d.push ? pill('set up', 'p-ok') : pill('not set up', 'p-warn')) + '</dd></dl>') + '</div>';
      wireImages(root);
      $('prS').addEventListener('click', function () { var f = $('prf'); act(this, '/ad/profile', { values: { name: f.querySelector('[name=name]').value, email: f.querySelector('[name=email]').value, address: f.querySelector('[name=address]').value, image: f.querySelector('[name=image]').value } }); });
      $('pwS').addEventListener('click', function () { var f = $('pwf'); var b = this; act(b, '/ad/profile/password', { current: f.querySelector('[name=current]').value, 'new': f.querySelector('[name=new]').value, confirm: f.querySelector('[name=confirm]').value }, function () { f.querySelectorAll('input').forEach(function (i) { i.value = ''; }); }); });
    });
  } };
};

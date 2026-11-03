/* Freshful admin console (admin update 1, 22 Oct 2026). Head-office screens for the admin role.
   Plugs into cc.js (window.CCX) and uses its helpers, styles and dialogs. No browser default pop-ups. */
window.CCX = function (K) {
  'use strict';
  var C = K.C, V = K.V, S = K.S, $ = K.$, esc = K.esc, money = K.money, money2 = K.money2, num = K.num, pct = K.pct, dt = K.dt, dOnly = K.dOnly, dStr = K.dStr,
    pill = K.pill, api = K.api, post = K.post, act = K.act, ask = K.ask, toast = K.toast, card = K.card, kpi = K.kpi, slot = K.slot, mount = K.mount,
    hbars = K.hbars, T = K.T, el = K.el, openDrawer = K.openDrawer, openModal = K.openModal, closeModal = K.closeModal, closeDrawer = K.closeDrawer;

  /* ------------------------------------------------------------------ menu (the /admin/new style): groups, find, rail, Ctrl K, breadcrumb */
  (function shell() {
    /* leaving a page closes any open window from that page */
    window.addEventListener('hashchange', function () { var a = document.querySelector('.cc-ask [data-x]'); if (a) a.click(); closeModal(); closeDrawer(); });
    var side = $('ccSide'); if (!side) return;
    var groups = [].slice.call(side.querySelectorAll('.ad-grp[data-group]'));
    var closed = K.store.get('ad-closed') || {};
    groups.forEach(function (g) {
      var k = g.getAttribute('data-group'), h = g.querySelector('.ad-grp-h');
      if (closed[k]) { g.classList.add('is-closed'); h.setAttribute('aria-expanded', 'false'); }
      h.addEventListener('click', function () { var c = g.classList.toggle('is-closed'); h.setAttribute('aria-expanded', c ? 'false' : 'true'); closed[k] = c; K.store.set('ad-closed', closed); });
    });
    var find = $('adFind'), nores = $('adNoRes');
    if (find) find.addEventListener('input', function () {
      var q = find.value.trim().toLowerCase(), any = false;
      groups.forEach(function (g) {
        var vis = 0;
        g.querySelectorAll('a[data-label]').forEach(function (a) { var on = !q || a.getAttribute('data-label').indexOf(q) >= 0; a.hidden = !on; if (on) vis++; });
        g.hidden = !vis; if (q && vis) g.classList.remove('is-closed'); if (vis) any = true;
      });
      if (!q) groups.forEach(function (g) { if (closed[g.getAttribute('data-group')]) g.classList.add('is-closed'); });
      nores.hidden = any;
    });
    var rb = $('adRail');
    if (rb) rb.addEventListener('click', function () { var on = document.body.classList.toggle('ad-rail'); try { localStorage.setItem('ad-rail', on ? '1' : '0'); } catch (e) {} });
    var tip = document.createElement('div'); tip.className = 'ad-tip'; document.body.appendChild(tip);
    side.querySelectorAll('[data-tip]').forEach(function (a) {
      a.addEventListener('mouseenter', function () { if (!document.body.classList.contains('ad-rail')) return; var r = a.getBoundingClientRect(); tip.textContent = a.getAttribute('data-tip'); tip.style.top = (r.top + r.height / 2) + 'px'; tip.classList.add('is-on'); });
      a.addEventListener('mouseleave', function () { tip.classList.remove('is-on'); });
    });
    function crumb() {
      var key = (location.hash || '#' + (C.home || '')).slice(1).split('?')[0];
      var a = side.querySelector('a[data-view="' + key + '"]'), g = a && a.closest('.ad-grp');
      var c = $('adCrumb'); if (c) c.innerHTML = g ? '<b>' + esc(g.getAttribute('data-group')) + '</b> / ' + esc(a.getAttribute('data-tip')) : '';
      if (g && g.classList.contains('is-closed') && !document.body.classList.contains('ad-rail')) { g.classList.remove('is-closed'); }
    }
    window.addEventListener('hashchange', crumb); setTimeout(crumb, 0);
    function palette() {
      if (document.querySelector('.ad-pal')) return;
      var links = [].slice.call(side.querySelectorAll('a[data-view]'));
      var w = el('<div class="ad-pal" role="dialog" aria-modal="true" aria-label="Find a page"><div class="ad-pal-box"><input placeholder="Type a page name… (Esc to close)" aria-label="Page name"><div class="ad-pal-list"></div></div></div>');
      document.body.appendChild(w);
      var inp = w.querySelector('input'), list = w.querySelector('.ad-pal-list'), sel = 0, shown = [];
      function draw() {
        var q = inp.value.trim().toLowerCase();
        shown = links.filter(function (a) { return !q || a.getAttribute('data-label').indexOf(q) >= 0; }).slice(0, 40);
        if (sel >= shown.length) sel = 0;
        list.innerHTML = shown.length ? shown.map(function (a, i) { var g = a.closest('.ad-grp'); return '<a href="' + a.getAttribute('href') + '" class="' + (i === sel ? 'is-sel' : '') + '" style="--c:' + esc(g.style.getPropertyValue('--c')) + '">' + a.querySelector('.ad-tile').outerHTML + esc(a.getAttribute('data-tip')) + '<small>' + esc(g.getAttribute('data-group')) + '</small></a>'; }).join('') : '<div class="cc-empty">No page matches.</div>';
      }
      function close() { document.removeEventListener('keydown', key, true); w.remove(); }
      function key(e) {
        if (e.key === 'Escape') { e.preventDefault(); close(); }
        else if (e.key === 'ArrowDown') { e.preventDefault(); sel = Math.min(shown.length - 1, sel + 1); draw(); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); sel = Math.max(0, sel - 1); draw(); }
        else if (e.key === 'Enter' && shown[sel]) { e.preventDefault(); location.hash = shown[sel].getAttribute('href'); close(); }
      }
      inp.addEventListener('input', function () { sel = 0; draw(); });
      list.addEventListener('click', function (e) { if (e.target.closest('a')) close(); });
      w.addEventListener('click', function (e) { if (e.target === w) close(); });
      document.addEventListener('keydown', key, true);
      draw(); setTimeout(function () { inp.focus(); }, 10);
    }
    var sb = $('adSearchBtn'); if (sb) sb.addEventListener('click', palette);
    document.addEventListener('keydown', function (e) {
      var t = (e.target && e.target.tagName) || '';
      if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) { e.preventDefault(); palette(); }
      else if (e.key === '/' && !/INPUT|TEXTAREA|SELECT/.test(t) && find) { e.preventDefault(); if (document.body.classList.contains('ad-rail')) palette(); else find.focus(); }
    });
  })();

  /* ------------------------------------------------------------------ shared bits */
  var META = null;
  function meta(force) { if (META && !force) return Promise.resolve(META); return api('/ad/meta', {}, false).then(function (d) { META = d; return d; }); }
  function hashParams() { var h = location.hash.split('?')[1] || ''; var o = {}; h.split('&').forEach(function (kv) { if (!kv) return; var p = kv.split('='); o[decodeURIComponent(p[0])] = decodeURIComponent(p[1] || ''); }); return o; }
  function modal(title, body, buttons, wide) { openModal(title, body, buttons); $('ccModal').classList.toggle('ad-wide', !!wide); }
  function sw(on, attrs) { return '<button type="button" class="ad-sw' + (on ? ' is-on' : '') + '" role="switch" aria-checked="' + (on ? 'true' : 'false') + '" ' + (attrs || '') + '><i></i></button>'; }
  function thumb(url, big) { return url ? '<img class="ad-thumb' + (big ? ' big' : '') + '" src="' + esc(url) + '" alt="" loading="lazy" onerror="this.classList.add(\'is-bad\')">' : '<span class="ad-thumb none">No image</span>'; }
  function cut(s, n) { s = String(s == null ? '' : s).replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim(); return s.length > n ? esc(s.slice(0, n)) + '…' : esc(s); }
  function download(path, extra) { var q = Object.keys(extra || {}).map(function (k) { return encodeURIComponent(k) + '=' + encodeURIComponent(extra[k]); }).join('&'); location.href = C.base + path + (q ? '?' + q : ''); }
  /** KPI card with a coloured icon tile (dashboard). */
  function ikpi(label, value, sub, cls, ic, go) { var k = el(kpi(label, value, sub, cls, go)); k.insertAdjacentHTML('afterbegin', '<span class="ad-kico">' + K.icon(ic) + '</span>'); return k.outerHTML; }
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
  var RES = { 'ad-maincat': 'maincat', 'ad-category': 'category', 'ad-units': 'units', 'ad-tags': 'tags', 'ad-filters': 'filters', 'ad-buyers': 'buyers',
    'ad-cities': 'cities', 'ad-delivery': 'delivery', 'ad-banners': 'banners', 'ad-appbanners': 'appbanners', 'ad-slider': 'slider', 'ad-offers': 'offers', 'ad-promos': 'promos',
    'ad-coupons': 'coupons', 'ad-certs': 'certs', 'ad-policies': 'policies', 'ad-grievcat': 'grievcat', 'ad-newsletter': 'newsletter' };

  function fieldHtml(f, v, row) {
    var ro = f.ro ? ' disabled' : '', req = f.req ? ' *' : '', val = v == null ? '' : v;
    if (f.t === 'image') return imageField(f.name, f.label, f.folder, val, row ? row[f.name + '_url'] : '', f.req);
    if (f.t === 'status') return '<label class="ad-check"><input type="checkbox" name="' + f.name + '"' + (row ? (row[f.name] ? ' checked' : '') : ' checked') + '> On (visible / active)</label>';
    if (f.t === 'citypick') return '<div class="cc-f ad-citypick"><span>' + esc(f.label) + req + '</span><select class="cc-sel" data-citysel><option value="">Choose the state first</option></select>' +
      '<input class="cc-in" name="' + f.name + '" value="' + esc(val) + '" maxlength="' + (f.max || 80) + '" placeholder="Type the town name" hidden><small class="muted" data-citysrc></small></div>';
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
  /** City field: pick from the chosen state's cities (or "Other" to type a town that is not listed). */
  function wireCityPick(box, row) {
    var wrap = box.querySelector('.ad-citypick'); if (!wrap) return;
    var st = box.querySelector('[name=state_id]'), sel = wrap.querySelector('[data-citysel]'), inp = wrap.querySelector('input'), src = wrap.querySelector('[data-citysrc]');
    var cur = inp.value;
    function load() {
      var id = st.value;
      if (!id) { sel.innerHTML = '<option value="">Choose the state first</option>'; inp.hidden = true; return; }
      sel.innerHTML = '<option value="">Loading cities…</option>';
      api('/ad/cities-of/' + id, {}, false).then(function (d) {
        var list = d.cities || [], found = false;
        sel.innerHTML = '<option value="">Choose the city…</option>' + list.map(function (c) { var on = cur && c.name.toLowerCase() === cur.toLowerCase(); if (on) found = true; return '<option value="' + esc(c.name) + '"' + (on ? ' selected' : '') + (c.added && !on ? ' disabled' : '') + '>' + esc(c.name) + (c.added && !on ? ' (already added)' : '') + '</option>'; }).join('') + '<option value="__other"' + (cur && !found ? ' selected' : '') + '>Other (type the name)…</option>';
        inp.hidden = !(cur && !found); if (found) inp.value = cur;
        src.textContent = list.length ? list.length + ' cities from the ' + d.source + '.' : 'No list for this state: choose Other and type the name.';
      });
    }
    sel.addEventListener('change', function () { if (sel.value === '__other') { inp.hidden = false; inp.value = ''; inp.focus(); } else { inp.hidden = true; inp.value = sel.value; } });
    if (st) st.addEventListener('change', function () { cur = ''; inp.value = ''; load(); });
    load();
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
  /** Delete with a clear, styled warning. strong = the person types DELETE. If the record is still in use, the server says why and offers switching it off. */
  function confirmDelete(o) {
    var go = function () {
      post(o.path, {}).then(function (r) {
        if (r.ok) { toast(r.message, 'ok'); if (o.done) o.done(r); return; }
        if (r.used) {
          ask({ title: 'Cannot delete ' + o.name, text: r.message, tone: 'warn', ok: o.off ? 'Switch it off instead' : 'OK', cancel: 'Close' }).then(function (y) { if (y && o.off) o.off(); });
        } else toast(r.message || 'Could not delete.', 'bad');
      });
    };
    var text = 'This removes "' + o.name + '" for good. The website and app stop showing it at once. A copy is kept in the change log.';
    if (o.strong) {
      ask({ title: 'Delete ' + o.what + '?', text: text, tone: 'danger', ok: 'Delete for good', input: { label: 'Type DELETE to confirm', placeholder: 'DELETE' } }).then(function (v) {
        if (v == null) return; if (String(v).trim().toUpperCase() !== 'DELETE') { toast('Not deleted: type DELETE exactly.', 'bad'); return; } go();
      });
    } else ask({ title: 'Delete ' + o.what + '?', text: text, tone: 'danger', ok: 'Delete' }).then(function (y) { if (y) go(); });
  }
  function rowName(d, x) { var k = ['name', 'title', 'tag', 'unit', 'city', 'property_name', 'type', 'email'].filter(function (c) { return x[c]; })[0]; return k ? String(x[k]) : '#' + x.id; }
  function recordForm(d, res, row, after) {
    var editable = d.edit;
    var body = el('<div class="ad-form">' + help(row ? '' : d.help) + d.fields.map(function (f) { return fieldHtml(f, row ? row[f.name] : '', row); }).join('') + '</div>');
    if (!editable) body.querySelectorAll('input,select,textarea').forEach(function (e) { e.disabled = true; });
    wireImages(body); wireCityPick(body, row);
    var btns = [];
    if (editable) btns.push({ t: row ? 'Save' : 'Add', cls: 'pri', fn: function (b) {
      act(b, '/ad/r/' + res + '/save', { id: row ? row.id : '', values: readForm(body, d.fields) }, function () { closeModal(); after(); });
    } });
    if (row && d.delete) btns.unshift({ t: 'Delete', cls: 'danger', fn: function () {
      confirmDelete({ what: 'this ' + d.title.toLowerCase(), name: rowName(d, row), path: '/ad/r/' + res + '/' + row.id + '/delete', strong: /maincat|category/.test(res), done: function () { closeModal(); after(); },
        off: d.has_status && row.status ? function () { post('/ad/r/' + res + '/' + row.id + '/toggle', {}).then(function (x) { toast(x.message, x.ok ? 'ok' : 'bad'); closeModal(); after(); }); } : null });
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
          if (res === 'maincat') cols.push({ k: 'categories', t: 'Sub categories', n: 1 });
          if (d.edit) cols.push({ k: '_a', t: '', nosort: 1, csv: false, f: function (x) { return '<div class="ad-acts"><button type="button" class="cc-btn" data-ed="' + x.id + '">Edit</button>' + (d.delete ? '<button type="button" class="cc-btn ad-del" data-del="' + x.id + '">Delete</button>' : '') + '</div>'; } });
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
              var byId = function (id) { return r.rows.filter(function (y) { return String(y.id) === String(id); })[0]; };
              w.querySelectorAll('[data-ed]').forEach(function (b) { b.addEventListener('click', function (e) { e.stopPropagation(); recordForm(d, res, byId(b.getAttribute('data-ed')), reload); }); });
              w.querySelectorAll('[data-del]').forEach(function (b) { b.addEventListener('click', function (e) { e.stopPropagation(); var x = byId(b.getAttribute('data-del'));
                confirmDelete({ what: 'this ' + d.title.toLowerCase(), name: rowName(d, x), path: '/ad/r/' + res + '/' + x.id + '/delete', strong: /maincat|category/.test(res), done: function () { META = null; reload(); },
                  off: d.has_status && x.status ? function () { post('/ad/r/' + res + '/' + x.id + '/toggle', {}).then(function (y) { toast(y.message, y.ok ? 'ok' : 'bad'); reload(); }); } : null }); }); });
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
        ikpi('Customers', num(a.customers, 0), 'registered accounts', 'k-pink', 'users', 'customers') + ikpi('Revenue (delivered)', money(a.revenue), num(a.delivered, 0) + ' delivered orders', 'k-green', 'cash', 'reports') +
        ikpi('Total orders', num(a.orders, 0), 'since the start', 'k-blue', 'receipt', 'orders') + ikpi('Products', num(a.products_on, 0) + ' on sale', num(a.products, 0) + ' in total', 'k-teal', 'package', 'ad-products') +
        ikpi('Pending to accept', num(a['new'], 0), 'waiting at hubs', a['new'] ? 'warn' : 'k-indigo', 'clock', 'ad-dispatch') + ikpi('Ongoing', num(a.ongoing, 0), 'accepted or out for delivery', 'k-purple', 'truck', 'ad-dispatch') +
        ikpi('Completed', num(a.delivered, 0), '', 'k-green', 'shield', 'orders') + ikpi('Cancelled', num(a.cancelled, 0), a.orders ? pct(a.cancelled * 100 / a.orders) + ' of all orders' : '', 'k-red', 'close', 'cancels') + '</div>' +
        '<div class="ad-sec">In the chosen dates</div><div class="cc-kpis">' +
        ikpi('Sales', money(g.sales), num(g.orders, 0) + ' orders', 'k-orange', 'chart', 'reports') + ikpi('Average order', money(g.aov), 'per order', 'k-indigo', 'scale') + ikpi('Cancelled', num(g.cancelled, 0), '', g.cancelled ? 'warn' : 'k-slate', 'close', 'cancels') +
        ikpi('New customers', g.new_customers == null ? '–' : num(g.new_customers, 0), 'signed up', 'k-pink', 'user', 'ad-buyers') + '</div>' +
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
        '<div class="ad-3"><label class="cc-f"><span>Category *</span><select class="cc-sel" name="main"><option value="">Choose…</option>' + mains.map(function (n) { return '<option' + (n === p.main ? ' selected' : '') + '>' + esc(n) + '</option>'; }).join('') + '</select></label>' +
        '<label class="cc-f"><span>Sub category *</span><select class="cc-sel" name="category_id"><option value="">Choose…</option>' + catOpts(p.main) + '</select></label>' +
        '<label class="cc-f"><span>Product name * <small class="muted">(as customers see it)</small></span><input class="cc-in" name="item_name" maxlength="150" list="pfNames" autocomplete="off" placeholder="e.g. Chicken Curry Cut" value="' + esc(p.item_name || (preset && preset.name) || '') + '"><datalist id="pfNames"></datalist><input type="hidden" name="item_id" value="' + esc(p.item_id || '') + '"></label></div>' +
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
        imageField('product_image', 'Product photo (shown on the website and app)', 'products', p.product_image === 'false' ? '' : (p.product_image || ''), p.image_url || '', false) +
        '<div class="ad-checks"><label class="ad-check"><input type="checkbox" name="active"' + (id ? (p.active ? ' checked' : '') : '') + '> On sale</label><label class="ad-check"><input type="checkbox" name="confirm_pack"> This pack size is correct</label><label class="ad-check"><input type="checkbox" name="confirm_cost"> Sell below cost</label></div>' +
        (r && r.hubs && r.hubs.length ? '<details class="ad-det"><summary>Stock in hubs (' + r.hubs.length + ')</summary>' + r.hubs.map(function (h) { return '<div>' + esc(h.hub) + ': <b>' + num(h.live) + '</b> live' + (h.incoming ? ', ' + num(h.incoming) + ' incoming' : '') + '</div>'; }).join('') + '</details>' : '') +
        (p.stock !== undefined && p.stock !== '' && id ? '<input type="hidden" name="stock" value="' + esc(p.stock) + '">' : '') + '</div>');
      var q = function (n) { return body.querySelector('[name="' + n + '"]'); };
      function loadItems() {
        var c = q('category_id').value, dl = body.querySelector('#pfNames');
        dl.innerHTML = '';
        if (c) api('/ad/products/items/' + c, {}, false).then(function (d) { dl.innerHTML = d.items.map(function (i) { return '<option value="' + esc(i.name) + '">'; }).join(''); });
        var fs = m.filters.filter(function (f) { return String(f.cat) === String(c); });
        body.querySelector('#pfProps').innerHTML = fs.length ? fs.map(function (f) { return '<div class="ad-prop"><b>' + esc(f.name) + '</b>' + f.values.map(function (v) { return '<label class="ad-check"><input type="checkbox" name="property" value="' + esc(v) + '"' + (props.indexOf(v) >= 0 ? ' checked' : '') + '> ' + esc(v) + '</label>'; }).join('') + '</div>'; }).join('') : '<span class="muted">No filters for this sub category.</span>';
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
      q('main').addEventListener('change', function () { q('category_id').innerHTML = '<option value="">Choose…</option>' + catOpts(this.value); loadItems(); });
      q('category_id').addEventListener('change', function () { loadItems(); });
      ['MRP', 'main_price', 'cost_price', 'unit_quantity', 'product_unit'].forEach(function (n) { q(n).addEventListener('input', calc); q(n).addEventListener('change', calc); });
      wireImages(body); loadItems(); calc();
      modal(id ? 'Edit product' : (preset && preset.restore_code ? 'Recreate product ' + preset.restore_code : 'Add product'), body, [{ t: id ? 'Save product' : 'Add product', cls: 'pri', fn: function (b) {
        var v = {}; ['category_id', 'item_id', 'item_name', 'product_unit', 'unit_quantity', 'gross_quantity', 'MRP', 'main_price', 'cost_price', 'pieces', 'serves', 'cooking_time', 'ranking', 'description', 'product_image', 'stock'].forEach(function (n) { var e = q(n); if (e) v[n] = e.value; });
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
          '<select class="cc-sel" id="pMain"><option value="">All categories</option>' + m.mains.map(function (n) { return '<option>' + esc(n) + '</option>'; }).join('') + '</select>' +
          '<select class="cc-sel" id="pCat"><option value="">All sub categories</option></select>' +
          '<select class="cc-sel" id="pSt"><option value="">On and off</option><option value="on">On sale</option><option value="off">Off</option><option value="nostock">On sale, no stock</option></select>' +
          '<select class="cc-sel" id="pIs"><option value="">Any issue or none</option><option value="any">Has an issue</option>' + Object.keys(issues).map(function (i) { return '<option value="' + esc(i) + '"' + (st.issue === i ? ' selected' : '') + '>' + esc(i) + ' (' + issues[i] + ')</option>'; }).join('') + '</select>' +
          '<button type="button" class="cc-btn pri" id="pAdd">+ Add product</button><button type="button" class="cc-btn" id="pExp">Export Excel</button><a class="cc-btn" href="#ad-import">Import / bulk price update</a></div>' +
          '<div class="ad-bulk" id="pBulk" hidden><b id="pBulkN"></b> selected <button type="button" class="cc-btn sm" data-bulk="1">Switch on</button><button type="button" class="cc-btn sm" data-bulk="0">Switch off</button><button type="button" class="cc-btn sm" data-bulk="x">Clear selection</button></div>' + slot('t'), { flush: true }) + '</div>';
      var fillCats = function () { $('pCat').innerHTML = '<option value="">All sub categories</option>' + m.categories.filter(function (c) { return !st.main || c.main === st.main; }).map(function (c) { return '<option value="' + esc(c.name) + '">' + esc(c.name) + '</option>'; }).join(''); };
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
          { k: 'main', t: 'Category', f: function (p) { return esc(p.main || '–'); } },
          { k: 'category', t: 'Sub category', f: function (p) { return esc(p.category || '–'); } },
          { k: 'mrp', t: 'MRP', n: 1, f: function (p) { return money2(p.mrp); } },
          { k: 'price', t: 'Price', n: 1, f: function (p) { return '<b>' + money2(p.price) + '</b>' + (p.off ? '<small>' + p.off + '% off</small>' : ''); } },
          { k: 'margin', t: 'Margin', n: 1, v: function (p) { return p.margin == null ? -999 : p.margin; }, f: function (p) { return p.margin == null ? '<span class="muted">no cost</span>' : '<span class="' + (p.margin < 0 ? 'down' : '') + '">' + p.margin + '%</span><small>cost ' + money2(p.cost) + '</small>'; } },
          { k: 'live', t: 'Live stock', n: 1, f: function (p) { return p.live > 0 ? '<b>' + esc(S.units === 'p' ? num(p.live) + ' pk' : p.live_text) + '</b><small>' + num(p.hubs, 0) + ' hub(s)</small>' : '<span class="down">0</span>'; } },
          { k: 'active', t: 'On sale', v: function (p) { return p.active ? 1 : 0; }, c: function (p) { return p.active ? 'On' : 'Off'; }, f: function (p) { return sw(p.active, 'data-tg="' + p.id + '"'); } },
          { k: 'issues', t: 'Check', v: function (p) { return p.issues.length; }, c: function (p) { return p.issues.join('; '); }, f: function (p) { return p.issues.map(function (i) { return pill(i, /price|MRP|missing/i.test(i) ? 'p-bad' : 'p-warn'); }).join(' ') || '<span class="muted">ok</span>'; } },
          { k: '_a', t: '', nosort: 1, csv: false, f: function (p) { return '<div class="ad-acts"><button type="button" class="cc-btn" data-ed="' + p.id + '">Edit</button><button type="button" class="cc-btn ad-del" data-del="' + p.id + '">Delete</button></div>'; } }
        ], rows, { csv: 'freshful-products.csv', per: 50, click: function (p) { productForm(p.id, null, reload); }, after: function (w) {
          w.querySelectorAll('[data-ed]').forEach(function (b) { b.addEventListener('click', function (e) { e.stopPropagation(); productForm(+b.getAttribute('data-ed'), null, reload); }); });
          w.querySelectorAll('[data-del]').forEach(function (b) { b.addEventListener('click', function (e) { e.stopPropagation(); var p = r.rows.filter(function (x) { return String(x.id) === b.getAttribute('data-del'); })[0];
            confirmDelete({ what: 'this product', name: p.name + ' ' + p.pack + ' (' + p.code + ')', path: '/ad/products/' + p.id + '/delete', strong: true, done: reload,
              off: p.active ? function () { post('/ad/products/' + p.id + '/toggle', {}).then(function (x) { toast(x.message, x.ok ? 'ok' : 'bad'); reload(); }); } : null }); }); });
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
      card('2. Add new products', '<ol class="ad-steps"><li><button type="button" class="cc-btn sm" id="iTpl">Download the template</button>.</li><li>One row per pack size. The category and sub category must already exist; a new product name is added to that sub category.</li><li>Upload it. New products are added <b>switched off</b>: add photos, check them, then switch them on.</li></ol>' +
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
        var tbl = mode === 'import' ? T([{ k: 'line', t: 'Row', n: 1 }, { k: 'category', t: 'Sub category' }, { k: 'item', t: 'Product name', f: function (x) { return esc(x.item) + (x.new_item ? ' ' + pill('new name', 'p-info') : ''); } }, { k: 'pack', t: 'Pack', f: function (x) { return esc(x.pack) + (x.suspect ? ' ' + pill('check', 'p-warn') : ''); } }, { k: 'mrp', t: 'MRP', n: 1 }, { k: 'price', t: 'Price', n: 1 }], list, { per: 20 })
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

  /* ------------------------------------------------------------------ maps: Google Maps (the website's key) or, without a key, OpenStreetMap */
  var MAPLIB = null;
  function loadMaps() {
    if (MAPLIB) return MAPLIB;
    MAPLIB = new Promise(function (ok, bad) {
      var timer = setTimeout(function () { MAPLIB = null; bad(new Error('map')); }, 12000);
      if (C.mapsKey) {
        if (window.google && window.google.maps && window.google.maps.Map) { clearTimeout(timer); ok('g'); return; }
        window.__adMapsReady = function () { clearTimeout(timer); ok('g'); };
        var g = document.createElement('script'); g.async = true;
        g.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(C.mapsKey) + '&v=weekly&callback=__adMapsReady';
        g.onerror = function () { clearTimeout(timer); MAPLIB = null; bad(new Error('map')); };
        window.gm_authFailure = function () { toast('Google refused the maps key for this address. Allow freshful.in/admin/* on the key in Google Cloud.', 'bad'); };
        document.head.appendChild(g);
      } else {
        if (window.L && window.L.map) { clearTimeout(timer); ok('l'); return; }
        var css = document.createElement('link'); css.rel = 'stylesheet'; css.href = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css'; document.head.appendChild(css);
        var sc = document.createElement('script'); sc.src = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js';
        sc.onload = function () { clearTimeout(timer); ok('l'); }; sc.onerror = function () { clearTimeout(timer); MAPLIB = null; bad(new Error('map')); };
        document.head.appendChild(sc);
      }
    });
    return MAPLIB;
  }
  /** One small map API for both engines: poly / rect / circle / marker / dot / fit / click. Points are [lat, lng]. */
  function makeMap(box, center, zoom) {
    return loadMaps().then(function (kind) {
      box.innerHTML = '';
      var m = { kind: kind, items: [] };
      if (kind === 'g') {
        var G = google.maps;
        var map = new G.Map(box, { center: { lat: center[0], lng: center[1] }, zoom: zoom || 12, mapTypeControl: true, streetViewControl: false, fullscreenControl: true, clickableIcons: false, gestureHandling: 'greedy' });
        m.map = map;
        var ll = function (p) { return { lat: p[0], lng: p[1] }; }, back = function (x) { return [+x.lat().toFixed(7), +x.lng().toFixed(7)]; };
        m.poly = function (pts, o) {
          o = o || {};
          var pg = new G.Polygon({ map: map, paths: pts.map(ll), strokeColor: o.color || '#EA4A1F', strokeWeight: o.weight || 2, fillColor: o.fill || o.color || '#EA4A1F', fillOpacity: o.opacity == null ? .12 : o.opacity, editable: !!o.editable, clickable: !!(o.onclick || o.editable) });
          if (o.editable && o.onchange) { var path = pg.getPath(), fire = function () { o.onchange(path.getArray().map(back)); }; ['set_at', 'insert_at', 'remove_at'].forEach(function (ev) { G.event.addListener(path, ev, fire); }); }
          if (o.onclick) pg.addListener('click', o.onclick);
          m.items.push(pg); return { remove: function () { pg.setMap(null); }, raw: pg };
        };
        m.line = function (pts, o) { o = o || {}; var pl = new G.Polyline({ map: map, path: pts.map(ll), strokeColor: o.color || '#EA4A1F', strokeWeight: 2, strokeOpacity: .9 }); m.items.push(pl); return { remove: function () { pl.setMap(null); } }; };
        m.rect = function (a, b, o) {
          o = o || {};
          var r = new G.Rectangle({ map: map, bounds: { south: Math.min(a[0], b[0]), north: Math.max(a[0], b[0]), west: Math.min(a[1], b[1]), east: Math.max(a[1], b[1]) }, strokeColor: '#EA4A1F', strokeWeight: 2, fillColor: '#EA4A1F', fillOpacity: .12, editable: !!o.editable, draggable: !!o.editable });
          if (o.onchange) r.addListener('bounds_changed', function () { var bb = r.getBounds(), ne = bb.getNorthEast(), sw = bb.getSouthWest(); o.onchange([+sw.lat().toFixed(7), +sw.lng().toFixed(7)], [+ne.lat().toFixed(7), +ne.lng().toFixed(7)]); });
          m.items.push(r); return { remove: function () { r.setMap(null); } };
        };
        m.circle = function (c, radius, o) {
          o = o || {};
          var ci = new G.Circle({ map: map, center: ll(c), radius: radius, strokeColor: o.color || '#EA4A1F', strokeWeight: o.weight || 2, fillColor: o.fill || o.color || '#EA4A1F', fillOpacity: o.opacity == null ? .1 : o.opacity, editable: !!o.editable, draggable: !!o.editable, clickable: !!(o.onclick || o.editable) });
          if (o.onchange) { var f = function () { o.onchange(back(ci.getCenter()), Math.round(ci.getRadius())); }; ci.addListener('radius_changed', f); ci.addListener('center_changed', f); }
          if (o.onclick) ci.addListener('click', o.onclick);
          m.items.push(ci); return { remove: function () { ci.setMap(null); }, raw: ci };
        };
        m.marker = function (p, o) {
          o = o || {};
          var mk = new G.Marker({ map: map, position: ll(p), draggable: !!o.draggable, title: o.title || '', label: o.label ? { text: o.label, color: '#fff', fontSize: '11px', fontWeight: '700' } : null,
            icon: o.dot ? { path: G.SymbolPath.CIRCLE, scale: o.size || 7, fillColor: o.color || '#2563EB', fillOpacity: 1, strokeColor: o.ring || '#fff', strokeWeight: o.ringW || 2 } : (o.pin ? { path: 'M12 2C8 2 5 5 5 9c0 5.2 7 13 7 13s7-7.8 7-13c0-4-3-7-7-7z', fillColor: o.color || '#EA4A1F', fillOpacity: 1, strokeColor: '#fff', strokeWeight: 1.5, scale: 1.6, anchor: new G.Point(12, 22), labelOrigin: new G.Point(12, 9) } : undefined) });
          if (o.ondrag) mk.addListener('dragend', function () { o.ondrag(back(mk.getPosition())); });
          if (o.onclick) mk.addListener('click', o.onclick);
          m.items.push(mk); return { remove: function () { mk.setMap(null); }, move: function (q) { mk.setPosition(ll(q)); }, raw: mk };
        };
        m.fit = function (pts) { if (!pts.length) return; if (pts.length === 1) { map.setCenter(ll(pts[0])); map.setZoom(14); return; } var b = new G.LatLngBounds(); pts.forEach(function (p) { b.extend(ll(p)); }); map.fitBounds(b, 30); };
        m.click = function (fn) { map.addListener('click', function (e) { fn(+e.latLng.lat().toFixed(7), +e.latLng.lng().toFixed(7)); }); };
        m.clear = function () { m.items.forEach(function (x) { x.setMap(null); }); m.items = []; };
        m.center = function (p, z) { map.setCenter(ll(p)); if (z) map.setZoom(z); };
        m.info = function (p, html) { if (!m._iw) m._iw = new G.InfoWindow(); m._iw.setContent(html); m._iw.setPosition(ll(p)); m._iw.open({ map: map }); };
      } else {
        var Lf = window.L;
        var lm = Lf.map(box, { zoomControl: true }).setView(center, zoom || 12);
        Lf.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(lm);
        m.map = lm; setTimeout(function () { lm.invalidateSize(); }, 150);
        var grp = function (x) { m.items.push(x); return x; };
        m.poly = function (pts, o) { o = o || {}; var pg = grp(Lf.polygon(pts, { color: o.color || '#EA4A1F', weight: o.weight || 2, fillOpacity: o.opacity == null ? .12 : o.opacity }).addTo(lm)); if (o.onclick) pg.on('click', o.onclick); return { remove: function () { lm.removeLayer(pg); }, raw: pg }; };
        m.line = function (pts, o) { var pl = grp(Lf.polyline(pts, { color: (o && o.color) || '#EA4A1F', weight: 2, dashArray: '4 4' }).addTo(lm)); return { remove: function () { lm.removeLayer(pl); } }; };
        m.rect = function (a, b) { var r = grp(Lf.rectangle([a, b], { color: '#EA4A1F', weight: 2, fillOpacity: .12 }).addTo(lm)); return { remove: function () { lm.removeLayer(r); } }; };
        m.circle = function (c, radius, o) { o = o || {}; var ci = grp(Lf.circle(c, { radius: radius, color: o.color || '#EA4A1F', weight: o.weight || 2, fillOpacity: o.opacity == null ? .1 : o.opacity, fillColor: o.fill || o.color || '#EA4A1F' }).addTo(lm)); if (o.onclick) ci.on('click', o.onclick); return { remove: function () { lm.removeLayer(ci); }, raw: ci }; };
        m.marker = function (p, o) {
          o = o || {};
          var mk = o.dot ? Lf.circleMarker(p, { radius: o.size || 7, color: o.ring || '#fff', weight: o.ringW || 2, fillColor: o.color || '#2563EB', fillOpacity: 1 }) : Lf.marker(p, { draggable: !!o.draggable, title: o.title || '' });
          grp(mk.addTo(lm)); if (o.title && o.dot) mk.bindTooltip(o.title);
          if (o.ondrag) mk.on('dragend', function () { var q = mk.getLatLng(); o.ondrag([+q.lat.toFixed(7), +q.lng.toFixed(7)]); });
          if (o.onclick) mk.on('click', o.onclick);
          return { remove: function () { lm.removeLayer(mk); }, move: function (q) { mk.setLatLng(q); }, raw: mk };
        };
        m.fit = function (pts) { if (!pts.length) return; if (pts.length === 1) { lm.setView(pts[0], 14); return; } lm.fitBounds(pts, { padding: [30, 30] }); };
        m.click = function (fn) { lm.on('click', function (e) { fn(+e.latlng.lat.toFixed(7), +e.latlng.lng.toFixed(7)); }); };
        m.clear = function () { m.items.forEach(function (x) { lm.removeLayer(x); }); m.items = []; };
        m.center = function (p, z) { lm.setView(p, z || lm.getZoom()); };
        m.info = function (p, html) { Lf.popup().setLatLng(p).setContent(html).openOn(lm); };
      }
      return m;
    });
  }
  /** Find a place: Google's geocoder when the key allows it, else OpenStreetMap search. */
  function findPlace(q) {
    var osm = function () { return fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=in&q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } }).then(function (r) { return r.json(); }).then(function (j) { return j && j[0] ? [+j[0].lat, +j[0].lon] : null; }).catch(function () { return null; }); };
    if (window.google && google.maps && google.maps.Geocoder) {
      return new Promise(function (ok) { new google.maps.Geocoder().geocode({ address: q, region: 'in' }, function (res, st) { if (st === 'OK' && res[0]) { var l = res[0].geometry.location; ok([l.lat(), l.lng()]); } else osm().then(ok); }); });
    }
    return osm();
  }
  function mapFail(box) { box.innerHTML = '<div class="cc-empty">The map could not load. ' + (C.mapsKey ? 'Check that the Google Maps key allows this address (freshful.in/admin/*).' : 'No Google Maps key was found, and OpenStreetMap could not be reached.') + '</div>'; }
  function drawHubShape(m, h, o) {
    o = o || {}; var col = h.active === false ? '#94A3B8' : (o.color || '#EA4A1F'), pts = [];
    if (h.kind === 'polygon' && h.points && h.points.length >= 3) { m.poly(h.points, { color: col, opacity: o.opacity == null ? .06 : o.opacity, weight: 1.5 }); pts = pts.concat(h.points); }
    else if (h.kind === 'circle' && (h.lat != null || (h.points && h.points[0]))) { var c = h.lat != null ? [h.lat, h.lng] : h.points[0]; m.circle(c, h.radius_m || C.circleKm * 1000, { color: col, opacity: o.opacity == null ? .05 : o.opacity, weight: 1.5 }); pts.push(c); }
    if (h.lat != null && h.lng != null) { m.marker([h.lat, h.lng], { pin: true, color: col, title: h.name }); pts.push([h.lat, h.lng]); }
    return pts;
  }

  /* ------------------------------------------------------------------ hubs and delivery areas */
  V['ad-hubs'] = { t: 'Hubs & delivery areas', dates: false, nofilter: true, run: function (root) {
    return api('/ad/hubs', {}, false).then(function (d) {
      var on = d.rows.filter(function (h) { return h.active; }).length;
      root.innerHTML = '<div class="cc-grid"><div class="cc-kpis">' + kpi('Hubs', num(d.rows.length, 0), num(on, 0) + ' open', 'k-green') + kpi('Drawn areas', num(d.rows.filter(function (h) { return h.kind === 'polygon'; }).length, 0), 'polygon or box', 'k-blue') +
        kpi('Circles', num(d.rows.filter(function (h) { return h.kind === 'circle'; }).length, 0), 'radius around the hub', 'k-purple') + kpi('Need fixing', num(d.rows.filter(function (h) { return h.issues.length; }).length, 0), 'see the Check column', d.rows.some(function (h) { return h.issues.length; }) ? 'warn' : 'k-slate') + '</div>' +
        card('All hubs on the map', '<div class="ad-map" id="hAll"><div class="cc-load"><i></i>Loading map…</div></div>', { flush: true }) +
        card('Hubs', '<div class="ad-tools"><input class="cc-in" id="hQ" placeholder="Search hub or city…" style="flex:1;min-width:180px"><button type="button" class="cc-btn pri" id="hAdd">+ Add hub</button></div>' + slot('t'), { flush: true }) + '</div>';
      var reload = function () { V['ad-hubs'].run(root); };
      makeMap($('hAll'), [12.97, 77.59], 7).then(function (m) { var pts = []; d.rows.forEach(function (h) { pts = pts.concat(drawHubShape(m, h, { opacity: .1 })); }); m.fit(pts); }).catch(function () { mapFail($('hAll')); });
      var q = '';
      function draw() {
        var rows = d.rows.filter(function (h) { return !q || (h.name + ' ' + h.city).toLowerCase().indexOf(q) >= 0; });
        var n = T([{ k: 'name', t: 'Hub', f: function (h) { return '<b>' + esc(h.name) + '</b><small>#' + h.id + '</small>'; } }, { k: 'city', t: 'City' },
          { k: 'kind', t: 'Delivery area', f: function (h) { return h.kind === 'polygon' ? (h.drawn_as === 'box' ? 'Box' : 'Polygon') + ' · ' + h.points.length + ' corners' : h.kind === 'circle' ? 'Circle · ' + num((h.radius_m || C.circleKm * 1000) / 1000, 1) + ' km' : '<span class="down">none</span>'; } },
          { k: 'hub_users', t: 'Staff', n: 1, v: function (h) { return h.hub_users + h.pos_users; }, f: function (h) { return num(h.hub_users, 0) + ' hub · ' + num(h.pos_users, 0) + ' POS'; } }, { k: 'riders', t: 'Riders', n: 1 }, { k: 'addresses', t: 'Customer addresses', n: 1 },
          { k: 'active', t: 'Open', v: function (h) { return h.active ? 1 : 0; }, c: function (h) { return h.active ? 'Open' : 'Closed'; }, f: function (h) { return sw(h.active, 'data-tg="' + h.id + '"'); } },
          { k: 'issues', t: 'Check', v: function (h) { return h.issues.length; }, c: function (h) { return h.issues.join('; '); }, f: function (h) { return h.issues.map(function (i) { return pill(i, /No delivery area|No hub/.test(i) ? 'p-bad' : 'p-warn'); }).join(' ') || '<span class="muted">ok</span>'; } },
          { k: '_a', t: '', nosort: 1, csv: false, f: function (h) { return '<div class="ad-acts"><button type="button" class="cc-btn" data-ed="' + h.id + '">' + (h.kind ? 'Edit area' : 'Draw area') + '</button></div>'; } }],
          rows, { csv: 'freshful-hubs.csv', click: function (h) { hubEditor(h, d, reload); }, after: function (w) {
            w.querySelectorAll('[data-ed]').forEach(function (b) { b.addEventListener('click', function (e) { e.stopPropagation(); hubEditor(d.rows.filter(function (x) { return String(x.id) === b.getAttribute('data-ed'); })[0], d, reload); }); });
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

  /** Hub editor: Polygon / Box / Circle / Point, like the Fastic area editor. */
  function hubEditor(h, d, after) {
    var kindOf = function () { if (!h || !h.kind) return 'polygon'; if (h.kind === 'circle') return 'circle'; return h.drawn_as === 'box' ? 'box' : 'polygon'; };
    var st = { kind: kindOf(), points: h ? h.points.slice() : [], lat: h ? h.lat : null, lng: h ? h.lng : null, radius: (h && h.radius_m) || C.circleKm * 1000, boxA: null };
    if (h && h.centre && h.issues.join(' ').indexOf('corner of the area') >= 0) { st.lat = +h.centre[0].toFixed(7); st.lng = +h.centre[1].toFixed(7); }
    if (st.kind === 'circle' && st.lat == null && st.points[0]) { st.lat = st.points[0][0]; st.lng = st.points[0][1]; }
    var K4 = [['polygon', 'Polygon', '<path d="M12 3l9 6-3.5 11h-11L3 9z"/>'], ['box', 'Box', '<rect x="4" y="5" width="16" height="14" rx="1.5"/>'], ['circle', 'Circle', '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="1.5"/>'], ['point', 'Point', '<circle cx="12" cy="12" r="3"/><circle cx="12" cy="12" r="8" stroke-dasharray="3 3"/>']];
    var body = el('<div class="ad-form"><div class="ad-mapkinds">' + K4.map(function (k) { return '<button type="button" data-k="' + k[0] + '"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + k[2] + '</svg>' + k[1] + '</button>'; }).join('') + '<span style="margin-left:auto" class="pill p-mute" id="hmCnt"></span></div>' +
      '<div class="ad-3"><label class="cc-f"><span>Hub name *</span><input class="cc-in" name="name" maxlength="100" value="' + esc(h ? h.name : '') + '"></label>' +
      '<label class="cc-f"><span>City *</span><select class="cc-sel" name="city_id"><option value="">Choose…</option>' + d.cities.map(function (c) { return '<option value="' + c.id + '"' + (h && h.city_id === c.id ? ' selected' : '') + '>' + esc(c.name) + '</option>'; }).join('') + '</select></label>' +
      '<label class="cc-f" id="hmRadW"><span>Circle radius (km)</span><input class="cc-in" type="number" min="0.3" max="30" step="0.1" id="hmRad"></label></div>' +
      '<div class="ad-tools"><input class="cc-in" id="hmFind" placeholder="Find a place on the map (area, street, landmark)" style="flex:1;min-width:200px"><button type="button" class="cc-btn" id="hmFindGo">Find</button></div>' +
      '<p class="cc-note" id="hmHelp"></p>' +
      '<div class="ad-maptools"><button type="button" class="cc-btn sm" id="hmUndo">Remove last corner</button><button type="button" class="cc-btn sm" id="hmClear">Start again</button><button type="button" class="cc-btn sm" id="hmMid">Put hub pin in the middle</button></div>' +
      '<div class="ad-map tall" id="hmMap"><div class="cc-load"><i></i>Loading map…</div></div>' +
      '<details class="ad-det"><summary>Type the points instead (one "latitude, longitude" per line)</summary><label class="cc-f"><span>Hub pin</span><input class="cc-in" id="hmPin" placeholder="12.9716, 77.5946"></label><label class="cc-f"><span>Area corners</span><textarea id="hmPts" rows="5" placeholder="12.97, 77.59&#10;12.98, 77.60&#10;12.96, 77.61"></textarea></label><button type="button" class="cc-btn sm" id="hmApply">Use these points</button></details>' +
      '<div class="ad-checks"><label class="ad-check"><input type="checkbox" name="active"' + (h && h.active ? ' checked' : '') + '> Open for orders</label><label class="ad-check"><input type="checkbox" name="confirm_outside"> The hub is outside its area</label></div>' +
      (h && h.issues.length ? '<p class="cc-note">' + h.issues.map(function (i) { return pill(i, 'p-warn'); }).join(' ') + '</p>' : '') + '</div>');
    var M = null, shapes = [], pin = null;
    var q = function (s) { return body.querySelector(s); };
    function centre() { var a = 0, cx = 0, cy = 0, n = st.points.length; if (n < 3) return null; for (var i = 0; i < n; i++) { var p = st.points[i], w = st.points[(i + 1) % n], f = p[1] * w[0] - w[1] * p[0]; a += f; cx += (p[1] + w[1]) * f; cy += (p[0] + w[0]) * f; } if (Math.abs(a) < 1e-12) return [st.points.reduce(function (s, p) { return s + p[0]; }, 0) / n, st.points.reduce(function (s, p) { return s + p[1]; }, 0) / n]; return [cy / (3 * a), cx / (3 * a)]; }
    function txt() { q('#hmPin').value = st.lat != null ? st.lat + ', ' + st.lng : ''; q('#hmPts').value = st.points.map(function (p) { return p[0] + ', ' + p[1]; }).join('\n'); q('#hmRad').value = (st.radius / 1000).toFixed(1); }
    function ui() {
      body.querySelectorAll('.ad-mapkinds button').forEach(function (b) { b.classList.toggle('is-on', b.getAttribute('data-k') === st.kind); });
      var help = { polygon: 'Click the map to place the corners one after another (at least 3). Drag a corner to move it; drag the orange pin to the hub.', box: 'Click two opposite corners of the box. Drag its edges to resize.', circle: 'Click the map (or drag the pin) to set the hub; drag the circle edge or type the radius. Customers inside are served.', point: 'Drag the pin (or click the map) to set the exact hub location. The delivery area stays as it is.' }[st.kind];
      q('#hmHelp').textContent = help;
      q('#hmUndo').hidden = st.kind !== 'polygon'; q('#hmClear').hidden = st.kind === 'point'; q('#hmMid').hidden = st.kind === 'circle' || st.kind === 'point'; q('#hmRadW').hidden = st.kind !== 'circle';
      q('#hmCnt').textContent = st.kind === 'circle' ? 'radius ' + (st.radius / 1000).toFixed(1) + ' km' : st.kind === 'point' ? (st.lat != null ? 'pin set' : 'no pin') : st.points.length + ' corners';
    }
    function redraw() {
      txt(); ui(); if (!M) return;
      shapes.forEach(function (x) { x.remove(); }); shapes = [];
      if ((st.kind === 'polygon' || st.kind === 'box' || st.kind === 'point') && st.points.length) {
        if (st.points.length >= 3) shapes.push(M.poly(st.points, { editable: st.kind === 'polygon', opacity: .14, onchange: function (p) { st.points = p; txt(); ui(); } }));
        else shapes.push(M.line(st.points));
        if (st.kind === 'polygon') st.points.forEach(function (p, i) { if (st.points.length < 3) shapes.push(M.marker(p, { dot: true, size: 5, color: '#EA4A1F', title: 'Corner ' + (i + 1) })); });
      }
      if (st.kind === 'box' && st.boxA) shapes.push(M.marker(st.boxA, { dot: true, size: 6, color: '#EA4A1F', title: 'First corner' }));
      if (st.kind === 'circle' && st.lat != null) shapes.push(M.circle([st.lat, st.lng], st.radius, { editable: M.kind === 'g', onchange: function (c, r) { st.lat = c[0]; st.lng = c[1]; st.radius = r; txt(); ui(); if (pin) pin.move(c); } }));
      if (st.lat != null) { if (!pin) pin = M.marker([st.lat, st.lng], { draggable: true, pin: true, color: '#EA4A1F', title: 'Hub', ondrag: function (p) { st.lat = p[0]; st.lng = p[1]; if (st.kind === 'circle') redraw(); else { txt(); ui(); } } }); else pin.move([st.lat, st.lng]); }
    }
    body.querySelectorAll('.ad-mapkinds button').forEach(function (b) { b.addEventListener('click', function () {
      var k = b.getAttribute('data-k');
      if (k === st.kind) return;
      if (k === 'box') { st.points = []; st.boxA = null; }
      if (k === 'polygon' && st.kind === 'box') { /* keep the box corners as a polygon */ }
      if (k === 'circle' && st.lat == null) { var c = centre() || st.points[0]; if (c) { st.lat = +c[0].toFixed(7); st.lng = +c[1].toFixed(7); } }
      if (k === 'point' && h && h.points.length >= 3 && h.kind === 'polygon') st.points = h.points.slice();
      st.kind = k; redraw();
    }); });
    q('#hmUndo').addEventListener('click', function () { st.points.pop(); redraw(); });
    q('#hmClear').addEventListener('click', function () { st.points = []; st.boxA = null; if (st.kind === 'circle') { st.lat = null; st.lng = null; if (pin) { pin.remove(); pin = null; } } redraw(); });
    q('#hmMid').addEventListener('click', function () { var c = centre(); if (!c) { toast('Draw the area first (at least 3 corners).', 'bad'); return; } st.lat = +c[0].toFixed(7); st.lng = +c[1].toFixed(7); redraw(); });
    q('#hmRad').addEventListener('change', function () { var v = +this.value; if (v >= 0.3 && v <= 30) { st.radius = Math.round(v * 1000); redraw(); } else toast('Radius: between 0.3 and 30 km.', 'bad'); });
    var doFind = function () { var t = q('#hmFind').value.trim(); if (!t) return; q('#hmFindGo').textContent = 'Finding…'; findPlace(t + ', India').then(function (p) { q('#hmFindGo').textContent = 'Find'; if (!p) { toast('Place not found. Try another name.', 'bad'); return; } if (M) M.center(p, 15); }); };
    q('#hmFindGo').addEventListener('click', doFind); q('#hmFind').addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); doFind(); } });
    q('#hmApply').addEventListener('click', function () {
      var pts = q('#hmPts').value.split(/\n+/).map(function (l) { var m = l.match(/(-?\d+(?:\.\d+)?)\s*[, ]\s*(-?\d+(?:\.\d+)?)/); return m ? [+m[1], +m[2]] : null; }).filter(Boolean);
      var pm = q('#hmPin').value.match(/(-?\d+(?:\.\d+)?)\s*[, ]\s*(-?\d+(?:\.\d+)?)/);
      if (st.kind !== 'circle' && st.kind !== 'point') st.points = pts; if (pm) { st.lat = +pm[1]; st.lng = +pm[2]; }
      redraw(); if (M) M.fit(st.points.length ? st.points : (st.lat != null ? [[st.lat, st.lng]] : [])); toast('Points applied.', 'ok');
    });
    modal(h ? 'Edit ' + h.name : 'Add hub', body, [{ t: 'Save area', cls: 'pri', fn: function (b) {
      var v = { name: q('[name=name]').value, city_id: q('[name=city_id]').value, kind: st.kind, points: st.points, lat: st.lat, lng: st.lng, radius_m: st.radius, active: q('[name=active]').checked, confirm_outside: q('[name=confirm_outside]').checked ? 1 : 0 };
      if (st.kind === 'box' && st.points.length !== 4) { toast('Click two opposite corners to draw the box.', 'bad'); return; }
      act(b, '/ad/hubs/save', { id: h ? h.id : '', values: v }, function () { closeModal(); after(); });
    } }], true);
    txt(); ui();
    var start = st.lat != null ? [st.lat, st.lng] : (st.points[0] || (d.rows.filter(function (x) { return x.lat != null; })[0] ? [d.rows.filter(function (x) { return x.lat != null; })[0].lat, d.rows.filter(function (x) { return x.lat != null; })[0].lng] : [12.97, 77.59]));
    makeMap(q('#hmMap'), start, 14).then(function (m) {
      M = m;
      d.rows.forEach(function (o) { if (!h || o.id !== h.id) drawHubShape(m, o, { color: '#64748B', opacity: .03 }); });
      m.click(function (la, ln) {
        if (st.kind === 'polygon') { st.points.push([la, ln]); if (st.lat == null && st.points.length >= 3) { var c = centre(); st.lat = +c[0].toFixed(7); st.lng = +c[1].toFixed(7); } }
        else if (st.kind === 'box') { if (!st.boxA || st.points.length === 4) { st.boxA = [la, ln]; st.points = []; } else { var a = st.boxA; st.points = [[Math.min(a[0], la), Math.min(a[1], ln)], [Math.min(a[0], la), Math.max(a[1], ln)], [Math.max(a[0], la), Math.max(a[1], ln)], [Math.max(a[0], la), Math.min(a[1], ln)]]; st.boxA = null; if (st.lat == null) { var c2 = centre(); st.lat = +c2[0].toFixed(7); st.lng = +c2[1].toFixed(7); } } }
        else { st.lat = la; st.lng = ln; }
        redraw();
      });
      redraw();
      var all = st.points.slice(); if (st.lat != null) all.push([st.lat, st.lng]); if (all.length > 1) m.fit(all);
    }).catch(function () { mapFail(q('#hmMap')); q('details').open = true; });
  }

  /* ------------------------------------------------------------------ god view: every hub, open order and delivery boy on one live map */
  var STAGE_COL = { 'new': '#2563EB', accepted: '#D97706', out: '#7C3AED' };
  V['ad-god'] = { t: 'God view (live map)', dates: false, run: function (root) {
    root.innerHTML = '<div class="cc-grid"><div class="cc-kpis" id="gvK"></div><div class="ad-split"><div class="cc-card"><div class="cc-body flush"><div class="ad-map tall" id="gvMap"><div class="cc-load"><i></i>Loading map…</div></div></div></div><div id="gvSide"></div></div><p class="cc-note">Refreshes by itself every 30 seconds.</p></div>';
    var mapEl = root.querySelector('#gvMap'), M = null, fitted = false, data = null;
    function side(d) {
      var late = d.orders.filter(function (o) { return o.late; });
      var cnt = function (s) { return d.orders.filter(function (o) { return o.stage === s; }).length; };
      var k = root.querySelector('#gvK');
      k.innerHTML = kpi('Open orders', num(d.orders.length, 0), num(d.with_location, 0) + ' with a map location', 'k-blue', 'ad-dispatch') + kpi('New', num(cnt('new'), 0), 'waiting to be accepted', 'k-indigo', 'ad-dispatch') + kpi('Accepted', num(cnt('accepted'), 0), 'packing / waiting for a rider', 'k-amber', 'ad-dispatch') +
        kpi('Out for delivery', num(cnt('out'), 0), '', 'k-purple', 'ad-dispatch') + kpi('Late', num(late.length, 0), 'past their due time', late.length ? 'bad' : 'k-green', 'ad-dispatch') +
        kpi('Delivery boys', num(d.riders.length, 0), d.rider_source ? num(d.riders_located, 0) + ' seen on the map' : 'no live location yet', 'k-teal', 'ad-riders-live');
      K.wireGo(k);
      var sd = root.querySelector('#gvSide');
      sd.innerHTML = card('Legend', '<div class="ad-legend"><span><i style="background:#2563EB"></i>New</span><span><i style="background:#D97706"></i>Accepted</span><span><i style="background:#7C3AED"></i>Out</span><span><i style="background:#DC2626"></i>Late (red ring)</span><span><i style="background:#0E9F6E"></i>Delivery boy</span><span><i style="background:#EA4A1F"></i>Hub</span></div>' +
        (d.rider_source ? '' : '<p class="cc-note" style="margin-top:10px">Delivery boy positions appear when the rider app starts sending its location.</p>') + (d.orders.length - d.with_location ? '<p class="cc-note" style="margin-top:8px">' + (d.orders.length - d.with_location) + ' open order(s) have no map location on their address.</p>' : '')) +
        card('Late now (' + late.length + ')', late.length ? '<div class="ad-thread">' + late.slice(0, 30).map(function (o) { return '<div class="ad-oc is-late" data-o="' + esc(o.id) + '"><div class="ad-oc-r"><b>' + esc(o.id) + '</b>' + K.stagePill(o.stage) + '</div><span>' + esc(o.customer || '') + ' · ' + esc(o.hub_name) + '</span><small class="down">due ' + dt(o.due) + '</small></div>'; }).join('') + '</div>' : '<div class="cc-empty">Nothing late.</div>');
      sd.querySelectorAll('[data-o]').forEach(function (x) { x.addEventListener('click', function () { K.orderDrawer(x.getAttribute('data-o')); }); });
    }
    function paint(d) {
      if (!M) return; M.clear(); var pts = [];
      d.hubs.forEach(function (h) { pts = pts.concat(drawHubShape(M, h)); });
      d.orders.forEach(function (o) { if (o.lat == null) return; pts.push([o.lat, o.lng]); M.marker([o.lat, o.lng], { dot: true, size: 7, color: STAGE_COL[o.stage] || '#64748B', ring: o.late ? '#DC2626' : '#fff', ringW: o.late ? 3 : 2, title: o.id + ' · ' + (o.customer || '') + ' · ' + o.stage + (o.late ? ' · LATE' : ''), onclick: function () { K.orderDrawer(o.id); } }); });
      d.riders.forEach(function (r) { if (r.lat == null) return; pts.push([r.lat, r.lng]); var age = r.seen ? Math.round((Date.now() / 1000 - r.seen) / 60) : null; M.marker([r.lat, r.lng], { dot: true, size: 9, color: age != null && age > 15 ? '#94A3B8' : '#0E9F6E', title: r.name + ' · ' + r.hub_name + (age != null ? ' · seen ' + age + ' min ago' : '') + (r.orders.length ? ' · carrying ' + r.orders.join(', ') : '') }); });
      if (!fitted) { M.fit(pts); fitted = true; }
    }
    function update() { return api('/ad/live/god').then(function (d) { data = d; side(d); paint(d); }); }
    if (S.timer) clearInterval(S.timer);
    S.timer = setInterval(function () { if (S.view === 'ad-god' && !document.hidden && document.body.contains(mapEl)) update().catch(function () {}); }, 30000);
    return update().then(function () {
      makeMap(mapEl, [12.97, 77.59], 11).then(function (m) { M = m; paint(data); }).catch(function () { mapFail(mapEl); });
    });
  } };

  /* ------------------------------------------------------------------ order heat map */
  V['ad-heat'] = { t: 'Order heat map', dates: true, run: function (root) {
    return api('/ad/live/heat').then(function (d) {
      var max = d.cells.reduce(function (m, c) { return Math.max(m, c.orders); }, 1);
      var tone = function (n) { var r = n / max; return r > .66 ? '#DC2626' : r > .33 ? '#EA580C' : r > .12 ? '#F59E0B' : '#FACC15'; };
      var totV = d.cells.reduce(function (s, c) { return s + c.value; }, 0);
      root.innerHTML = '<div class="cc-grid"><div class="cc-kpis">' + kpi('Orders', num(d.total, 0), 'not cancelled, in these dates', 'k-blue') + kpi('On the map', num(d.located, 0), d.total ? Math.round(d.located * 100 / d.total) + '% have an address location' : '', 'k-green') +
        kpi('Busy spots', num(d.cells.length, 0), 'squares of about 100 m', 'k-orange') + kpi('Value', money(totV), '', 'k-purple') + '</div>' +
        '<div class="ad-split"><div class="cc-card"><div class="cc-body flush"><div class="ad-map tall" id="htMap"><div class="cc-load"><i></i>Loading map…</div></div></div></div><div>' +
        card('Busiest spots', d.cells.length ? T([{ k: 'orders', t: 'Orders', n: 1 }, { k: 'customers', t: 'Customers', n: 1 }, { k: 'value', t: 'Value', n: 1, f: function (c) { return money(c.value); } }], d.cells.slice(0, 50), { noPage: 1, click: function (c) { if (S.p.htMap) S.p.htMap.center([c.lat, c.lng], 16); } }).outerHTML : '<div class="cc-empty">No located orders in these dates.</div>', { flush: true }) +
        card('Legend', '<div class="ad-legend"><span><i style="background:#FACC15"></i>Few</span><span><i style="background:#F59E0B"></i>Some</span><span><i style="background:#EA580C"></i>Many</span><span><i style="background:#DC2626"></i>Most</span></div><p class="cc-note" style="margin-top:8px">Bigger circle = more orders. Use it to plan new hubs, rider shifts and local offers.</p>') + '</div></div></div>';
      var tb = root.querySelector('.ad-split .cc-t'); if (tb) tb.querySelectorAll('tbody tr').forEach(function (tr, i) { tr.classList.add('click'); tr.addEventListener('click', function () { var c = d.cells[i]; if (S.p.htMap && c) S.p.htMap.center([c.lat, c.lng], 16); }); });
      return makeMap(root.querySelector('#htMap'), [12.97, 77.59], 11).then(function (m) {
        S.p.htMap = m; var pts = [];
        d.hubs.forEach(function (h) { pts = pts.concat(drawHubShape(m, h, { color: '#475569', opacity: .03 })); });
        d.cells.forEach(function (c) { pts.push([c.lat, c.lng]); m.circle([c.lat, c.lng], 60 + 140 * Math.sqrt(c.orders / max), { color: tone(c.orders), fill: tone(c.orders), opacity: .55, weight: 0, onclick: function () { m.info([c.lat, c.lng], '<b>' + c.orders + ' orders</b><br>' + c.customers + ' customers<br>' + money(c.value)); } }); });
        m.fit(pts);
      }).catch(function () { mapFail(root.querySelector('#htMap')); });
    });
  } };

  /* ------------------------------------------------------------------ delivery boys: live locations */
  V['ad-riders-live'] = { t: 'Delivery boys: live locations', dates: false, run: function (root) {
    return api('/ad/live/god').then(function (d) {
      var located = d.riders.filter(function (r) { return r.lat != null; });
      root.innerHTML = '<div class="cc-grid"><div class="cc-kpis">' + kpi('Delivery boys', num(d.riders.length, 0), num(d.riders.filter(function (r) { return r.active; }).length, 0) + ' active', 'k-teal') + kpi('On the map', num(located.length, 0), d.rider_source ? 'from ' + esc(d.rider_source) : 'the rider app sends no location yet', located.length ? 'k-green' : 'k-slate') +
        kpi('Carrying orders', num(d.riders.filter(function (r) { return r.orders.length; }).length, 0), num(d.orders.filter(function (o) { return o.stage === 'out'; }).length, 0) + ' orders out', 'k-purple') + '</div>' +
        (d.rider_source ? '' : card('Live location is not switched on yet', '<p class="cc-note ad-help">No delivery boy location is stored in the database yet. As soon as the rider app saves its position (the app\'s location API), every delivery boy appears on this map and in God view, refreshed every 30 seconds.</p>')) +
        card('Map', '<div class="ad-map tall" id="rlMap"><div class="cc-load"><i></i>Loading map…</div></div>', { flush: true }) + card('Delivery boys', slot('t'), { flush: true }) + '</div>';
      mount(root, 't', T([{ k: 'name', t: 'Name', f: function (r) { return '<b>' + esc(r.name) + '</b><small>' + esc(r.phone) + '</small>'; } }, { k: 'hub_name', t: 'Hub' },
        { k: 'seen', t: 'Last seen', n: 1, f: function (r) { return r.seen ? K.ago(r.seen) : (r.lat != null ? 'yes' : '<span class="muted">never</span>'); } },
        { k: 'orders', t: 'Carrying', v: function (r) { return r.orders.length; }, f: function (r) { return r.orders.length ? esc(r.orders.join(', ')) : '<span class="muted">nothing</span>'; } },
        { k: 'active', t: 'Status', v: function (r) { return r.active ? 1 : 0; }, f: function (r) { return r.active ? pill('Active', 'p-ok') : pill('Off'); } }], d.riders, { csv: 'freshful-riders-live.csv', click: function (r) { if (r.lat != null && S.p.rlMap) S.p.rlMap.center([r.lat, r.lng], 16); } }));
      return makeMap(root.querySelector('#rlMap'), [12.97, 77.59], 11).then(function (m) {
        S.p.rlMap = m; var pts = [];
        d.hubs.forEach(function (h) { pts = pts.concat(drawHubShape(m, h, { opacity: .04 })); });
        located.forEach(function (r) { pts.push([r.lat, r.lng]); var age = r.seen ? Math.round((Date.now() / 1000 - r.seen) / 60) : null; m.marker([r.lat, r.lng], { dot: true, size: 9, color: age != null && age > 15 ? '#94A3B8' : '#0E9F6E', title: r.name + (age != null ? ' · ' + age + ' min ago' : '') }); });
        m.fit(pts);
      }).catch(function () { mapFail(root.querySelector('#rlMap')); });
    });
  } };

  /* ------------------------------------------------------------------ live dispatch: every hub's board in one place */
  V['ad-dispatch'] = { t: 'Live dispatch', dates: false, auto: 20, run: function (root) {
    return api('/ad/live/dispatch').then(function (d) {
      var c = d.counts, byHubRiders = {};
      d.riders.forEach(function (r) { (byHubRiders[r.hub_id] = byHubRiders[r.hub_id] || []).push(r); });
      var cols = [['new', 'New: accept or reject', '#2563EB'], ['accepted', 'Accepted: give to a rider', '#D97706'], ['ready', 'Weighed and ready', '#0891B2'], ['out', 'Out for delivery', '#7C3AED']];
      var card1 = function (o) {
        var acts = [];
        if (o.stage === 'new') acts.push('<button type="button" class="cc-btn pri" data-a="accept">Accept</button><button type="button" class="cc-btn ad-del" data-a="reject">Reject</button>');
        if (o.stage === 'accepted') acts.push('<button type="button" class="cc-btn pri" data-a="smart">Dispatch (smart pick)</button><button type="button" class="cc-btn" data-a="pick">Choose rider</button>');
        if (o.stage === 'out') acts.push('<button type="button" class="cc-btn" data-a="reassign">Change rider</button>');
        if (o.stage !== 'new') acts.push('<button type="button" class="cc-btn ad-del" data-a="cancel">Cancel</button>');
        return '<div class="ad-oc' + (o.late ? ' is-late' : '') + '" data-o="' + esc(o.order_id) + '"><div class="ad-oc-r"><b>' + esc(o.order_id) + '</b><span>' + money(o.amount) + '</span></div>' +
          '<div class="ad-oc-r"><span>' + esc(o.customer) + '</span>' + pill(o.delivery_type + (o.slot ? ' ' + o.slot : ''), o.delivery_type === 'Express' ? 'p-info' : 'p-mute') + '</div>' +
          '<small>' + esc(o.hub) + (o.area ? ' · ' + esc(o.area) : '') + ' · ' + esc(o.payment_type) + '</small>' +
          '<small class="' + (o.late ? 'down' : '') + '">' + (o.late ? 'LATE · ' : '') + 'due ' + dt(o.due_at) + (o.rider ? ' · rider ' + esc(o.rider) : '') + '</small>' +
          (o.stage === 'accepted' && !o.weighed ? '<small class="ad-wait">Hub must weigh the items before dispatch (if the order has loose items)</small>' : '') +
          '<div class="ad-acts">' + acts.join('') + '</div></div>';
      };
      root.innerHTML = '<div class="cc-grid"><div class="cc-kpis">' + kpi('New', num(c['new'] || 0, 0), 'waiting to be accepted', (c['new'] ? 'warn' : 'k-blue')) + kpi('To dispatch', num((c.accepted || 0) + (c.ready || 0), 0), 'accepted, no rider yet', 'k-amber') +
        kpi('Out for delivery', num(c.out || 0, 0), c.no_rider ? c.no_rider + ' without a rider' : '', 'k-purple') + kpi('Late', num(c.late || 0, 0), 'past the due time', c.late ? 'bad' : 'k-green') + kpi('Free riders', num(c.riders_free || 0, 0), 'of ' + num(c.riders || 0, 0) + ' at their hubs', 'k-teal') + '</div>' +
        '<div class="ad-board">' + cols.map(function (k) { var list = d.orders.filter(function (o) { return o.board === k[0]; }); return '<section class="ad-col"><h3><span class="ad-tile" style="--c:' + k[2] + ';width:24px;height:24px"></span>' + esc(k[1]) + '<em>' + list.length + '</em></h3>' + (list.length ? list.map(card1).join('') : '<div class="cc-empty" style="padding:18px 6px">Nothing here.</div>') + '</section>'; }).join('') + '</div>' +
        card('Delivery boys at the hubs', T([{ k: 'name', t: 'Rider', f: function (r) { return '<b>' + esc(r.name) + '</b>' + (r.smart ? ' ' + pill('next pick', 'p-ok') : ''); } }, { k: 'hub', t: 'Hub' },
          { k: 'state', t: 'Now', f: function (r) { return pill({ available: 'At hub, free', out: 'Out (' + r.out + ')', returning: 'Coming back', off: 'Off duty' }[r.state] || r.state, { available: 'p-ok', out: 'p-info', returning: 'p-warn', off: 'p-mute' }[r.state]); } },
          { k: 'delivered_today', t: 'Delivered today', n: 1 }, { k: 'cash_due', t: 'Cash to hand over', n: 1, f: function (r) { return r.cash_due ? money(r.cash_due) : '–'; } }], d.riders, { per: 30 }).outerHTML, { flush: true }) + '</div>';
      var orderOf = function (id) { return d.orders.filter(function (o) { return o.order_id === id; })[0]; };
      var riderPick = function (o, title, action) {
        var rs = (byHubRiders[o.hub_id] || []).filter(function (r) { return r.state !== 'off'; });
        if (!rs.length) { toast('No riders on duty at ' + o.hub + '.', 'bad'); return; }
        openModal(title + ' · ' + o.order_id, '<label class="cc-f"><span>Rider at ' + esc(o.hub) + '</span><select class="cc-sel" id="dpR">' + rs.map(function (r) { return '<option value="' + r.id + '">' + esc(r.name) + ' · ' + ({ available: 'free', out: 'out with ' + r.out, returning: 'coming back' }[r.state] || r.state) + (r.smart ? ' (best pick)' : '') + '</option>'; }).join('') + '</select></label>',
          [{ t: title, cls: 'pri', fn: function (b) { act(b, '/ad/live/dispatch/act', { order_id: o.order_id, action: action, rider_id: $('dpR').value }, function () { closeModal(); V['ad-dispatch'].run(root); }); } }]);
      };
      root.querySelectorAll('.ad-oc[data-o]').forEach(function (x) {
        x.addEventListener('click', function (e) {
          var b = e.target.closest('[data-a]'), o = orderOf(x.getAttribute('data-o'));
          if (!b) { K.orderDrawer(o.order_id); return; }
          e.stopPropagation(); var a = b.getAttribute('data-a');
          if (a === 'accept') act(b, '/ad/live/dispatch/act', { order_id: o.order_id, action: 'accept' }, function () { V['ad-dispatch'].run(root); });
          if (a === 'smart') act(b, '/ad/live/dispatch/act', { order_id: o.order_id, action: 'dispatch', auto: 1 }, function () { V['ad-dispatch'].run(root); });
          if (a === 'pick') riderPick(o, 'Dispatch', 'dispatch');
          if (a === 'reassign') riderPick(o, 'Change rider', 'reassign');
          if (a === 'reject' || a === 'cancel') ask({ title: (a === 'reject' ? 'Reject ' : 'Cancel ') + o.order_id + '?', text: 'The customer is told, and stock goes back to ' + o.hub + '.', tone: 'danger', ok: a === 'reject' ? 'Reject order' : 'Cancel order', input: { label: 'Reason (the customer sees it)', placeholder: 'e.g. Out of stock' } })
            .then(function (v) { if (v) act(b, '/ad/live/dispatch/act', { order_id: o.order_id, action: a, reason: v }, function () { V['ad-dispatch'].run(root); }); });
        });
      });
      K.badge('ad-dispatch', (c['new'] || 0) + (c.late || 0));
    });
  } };

  /* ------------------------------------------------------------------ refunds */
  var REASON = { cancelled: 'Cancelled, paid online', weight: 'Weighed less (prepaid)', dispute: 'Dispute settled', manual: 'Goodwill / manual' };
  function refundModal(r, after) {
    var body = el('<div class="ad-form"><dl class="cc-dl"><dt>Order</dt><dd>' + esc(r.order_id) + '</dd><dt>Customer</dt><dd>' + esc(r.customer || '–') + (r.phone ? ' · ' + esc(r.phone) : '') + '</dd><dt>Why</dt><dd>' + esc(REASON[r.reason] || r.reason) + (r.why ? ' · ' + esc(r.why) : '') + '</dd>' +
      (r.payment_id ? '<dt>Payment ref</dt><dd>' + esc(r.payment_id) + '</dd>' : '') + (r.wallet != null ? '<dt>Wallet now</dt><dd>' + money2(r.wallet) + '</dd>' : '') + '</dl>' +
      '<div class="ad-2"><label class="cc-f"><span>Amount (Rs.) *</span><input class="cc-in" type="number" min="0" step="0.01" name="amount" value="' + esc(r.amount) + '"></label>' +
      '<label class="cc-f"><span>How it goes back *</span><select class="cc-sel" name="method"><option value="wallet">Freshful wallet (instant)</option><option value="gateway">Payment gateway refund (Razorpay)</option><option value="upi">UPI transfer</option><option value="bank">Bank transfer</option><option value="cash">Cash</option></select></label></div>' +
      '<label class="cc-f" id="rfRefW" hidden><span>Reference * <small class="muted">(gateway refund id / UTR)</small></span><input class="cc-in" name="reference" maxlength="120"></label>' +
      '<label class="cc-f"><span>Note</span><input class="cc-in" name="note" maxlength="250" placeholder="optional"></label>' +
      '<p class="cc-note">Wallet refunds are added to the customer\'s Freshful wallet at once, with a wallet ledger entry, and can only be paid once per order.</p></div>');
    var m = body.querySelector('[name=method]');
    m.addEventListener('change', function () { body.querySelector('#rfRefW').hidden = m.value === 'wallet' || m.value === 'cash'; });
    modal('Refund ' + r.order_id, body, [{ t: 'Refund now', cls: 'pri', fn: function (b) {
      var v = { order_id: r.order_id, reason: r.reason, amount: body.querySelector('[name=amount]').value, method: m.value, reference: body.querySelector('[name=reference]').value, note: body.querySelector('[name=note]').value, ticket_id: r.ticket_id || '' };
      ask({ title: 'Refund ' + money2(v.amount) + '?', text: v.method === 'wallet' ? 'The money is added to the customer\'s wallet now.' : 'This records that the money was sent back by ' + v.method.toUpperCase() + '.', ok: 'Yes, refund' }).then(function (y) { if (y) act(b, '/ad/refunds/pay', v, function () { closeModal(); after(); }); });
    } }]);
  }
  V['ad-refunds'] = { t: 'Refunds', dates: true, run: function (root) {
    var st = S.p.rfSt == null ? 'pending' : S.p.rfSt;
    return api('/ad/refunds', { status: st }).then(function (d) {
      if (!d.ok) { root.innerHTML = card('Refunds', '<div class="cc-empty">' + esc(d.message) + '</div>'); return; }
      var s = d.summary;
      root.innerHTML = '<div class="cc-grid"><div class="cc-kpis">' + kpi('To refund', money(s.pending_value), num(s.pending, 0) + ' order(s) waiting', s.pending ? 'warn' : 'k-green') + kpi('Refunded', money(s.done_value), num(s.done, 0) + ' order(s)', 'k-blue') + kpi('No refund due', num(s.rejected, 0), 'checked and closed', 'k-slate') + '</div>' +
        '<div class="cc-card"><div class="cc-body"><p class="cc-note ad-help">Listed automatically: orders paid online and then cancelled, and prepaid orders that were weighed lighter than ordered (money to return). Wallet-paid orders are not listed because the old checkout never took the wallet money.</p></div></div>' +
        card('Refunds', '<div class="ad-tools"><div class="cc-tabs" id="rfTabs">' + [['pending', 'To refund'], ['done', 'Refunded'], ['rejected', 'No refund'], ['', 'All']].map(function (t) { return '<button type="button" data-s="' + t[0] + '"' + (t[0] === st ? ' class="is-on"' : '') + '>' + t[1] + '</button>'; }).join('') + '</div><span style="flex:1"></span><button type="button" class="cc-btn pri" id="rfNew">+ Manual refund</button></div>' + slot('t'), { flush: true }) + '</div>';
      mount(root, 't', T([{ k: 'order_id', t: 'Order', f: function (r) { return '<b>' + esc(r.order_id) + '</b><small>' + dt(r.placed) + '</small>'; } }, { k: 'customer', t: 'Customer', f: function (r) { return esc(r.customer || '–') + '<small>' + esc(r.phone) + '</small>'; } }, { k: 'hub', t: 'Hub' },
        { k: 'reason', t: 'Why', f: function (r) { return esc(REASON[r.reason] || r.reason) + (r.why ? '<small>' + esc(r.why) + '</small>' : ''); } }, { k: 'amount', t: 'Amount', n: 1, f: function (r) { return '<b>' + money2(r.amount) + '</b>'; } },
        { k: 'status', t: 'Status', f: function (r) { return r.status === 'done' ? pill('Refunded · ' + r.method, 'p-ok') + (r.reference ? '<small>' + esc(r.reference) + '</small>' : '') : r.status === 'rejected' ? pill('No refund', 'p-mute') + '<small>' + esc(r.note) + '</small>' : pill('To refund', 'p-warn'); } },
        { k: '_a', t: '', nosort: 1, csv: false, f: function (r) { return r.status === 'pending' ? '<div class="ad-acts"><button type="button" class="cc-btn pri" data-pay="' + esc(r.key) + '">Refund</button><button type="button" class="cc-btn" data-no="' + esc(r.key) + '">No refund</button></div>' : ''; } }],
        d.rows, { csv: 'freshful-refunds.csv', click: function (r) { K.orderDrawer(r.order_id); }, after: function (w) {
          var by = function (k) { return d.rows.filter(function (r) { return r.key === k; })[0]; };
          w.querySelectorAll('[data-pay]').forEach(function (b) { b.addEventListener('click', function (e) { e.stopPropagation(); refundModal(by(b.getAttribute('data-pay')), function () { V['ad-refunds'].run(root); }); }); });
          w.querySelectorAll('[data-no]').forEach(function (b) { b.addEventListener('click', function (e) { e.stopPropagation(); var r = by(b.getAttribute('data-no'));
            ask({ title: 'No refund for ' + r.order_id + '?', tone: 'warn', ok: 'Save', input: { label: 'Why no refund is due', placeholder: 'e.g. refunded by the payment gateway automatically' } }).then(function (v) { if (v) act(b, '/ad/refunds/reject', { order_id: r.order_id, reason: r.reason, note: v }, function () { V['ad-refunds'].run(root); }); }); }); });
        } }));
      root.querySelectorAll('#rfTabs button').forEach(function (b) { b.addEventListener('click', function () { S.p.rfSt = b.getAttribute('data-s'); V['ad-refunds'].run(root); }); });
      $('rfNew').addEventListener('click', function () {
        ask({ title: 'Manual refund', text: 'For a goodwill refund on any order.', ok: 'Next', input: { label: 'Order number', placeholder: 'e.g. FF2610220416' } }).then(function (oid) { if (oid) refundModal({ order_id: oid.trim(), reason: 'manual', amount: '', customer: '', phone: '' }, function () { V['ad-refunds'].run(root); }); });
      });
    });
  } };

  /* ------------------------------------------------------------------ support tickets & disputes */
  var TST = { open: ['Open', 'p-info'], in_progress: ['In progress', 'p-warn'], waiting: ['Waiting for customer', 'p-mute'], resolved: ['Resolved', 'p-ok'], closed: ['Closed', 'p-mute'] };
  var PRI = { urgent: ['Urgent', 'p-bad'], high: ['High', 'p-warn'], normal: ['Normal', 'p-mute'], low: ['Low', 'p-mute'] };
  function tPill(s) { var x = TST[s] || [s, 'p-mute']; return pill(x[0], x[1]); }
  function ticketDrawer(id, after) {
    openDrawer('Ticket', '<div class="cc-load"><i></i>Loading…</div>');
    api('/ad/tickets/' + id, {}, false).then(function (r) {
      if (!r.ok) { $('ccDBody').innerHTML = '<div class="cc-empty">' + esc(r.message) + '</div>'; return; }
      var t = r.ticket, dispute = t.kind === 'dispute';
      $('ccDTitle').textContent = (dispute ? 'Dispute ' : t.kind === 'enquiry' ? 'Enquiry ' : 'Ticket ') + t.code;
      var h = '<div class="cc-chips">' + tPill(t.status) + pill((PRI[t.priority] || [t.priority])[0], (PRI[t.priority] || [0, 'p-mute'])[1]) + (t.late ? pill('Overdue', 'p-bad') : '') + (t.category ? pill(t.category, 'p-info') : '') + (t.source !== 'manual' ? pill('from ' + t.source, 'p-mute') : '') + '</div>' +
        '<h3 style="margin:4px 0">' + esc(t.subject) + '</h3>' + (t.body ? '<div class="ad-msg t-customer"><header><b>' + esc(t.contact_name || 'Customer') + '</b>' + dStr(t.created_at) + '</header>' + esc(t.body).replace(/\n/g, '<br>') + '</div>' : '') +
        '<dl class="cc-dl"><dt>Customer</dt><dd>' + esc(t.contact_name || '–') + (t.contact_phone ? ' · <a href="tel:' + esc(t.contact_phone) + '">' + esc(t.contact_phone) + '</a>' : '') + (t.contact_email ? ' · ' + esc(t.contact_email) : '') + (r.other_tickets ? ' · ' + r.other_tickets + ' other ticket(s)' : '') + '</dd>' +
        (r.order ? '<dt>Order</dt><dd><a href="#" id="tkOrd">' + esc(r.order.id) + '</a> · ' + K.stagePill(r.order.stage) + ' · ' + money2(r.order.amount) + ' · ' + esc(r.order.pay) + '</dd>' : '') + (t.hub ? '<dt>Hub</dt><dd>' + esc(t.hub) + '</dd>' : '') +
        '<dt>Opened</dt><dd>' + dStr(t.created_at) + ' by ' + esc(t.created_by || '–') + '</dd><dt>Answer by</dt><dd>' + dStr(t.due_at) + '</dd>' + (dispute ? '<dt>Claim</dt><dd>' + money2(t.claim_amount) + (t.outcome ? ' · outcome: ' + esc((r.outcomes || {})[t.outcome] || t.outcome) + ' ' + money2(t.settled_amount) : '') + '</dd>' : '') +
        (r.refunds.length ? '<dt>Refunds</dt><dd>' + r.refunds.map(function (x) { return esc(x.reason) + ' ' + money2(x.amount) + ' (' + esc(x.status) + (x.method ? ', ' + esc(x.method) : '') + ')'; }).join('<br>') + '</dd>' : '') + '</dl>' +
        '<div class="ad-3"><label class="cc-f"><span>Status</span><select class="cc-sel" id="tkS">' + Object.keys(r.statuses).map(function (k) { return '<option value="' + k + '"' + (k === t.status ? ' selected' : '') + '>' + esc(r.statuses[k]) + '</option>'; }).join('') + '</select></label>' +
        '<label class="cc-f"><span>Priority</span><select class="cc-sel" id="tkP">' + Object.keys(PRI).map(function (k) { return '<option value="' + k + '"' + (k === t.priority ? ' selected' : '') + '>' + PRI[k][0] + '</option>'; }).join('') + '</select></label>' +
        '<label class="cc-f"><span>Handled by</span><input class="cc-in" id="tkA" maxlength="120" value="' + esc(t.assigned_to) + '" placeholder="name"></label></div><div><button type="button" class="cc-btn" id="tkU">Save changes</button></div>' +
        '<div><b>Conversation</b><div class="ad-thread" style="margin-top:8px">' + (r.messages.length ? r.messages.map(function (m) { return '<div class="ad-msg t-' + esc(m.type) + '"><header><b>' + esc(m.author || '') + '</b>' + dt(m.at) + ' · ' + esc({ note: 'internal note', email: 'e-mail', whatsapp: 'WhatsApp', sms: 'SMS', phone: 'phone call' }[m.channel] || m.channel) + (m.sent === 'not_sent' ? ' · <span class="down">not sent</span>' : '') + '</header>' + esc(m.body).replace(/\n/g, '<br>') + '</div>'; }).join('') : '<p class="cc-note">No replies yet.</p>') + '</div></div>' +
        '<div class="cc-card"><div class="cc-body"><div class="ad-form"><label class="cc-f"><span>Reply</span><textarea id="tkR" rows="4" maxlength="5000" placeholder="Write to the customer, or an internal note"></textarea></label>' +
        '<div class="ad-3"><label class="cc-f"><span>Send by</span><select class="cc-sel" id="tkC"><option value="whatsapp">WhatsApp</option><option value="email"' + (t.contact_email ? '' : ' disabled') + '>E-mail' + (r.mail ? '' : ' (not set up)') + '</option><option value="phone">Phone call (log what was said)</option><option value="note">Internal note (customer does not see)</option></select></label>' +
        '<label class="cc-f"><span>Then set status</span><select class="cc-sel" id="tkSA"><option value="">Keep</option>' + Object.keys(r.statuses).map(function (k) { return '<option value="' + k + '">' + esc(r.statuses[k]) + '</option>'; }).join('') + '</select></label><div style="display:flex;align-items:flex-end"><button type="button" class="cc-btn pri" id="tkSend">Send reply</button></div></div></div></div></div>' +
        (dispute && !['resolved', 'closed'].includes(t.status) ? '<div class="cc-card"><div class="cc-body"><div class="ad-form"><b>Settle this dispute</b><div class="ad-3"><label class="cc-f"><span>Outcome</span><select class="cc-sel" id="dsO">' + Object.keys(r.outcomes).map(function (k) { return '<option value="' + k + '">' + esc(r.outcomes[k]) + '</option>'; }).join('') + '</select></label>' +
          '<label class="cc-f"><span>Refund (Rs.)</span><input class="cc-in" type="number" step="0.01" min="0" id="dsA" value="' + esc(t.claim_amount || '') + '"></label><label class="cc-f"><span>Refund to</span><select class="cc-sel" id="dsM"><option value="wallet">Freshful wallet</option><option value="upi">UPI</option><option value="bank">Bank</option><option value="gateway">Payment gateway</option><option value="cash">Cash</option></select></label></div>' +
          '<div class="ad-2"><label class="cc-f"><span>Reference (UPI / bank / gateway)</span><input class="cc-in" id="dsR" maxlength="120"></label><label class="cc-f"><span>Note</span><input class="cc-in" id="dsN" maxlength="250"></label></div><div><button type="button" class="cc-btn pri" id="dsGo">Settle dispute</button></div></div></div></div>' : '');
      $('ccDBody').innerHTML = h;
      var o = $('tkOrd'); if (o) o.addEventListener('click', function (e) { e.preventDefault(); K.orderDrawer(r.order.id); });
      $('tkU').addEventListener('click', function () { act(this, '/ad/tickets/' + id + '/update', { values: { status: $('tkS').value, priority: $('tkP').value, assigned_to: $('tkA').value } }, function () { ticketDrawer(id, after); if (after) after(); }); });
      $('tkSend').addEventListener('click', function () {
        var b = this, ch = $('tkC').value, txt = $('tkR').value.trim();
        if (!txt) { toast('Write the reply first.', 'bad'); return; }
        var w = ch === 'whatsapp' ? window.open('about:blank', '_blank') : null;
        post('/ad/tickets/' + id + '/reply', { body: txt, channel: ch, status_after: $('tkSA').value }).then(function (x) {
          toast(x.message, x.ok ? 'ok' : 'bad');
          if (w) { if (x.ok && x.whatsapp) w.location = x.whatsapp; else w.close(); }
          if (x.ok) { ticketDrawer(id, after); if (after) after(); }
        });
      });
      var ds = $('dsGo'); if (ds) ds.addEventListener('click', function () {
        var v = { outcome: $('dsO').value, amount: $('dsA').value, method: $('dsM').value, reference: $('dsR').value, note: $('dsN').value };
        var refund = v.outcome === 'refund' || v.outcome === 'partial';
        ask({ title: 'Settle dispute ' + t.code + '?', text: refund ? money2(v.amount) + ' is refunded by ' + v.method + ' and the dispute is resolved.' : 'The dispute is resolved with no refund.', ok: 'Settle', tone: refund ? 'warn' : 'info' }).then(function (y) { if (y) act(ds, '/ad/tickets/' + id + '/settle', v, function () { ticketDrawer(id, after); if (after) after(); }); });
      });
    });
  }
  function newTicket(kind, after, preset) {
    preset = preset || {};
    api('/ad/tickets', { kind: kind }).then(function (meta) {
      var cats = kind === 'dispute' ? meta.types : meta.categories;
      var body = el('<div class="ad-form"><div class="ad-2"><label class="cc-f"><span>Order number' + (kind === 'dispute' ? ' *' : '') + '</span><input class="cc-in" name="order_id" maxlength="40" value="' + esc(preset.order_id || '') + '"></label>' +
        '<label class="cc-f"><span>Customer mobile</span><input class="cc-in" name="phone" maxlength="13" value="' + esc(preset.phone || '') + '"></label></div>' +
        '<div class="ad-2"><label class="cc-f"><span>' + (kind === 'dispute' ? 'Dispute type *' : 'Category') + '</span><select class="cc-sel" name="category"><option value="">Choose…</option>' + (cats || []).map(function (c) { return '<option>' + esc(c) + '</option>'; }).join('') + '</select></label>' +
        '<label class="cc-f"><span>Priority</span><select class="cc-sel" name="priority"><option value="normal">Normal (answer within 24 h)</option><option value="high"' + (kind === 'dispute' ? ' selected' : '') + '>High (8 h)</option><option value="urgent">Urgent (2 h)</option><option value="low">Low (3 days)</option></select></label></div>' +
        '<label class="cc-f"><span>Subject *</span><input class="cc-in" name="subject" maxlength="200"></label><label class="cc-f"><span>What the customer said</span><textarea name="body" rows="4" maxlength="5000"></textarea></label>' +
        (kind === 'dispute' ? '<label class="cc-f"><span>Amount the customer claims (Rs.)</span><input class="cc-in" type="number" step="0.01" min="0" name="claim"></label>' : '') + '</div>');
      modal(kind === 'dispute' ? 'Open a dispute' : 'New support ticket', body, [{ t: 'Open', cls: 'pri', fn: function (b) {
        var g = function (n) { var e = body.querySelector('[name=' + n + ']'); return e ? e.value : ''; };
        act(b, '/ad/tickets', { values: { kind: kind, order_id: g('order_id'), phone: g('phone'), category: g('category'), priority: g('priority'), subject: g('subject'), body: g('body'), claim: g('claim') } }, function (x) { closeModal(); if (after) after(); ticketDrawer(x.id, after); });
      } }]);
    });
  }
  function ticketView(kind, key, title) {
    V[key] = { t: title, dates: true, run: function (root) {
      var st = S.p['tk' + kind] == null ? 'active' : S.p['tk' + kind], q = S.p['tq' + kind] || '';
      return api('/ad/tickets', { kind: kind, status: st, q: q }).then(function (d) {
        if (!d.ok) { root.innerHTML = card(title, '<div class="cc-empty">' + esc(d.message) + '</div>'); return; }
        var c = d.counts;
        root.innerHTML = '<div class="cc-grid"><div class="cc-kpis">' + kpi('Open', num(c.open || 0, 0), 'not answered yet', c.open ? 'warn' : 'k-green') + kpi('In progress', num(c.in_progress || 0, 0), '', 'k-amber') + kpi('Waiting for customer', num(c.waiting || 0, 0), '', 'k-slate') +
          kpi('Overdue', num(c.overdue || 0, 0), 'past the answer-by time', c.overdue ? 'bad' : 'k-green') + kpi('Resolved / closed', num((c.resolved || 0) + (c.closed || 0), 0), 'in these dates', 'k-blue') + '</div>' +
          (d.imported ? '<p class="cc-note">' + d.imported + ' new complaint(s) from the app were added.</p>' : '') +
          card(title, '<div class="ad-tools"><div class="cc-tabs" id="tkTabs">' + [['active', 'Active'], ['overdue', 'Overdue'], ['open', 'Open'], ['in_progress', 'In progress'], ['waiting', 'Waiting'], ['resolved', 'Resolved'], ['closed', 'Closed'], ['', 'All']].map(function (t) { return '<button type="button" data-s="' + t[0] + '"' + (t[0] === st ? ' class="is-on"' : '') + '>' + t[1] + '</button>'; }).join('') + '</div>' +
            '<input class="cc-in" id="tkQ" placeholder="Search code, order, name, mobile…" value="' + esc(q) + '" style="flex:1;min-width:160px"><button type="button" class="cc-btn pri" id="tkNew">' + (kind === 'dispute' ? '+ Open dispute' : '+ New ticket') + '</button></div>' + slot('t'), { flush: true }) + '</div>';
        var reload = function () { V[key].run(root); };
        mount(root, 't', T([{ k: 'code', t: 'Ticket', f: function (x) { return '<b>' + esc(x.code) + '</b><small>' + dt(x.at) + '</small>'; } }, { k: 'subject', t: 'Subject', f: function (x) { return esc(x.subject) + (x.category ? '<small>' + esc(x.category) + '</small>' : ''); } },
          { k: 'customer', t: 'Customer', f: function (x) { return esc(x.customer || '–') + '<small>' + esc(x.phone) + '</small>'; } }, { k: 'order_id', t: 'Order', f: function (x) { return esc(x.order_id || '–') + (x.hub ? '<small>' + esc(x.hub) + '</small>' : ''); } },
          { k: 'priority', t: 'Priority', f: function (x) { var p = PRI[x.priority] || [x.priority, 'p-mute']; return pill(p[0], p[1]); } }, { k: 'status', t: 'Status', f: function (x) { return tPill(x.status) + (x.late ? ' ' + pill('Overdue', 'p-bad') : ''); } },
          { k: 'replies', t: 'Replies', n: 1, f: function (x) { return num(x.replies, 0) + (x.last ? '<small>' + K.ago(x.last) + '</small>' : ''); } }].concat(kind === 'dispute' ? [{ k: 'claim', t: 'Claim', n: 1, f: function (x) { return money2(x.claim) + (x.settled ? '<small>settled ' + money2(x.settled) + '</small>' : ''); } }] : []).concat([{ k: 'assigned', t: 'Handled by' }]),
          d.rows, { csv: 'freshful-' + kind + 's.csv', click: function (x) { ticketDrawer(x.id, reload); } }));
        root.querySelectorAll('#tkTabs button').forEach(function (b) { b.addEventListener('click', function () { S.p['tk' + kind] = b.getAttribute('data-s'); reload(); }); });
        $('tkQ').addEventListener('keydown', function (e) { if (e.key === 'Enter') { S.p['tq' + kind] = this.value.trim(); reload(); } });
        $('tkNew').addEventListener('click', function () { newTicket(kind, reload); });
        K.badge(key, c.overdue || 0);
      });
    } };
  }
  ticketView('ticket', 'ad-tickets', 'Support tickets');
  ticketView('dispute', 'ad-disputes', 'Disputes');

  /* ------------------------------------------------------------------ enquiries with reply */
  function enquiryView(type, key, title) {
    V[key] = { t: title, dates: false, nofilter: true, run: function (root) {
      return api('/ad/enquiries/' + type, {}, false).then(function (d) {
        if (!d.ok) { root.innerHTML = card(title, '<div class="cc-empty">' + esc(d.message) + '</div>'); return; }
        var fresh = d.rows.filter(function (r) { return r.status === 'new' || r.status === 'open'; }).length;
        root.innerHTML = '<div class="cc-grid"><div class="cc-kpis">' + kpi('Enquiries', num(d.rows.length, 0), '', 'k-purple') + kpi('Not answered', num(fresh, 0), '', fresh ? 'warn' : 'k-green') + kpi('Answered', num(d.rows.length - fresh, 0), '', 'k-blue') + '</div>' +
          (d.mail ? '' : '<p class="cc-note">E-mail is not set up on the site (MAIL_MAILER), so replies go by WhatsApp or a call; every reply is saved here.</p>') + card(title, slot('t'), { flush: true }) + '</div>';
        var reload = function () { V[key].run(root); };
        var open = function (r) { post('/ad/enquiries/' + type + '/' + r.id + '/ticket', {}).then(function (x) { if (!x.ok) { toast(x.message, 'bad'); return; } ticketDrawer(x.id, reload); }); };
        mount(root, 't', T([{ k: 'at', t: 'Received', n: 1, f: function (r) { return r.at ? dt(r.at) : '–'; } }, { k: 'name', t: 'Name', f: function (r) { return '<b>' + esc(r.name) + '</b>'; } }, { k: 'phone', t: 'Phone' }, { k: 'email', t: 'Email' }]
          .concat(type === 'contact' ? [{ k: 'text', t: 'Message', f: function (r) { return cut(r.text, 120); } }] : [])
          .concat([{ k: 'status', t: 'Status', f: function (r) { return r.status === 'new' ? pill('New', 'p-info') : tPill(r.status); } }, { k: '_a', t: '', nosort: 1, csv: false, f: function (r) { return '<div class="ad-acts"><button type="button" class="cc-btn pri" data-rp="' + r.id + '">' + (r.ticket_id ? 'Open' : 'Reply') + '</button></div>'; } }]),
          d.rows, { csv: 'freshful-' + type + '-enquiries.csv', click: open, after: function (w) { w.querySelectorAll('[data-rp]').forEach(function (b) { b.addEventListener('click', function (e) { e.stopPropagation(); open(d.rows.filter(function (r) { return String(r.id) === b.getAttribute('data-rp'); })[0]); }); }); } }));
      });
    } };
  }
  enquiryView('contact', 'ad-contact', 'Contact us messages');
  enquiryView('franchise', 'ad-franchise', 'Franchise enquiries');

  /* ------------------------------------------------------------------ ratings & reviews */
  var stars = function (n) { n = Math.max(0, Math.min(5, Math.round(+n || 0))); return '<span class="ad-stars">' + '★★★★★'.slice(0, n) + '<span class="muted">' + '★★★★★'.slice(0, 5 - n) + '</span></span>'; };
  V['ad-reviews'] = { t: 'Ratings & reviews', dates: false, nofilter: true, run: function (root) {
    return api('/ad/reviews', {}, false).then(function (d) {
      if (!d.available) { root.innerHTML = '<div class="cc-grid">' + card('Ratings & reviews', '<div class="cc-empty">' + esc(d.reason || 'Not available.') + '<br><small>When the customer app starts saving ratings, they appear here with reply and hide options.</small></div>') + '</div>'; return; }
      var max = Math.max.apply(null, [1, 2, 3, 4, 5].map(function (k) { return d.dist[k] || 0; })) || 1;
      root.innerHTML = '<div class="cc-grid"><div class="cc-kpis">' + kpi('Average rating', d.avg == null ? '–' : d.avg + ' / 5', stars(d.avg), 'k-amber') + kpi('Ratings', num(d.count, 0), 'from ' + esc(d.table), 'k-blue') +
        kpi('Low ratings', num((d.dist[1] || 0) + (d.dist[2] || 0), 0), '1 or 2 stars: follow up', (d.dist[1] || d.dist[2]) ? 'warn' : 'k-green') + '</div>' +
        card('How customers rate you', '<div class="cc-hbars">' + [5, 4, 3, 2, 1].map(function (k) { return '<div><span>' + k + ' stars</span><div class="bar"><i style="width:' + Math.max(1, (d.dist[k] || 0) * 100 / max) + '%;background:' + (k >= 4 ? 'var(--ok)' : k === 3 ? 'var(--warn)' : 'var(--bad)') + '"></i></div><em>' + num(d.dist[k] || 0, 0) + '</em></div>'; }).join('') + '</div>') +
        card('Reviews', slot('t'), { flush: true }) + '</div>';
      var reload = function () { V['ad-reviews'].run(root); };
      mount(root, 't', T([{ k: 'at', t: 'Date', n: 1, f: function (r) { return r.at ? dt(r.at) : '–'; } }, { k: 'stars', t: 'Rating', n: 1, f: function (r) { return stars(r.stars); } }, { k: 'text', t: 'Review', f: function (r) { return cut(r.text, 140) || '<span class="muted">no text</span>'; } },
        { k: 'customer', t: 'Customer' }, { k: 'product', t: 'Product / order', f: function (r) { return esc(r.product || r.order_id || '–'); } }, { k: 'reply', t: 'Your reply', f: function (r) { return r.reply ? cut(r.reply, 80) : '<span class="muted">none</span>'; } },
        { k: 'hidden', t: 'Shown', v: function (r) { return r.hidden ? 0 : 1; }, f: function (r) { return r.hidden ? pill('Hidden', 'p-mute') : pill('Shown', 'p-ok'); } }],
        d.rows, { csv: 'freshful-reviews.csv', click: function (r) {
          var body = el('<div class="ad-form"><div>' + stars(r.stars) + ' <b>' + esc(r.customer || '') + '</b> <small class="muted">' + dt(r.at) + '</small></div><div class="ad-msg t-customer">' + (esc(r.text) || '<span class="muted">no text</span>') + '</div>' +
            '<label class="cc-f"><span>Reply from Freshful</span><textarea id="rvR" rows="4" maxlength="2000">' + esc(r.reply) + '</textarea></label>' + (r.stars <= 2 ? '<p class="cc-note">Low rating: you can also open a support ticket for this customer.</p>' : '') + '</div>');
          modal('Review #' + r.id, body, [{ t: r.hidden ? 'Show review' : 'Hide review', fn: function (b) { act(b, '/ad/reviews/' + r.id, { reply: $('rvR').value, hidden: !r.hidden }, function () { closeModal(); reload(); }); } },
            { t: 'Save reply', cls: 'pri', fn: function (b) { act(b, '/ad/reviews/' + r.id, { reply: $('rvR').value }, function () { closeModal(); reload(); }); } }]);
        } }));
    });
  } };

  /* ------------------------------------------------------------------ push notifications */
  V['ad-push'] = { t: 'Push notifications', dates: false, nofilter: true, run: function (root) {
    return api('/ad/push', {}, false).then(function (d) {
      root.innerHTML = '<div class="cc-grid">' + (d.configured ? '<div class="cc-kpis">' + kpi('Firebase', 'Connected', esc(d.info.project), 'k-green') + kpi('Sends to', 'Every app user', 'topic "' + esc(d.info.topic) + '"', 'k-blue') + kpi('Sent so far', num(d.rows.length, 0), 'messages', 'k-purple') + '</div>' : card('Set up push notifications first', '<p class="cc-note ad-help">The old admin used Google\'s old notification service, which Google switched off in 2024, so pushes have not reached customers since then. To send again:</p>' +
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

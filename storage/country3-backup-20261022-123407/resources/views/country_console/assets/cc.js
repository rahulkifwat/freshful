/* Freshful country console (22 Oct 2026). Plain JavaScript, no libraries. Every screen reads JSON from /country_manager/console/api. */
(function () {
  'use strict';
  var C = window.CCC || {};
  var $ = function (id) { return document.getElementById(id); };
  var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  var inr0 = new Intl.NumberFormat('en-IN', { maximumFractionDigits: 0 });
  var money = function (v) { return v == null || v === '' ? '–' : (v < 0 ? '-₹' : '₹') + inr0.format(Math.abs(Math.round(+v))); };
  var money2 = function (v) { return v == null ? '–' : '₹' + (+v).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
  var num = function (v, d) { return v == null || v === '' ? '–' : (+v).toLocaleString('en-IN', { maximumFractionDigits: d == null ? 2 : d }); };
  var pct = function (v) { return v == null ? '–' : (Math.round(+v * 10) / 10) + '%'; };
  var dt = function (ts) { return ts ? new Date(ts * 1000).toLocaleString('en-IN', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : '–'; };
  var dOnly = function (ts) { return ts ? new Date(ts * 1000).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '–'; };
  var dStr = function (s) { if (!s) return '–'; var d = new Date(String(s).replace(' ', 'T')); return isNaN(d) ? esc(s) : d.toLocaleString('en-IN', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }); };
  var ago = function (ts) { if (!ts) return '–'; var m = Math.round((Date.now() / 1000 - ts) / 60); if (m < 1) return 'just now'; if (m < 60) return m + ' min ago'; var h = Math.round(m / 60); if (h < 48) return h + ' h ago'; return Math.round(h / 24) + ' days ago'; };
  var icon = function (n) { return (C.icons && C.icons[n]) || ''; };
  var vs = function (v, invert) { if (v == null) return '<span class="muted">new</span>'; var good = invert ? v <= 0 : v >= 0; return '<span class="' + (good ? 'up' : 'down') + '">' + (v > 0 ? '▲ ' : v < 0 ? '▼ ' : '') + Math.abs(v) + '%</span>'; };
  var STAGE = { 'new': ['New', 'p-info'], accepted: ['Accepted', 'p-warn'], out: ['Out for delivery', 'p-warn'], unsettled: ['Delivered, cash due', 'p-warn'], delivered: ['Delivered', 'p-ok'], cancelled: ['Cancelled', 'p-bad'] };
  var stagePill = function (s) { var x = STAGE[s] || [s, 'p-mute']; return '<span class="pill ' + x[1] + '">' + esc(x[0]) + '</span>'; };
  var pill = function (t, c) { return '<span class="pill ' + (c || 'p-mute') + '">' + esc(t) + '</span>'; };
  var ymd = function (d) { return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); };
  var store = { get: function (k) { try { return JSON.parse(localStorage.getItem(k)); } catch (e) { return null; } }, set: function (k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} } };

  /* ------------------------------------------------------------------ state */
  var S = { view: 'overview', f: { state: '', city: '', hub: '', htype: '', hstatus: 'all' }, preset: 'today', from: C.today, to: C.today, p: {}, timer: null, seq: 0 };
  var saved = store.get('cc-state-' + (C.role || 'country'));
  if (saved && saved.f) { S.f = Object.assign(S.f, saved.f); S.preset = saved.preset || 'today'; if (saved.preset === 'custom') { S.from = saved.from || C.today; S.to = saved.to || C.today; } }

  function setPreset(p) {
    var t = new Date(C.today + 'T12:00:00'), f = new Date(t), e = new Date(t);
    if (p === 'yesterday') { f.setDate(t.getDate() - 1); e.setDate(t.getDate() - 1); }
    else if (p === '7') f.setDate(t.getDate() - 6);
    else if (p === '30') f.setDate(t.getDate() - 29);
    else if (p === '90') f.setDate(t.getDate() - 89);
    else if (p === 'month') f.setDate(1);
    else if (p === 'lastmonth') { f = new Date(t.getFullYear(), t.getMonth() - 1, 1); e = new Date(t.getFullYear(), t.getMonth(), 0); }
    if (p !== 'custom') { S.from = ymd(f); S.to = ymd(e); }
    S.preset = p;
  }
  setPreset(S.preset);
  function save() { store.set('cc-state-' + (C.role || 'country'), { f: S.f, preset: S.preset, from: S.from, to: S.to }); }

  function qs(extra, withDates) {
    var o = {}; Object.keys(S.f).forEach(function (k) { if (S.f[k] !== '' && !(k === 'hstatus' && S.f[k] === 'all')) o[k] = S.f[k]; });
    if (withDates !== false) { o.from = S.from; o.to = S.to; }
    Object.keys(extra || {}).forEach(function (k) { if (extra[k] !== '' && extra[k] != null) o[k] = extra[k]; });
    return Object.keys(o).map(function (k) { return encodeURIComponent(k) + '=' + encodeURIComponent(o[k]); }).join('&');
  }

  /* ------------------------------------------------------------------ server */
  function live(ok, text) { $('ccLive').classList.toggle('is-err', !ok); $('ccLiveText').textContent = text; }
  function api(path, extra, withDates) {
    var url = C.base + path + '?' + qs(extra, withDates);
    return fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' }).then(function (r) {
      if (r.status === 401) { loginGone(); throw new Error('login'); }
      return r.json().then(function (j) { if (!r.ok && !j) throw new Error('HTTP ' + r.status); live(true, 'Updated ' + new Date().toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' })); return j; });
    }).catch(function (e) { if (e.message !== 'login') live(false, 'Connection problem'); throw e; });
  }
  function post(path, body) {
    var b = Object.assign({}, S.f, body || {});
    return fetch(C.base + path, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': C.csrf, 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(b) })
      .then(function (r) {
        if (r.status === 401) { loginGone(); return { ok: false, message: 'Please log in again.' }; }
        if (r.status === 419) return { ok: false, message: 'This page has expired. Reload the page and try again.' };
        if (r.status === 429) return { ok: false, message: 'Too many changes in a short time. Wait a minute.' };
        return r.json().catch(function () { return { ok: false, message: 'Server error (' + r.status + ').' }; });
      }).catch(function () { return { ok: false, message: 'No connection. Check the internet and try again.' }; });
  }
  function loginGone() { $('ccView').innerHTML = '<div class="cc-card"><div class="cc-empty">Your session has ended. <a href="' + esc(C.login) + '">Log in again</a>.</div></div>'; }

  /* ------------------------------------------------------------------ ui helpers */
  function toast(msg, kind) { var t = document.createElement('div'); t.className = 'cc-toast ' + (kind || ''); t.textContent = msg; $('ccToasts').appendChild(t); setTimeout(function () { t.remove(); }, kind === 'bad' ? 7000 : 4500); }
  function loading(el) { (el || $('ccView')).innerHTML = '<div class="cc-load"><i></i>Loading…</div>'; }
  function el(html) { var d = document.createElement('div'); d.innerHTML = html.trim(); return d.firstChild; }
  function card(title, body, o) {
    o = o || {};
    return '<section class="cc-card"' + (o.id ? ' id="' + o.id + '"' : '') + '><header><h2>' + esc(title) + '</h2>' + (o.actions || '') + (o.sub ? '<p>' + o.sub + '</p>' : '') + '</header><div class="cc-body' + (o.flush ? ' flush' : '') + '">' + body + '</div></section>';
  }
  function kpi(label, value, sub, cls, go) { return '<div class="cc-kpi ' + (cls || '') + '"' + (go ? ' data-go="' + go + '"' : '') + '><small>' + esc(label) + '</small><b>' + value + '</b>' + (sub ? '<span>' + sub + '</span>' : '') + '</div>'; }
  function slot(id) { return '<div data-slot="' + id + '"></div>'; }
  function mount(root, id, node) { var s = root.querySelector('[data-slot="' + id + '"]'); if (s && node) s.replaceWith(node); }
  function wireGo(root) { root.querySelectorAll('[data-go]').forEach(function (k) { k.addEventListener('click', function () { location.hash = k.getAttribute('data-go'); }); }); }
  function hbars(items, cls, fmt) {
    if (!items.length) return '<div class="cc-empty">Nothing in these dates.</div>';
    var max = Math.max.apply(null, items.map(function (i) { return +i.value || 0; })) || 1;
    return '<div class="cc-hbars">' + items.map(function (i) { return '<div><span title="' + esc(i.label) + '">' + esc(i.label) + '</span><div class="bar ' + (cls || '') + '"><i style="width:' + Math.max(1, (i.value || 0) * 100 / max) + '%"></i></div><em>' + (fmt ? fmt(i.value, i) : num(i.value)) + '</em></div>'; }).join('') + '</div>';
  }
  function prog(p, mark) { p = Math.max(0, Math.min(100, p || 0)); return '<div class="cc-prog"><i style="width:' + p + '%;' + (p < 60 ? 'background:var(--bad)' : p < 90 ? 'background:var(--warn)' : '') + '"></i>' + (mark != null ? '<em style="left:' + Math.min(100, mark) + '%"></em>' : '') + '</div>'; }
  function csvDownload(name, head, rows) {
    var q = function (v) { v = v == null ? '' : String(v); if (/^[=+\-@]/.test(v) && isNaN(+v)) v = "'" + v; return /[",\n]/.test(v) ? '"' + v.replace(/"/g, '""') + '"' : v; };
    var txt = '﻿' + [head.map(q).join(',')].concat(rows.map(function (r) { return r.map(q).join(','); })).join('\n');
    var a = document.createElement('a'); a.href = URL.createObjectURL(new Blob([txt], { type: 'text/csv;charset=utf-8' })); a.download = name; document.body.appendChild(a); a.click(); setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 500);
  }

  /** Sortable, paged table. cols: [{k, t, n (numeric), f(row) -> html, v(row) -> sort/csv value, csv:false}] */
  function T(cols, rows, o) {
    o = o || {};
    var st = { sort: o.sort || null, dir: o.dir || -1, page: 1, per: o.per || 50 };
    var wrap = document.createElement('div');
    var val = function (c, r) { return c.v ? c.v(r) : r[c.k]; };
    function draw() {
      var list = rows.slice();
      if (st.sort != null) { var c = cols[st.sort]; list.sort(function (a, b) { var x = val(c, a), y = val(c, b); if (x == null) x = c.n ? -Infinity : ''; if (y == null) y = c.n ? -Infinity : ''; return (x > y ? 1 : x < y ? -1 : 0) * st.dir; }); }
      var pages = Math.max(1, Math.ceil(list.length / st.per)); if (st.page > pages) st.page = pages;
      var view = o.noPage ? list : list.slice((st.page - 1) * st.per, st.page * st.per);
      var h = '<div class="cc-tw"' + (o.maxH ? ' style="max-height:' + o.maxH + 'px"' : '') + '><table class="cc-t"><thead><tr>' + cols.map(function (c, i) {
        return '<th class="' + (c.n ? 'n ' : '') + (c.nosort ? '' : 's') + '" data-i="' + i + '">' + esc(c.t) + (st.sort === i ? (st.dir > 0 ? ' ▲' : ' ▼') : '') + '</th>';
      }).join('') + '</tr></thead><tbody>';
      if (!view.length) h += '<tr><td colspan="' + cols.length + '"><div class="cc-empty">' + esc(o.empty || 'Nothing to show for these filters.') + '</div></td></tr>';
      view.forEach(function (r, i) { h += '<tr' + (o.click ? ' class="click" data-r="' + ((st.page - 1) * st.per + i) + '"' : '') + '>' + cols.map(function (c) { return '<td' + (c.n ? ' class="n"' : '') + '>' + (c.f ? c.f(r) : esc(r[c.k])) + '</td>'; }).join('') + '</tr>'; });
      h += '</tbody>' + (o.foot ? '<tfoot><tr>' + o.foot.map(function (x, i) { return '<td' + (cols[i] && cols[i].n ? ' class="n"' : '') + '>' + x + '</td>'; }).join('') + '</tr></tfoot>' : '') + '</table></div>';
      if ((!o.noPage && pages > 1) || o.csv) {
        h += '<div class="cc-pager">' + (o.csv ? '<button type="button" class="cc-btn sm" data-csv style="margin-right:auto">Download Excel (CSV)</button>' : '') + '<span>' + num(list.length, 0) + ' row' + (list.length === 1 ? '' : 's') + '</span>' +
          (!o.noPage && pages > 1 ? '<button type="button" class="cc-btn sm" data-pg="-1"' + (st.page <= 1 ? ' disabled' : '') + '>‹ Prev</button><span>Page ' + st.page + ' of ' + pages + '</span><button type="button" class="cc-btn sm" data-pg="1"' + (st.page >= pages ? ' disabled' : '') + '>Next ›</button>' : '') + '</div>';
      }
      wrap.innerHTML = h;
      wrap.querySelectorAll('th.s').forEach(function (th) { th.addEventListener('click', function () { var i = +th.getAttribute('data-i'); if (st.sort === i) st.dir = -st.dir; else { st.sort = i; st.dir = cols[i].n ? -1 : 1; } draw(); }); });
      wrap.querySelectorAll('[data-pg]').forEach(function (b) { b.addEventListener('click', function () { st.page += +b.getAttribute('data-pg'); draw(); }); });
      var cb = wrap.querySelector('[data-csv]');
      if (cb) cb.addEventListener('click', function () { var cc = cols.filter(function (c) { return c.csv !== false; }); csvDownload(o.csv, cc.map(function (c) { return c.t; }), list.map(function (r) { return cc.map(function (c) { var v = c.c ? c.c(r) : val(c, r); return v == null ? '' : v; }); })); });
      if (o.click) wrap.querySelectorAll('tr.click').forEach(function (tr) { tr.addEventListener('click', function (e) { if (e.target.closest('button,a,select,input')) return; o.click(list[+tr.getAttribute('data-r')], e); }); });
      if (o.after) o.after(wrap, list);
    }
    draw();
    return wrap;
  }

  /* ------------------------------------------------------------------ charts (SVG, no library) */
  function tip(e, html) { var t = $('ccTip'); if (!html) { t.style.display = 'none'; return; } t.innerHTML = html; t.style.display = 'block'; var x = e.clientX + 14, y = e.clientY + 14; if (x + t.offsetWidth > innerWidth - 8) x = e.clientX - t.offsetWidth - 14; t.style.left = x + 'px'; t.style.top = y + 'px'; }
  function nice(m) { if (m <= 0) return 1; var p = Math.pow(10, Math.floor(Math.log10(m))), n = m / p; return (n <= 1 ? 1 : n <= 2 ? 2 : n <= 5 ? 5 : 10) * p; }
  /** bars (left axis) + optional line (right axis). labels[], bars[], line[] */
  function chartBarLine(labels, bars, line, o) {
    o = o || {};
    var W = 760, H = o.h || 230, L = 52, R = line ? 48 : 12, Tp = 12, B = 30, w = W - L - R, h = H - Tp - B, n = labels.length || 1;
    var bm = nice(Math.max.apply(null, bars.concat([0])) * 1.08), lm = line ? nice(Math.max.apply(null, line.concat([0])) * 1.08) : 1, bw = w / n;
    var s = '<svg class="cc-chart" viewBox="0 0 ' + W + ' ' + H + '" role="img" aria-label="' + esc(o.label || 'chart') + '">';
    for (var g = 0; g <= 4; g++) { var y = Tp + h - h * g / 4; s += '<line class="gl" x1="' + L + '" x2="' + (W - R) + '" y1="' + y + '" y2="' + y + '"/><text x="' + (L - 6) + '" y="' + (y + 4) + '" text-anchor="end">' + esc(o.fb ? o.fb(bm * g / 4) : num(bm * g / 4, 0)) + '</text>'; if (line) s += '<text x="' + (W - R + 6) + '" y="' + (y + 4) + '">' + esc(o.fl ? o.fl(lm * g / 4) : num(lm * g / 4, 0)) + '</text>'; }
    var step = Math.ceil(n / 12);
    labels.forEach(function (lb, i) {
      var x = L + bw * i, bh = h * (bars[i] || 0) / bm;
      var bwid = Math.min(46, Math.max(1, bw * .64));
      s += '<rect x="' + (x + (bw - bwid) / 2) + '" y="' + (Tp + h - bh) + '" width="' + bwid + '" height="' + Math.max(0, bh) + '" rx="2" fill="' + (o.barColor || 'var(--c1-soft)') + '"/>';
      if (i % step === 0) s += '<text x="' + (x + bw / 2) + '" y="' + (H - 10) + '" text-anchor="middle">' + esc(o.xl ? o.xl(lb) : lb) + '</text>';
    });
    if (line) { s += '<polyline fill="none" stroke="var(--brand)" stroke-width="2.4" stroke-linejoin="round" points="' + labels.map(function (lb, i) { return (L + bw * i + bw / 2) + ',' + (Tp + h - h * (line[i] || 0) / lm); }).join(' ') + '"/>'; if (n <= 45) labels.forEach(function (lb, i) { s += '<circle cx="' + (L + bw * i + bw / 2) + '" cy="' + (Tp + h - h * (line[i] || 0) / lm) + '" r="2.8" fill="var(--brand)"/>'; }); }
    labels.forEach(function (lb, i) { s += '<rect data-i="' + i + '" x="' + (L + bw * i) + '" y="' + Tp + '" width="' + bw + '" height="' + h + '" fill="transparent"/>'; });
    s += '</svg>';
    var node = el('<div>' + s + (o.legend ? '<div class="cc-legend">' + o.legend + '</div>' : '') + '</div>');
    node.querySelectorAll('rect[data-i]').forEach(function (r) { r.addEventListener('mousemove', function (e) { var i = +r.getAttribute('data-i'); tip(e, o.tip ? o.tip(i) : esc(labels[i]) + ': ' + num(bars[i])); }); r.addEventListener('mouseleave', function () { tip(null); }); });
    return node;
  }
  /** grouped bars: series [{name, values, color}] */
  function chartGroups(labels, series, o) {
    o = o || {};
    var W = 760, H = o.h || 210, L = 40, R = 10, Tp = 10, B = 28, w = W - L - R, h = H - Tp - B, n = labels.length || 1, k = series.length;
    var all = []; series.forEach(function (x) { all = all.concat(x.values); });
    var m = nice(Math.max.apply(null, all.concat([0])) * 1.08), gw = w / n, bw = gw * .8 / k;
    var s = '<svg class="cc-chart" viewBox="0 0 ' + W + ' ' + H + '">';
    for (var g = 0; g <= 4; g++) { var y = Tp + h - h * g / 4; s += '<line class="gl" x1="' + L + '" x2="' + (W - R) + '" y1="' + y + '" y2="' + y + '"/><text x="' + (L - 6) + '" y="' + (y + 4) + '" text-anchor="end">' + num(m * g / 4, 0) + '</text>'; }
    labels.forEach(function (lb, i) {
      series.forEach(function (x, j) { var bh = h * (x.values[i] || 0) / m; s += '<rect x="' + (L + gw * i + gw * .1 + bw * j) + '" y="' + (Tp + h - bh) + '" width="' + Math.max(1, bw - 1) + '" height="' + bh + '" rx="1.5" fill="' + x.color + '"/>'; });
      if (i % Math.ceil(n / 12) === 0) s += '<text x="' + (L + gw * i + gw / 2) + '" y="' + (H - 9) + '" text-anchor="middle">' + esc(lb) + '</text>';
      s += '<rect data-i="' + i + '" x="' + (L + gw * i) + '" y="' + Tp + '" width="' + gw + '" height="' + h + '" fill="transparent"/>';
    });
    s += '</svg>';
    var node = el('<div>' + s + '<div class="cc-legend">' + series.map(function (x) { return '<span><i style="background:' + x.color + '"></i>' + esc(x.name) + '</span>'; }).join('') + '</div></div>');
    node.querySelectorAll('rect[data-i]').forEach(function (r) { r.addEventListener('mousemove', function (e) { var i = +r.getAttribute('data-i'); tip(e, '<b>' + esc(labels[i]) + '</b><br>' + series.map(function (x) { return esc(x.name) + ': ' + num(x.values[i]); }).join('<br>')); }); r.addEventListener('mouseleave', function () { tip(null); }); });
    return node;
  }

  /* ------------------------------------------------------------------ drawer and modal */
  function openDrawer(title, body) { $('ccDTitle').textContent = title; var b = $('ccDBody'); b.innerHTML = ''; if (typeof body === 'string') b.innerHTML = body; else if (body) b.appendChild(body); $('ccDrawer').classList.add('is-on'); $('ccOver').classList.add('is-on'); }
  function closeDrawer() { $('ccDrawer').classList.remove('is-on'); if (!$('ccModal').classList.contains('is-on')) $('ccOver').classList.remove('is-on'); }
  function openModal(title, body, buttons) {
    $('ccMTitle').textContent = title; var b = $('ccMBody'); b.innerHTML = ''; if (typeof body === 'string') b.innerHTML = body; else b.appendChild(body);
    var f = $('ccMFoot'); f.innerHTML = '';
    (buttons || []).concat([{ t: 'Close', fn: closeModal }]).forEach(function (x) { var bt = document.createElement('button'); bt.type = 'button'; bt.className = 'cc-btn ' + (x.cls || ''); bt.textContent = x.t; bt.addEventListener('click', function () { x.fn(bt); }); f.appendChild(bt); });
    $('ccModal').classList.add('is-on'); $('ccOver').classList.add('is-on');
    var first = b.querySelector('input,select,textarea'); if (first) setTimeout(function () { first.focus(); }, 30);
  }
  function closeModal() { $('ccModal').classList.remove('is-on'); if (!$('ccDrawer').classList.contains('is-on')) $('ccOver').classList.remove('is-on'); }
  $('ccDClose').addEventListener('click', closeDrawer);
  $('ccMClose').addEventListener('click', closeModal);
  $('ccOver').addEventListener('click', function () { if ($('ccModal').classList.contains('is-on')) closeModal(); else closeDrawer(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { if ($('ccModal').classList.contains('is-on')) closeModal(); else closeDrawer(); } });
  /** Run a POST from a button: busy state, toast, then callback on success. */
  function act(btn, path, body, done) {
    if (btn) { btn.disabled = true; btn.dataset.t = btn.textContent; btn.textContent = 'Working…'; }
    return post(path, body).then(function (r) {
      if (btn) { btn.disabled = false; btn.textContent = btn.dataset.t; }
      toast(r.message || (r.ok ? 'Done.' : 'Could not do that.'), r.ok ? 'ok' : 'bad');
      if (r.ok && done) done(r);
      return r;
    });
  }

  /* ------------------------------------------------------------------ filter bar */
  var M = C.meta || { states: [], cities: [], hubs: [], types: [] };
  var ALL = C.role === 'country' ? 'All India' : 'All your hubs';
  var hubName = function (id) { var h = M.hubs.filter(function (x) { return String(x.id) === String(id); })[0]; return h ? h.name : ''; };
  function fillFilters() {
    var fs = $('fState'), fc = $('fCity'), fh = $('fHub'), ft = $('fType');
    var opt = function (v, t, sel) { return '<option value="' + esc(v) + '"' + (String(sel) === String(v) ? ' selected' : '') + '>' + esc(t) + '</option>'; };
    $('fStateWrap').hidden = !M.states.length;
    $('fTypeWrap').hidden = M.types.length < 2;
    fs.innerHTML = opt('', 'All states', S.f.state) + M.states.map(function (s) { return opt(s.id, s.name, S.f.state); }).join('');
    var cities = M.cities.filter(function (c) { return !S.f.state || String(c.state_id) === String(S.f.state); });
    if (S.f.city && !cities.some(function (c) { return String(c.id) === String(S.f.city); })) S.f.city = '';
    fc.innerHTML = opt('', 'All cities', S.f.city) + cities.map(function (c) { return opt(c.id, c.name, S.f.city); }).join('');
    var hubs = M.hubs.filter(function (h) { return (!S.f.state || String(h.state_id) === String(S.f.state)) && (!S.f.city || String(h.city_id) === String(S.f.city)) && (!S.f.htype || String(h.type).toLowerCase() === String(S.f.htype).toLowerCase()) && (S.f.hstatus === 'all' || (S.f.hstatus === 'active') === !!h.active); });
    if (S.f.hub && !hubs.some(function (h) { return String(h.id) === String(S.f.hub); })) S.f.hub = '';
    fh.innerHTML = opt('', 'All hubs (' + hubs.length + ')', S.f.hub) + hubs.map(function (h) { return opt(h.id, h.name + (h.city ? ' · ' + h.city : '') + (h.active ? '' : ' (inactive)'), S.f.hub); }).join('');
    ft.innerHTML = opt('', 'Own + franchise', S.f.htype) + M.types.map(function (t) { return opt(t, t.charAt(0).toUpperCase() + t.slice(1), S.f.htype); }).join('');
    $('fStatus').value = S.f.hstatus; $('fPreset').value = S.preset; $('fFrom').value = S.from; $('fTo').value = S.to; $('fCustom').hidden = S.preset !== 'custom';
    var lbl = S.f.hub ? hubName(S.f.hub) : S.f.city ? (M.cities.filter(function (c) { return String(c.id) === String(S.f.city); })[0] || {}).name : S.f.state ? (M.states.filter(function (s) { return String(s.id) === String(S.f.state); })[0] || {}).name : ALL;
    $('ccScope').innerHTML = 'Showing <b>' + esc(lbl || ALL) + '</b>' + (S.f.htype ? ' · ' + esc(S.f.htype) : '') + ' · ' + hubs.length + ' hub' + (hubs.length === 1 ? '' : 's');
  }
  ['fState', 'fCity', 'fHub', 'fType', 'fStatus'].forEach(function (id) {
    $(id).addEventListener('change', function () {
      var k = { fState: 'state', fCity: 'city', fHub: 'hub', fType: 'htype', fStatus: 'hstatus' }[id];
      S.f[k] = this.value;
      if (k === 'state') { S.f.city = ''; S.f.hub = ''; } if (k === 'city') S.f.hub = '';
      fillFilters(); save(); render();
    });
  });
  $('fPreset').addEventListener('change', function () { setPreset(this.value); fillFilters(); save(); if (this.value !== 'custom') render(); });
  ['fFrom', 'fTo'].forEach(function (id) { $(id).addEventListener('change', function () { if (!$('fFrom').value || !$('fTo').value) return; S.from = $('fFrom').value; S.to = $('fTo').value; if (S.from > S.to) { var x = S.from; S.from = S.to; S.to = x; } save(); render(); }); });
  $('fClear').addEventListener('click', function () { S.f = { state: '', city: '', hub: '', htype: '', hstatus: 'all' }; setPreset('today'); fillFilters(); save(); render(); });
  function setScopeHub(id) { S.f.hub = String(id); S.f.city = ''; S.f.state = ''; S.f.hstatus = 'all'; fillFilters(); save(); }
  var rangeText = function () { return S.from === S.to ? dOnly(Date.parse(S.from + 'T12:00:00') / 1000) : dOnly(Date.parse(S.from + 'T12:00:00') / 1000) + ' – ' + dOnly(Date.parse(S.to + 'T12:00:00') / 1000); };
  function hubOptions(sel, onlyActive) { return M.hubs.filter(function (h) { return !onlyActive || h.active; }).map(function (h) { return '<option value="' + h.id + '"' + (String(sel) === String(h.id) ? ' selected' : '') + '>' + esc(h.name + (h.city ? ' · ' + h.city : '')) + '</option>'; }).join(''); }

  /* ------------------------------------------------------------------ shared drawers */
  function orderDrawer(id) {
    openDrawer('Order ' + id, '<div class="cc-load"><i></i>Loading…</div>');
    api('/order/' + encodeURIComponent(id), {}, false).then(function (r) {
      if (!r.ok) { $('ccDBody').innerHTML = '<div class="cc-empty">' + esc(r.message) + '</div>'; return; }
      var o = r.order, h = r.hub || {};
      var lines = (o.lines || []).map(function (l) { return '<tr><td>' + esc(l.name) + (l.label ? '<small>' + esc(l.label) + '</small>' : '') + '</td><td class="n">' + num(l.qty) + '</td><td class="n">' + money2(l.price) + '</td><td class="n">' + money2(l.line) + '</td></tr>'; }).join('');
      var canCancel = ['new', 'accepted', 'out'].indexOf(o.stage) >= 0, canMove = ['new', 'accepted'].indexOf(o.stage) >= 0;
      var html = '<div class="cc-chips">' + stagePill(o.stage) + pill(o.status) + (o.late ? pill('Late', 'p-bad') : '') + pill(o.delivery_type + (o.slot ? ' · ' + o.slot : ''), 'p-info') + pill(o.payment_type + ' · ' + o.payment_status) + '</div>' +
        '<dl class="cc-dl"><dt>Hub</dt><dd>' + esc(h.name || '') + (h.city ? ', ' + esc(h.city) : '') + (h.state ? ', ' + esc(h.state) : '') + '</dd><dt>Placed</dt><dd>' + dt(o.placed_at) + '</dd><dt>Due</dt><dd>' + dt(o.due_at) + (o.delivered_at ? ' · delivered ' + dt(o.delivered_at) : '') + '</dd>' +
        '<dt>Customer</dt><dd>' + esc(o.customer) + (o.phone ? ' · <a href="tel:' + esc(o.phone) + '">' + esc(o.phone) + '</a>' : '') + '</dd><dt>Address</dt><dd>' + esc(o.address || o.area || '–') + '</dd><dt>Rider</dt><dd>' + esc(o.rider || '–') + '</dd>' +
        (o.schedule_date ? '<dt>Scheduled for</dt><dd>' + esc(o.schedule_date) + ' ' + esc(o.slot) + '</dd>' : '') + (o.cancel_reason ? '<dt>Cancel reason</dt><dd>' + esc(o.cancel_reason) + '</dd>' : '') + '</dl>' +
        '<div class="cc-card"><div class="cc-body flush"><div class="cc-tw"><table class="cc-t"><thead><tr><th>Item</th><th class="n">Qty</th><th class="n">Price</th><th class="n">Line</th></tr></thead><tbody>' + lines + '</tbody><tfoot>' +
        '<tr><td colspan="3">Items</td><td class="n">' + money2(o.items_value) + '</td></tr><tr><td colspan="3">Delivery</td><td class="n">' + money2(o.delivery_charge) + '</td></tr>' + (o.discount ? '<tr><td colspan="3">Discount' + (o.promo ? ' (' + esc(o.promo) + ')' : '') + '</td><td class="n">-' + money2(o.discount) + '</td></tr>' : '') +
        (o.adjustment ? '<tr><td colspan="3">Weight adjustment</td><td class="n">' + money2(o.adjustment) + '</td></tr>' : '') + '<tr><td colspan="3">Amount</td><td class="n">' + money2(o.amount) + '</td></tr></tfoot></table></div></div></div>' +
        '<div><b>Timeline</b><ul class="cc-tl" style="margin-top:8px">' + (o.timeline || []).map(function (t) { return '<li><i></i><div>' + esc(t.label) + '<br><small>' + dStr(t.at) + (t.by ? ' · ' + esc(t.by) : '') + '</small></div></li>'; }).join('') + '</ul></div>' +
        (canCancel || canMove ? '<div class="cc-row">' + (canMove ? '<button type="button" class="cc-btn" id="oMove">Move to another hub</button>' : '') + (canCancel ? '<button type="button" class="cc-btn danger" id="oCancel">Cancel order</button>' : '') + '</div>' : '') +
        '<p class="cc-note">Order work (accept, weigh, dispatch, settle) is done by the hub in its POS console. Changes made here use the same rules and put stock back when an order is cancelled or moved.</p>';
      $('ccDBody').innerHTML = html;
      var bc = $('oCancel'), bm = $('oMove');
      if (bc) bc.addEventListener('click', function () {
        openModal('Cancel order ' + id, '<label class="cc-f"><span>Reason (the customer and hub will see this)</span><select class="cc-sel" id="cR"><option>Customer asked to cancel</option><option>Out of stock</option><option>Customer not reachable</option><option>Duplicate order</option><option>Outside delivery area</option><option>Payment problem</option><option value="other">Other…</option></select></label><label class="cc-f" id="cRO" hidden><span>Write the reason</span><input class="cc-in" id="cRT" maxlength="180"></label>',
          [{ t: 'Cancel order', cls: 'pri', fn: function (b) { var v = $('cR').value === 'other' ? $('cRT').value.trim() : $('cR').value; if (!v) { toast('Write the reason.', 'bad'); return; } act(b, '/order/' + encodeURIComponent(id) + '/cancel', { reason: v }, function () { closeModal(); orderDrawer(id); refreshSoft(); }); } }]);
        $('cR').addEventListener('change', function () { $('cRO').hidden = this.value !== 'other'; });
      });
      if (bm) bm.addEventListener('click', function () {
        openModal('Move order ' + id, '<label class="cc-f"><span>Move to hub</span><select class="cc-sel" id="mH">' + (r.move_to || []).map(function (x) { return '<option value="' + x.id + '">' + esc(x.name + (x.city ? ' · ' + x.city : '')) + '</option>'; }).join('') + '</select></label><label class="cc-f"><span>Note for the hubs</span><input class="cc-in" id="mN" maxlength="150" placeholder="e.g. closer to the customer"></label>',
          [{ t: 'Move order', cls: 'pri', fn: function (b) { act(b, '/order/' + encodeURIComponent(id) + '/move', { to_hub: $('mH').value, note: $('mN').value }, function () { closeModal(); orderDrawer(id); refreshSoft(); }); } }]);
      });
    });
  }

  function writeOffModal(hubId, p, after) {
    var reasons = C.reasons || {};
    openModal('Write off: ' + p.name, '<p class="cc-note">' + esc(hubName(hubId)) + ' · live ' + num(p.live) + (p.expired ? ' · expired ' + num(p.expired) : '') + (p.danger ? ' · danger ' + num(p.danger) : '') + '. Oldest stock is written off first.</p>' +
      '<div class="cc-row"><label class="cc-f"><span>Quantity</span><input class="cc-in" id="wQ" type="number" min="0" step="0.001" value="' + (p.expired || '') + '"></label><label class="cc-f"><span>Reason</span><select class="cc-sel" id="wR">' + Object.keys(reasons).map(function (k) { return '<option value="' + esc(k) + '"' + (k === (p.expired ? 'expired' : 'spoiled') ? ' selected' : '') + '>' + esc(reasons[k]) + '</option>'; }).join('') + '</select></label></div>' +
      '<label class="cc-f"><span>Note (what happened, who checked)</span><input class="cc-in" id="wN" maxlength="160"></label>',
      [{ t: 'Write off', cls: 'pri', fn: function (b) { var q = parseFloat($('wQ').value); if (!(q > 0)) { toast('Enter the quantity.', 'bad'); return; } if (!confirm('Write off ' + q + ' of ' + p.name + ' at ' + hubName(hubId) + '? This takes it off live stock.')) return; act(b, '/wastage', { hub_id: hubId, product_id: p.id, qty: q, reason: $('wR').value, note: $('wN').value }, function () { closeModal(); if (after) after(); }); } }]);
  }
  function countModal(hubId, p, after) {
    openModal('Correct stock: ' + p.name, '<p class="cc-note">' + esc(hubName(hubId)) + ' · system shows ' + num(p.live) + '. Use this after a physical count; the difference is recorded in the stock ledger with your name.</p>' +
      '<label class="cc-f"><span>Counted quantity on the shelf</span><input class="cc-in" id="kQ" type="number" min="0" step="0.001" value="' + num(p.live, 3).replace(/,/g, '') + '"></label><label class="cc-f"><span>Note (required)</span><input class="cc-in" id="kN" maxlength="200" placeholder="e.g. physical count by area manager"></label>',
      [{ t: 'Save count', cls: 'pri', fn: function (b) { act(b, '/stock/count', { hub_id: hubId, product_id: p.id, counted: $('kQ').value, note: $('kN').value }, function () { closeModal(); if (after) after(); }); } }]);
  }

  function productDrawer(id, name) {
    openDrawer(name || 'Product', '<div class="cc-load"><i></i>Loading…</div>');
    api('/stock/product/' + id, {}, false).then(function (r) {
      if (!r.ok) { $('ccDBody').innerHTML = '<div class="cc-empty">' + esc(r.message) + '</div>'; return; }
      var p = r.product, b = $('ccDBody');
      var tot = r.rows.reduce(function (a, x) { a.live += x.live; a.d += x.danger; a.e += x.expired; a.s += x.sold_today; a.v += x.value; return a; }, { live: 0, d: 0, e: 0, s: 0, v: 0 });
      b.innerHTML = '<div class="cc-kv"><div><small>Pack</small><b>' + esc(p.pack || '–') + '</b></div><div><small>Price</small><b>' + money2(p.price) + '</b></div><div><small>Category</small><b>' + esc(p.category) + '</b></div><div><small>Live (all hubs)</small><b>' + num(tot.live) + '</b></div><div><small>Value</small><b>' + money(tot.v) + '</b></div><div><small>Danger / expired</small><b>' + num(tot.d) + ' / ' + num(tot.e) + '</b></div><div><small>Sold today</small><b>' + num(tot.s) + '</b></div></div>' + slot('t');
      mount(b, 't', T([
        { k: 'hub', t: 'Hub', f: function (x) { return esc(x.hub) + '<small>' + esc(x.city) + '</small>'; } },
        { k: 'live', t: 'Live', n: 1, f: function (x) { return x.listed ? '<b>' + num(x.live) + '</b>' : '<span class="muted">not listed</span>'; } },
        { k: 'fresh', t: 'Fresh', n: 1, f: function (x) { return num(x.fresh); } },
        { k: 'danger', t: 'Danger', n: 1, f: function (x) { return x.danger ? '<b style="color:var(--warn)">' + num(x.danger) + '</b>' : '0'; } },
        { k: 'expired', t: 'Expired', n: 1, f: function (x) { return x.expired ? '<b style="color:var(--bad)">' + num(x.expired) + '</b>' : '0'; } },
        { k: 'pending', t: 'Pending in', n: 1, f: function (x) { return num(x.pending); } },
        { k: 'per_day', t: 'Sells / day', n: 1, f: function (x) { return num(x.per_day); } },
        { k: 'cover_days', t: 'Cover', n: 1, f: function (x) { return x.cover_days == null ? '–' : num(x.cover_days, 1) + ' d'; } },
        { k: 'a', t: '', nosort: 1, csv: false, f: function (x) { return x.listed ? '<button type="button" class="cc-btn sm" data-w="' + x.hub_id + '">Write off</button> <button type="button" class="cc-btn sm" data-k="' + x.hub_id + '">Count</button>' : ''; } }
      ], r.rows, { noPage: 1, csv: 'stock-' + p.code + '.csv', after: function (w, list) {
        w.querySelectorAll('[data-w]').forEach(function (bt) { bt.addEventListener('click', function () { var x = list.filter(function (y) { return String(y.hub_id) === bt.getAttribute('data-w'); })[0]; writeOffModal(x.hub_id, { id: p.id, name: p.name, live: x.live, danger: x.danger, expired: x.expired }, function () { productDrawer(id, name); }); }); });
        w.querySelectorAll('[data-k]').forEach(function (bt) { bt.addEventListener('click', function () { var x = list.filter(function (y) { return String(y.hub_id) === bt.getAttribute('data-k'); })[0]; countModal(x.hub_id, { id: p.id, name: p.name, live: x.live }, function () { productDrawer(id, name); }); }); });
      } }));
    });
  }

  function customerDrawer(id) {
    openDrawer('Customer', '<div class="cc-load"><i></i>Loading…</div>');
    api('/customers/' + id, {}, false).then(function (r) {
      if (!r.ok) { $('ccDBody').innerHTML = '<div class="cc-empty">' + esc(r.message) + '</div>'; return; }
      var c = r.customer, b = $('ccDBody');
      $('ccDTitle').textContent = c.name || ('Customer #' + c.id);
      var live = r.orders.filter(function (o) { return o.stage !== 'cancelled'; });
      b.innerHTML = '<dl class="cc-dl"><dt>Phone</dt><dd>' + (c.phone ? '<a href="tel:' + esc(c.phone) + '">' + esc(c.phone) + '</a>' : '–') + '</dd><dt>Email</dt><dd>' + esc(c.email || '–') + '</dd><dt>Wallet</dt><dd>' + (c.wallet_amount != null ? money2(c.wallet_amount) : '–') + '</dd><dt>Orders</dt><dd>' + live.length + ' (' + money(live.reduce(function (a, o) { return a + o.value; }, 0)) + ')</dd></dl>' +
        (r.addresses.length ? '<div><b>Addresses</b>' + r.addresses.map(function (a) { return '<p class="cc-note">' + esc([a.name, a.address, a.locality, a.city, a.pincode].filter(Boolean).join(', ')) + '</p>'; }).join('') + '</div>' : '') + slot('o') + (r.wallet_ledger.length ? '<b>Wallet ledger</b>' + slot('w') : '');
      mount(b, 'o', T([{ k: 'order_id', t: 'Order' }, { k: 'at', t: 'Date', n: 1, f: function (o) { return dt(o.at); } }, { k: 'hub', t: 'Hub' }, { k: 'value', t: 'Value', n: 1, f: function (o) { return money(o.value); } }, { k: 'pay', t: 'Pay' }, { k: 'stage', t: 'Status', f: function (o) { return stagePill(o.stage); } }],
        r.orders, { per: 15, click: function (o) { orderDrawer(o.order_id); } }));
      if (r.wallet_ledger.length) mount(b, 'w', T([{ k: 'created_at', t: 'When', f: function (l) { return dStr(l.created_at); } }, { k: 'event_type', t: 'Type' }, { k: 'amount', t: 'Amount', n: 1, f: function (l) { return money2(l.amount); } }, { k: 'balance_after', t: 'Balance', n: 1, f: function (l) { return money2(l.balance_after); } }, { k: 'description', t: 'Note' }], r.wallet_ledger, { per: 10 }));
    });
  }

  /* ------------------------------------------------------------------ views */
  var V = {};

  V.overview = { t: 'Command centre', dates: true, auto: 90, run: function (root) {
    return api('/overview').then(function (d) {
      var k = d.kpis;
      badge('live', k.late_now); badge('danger', k.expired_items); badge('cash', k.cash_over);
      var h = '<div class="cc-kpis">' +
        kpi('Sales', money(k.sales), vs(k.sales_vs) + ' vs ' + money(k.sales_prev), '', 'reports') +
        kpi('Orders', num(k.orders, 0), vs(k.orders_vs) + ' · ' + num(k.express, 0) + ' express / ' + num(k.scheduled, 0) + ' sched.', '', 'orders') +
        kpi('Avg order value', money(k.aov), vs(k.aov_vs)) +
        kpi('Cancelled', num(k.cancelled, 0), pct(k.cancel_rate) + ' of orders', k.cancel_rate >= 10 ? 'warn' : '', 'cancels') +
        kpi('On time', k.on_time == null ? '–' : k.on_time + '%', 'avg Express ' + (k.avg_min == null ? '–' : k.avg_min + ' min') + ' · ' + num(k.delivered, 0) + ' delivered', k.on_time != null && k.on_time < 85 ? 'warn' : '') +
        kpi('Customers', num(k.customers, 0), num(k.new_customers, 0) + ' new · ' + num(k.repeat_customers, 0) + ' repeat', '', 'customers') +
        kpi('Live now', num(k.open_now, 0) + ' open', k.late_now ? '<span class="down">' + k.late_now + ' late</span>' : 'none late', k.late_now ? 'bad' : '', 'live') +
        kpi('Wastage', money(k.wastage_value), k.wastage_pct == null ? 'no sales' : pct(k.wastage_pct) + ' of sales (limit ' + C.wasteLimit + '%)', k.wastage_pct > C.wasteLimit ? 'warn' : '', 'wastage') +
        kpi('Stock value', money(k.stock_value), num(k.out_items, 0) + ' line(s) out of stock', '', 'stock') +
        kpi('Danger + expired', money(k.danger_value + k.expired_value), money(k.expired_value) + ' expired', k.expired_value > 0 ? 'bad' : k.danger_value > 0 ? 'warn' : '', 'danger') +
        kpi('Cash at hubs', k.cash_in_hand == null ? '–' : money(k.cash_in_hand), k.cash_over ? '<span class="down">' + k.cash_over + ' hub(s) over limit</span>' : 'within limits', k.cash_over ? 'warn' : '', 'cash') +
        kpi('Hubs', num(k.hubs_selling, 0) + ' / ' + num(k.hubs_active, 0), 'selling / active hubs', '', 'league') + '</div>';
      h += '<div class="cc-grid g32">' + card('Sales and orders by day', slot('trend'), { sub: esc(d.scope) + ' · ' + rangeText() + ' · compared with ' + esc(d.compare) }) +
        card('Smart alerts', '<div class="cc-ins">' + d.insights.map(function (i) { return '<a data-go="' + esc(i.view) + '"><i class="lv-' + i.level + '"></i><div><b>' + esc(i.title) + '</b><span>' + esc(i.text) + '</span></div></a>'; }).join('') + '</div>', { flush: 1 }) + '</div>';
      h += card('Live order pipeline', '<div class="cc-pipe">' + [['new', 'New'], ['accepted', 'Accepted'], ['out', 'Out for delivery'], ['late', 'Late now'], ['later', 'Scheduled later']].map(function (x) { return '<div class="' + (x[0] === 'late' && d.pipeline.late ? 'late' : '') + '" data-go="live"><small>' + x[1] + '</small><b>' + num(d.pipeline[x[0]], 0) + '</b></div>'; }).join('') + '</div>');
      if (d.targets && d.targets.length) { var tg = d.targets[0]; h += card('Monthly target: ' + tg.label, '<div class="cc-kv"><div><small>Sales so far</small><b>' + money(tg.sales) + '</b></div><div><small>Target</small><b>' + money(tg.sales_target) + '</b></div><div><small>Projected</small><b>' + money(tg.projected) + '</b></div><div><small>Pace</small><b class="' + (tg.pace >= 100 ? 'up' : 'down') + '">' + (tg.pace == null ? '–' : tg.pace + '%') + '</b></div><div><small>Needed per day</small><b>' + money(tg.needed_per_day) + '</b></div></div><div style="margin-top:10px">' + prog(tg.sales_pct, tg.days ? tg.days_passed * 100 / tg.days : null) + '</div>', { actions: '<a class="cc-btn sm" href="#targets">All targets</a>' }); }
      h += '<div class="cc-grid g2">' + card('Orders by hour', slot('hours'), { sub: 'Compared with ' + esc(d.compare) }) + card('Ranking', '<div class="cc-tabs" id="lgTabs"><button type="button" data-l="states" class="is-on">States</button><button type="button" data-l="cities">Cities</button><button type="button" data-l="hubs">Hubs</button></div><div style="margin-top:10px">' + slot('league') + '</div>', { actions: '<a class="cc-btn sm" href="#league">Full ranking</a>' }) + '</div>';
      h += '<div class="cc-grid g3">' + card('Top products', slot('top'), { flush: 1, actions: '<a class="cc-btn sm" href="#reports">Sales report</a>' }) + card('Category mix', hbars(d.categories.map(function (c) { return { label: c.name, value: c.value }; }), 'b2', money)) +
        card('Payment and delivery mix', hbars(d.pay_mix.map(function (p) { return { label: p.type, value: p.sales }; }), '', money) + '<div style="height:14px"></div>' + hbars([{ label: 'Express', value: d.type_mix.Express.sales }, { label: 'Scheduled', value: d.type_mix.Scheduled.sales }], 'b3', money)) + '</div>';
      root.innerHTML = h;
      var days = d.days;
      mount(root, 'trend', chartBarLine(days.map(function (x) { return x.date; }), days.map(function (x) { return x.orders; }), days.map(function (x) { return x.sales; }), {
        xl: function (s) { return s.slice(8, 10) + '/' + s.slice(5, 7); }, fl: function (v) { return v >= 100000 ? (v / 100000).toFixed(1) + 'L' : v >= 1000 ? Math.round(v / 1000) + 'k' : v; },
        legend: '<span><i style="background:var(--c1-soft)"></i>Orders (left)</span><span><i style="background:var(--brand)"></i>Sales ₹ (right)</span>',
        tip: function (i) { var x = days[i]; return '<b>' + dOnly(Date.parse(x.date + 'T12:00:00') / 1000) + '</b><br>' + num(x.orders, 0) + ' orders · ' + money(x.sales) + (x.cancelled ? '<br>' + x.cancelled + ' cancelled' : ''); } }));
      var hl = []; for (var i = 0; i < 24; i++) hl.push(String(i).padStart(2, '0'));
      mount(root, 'hours', chartGroups(hl, [{ name: 'This period', values: d.hours.cur, color: 'var(--c1)' }, { name: 'Compared period', values: d.hours.prev, color: 'var(--c1-soft)' }]));
      var lgCols = function (lvl) { return [{ k: 'rank', t: '#', n: 1 }, { k: 'name', t: lvl === 'hubs' ? 'Hub' : lvl === 'cities' ? 'City' : 'State', f: function (r) { return esc(r.name) + (r.sub ? '<small>' + esc(r.sub) + '</small>' : ''); } }, { k: 'sales', t: 'Sales', n: 1, f: function (r) { return money(r.sales); } }, { k: 'orders', t: 'Orders', n: 1 }, { k: 'growth', t: 'Growth', n: 1, f: function (r) { return vs(r.growth); } }, { k: 'share', t: 'Share', n: 1, f: function (r) { return pct(r.share); } }]; };
      var drawLg = function (lvl) { var s = root.querySelector('[data-slot="league"]') || root.querySelector('#lgBox'); var n = T(lgCols(lvl), d.league[lvl], { per: 8, click: lvl === 'hubs' ? function (r) { setScopeHub(r.id); render(); } : null }); n.id = 'lgBox'; s.replaceWith(n); };
      drawLg('states' in d.league && d.league.states.length ? 'states' : 'hubs');
      if (!d.league.states.length) { var tb = root.querySelector('#lgTabs [data-l="states"]'); if (tb) tb.remove(); root.querySelector('#lgTabs [data-l="hubs"]').classList.add('is-on'); }
      root.querySelectorAll('#lgTabs button').forEach(function (b) { b.addEventListener('click', function () { root.querySelectorAll('#lgTabs button').forEach(function (x) { x.classList.toggle('is-on', x === b); }); drawLg(b.getAttribute('data-l')); }); });
      mount(root, 'top', T([{ k: 'name', t: 'Product', f: function (p) { return esc(p.name) + '<small>' + esc(p.pack) + ' · ' + esc(p.category) + '</small>'; } }, { k: 'qty', t: 'Qty', n: 1, f: function (p) { return num(p.qty); } }, { k: 'value', t: 'Value', n: 1, f: function (p) { return money(p.value); } }], d.top_products, { noPage: 1 }));
      wireGo(root);
    });
  } };

  V.live = { t: 'Live board', dates: false, auto: 30, run: function (root) {
    return api('/live', {}, false).then(function (d) {
      badge('live', d.stages.late);
      root.innerHTML = '<div class="cc-pipe">' + [['new', 'New'], ['accepted', 'Accepted / packing'], ['out', 'Out for delivery'], ['late', 'Late now'], ['later', 'Scheduled for later']].map(function (x) { return '<div class="' + (x[0] === 'late' && d.stages.late ? 'late' : '') + '"><small>' + x[1] + '</small><b>' + num(d.stages[x[0]], 0) + '</b></div>'; }).join('') + '</div>' +
        '<div class="cc-grid g2">' + card('Hubs right now', slot('h'), { flush: 1, sub: 'Refreshes every 30 seconds. Click a hub to see only that hub.' }) + card('Late orders', slot('l'), { flush: 1, sub: 'Past the promised time and not delivered yet. Click to open.' }) + '</div>';
      mount(root, 'h', T([{ k: 'hub', t: 'Hub', f: function (r) { return esc(r.hub) + '<small>' + esc(r.city) + (r.active ? '' : ' · inactive') + '</small>'; } }, { k: 'new', t: 'New', n: 1 }, { k: 'accepted', t: 'Accepted', n: 1 }, { k: 'out', t: 'Out', n: 1 }, { k: 'late', t: 'Late', n: 1, f: function (r) { return r.late ? '<b style="color:var(--bad)">' + r.late + '</b>' : '0'; } }, { k: 'open', t: 'Open', n: 1 }],
        d.hubs, { noPage: 1, click: function (r) { setScopeHub(r.hub_id); location.hash = 'orders'; S.p.orders = { view: 'live' }; } }));
      mount(root, 'l', T([{ k: 'order_id', t: 'Order', f: function (r) { return esc(r.order_id) + '<small>' + esc(r.type) + '</small>'; } }, { k: 'hub', t: 'Hub', f: function (r) { return esc(r.hub) + '<small>' + esc(r.city) + '</small>'; } }, { k: 'stage', t: 'Stage', f: function (r) { return stagePill(r.stage); } },
        { k: 'due_at', t: 'Late by', n: 1, f: function (r) { return '<b style="color:var(--bad)">' + Math.max(1, Math.round((d.server_time - r.due_at) / 60)) + ' min</b>'; } }, { k: 'value', t: 'Value', n: 1, f: function (r) { return money(r.value); } }], d.late, { per: 25, empty: 'No late orders. Good.', click: function (r) { orderDrawer(r.order_id); } }));
    });
  } };

  V.league = { t: 'State / city / hub ranking', dates: true, run: function (root) {
    return api('/overview').then(function (d) {
      var lvl = S.p.league || (d.league.states.length ? 'states' : 'hubs');
      root.innerHTML = '<div class="cc-tabs" id="lt">' + (d.league.states.length ? '<button type="button" data-l="states">States</button>' : '') + '<button type="button" data-l="cities">Cities</button><button type="button" data-l="hubs">Hubs</button></div>' + card('Ranking by sales', slot('t'), { flush: 1, sub: esc(d.scope) + ' · ' + rangeText() + ' · growth compared with ' + esc(d.compare) });
      root.querySelectorAll('#lt button').forEach(function (b) { b.classList.toggle('is-on', b.getAttribute('data-l') === lvl); b.addEventListener('click', function () { S.p.league = b.getAttribute('data-l'); render(); }); });
      var cols = [{ k: 'rank', t: '#', n: 1 }, { k: 'name', t: lvl === 'hubs' ? 'Hub' : lvl === 'cities' ? 'City' : 'State', f: function (r) { return esc(r.name) + (r.sub ? '<small>' + esc(r.sub) + '</small>' : '') + (r.active === false ? ' ' + pill('inactive') : ''); } },
        { k: 'sales', t: 'Sales', n: 1, f: function (r) { return money(r.sales); } }, { k: 'prev_sales', t: 'Before', n: 1, f: function (r) { return money(r.prev_sales); } }, { k: 'growth', t: 'Growth', n: 1, f: function (r) { return vs(r.growth); } },
        { k: 'orders', t: 'Orders', n: 1 }, { k: 'aov', t: 'AOV', n: 1, f: function (r) { return money(r.aov); } }, { k: 'customers', t: 'Customers', n: 1 }, { k: 'cancel_rate', t: 'Cancel %', n: 1, f: function (r) { return r.cancel_rate >= 15 ? '<b style="color:var(--bad)">' + pct(r.cancel_rate) + '</b>' : pct(r.cancel_rate); } },
        { k: 'share', t: 'Share', n: 1, f: function (r) { return '<div style="display:flex;gap:8px;align-items:center;justify-content:flex-end">' + pct(r.share) + '<div class="bar" style="width:60px"><i style="width:' + r.share + '%"></i></div></div>'; }, c: function (r) { return r.share; } }];
      if (lvl === 'hubs') cols = cols.concat([{ k: 'open', t: 'Open now', n: 1 }, { k: 'late', t: 'Late', n: 1, f: function (r) { return r.late ? '<b style="color:var(--bad)">' + r.late + '</b>' : '0'; } }, { k: 'wastage_pct', t: 'Wastage %', n: 1, f: function (r) { return r.wastage_pct > C.wasteLimit ? '<b style="color:var(--warn)">' + pct(r.wastage_pct) + '</b>' : pct(r.wastage_pct); } },
        { k: 'expired_value', t: 'Expired ₹', n: 1, f: function (r) { return r.expired_value ? '<b style="color:var(--bad)">' + money(r.expired_value) + '</b>' : '0'; } }, { k: 'out_items', t: 'Out of stock', n: 1 }, { k: 'cash', t: 'Cash held', n: 1, f: function (r) { return r.cash == null ? '–' : (r.cash > C.cashLimit ? '<b style="color:var(--warn)">' + money(r.cash) + '</b>' : money(r.cash)); } }]);
      mount(root, 't', T(cols, d.league[lvl], { per: 100, csv: 'ranking-' + lvl + '-' + S.from + '-' + S.to + '.csv', click: lvl === 'hubs' ? function (r) { setScopeHub(r.id); location.hash = 'overview'; } : function (r) { if (lvl === 'states') { S.f.state = String(r.id); S.f.city = ''; } else { S.f.city = String(r.id); } S.f.hub = ''; fillFilters(); save(); S.p.league = lvl === 'states' ? 'cities' : 'hubs'; render(); },
        foot: ['', 'Total', money(d.kpis.sales), money(d.kpis.sales_prev), vs(d.kpis.sales_vs), num(d.kpis.orders, 0), money(d.kpis.aov), num(d.kpis.customers, 0), pct(d.kpis.cancel_rate), '100%'].concat(lvl === 'hubs' ? ['', '', '', '', '', ''] : []) }));
    });
  } };

  function ordersView(root, preset) {
    var p = S.p.orders = Object.assign({ stage: '', status: '', type: '', pay: '', q: '', view: '', page: 1 }, S.p.orders || {}, preset || {});
    var bar = '<div class="cc-chips" style="align-items:center">' +
      (preset ? '' : '<select class="cc-sel" id="oV"><option value="">Placed in the dates</option><option value="live">Open right now</option><option value="late">Late right now</option></select>') +
      '<select class="cc-sel" id="oS"><option value="">All stages</option><option value="new">New</option><option value="accepted">Accepted</option><option value="out">Out for delivery</option><option value="done">Delivered</option><option value="cancelled">Cancelled</option></select>' +
      '<select class="cc-sel" id="oT"><option value="">Express + scheduled</option><option>Express</option><option>Scheduled</option></select>' +
      '<select class="cc-sel" id="oP"><option value="">All payments</option><option>Cash</option><option>Online</option><option>Wallet</option></select>' +
      '<select class="cc-sel" id="oO"><option value="">Any old status</option><option>Order Pending</option><option>Order Placed</option><option>Order Processed</option><option>Order Shipped</option><option>Order Dispatched</option><option>Order Delivered</option><option>Order Cancel</option></select>' +
      '<input class="cc-in" id="oQ" placeholder="Order ID or phone" style="width:180px"><button type="button" class="cc-btn" id="oGo">Search</button><a class="cc-btn" id="oCsv" style="margin-left:auto">Download Excel (CSV)</a></div>';
    root.innerHTML = bar + '<div id="oSum"></div>' + card('Orders', '<div id="oBox"><div class="cc-load"><i></i>Loading…</div></div>', { flush: 1, sub: 'Click an order to see details, cancel it or move it to another hub.' });
    var set = function (id, v) { var e = $(id); if (e) e.value = v; };
    set('oV', p.view); set('oS', p.stage); set('oT', p.type); set('oP', p.pay); set('oO', p.status); set('oQ', p.q);
    var load = function () {
      var params = { stage: p.stage, status: p.status, type: p.type, pay: p.pay, q: p.q, view: p.view, page: p.page };
      $('oCsv').href = C.base + '/orders?' + qs(Object.assign({}, params, { format: 'csv' }));
      $('oBox').innerHTML = '<div class="cc-load"><i></i>Loading…</div>';
      return api('/orders', params).then(function (d) {
        var s = d.summary;
        $('oSum').innerHTML = '<div class="cc-chips"><span class="cc-chip">Orders <b>' + num(s.orders, 0) + '</b></span><span class="cc-chip">Value <b>' + money(s.value) + '</b></span><span class="cc-chip">Hubs <b>' + s.hubs + '</b></span>' + (s.late ? '<span class="cc-chip" style="color:var(--bad)">Late <b>' + s.late + '</b></span>' : '') + Object.keys(s.by_stage).filter(function (k) { return s.by_stage[k]; }).map(function (k) { return '<span class="cc-chip">' + esc((STAGE[k] || [k])[0]) + ' <b>' + s.by_stage[k] + '</b></span>'; }).join('') + '</div>';
        var box = $('oBox'); box.innerHTML = '';
        box.appendChild(T([
          { k: 'order_id', t: 'Order', f: function (o) { return '<b>' + esc(o.order_id) + '</b><small>' + dt(o.placed_at) + '</small>'; } },
          { k: 'hub', t: 'Hub', f: function (o) { return esc(o.hub) + '<small>' + esc(o.city) + '</small>'; } },
          { k: 'customer', t: 'Customer', f: function (o) { return esc(o.customer) + '<small>' + esc(o.phone) + (o.area ? ' · ' + esc(o.area) : '') + '</small>'; } },
          { k: 'amount', t: 'Amount', n: 1, f: function (o) { return money(o.amount) + '<small>' + esc(o.payment_type) + '</small>'; } },
          { k: 'delivery_type', t: 'Delivery', f: function (o) { return esc(o.delivery_type) + (o.schedule_date ? '<small>' + esc(o.schedule_date) + ' ' + esc(o.slot) + '</small>' : ''); } },
          { k: 'stage', t: 'Status', f: function (o) { return stagePill(o.stage) + (o.late ? ' ' + pill('Late', 'p-bad') : '') + (o.cancel_reason ? '<small>' + esc(o.cancel_reason) + '</small>' : ''); } },
          { k: 'rider', t: 'Rider', f: function (o) { return esc(o.rider || '–'); } }
        ], d.orders, { noPage: 1, click: function (o) { orderDrawer(o.order_id); }, empty: 'No orders for these filters.' }));
        if (d.pages > 1) { var pg = el('<div class="cc-pager"><button type="button" class="cc-btn sm" data-p="-1"' + (d.page <= 1 ? ' disabled' : '') + '>‹ Prev</button><span>Page ' + d.page + ' of ' + d.pages + ' (50 per page)</span><button type="button" class="cc-btn sm" data-p="1"' + (d.page >= d.pages ? ' disabled' : '') + '>Next ›</button></div>'); box.appendChild(pg); pg.querySelectorAll('[data-p]').forEach(function (b) { b.addEventListener('click', function () { p.page = d.page + +b.getAttribute('data-p'); load(); }); }); }
      });
    };
    [['oV', 'view'], ['oS', 'stage'], ['oT', 'type'], ['oP', 'pay'], ['oO', 'status']].forEach(function (x) { var e = $(x[0]); if (e) e.addEventListener('change', function () { p[x[1]] = this.value; p.page = 1; load(); }); });
    $('oGo').addEventListener('click', function () { p.q = $('oQ').value.trim(); p.page = 1; load(); });
    $('oQ').addEventListener('keydown', function (e) { if (e.key === 'Enter') { p.q = this.value.trim(); p.page = 1; load(); } });
    return load();
  }
  V.orders = { t: 'All orders', dates: true, run: function (root) { return ordersView(root); } };
  V.sched = { t: 'Scheduled orders', dates: false, run: function (root) {
    var v = S.p.sched || 'sched-today';
    var holder = el('<div style="display:flex;flex-direction:column;gap:14px"><div class="cc-tabs" id="sT"><button type="button" data-v="sched-today">For today</button><button type="button" data-v="sched-tomorrow">For tomorrow</button><button type="button" data-v="sched-upcoming">All upcoming</button></div><div id="sBox"></div></div>');
    root.innerHTML = ''; root.appendChild(holder);
    holder.querySelectorAll('#sT button').forEach(function (b) { b.classList.toggle('is-on', b.getAttribute('data-v') === v); b.addEventListener('click', function () { S.p.sched = b.getAttribute('data-v'); S.p.orders = null; render(); }); });
    S.p.orders = null;
    return ordersView($('sBox'), { view: v });
  } };

  V.cancels = { t: 'Cancellations', dates: true, run: function (root) {
    return api('/orders/cancellations').then(function (d) {
      root.innerHTML = '<div class="cc-kpis">' + kpi('Cancelled orders', num(d.total, 0)) + kpi('Value lost', money(d.value)) + kpi('Top reason', esc(d.reasons[0] ? d.reasons[0].reason : '–'), d.reasons[0] ? d.reasons[0].orders + ' orders' : '') + '</div>' +
        '<div class="cc-grid g2">' + card('Reasons', hbars(d.reasons.map(function (r) { return { label: r.reason, value: r.orders }; }), 'bb', function (v) { return num(v, 0); })) + card('By hub', slot('h'), { flush: 1 }) + '</div>' + card('Cancelled orders', slot('l'), { flush: 1 });
      mount(root, 'h', T([{ k: 'hub', t: 'Hub', f: function (r) { return esc(r.hub) + '<small>' + esc(r.city) + '</small>'; } }, { k: 'orders', t: 'Orders', n: 1 }, { k: 'value', t: 'Value', n: 1, f: function (r) { return money(r.value); } }], d.hubs, { per: 10 }));
      mount(root, 'l', T([{ k: 'order_id', t: 'Order' }, { k: 'at', t: 'Placed', n: 1, f: function (r) { return dt(r.at); }, c: function (r) { return dt(r.at); } }, { k: 'hub', t: 'Hub', f: function (r) { return esc(r.hub) + '<small>' + esc(r.city) + '</small>'; } }, { k: 'value', t: 'Value', n: 1, f: function (r) { return money(r.value); } }, { k: 'type', t: 'Type' }, { k: 'pay', t: 'Pay' }, { k: 'reason', t: 'Reason', f: function (r) { return esc(r.reason || '–'); } }],
        d.rows, { csv: 'cancellations-' + S.from + '-' + S.to + '.csv', click: function (r) { orderDrawer(r.order_id); } }));
    });
  } };

  V.stock = { t: 'Stock by product', dates: false, run: function (root) {
    var p = S.p.stock = Object.assign({ q: '', category: '', status: '' }, S.p.stock || {});
    return api('/stock/products', p, false).then(function (d) {
      root.innerHTML = '<div class="cc-kpis">' + kpi('Stock value', money(d.total.live_value), d.hubs + ' hub(s)') + kpi('Products', num(d.total.products, 0)) + kpi('Out everywhere', num(d.total.out, 0), 'no hub has stock', d.total.out ? 'warn' : '') + kpi('Danger qty', num(d.total.danger_qty), 'received yesterday', d.total.danger_qty ? 'warn' : '', 'danger') + kpi('Expired qty', num(d.total.expired_qty), '2+ days old', d.total.expired_qty ? 'bad' : '', 'danger') + '</div>' +
        '<div class="cc-chips" style="align-items:center"><input class="cc-in" id="sQ" placeholder="Search product or code" style="width:220px" value="' + esc(p.q) + '"><select class="cc-sel" id="sC"><option value="">All categories</option>' + d.categories.map(function (c) { return '<option' + (c === p.category ? ' selected' : '') + '>' + esc(c) + '</option>'; }).join('') + '</select>' +
        '<select class="cc-sel" id="sS"><option value="">All stock</option><option value="gaps">Missing in some hubs</option><option value="out">Out everywhere</option><option value="danger">Has danger stock</option><option value="expired">Has expired stock</option><option value="pending">Pending inward</option><option value="inactive">Inactive products</option></select></div>' +
        card('Stock across hubs', slot('t'), { flush: 1, sub: 'Live = on the shelf now. Fresh = received today, danger = yesterday (sell today), expired = 2+ days. Cover = days the stock lasts at the 7-day selling pace. Click a product for every hub.' });
      $('sS').value = p.status;
      var go = function () { p.q = $('sQ').value.trim(); p.category = $('sC').value; p.status = $('sS').value; render(); };
      $('sQ').addEventListener('keydown', function (e) { if (e.key === 'Enter') go(); }); $('sC').addEventListener('change', go); $('sS').addEventListener('change', go);
      var st = { out: ['Out', 'p-bad'], expired: ['Expired', 'p-bad'], danger: ['Danger', 'p-warn'], gaps: ['Gaps', 'p-warn'], ok: ['OK', 'p-ok'] };
      mount(root, 't', T([
        { k: 'name', t: 'Product', f: function (r) { return '<b>' + esc(r.name) + '</b>' + (r.active ? '' : ' ' + pill('inactive')) + '<small>' + esc(r.pack) + ' · ' + esc(r.category) + ' · ' + esc(r.code) + '</small>'; } },
        { k: 'live', t: 'Live', n: 1, f: function (r) { return '<b>' + num(r.live) + '</b>'; } }, { k: 'fresh', t: 'Fresh', n: 1, f: function (r) { return num(r.fresh); } },
        { k: 'danger', t: 'Danger', n: 1, f: function (r) { return r.danger ? '<b style="color:var(--warn)">' + num(r.danger) + '</b>' : '0'; } }, { k: 'expired', t: 'Expired', n: 1, f: function (r) { return r.expired ? '<b style="color:var(--bad)">' + num(r.expired) + '</b>' : '0'; } },
        { k: 'pending', t: 'Pending in', n: 1, f: function (r) { return num(r.pending); } }, { k: 'value', t: 'Value', n: 1, f: function (r) { return money(r.value); } },
        { k: 'hubs_stocked', t: 'Hubs with stock', n: 1, f: function (r) { return r.hubs_stocked + ' / ' + r.hubs_listed + (r.hubs_out ? '<small style="color:var(--bad)">' + r.hubs_out + ' out</small>' : ''); } },
        { k: 'sold_today', t: 'Sold today', n: 1, f: function (r) { return num(r.sold_today); } }, { k: 'per_day', t: 'Sells / day', n: 1, f: function (r) { return num(r.per_day); } },
        { k: 'cover_days', t: 'Cover', n: 1, f: function (r) { return r.cover_days == null ? '–' : (r.cover_days < 1 ? '<b style="color:var(--bad)">' + num(r.cover_days, 1) + ' d</b>' : num(r.cover_days, 1) + ' d'); } },
        { k: 'status', t: 'Status', f: function (r) { var x = st[r.status] || [r.status]; return pill(x[0], x[1]); } }
      ], d.rows, { csv: 'stock-by-product.csv', click: function (r) { productDrawer(r.id, r.name + ' · ' + r.pack); } }));
    });
  } };

  V['stock-hubs'] = { t: 'Stock by hub', dates: false, run: function (root) {
    return api('/stock/hubs', {}, false).then(function (d) {
      root.innerHTML = card('Hub stock health', slot('t'), { flush: 1, sub: 'Worst first: expired stock, then danger stock and out-of-stock lines. Click a hub to open its stock.' });
      mount(root, 't', T([
        { k: 'hub', t: 'Hub', f: function (r) { return '<b>' + esc(r.hub) + '</b>' + (r.active ? '' : ' ' + pill('inactive')) + '<small>' + esc(r.city) + (r.state ? ', ' + esc(r.state) : '') + (r.type ? ' · ' + esc(r.type) : '') + '</small>'; } },
        { k: 'health', t: 'Health', f: function (r) { return r.health === 'bad' ? pill('Expired stock', 'p-bad') : r.health === 'warn' ? pill('Attention', 'p-warn') : pill('OK', 'p-ok'); } },
        { k: 'value', t: 'Stock value', n: 1, f: function (r) { return money(r.value); } }, { k: 'in_stock', t: 'In stock', n: 1, f: function (r) { return r.in_stock + ' / ' + r.skus; } },
        { k: 'out', t: 'Out', n: 1, f: function (r) { return r.out ? '<b style="color:var(--bad)">' + r.out + '</b>' : '0'; } }, { k: 'low', t: 'Low', n: 1 },
        { k: 'danger_value', t: 'Danger', n: 1, f: function (r) { return r.danger_qty ? money(r.danger_value) + '<small>' + num(r.danger_qty) + ' units</small>' : '0'; } },
        { k: 'expired_value', t: 'Expired', n: 1, f: function (r) { return r.expired_qty ? '<b style="color:var(--bad)">' + money(r.expired_value) + '</b><small>' + num(r.expired_qty) + ' units</small>' : '0'; } },
        { k: 'pending_lines', t: 'Pending in', n: 1 }, { k: 'wastage_7d', t: 'Wastage 7d', n: 1, f: function (r) { return money(r.wastage_7d); } },
        { k: 'last_inward', t: 'Last received', n: 1, f: function (r) { return ago(r.last_inward); }, c: function (r) { return r.last_inward ? dt(r.last_inward) : ''; } }
      ], d.rows, { per: 100, csv: 'stock-by-hub.csv', click: function (r) { setScopeHub(r.hub_id); location.hash = 'stock'; } }));
    });
  } };

  V.danger = { t: 'Danger & expired stock', dates: false, run: function (root) {
    return api('/stock/danger', {}, false).then(function (d) {
      if (!d.ok) { root.innerHTML = card('Danger stock', '<div class="cc-empty">' + esc(d.message) + '</div>'); return; }
      badge('danger', d.rows.filter(function (r) { return r.expired > 0; }).length);
      d.rows.forEach(function (r, i) { r._k = i; });
      root.innerHTML = '<div class="cc-kpis">' + kpi('Danger + expired value', money(d.total.value), d.hubs.length + ' hub(s)', d.total.value ? 'warn' : '') + kpi('Expired value', money(d.total.expired_value), num(d.total.expired) + ' units, 2+ days old', d.total.expired ? 'bad' : '') + kpi('Danger qty', num(d.total.danger), 'received yesterday, sell today') + kpi('Daily plan', C.planTime, 'nearby hubs take what will not sell') + '</div>' +
        card('Hubs with danger or expired stock', slot('h'), { flush: 1, actions: '<button type="button" class="cc-btn pri" id="dAll">Run plan for all hubs shown</button>', sub: 'The ' + C.planTime + ' plan sends stock a hub will not sell today to a nearby hub that is short. Stock moves only when the hub dispatches it with a rider.' }) +
        card('Products', slot('r'), { flush: 1, sub: 'Over = expected not to sell today at the normal pace. Write off what cannot be sold.' });
      $('dAll').addEventListener('click', function () { if (!confirm('Recalculate today\'s danger stock plan for every active hub shown? Already dispatched transfers are not touched.')) return; act(this, '/stock/danger/plan', { hub_id: 0 }, function () { render(); }); });
      mount(root, 'h', T([
        { k: 'hub', t: 'Hub', f: function (r) { return '<b>' + esc(r.hub) + '</b><small>' + esc(r.city) + ' · ' + r.nearby + ' nearby hub(s)</small>'; } },
        { k: 'value', t: 'Value', n: 1, f: function (r) { return money(r.value); } }, { k: 'danger', t: 'Danger', n: 1, f: function (r) { return num(r.danger); } },
        { k: 'expired', t: 'Expired', n: 1, f: function (r) { return r.expired ? '<b style="color:var(--bad)">' + num(r.expired) + '</b>' : '0'; } }, { k: 'over', t: 'Will not sell', n: 1, f: function (r) { return num(r.over); } },
        { k: 'plan_ran', t: 'Plan today', f: function (r) { return r.plan_ran ? pill('Ran ' + new Date(r.plan_ran * 1000).toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' }), 'p-ok') : pill('Not run', 'p-warn'); } },
        { k: 'planned', t: 'Transfers', n: 1, f: function (r) { return r.planned + ' planned · ' + r.dispatched + ' sent'; } },
        { k: 'a', t: '', nosort: 1, csv: false, f: function (r) { return r.active ? '<button type="button" class="cc-btn sm" data-plan="' + r.hub_id + '">Run plan</button>' : ''; } }
      ], d.hubs, { noPage: 1, empty: 'No danger or expired stock. Every hub is selling within 2 days.', after: function (w) { w.querySelectorAll('[data-plan]').forEach(function (b) { b.addEventListener('click', function () { act(b, '/stock/danger/plan', { hub_id: b.getAttribute('data-plan') }, function () { render(); }); }); }); } }));
      mount(root, 'r', T([
        { k: 'name', t: 'Product', f: function (r) { return '<b>' + esc(r.name) + '</b><small>' + esc(r.pack) + '</small>'; } }, { k: 'hub', t: 'Hub', f: function (r) { return esc(r.hub) + '<small>' + esc(r.city) + '</small>'; } },
        { k: 'fresh', t: 'Fresh', n: 1, f: function (r) { return num(r.fresh); } }, { k: 'danger', t: 'Danger', n: 1, f: function (r) { return r.danger ? '<b style="color:var(--warn)">' + num(r.danger) + '</b>' : '0'; } },
        { k: 'expired', t: 'Expired', n: 1, f: function (r) { return r.expired ? '<b style="color:var(--bad)">' + num(r.expired) + '</b>' : '0'; } }, { k: 'value', t: 'Value', n: 1, f: function (r) { return money(r.value); } },
        { k: 'rest_today', t: 'Will sell today', n: 1, f: function (r) { return num(r.rest_today); } }, { k: 'over', t: 'Over', n: 1, f: function (r) { return r.over ? '<b>' + num(r.over) + '</b>' : '0'; } },
        { k: 'plan', t: 'Plan', nosort: 1, f: function (r) { return r.plan.length ? r.plan.map(function (x) { return esc(num(x.qty) + ' → ' + x.to) + (x.km != null ? ' <small>' + x.km + ' km</small>' : ''); }).join('<br>') : '<small>' + esc(r.advice) + '</small>'; }, c: function (r) { return r.plan.map(function (x) { return x.qty + ' to ' + x.to; }).join('; ') || r.advice; } },
        { k: 'a', t: '', nosort: 1, csv: false, f: function (r) { return '<button type="button" class="cc-btn sm" data-wo="' + r._k + '">Write off</button>'; } }
      ], d.rows, { csv: 'danger-stock.csv', click: function (r) { productDrawer(r.id, r.name + ' · ' + r.pack); }, after: function (w) {
        w.querySelectorAll('[data-wo]').forEach(function (b) { b.addEventListener('click', function () { var r = d.rows[+b.getAttribute('data-wo')]; if (!r) return; writeOffModal(r.hub_id, { id: r.id, name: r.name, live: r.fresh + r.danger + r.expired, danger: r.danger, expired: r.expired }, function () { render(); }); }); });
      } }));
    });
  } };

  V.inward = { t: 'Accept inward stocks', dates: false, run: function (root) {
    return api('/stock/inward', {}, false).then(function (d) {
      root.innerHTML = '<div class="cc-kpis">' + kpi('Lines waiting', num(d.total.lines, 0)) + kpi('Value', money(d.total.value)) + kpi('Hubs', num(d.total.hubs, 0)) + '</div>' + card('Sent to hubs, not yet received', slot('t'), { flush: 1, sub: 'Until it is accepted the stock is not on the shelf. The hub normally accepts it in its console; you can accept it here on the hub\'s behalf after checking with them.' });
      d.rows.forEach(function (r, i) { r._k = i; });
      mount(root, 't', T([{ k: 'hub', t: 'Hub', f: function (r) { return esc(r.hub) + '<small>' + esc(r.city) + '</small>'; } }, { k: 'name', t: 'Product', f: function (r) { return esc(r.name) + '<small>' + esc(r.pack) + ' · ' + esc(r.code) + '</small>'; } }, { k: 'qty', t: 'Sent', n: 1, f: function (r) { return num(r.qty); } }, { k: 'value', t: 'Value', n: 1, f: function (r) { return money(r.value); } }, { k: 'live', t: 'Live now', n: 1, f: function (r) { return num(r.live); } }, { k: 'since', t: 'Waiting since', n: 1, f: function (r) { return ago(r.since); }, c: function (r) { return r.since ? dt(r.since) : ''; } },
        { k: 'a', t: '', nosort: 1, csv: false, f: function (r) { return '<button type="button" class="cc-btn sm pri" data-acc="' + r._k + '">Accept</button>'; } }],
        d.rows, { csv: 'pending-inward.csv', empty: 'Nothing waiting. Every hub has accepted its stock.', after: function (w) {
          w.querySelectorAll('[data-acc]').forEach(function (b) { b.addEventListener('click', function () {
            var r = d.rows[+b.getAttribute('data-acc')];
            openModal('Accept inward: ' + r.name, '<p class="cc-note">' + esc(r.hub) + ' · sent ' + num(r.qty) + '. Enter what really arrived; a difference needs a note. The stock is added as today\'s fresh batch, exactly as when the hub accepts it.</p><div class="cc-row"><label class="cc-f"><span>Received quantity</span><input class="cc-in" id="aQ" type="number" min="0" step="0.001" value="' + r.qty + '"></label></div><label class="cc-f"><span>Note</span><input class="cc-in" id="aN" maxlength="200" placeholder="e.g. 2 kg short, damaged box"></label>',
              [{ t: 'Accept stock', cls: 'pri', fn: function (bt) { act(bt, '/stock/accept', { hub_id: r.hub_id, inv_id: r.inv_id, received: $('aQ').value, note: $('aN').value }, function () { closeModal(); render(); }); } }]);
          }); });
        } }));
    });
  } };

  function ledgerView(root, key, defType, title) {
    var p = S.p[key] = Object.assign({ type: defType, q: '' }, S.p[key] || {});
    return api('/stock/ledger', p).then(function (d) {
      var types = d.types || {};
      var groups = { g_inward: 'Inward + transfers (communicator)', g_in: 'Everything coming in', g_out: 'Everything going out', g_transfer: 'Transfers only' };
      root.innerHTML = '<div class="cc-chips" style="align-items:center"><select class="cc-sel" id="lT"><option value="">All movements</option>' + Object.keys(groups).map(function (k) { return '<option value="' + k + '"' + (k === p.type ? ' selected' : '') + '>' + esc(groups[k]) + '</option>'; }).join('') + Object.keys(types).map(function (k) { return '<option value="' + k + '"' + (k === p.type ? ' selected' : '') + '>' + esc(types[k]) + '</option>'; }).join('') + '</select><input class="cc-in" id="lQ" placeholder="Product, order or note" value="' + esc(p.q) + '" style="width:220px"><button type="button" class="cc-btn" id="lGo">Search</button></div>' +
        '<div class="cc-chips">' + Object.keys(d.summary).map(function (k) { var s = d.summary[k]; return '<span class="cc-chip">' + esc(types[k] || k) + ' <b>' + num(s.qty) + '</b> · ' + money(s.value) + '</span>'; }).join('') + '</div>' +
        card(title, slot('t'), { flush: 1, sub: 'Each line is one change made through the stock engine, with who made it.' + (d.capped ? ' Showing the latest 3,000; narrow the dates to see more.' : '') });
      var go = function () { p.type = $('lT').value; p.q = $('lQ').value.trim(); render(); };
      $('lT').addEventListener('change', go); $('lGo').addEventListener('click', go); $('lQ').addEventListener('keydown', function (e) { if (e.key === 'Enter') go(); });
      mount(root, 't', T([{ k: 'at', t: 'When', n: 1, f: function (r) { return dt(r.at); }, c: function (r) { return dt(r.at); } }, { k: 'hub', t: 'Hub', f: function (r) { return esc(r.hub) + '<small>' + esc(r.city) + '</small>'; } }, { k: 'name', t: 'Product', f: function (r) { return esc(r.name) + '<small>' + esc(r.code) + '</small>'; } },
        { k: 'type', t: 'Movement', f: function (r) { return pill(types[r.type] || r.type, r.type === 'wastage' ? 'p-bad' : r.change < 0 ? 'p-warn' : 'p-ok'); }, c: function (r) { return types[r.type] || r.type; } },
        { k: 'before', t: 'Before', n: 1, f: function (r) { return num(r.before); } }, { k: 'change', t: 'Change', n: 1, f: function (r) { return '<b class="' + (r.change < 0 ? 'down' : 'up') + '">' + (r.change > 0 ? '+' : '') + num(r.change) + '</b>'; } }, { k: 'after', t: 'After', n: 1, f: function (r) { return num(r.after); } },
        { k: 'ref', t: 'Ref', f: function (r) { return esc(r.ref || ''); } }, { k: 'note', t: 'Note', f: function (r) { return esc(r.note || ''); } }, { k: 'by', t: 'By' }], d.rows, { csv: key + '-' + S.from + '-' + S.to + '.csv' }));
    });
  }
  V.ledger = { t: 'Stock ledger', dates: true, run: function (root) { return ledgerView(root, 'ledger', '', 'Every stock change'); } };
  V['comm-txn'] = { t: 'Hub inward / outward transactions', dates: true, run: function (root) { return ledgerView(root, 'commtxn', 'g_inward', 'Inward and inter-hub movements'); } };

  V.writeoff = { t: 'Write off / spoilage', dates: false, run: function (root) {
    var reasons = C.reasons || {};
    var hubId = S.p.woHub || S.f.hub || '';
    root.innerHTML = '<div class="cc-grid g2">' + card('Write off stock', '<div class="cc-row"><label class="cc-f"><span>Hub</span><select class="cc-sel" id="wH"><option value="">Choose the hub</option>' + hubOptions(hubId) + '</select></label></div>' +
      '<label class="cc-f"><span>Product</span><select class="cc-sel" id="wP" disabled><option>Choose the hub first</option></select></label><div id="wInfo" class="cc-note"></div>' +
      '<div class="cc-row"><label class="cc-f"><span>Quantity</span><input class="cc-in" id="wQ" type="number" min="0" step="0.001"></label><label class="cc-f"><span>Reason</span><select class="cc-sel" id="wR">' + Object.keys(reasons).map(function (k) { return '<option value="' + esc(k) + '">' + esc(reasons[k]) + '</option>'; }).join('') + '</select></label></div>' +
      '<div class="cc-chips" id="wFill"></div><label class="cc-f"><span>Note (what happened, who checked)</span><textarea id="wN" maxlength="160"></textarea></label><div><button type="button" class="cc-btn pri" id="wGo">Write off</button></div>' +
      '<p class="cc-note">The oldest stock is written off first. It comes off live stock, is recorded in the stock ledger and the wastage report with your name, and the hub sees it in its own console.</p>', { sub: 'For stock that cannot be sold: expired, spoiled, damaged, rejected or missing.' }) +
      card('Today\'s write-offs', '<div id="wToday"><div class="cc-load"><i></i>Loading…</div></div>', { flush: 1 }) + '</div>';
    var prods = [];
    var loadToday = function () { api('/wastage', { from: C.today, to: C.today }, false).then(function (d) { var b = $('wToday'); if (!b) return; b.innerHTML = ''; b.appendChild(T([{ k: 'at', t: 'Time', f: function (r) { return new Date(r.at * 1000).toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' }); } }, { k: 'hub', t: 'Hub' }, { k: 'name', t: 'Product' }, { k: 'qty', t: 'Qty', n: 1, f: function (r) { return num(r.qty); } }, { k: 'value', t: 'Value', n: 1, f: function (r) { return money(r.value); } }, { k: 'reason_label', t: 'Reason' }, { k: 'by', t: 'By' }], d.rows, { per: 15, empty: 'No write-offs today.' })); }); };
    var loadHub = function () {
      var h = $('wH').value; S.p.woHub = h; $('wP').disabled = true; $('wInfo').textContent = ''; $('wFill').innerHTML = '';
      if (!h) { $('wP').innerHTML = '<option>Choose the hub first</option>'; return; }
      $('wP').innerHTML = '<option>Loading…</option>';
      api('/stock/hub/' + h, {}, false).then(function (r) {
        prods = r.rows || [];
        $('wP').innerHTML = '<option value="">Choose the product</option>' + prods.map(function (p) { return '<option value="' + p.id + '">' + esc(p.name + ' (' + p.pack + ') · live ' + num(p.live) + (p.expired ? ' · expired ' + num(p.expired) : '') + (p.danger ? ' · danger ' + num(p.danger) : '')) + '</option>'; }).join('');
        $('wP').disabled = false;
      });
    };
    $('wH').addEventListener('change', loadHub);
    $('wP').addEventListener('change', function () {
      var p = prods.filter(function (x) { return String(x.id) === $('wP').value; })[0]; $('wFill').innerHTML = '';
      if (!p) { $('wInfo').textContent = ''; return; }
      $('wInfo').textContent = 'Live ' + num(p.live) + ' · fresh ' + num(p.fresh) + ' · danger ' + num(p.danger) + ' · expired ' + num(p.expired) + ' · price ' + money2(p.price);
      [['All expired', p.expired, 'expired'], ['All danger', p.danger, 'spoiled'], ['All stock', p.live, 'spoiled']].forEach(function (x) { if (x[1] > 0) { var b = el('<button type="button" class="cc-chip">' + esc(x[0]) + ' (' + num(x[1]) + ')</button>'); b.addEventListener('click', function () { $('wQ').value = x[1]; $('wR').value = x[2]; }); $('wFill').appendChild(b); } });
    });
    $('wGo').addEventListener('click', function () {
      var h = $('wH').value, pid = $('wP').value, q = parseFloat($('wQ').value);
      if (!h || !pid) { toast('Choose the hub and the product.', 'bad'); return; }
      if (!(q > 0)) { toast('Enter the quantity.', 'bad'); return; }
      var p = prods.filter(function (x) { return String(x.id) === pid; })[0];
      if (!confirm('Write off ' + q + ' of ' + (p ? p.name : 'this product') + ' at ' + hubName(h) + '?')) return;
      act(this, '/wastage', { hub_id: h, product_id: pid, qty: q, reason: $('wR').value, note: $('wN').value }, function () { $('wQ').value = ''; $('wN').value = ''; loadHub(); loadToday(); });
    });
    if (hubId) loadHub();
    loadToday();
    return Promise.resolve();
  } };

  V.wastage = { t: 'Wastage report', dates: true, run: function (root) {
    return api('/wastage').then(function (d) {
      var t = d.total;
      root.innerHTML = '<div class="cc-kpis">' + kpi('Wastage value', money(t.value), num(d.count, 0) + ' write-off(s)', t.pct > d.limit_pct ? 'warn' : '') + kpi('Quantity', num(t.qty)) + kpi('Of sales', t.pct == null ? '–' : pct(t.pct), 'limit ' + d.limit_pct + '% · sales ' + money(t.sales), t.pct > d.limit_pct ? 'bad' : '') + kpi('Top reason', esc(d.by_reason[0] ? d.by_reason[0].name : '–'), d.by_reason[0] ? money(d.by_reason[0].value) : '') + '</div>' +
        '<div class="cc-grid g32">' + card('Wastage by day', slot('days'), { sub: rangeText() }) + card('By reason', hbars(d.by_reason.map(function (r) { return { label: r.name, value: r.value }; }), 'bb', money)) + '</div>' +
        '<div class="cc-grid g2">' + card('By hub (wastage % of the hub\'s own sales)', slot('hubs'), { flush: 1 }) + card('By category', hbars(d.by_category.map(function (r) { return { label: r.name, value: r.value }; }), 'b4', money)) + '</div>' +
        card('By product', slot('prod'), { flush: 1 }) + card('Every write-off', slot('rows'), { flush: 1, actions: '<a class="cc-btn sm pri" href="#writeoff">Write off stock</a>' }) +
        (d.legacy.length ? card('Old panel records (not taken off stock)', slot('legacy'), { flush: 1, sub: 'Entries made in the old PHP panels (table wastage_stock). They never reduced live stock; shown for history only.' }) : '');
      mount(root, 'days', chartBarLine(d.days.map(function (x) { return x.date; }), d.days.map(function (x) { return x.value; }), null, { barColor: 'var(--bad)', fb: function (v) { return v >= 1000 ? Math.round(v / 1000) + 'k' : v; }, xl: function (s) { return s.slice(8, 10) + '/' + s.slice(5, 7); }, tip: function (i) { return dOnly(Date.parse(d.days[i].date + 'T12:00:00') / 1000) + ': ' + money(d.days[i].value); } }));
      mount(root, 'hubs', T([{ k: 'name', t: 'Hub', f: function (r) { return esc(r.name) + '<small>' + esc(r.city) + '</small>'; } }, { k: 'value', t: 'Wastage', n: 1, f: function (r) { return money(r.value); } }, { k: 'sales', t: 'Sales', n: 1, f: function (r) { return money(r.sales); } }, { k: 'pct', t: '% of sales', n: 1, f: function (r) { return r.pct > d.limit_pct ? '<b style="color:var(--bad)">' + pct(r.pct) + '</b>' : pct(r.pct); } }, { k: 'entries', t: 'Entries', n: 1 }], d.by_hub, { per: 15, csv: 'wastage-by-hub.csv' }));
      mount(root, 'prod', T([{ k: 'name', t: 'Product' }, { k: 'qty', t: 'Qty', n: 1, f: function (r) { return num(r.qty); } }, { k: 'value', t: 'Value', n: 1, f: function (r) { return money(r.value); } }, { k: 'entries', t: 'Entries', n: 1 }], d.by_product, { per: 15, csv: 'wastage-by-product.csv' }));
      mount(root, 'rows', T([{ k: 'at', t: 'When', n: 1, f: function (r) { return dt(r.at); }, c: function (r) { return dt(r.at); } }, { k: 'hub', t: 'Hub', f: function (r) { return esc(r.hub) + '<small>' + esc(r.city) + '</small>'; } }, { k: 'name', t: 'Product', f: function (r) { return esc(r.name) + '<small>' + esc(r.pack) + '</small>'; } }, { k: 'qty', t: 'Qty', n: 1, f: function (r) { return num(r.qty); } }, { k: 'value', t: 'Value', n: 1, f: function (r) { return money(r.value); } },
        { k: 'reason_label', t: 'Reason', f: function (r) { return pill(r.reason_label, r.reason === 'expired' ? 'p-warn' : 'p-bad'); }, c: function (r) { return r.reason_label; } }, { k: 'note', t: 'Note' }, { k: 'by', t: 'By' }], d.rows, { csv: 'wastage-' + S.from + '-' + S.to + '.csv', empty: 'No write-offs in these dates.' }));
      if (d.legacy.length) mount(root, 'legacy', T([{ k: 'at', t: 'When', n: 1, f: function (r) { return dt(r.at); } }, { k: 'hub', t: 'Hub' }, { k: 'name', t: 'Product' }, { k: 'qty', t: 'Qty', n: 1 }, { k: 'value', t: 'Value', n: 1, f: function (r) { return money(r.value); } }], d.legacy, { per: 15 }));
    });
  } };

  function transferModal(after, mode) {
    var send = mode === 'send';
    var box = el('<div style="display:flex;flex-direction:column;gap:12px"><div class="cc-row"><label class="cc-f"><span>From hub (sends)</span><select class="cc-sel" id="tF"><option value="">Choose</option>' + hubOptions(S.f.hub, true) + '</select></label><label class="cc-f"><span>To hub (receives)</span><select class="cc-sel" id="tTo"><option value="">Choose</option>' + hubOptions('', true) + '</select></label></div>' +
      (send ? '<div class="cc-row"><label class="cc-f"><span>Reason</span><select class="cc-sel" id="tR"><option value="balance">Balance stock between hubs</option><option value="older">Move danger stock (sell faster)</option><option value="other">Other</option></select></label><label class="cc-f"><span>Rider (of the sending hub)</span><select class="cc-sel" id="tRd"><option value="">Choose the hub first</option></select></label><label class="cc-f"><span>Outward / challan no.</span><input class="cc-in" id="tC" maxlength="40"></label></div>' : '') +
      '<div id="tLines"></div><button type="button" class="cc-btn sm" id="tAdd" disabled>+ Add product</button><label class="cc-f"><span>Instruction for the hubs</span><input class="cc-in" id="tN" maxlength="300" placeholder="e.g. balance stock before the weekend"></label>' +
      '<p class="cc-note">' + (send ? 'Send now: the stock leaves the sending hub immediately and is on the road until the receiving hub counts it in (Receive).' : 'Request: the sending hub sees it in its console, chooses the rider and dispatches. Stock leaves only when it is dispatched.') + '</p></div>');
    var prods = [];
    var line = function () { var r = el('<div class="cc-row" style="align-items:flex-end"><label class="cc-f" style="flex:3"><span>Product</span><select class="cc-sel" data-p>' + '<option value="">Choose</option>' + prods.map(function (p) { return '<option value="' + p.id + '">' + esc(p.name + ' (' + p.pack + ') · live ' + num(p.live)) + '</option>'; }).join('') + '</select></label><label class="cc-f"><span>Qty</span><input class="cc-in" data-q type="number" min="0" step="0.001"></label><button type="button" class="cc-btn sm" data-x>Remove</button></div>'); r.querySelector('[data-x]').addEventListener('click', function () { r.remove(); }); box.querySelector('#tLines').appendChild(r); };
    openModal(send ? 'Send stock to another hub (InterHub order)' : 'Request stock from another hub', box, [{ t: send ? 'Send now' : 'Create request', cls: 'pri', fn: function (b) {
      var lines = []; box.querySelectorAll('#tLines .cc-row').forEach(function (r) { var p = r.querySelector('[data-p]').value, q = parseFloat(r.querySelector('[data-q]').value); if (p && q > 0) lines.push({ product_id: +p, qty: q }); });
      if (!$('tF').value || !$('tTo').value) { toast('Choose both hubs.', 'bad'); return; } if (!lines.length) { toast('Add at least one product with a quantity.', 'bad'); return; }
      if (send && !confirm('Send now? The stock leaves ' + hubName($('tF').value) + ' immediately.')) return;
      act(b, send ? '/transfers/send' : '/transfers', send ? { from_hub: $('tF').value, to_hub: $('tTo').value, lines: lines, note: $('tN').value, reason: $('tR').value, rider_id: $('tRd').value, challan: $('tC').value } : { from_hub: $('tF').value, to_hub: $('tTo').value, lines: lines, note: $('tN').value }, function () { closeModal(); if (after) after(); });
    } }]);
    var load = function () { box.querySelector('#tLines').innerHTML = ''; var h = $('tF').value; $('tAdd').disabled = true; if (!h) return; api('/stock/hub/' + h, {}, false).then(function (r) { prods = (r.rows || []).filter(function (p) { return p.live > 0; }); $('tAdd').disabled = false; line(); });
      if (send) api('/riders/hub/' + h, {}, false).then(function (r) { $('tRd').innerHTML = '<option value="">No rider (hub hands over)</option>' + (r.rows || []).map(function (x) { return '<option value="' + x.id + '">' + esc(x.name + ' · ' + x.phone) + '</option>'; }).join(''); }); };
    $('tF').addEventListener('change', load); $('tAdd').addEventListener('click', line);
    if ($('tF').value) load();
  }

  V.transfers = { t: 'InterHub moments view', dates: true, run: function (root) {
    var status = S.p.tStatus == null ? 'open' : S.p.tStatus;
    return api('/transfers', { status: status }).then(function (d) {
      if (!d.available) { root.innerHTML = card('Transfers', '<div class="cc-empty">Transfers are not installed yet (hub console update 2).</div>'); return; }
      var ts = C.tStatus || {};
      root.innerHTML = '<div class="cc-chips" style="align-items:center"><button type="button" class="cc-chip' + (status === 'open' ? ' is-on' : '') + '" data-s="open">Open now</button><button type="button" class="cc-chip' + (status === '' ? ' is-on' : '') + '" data-s="">All in the dates</button>' + Object.keys(ts).map(function (k) { return '<button type="button" class="cc-chip' + (status === k ? ' is-on' : '') + '" data-s="' + k + '">' + esc(ts[k]) + ' <b>' + (d.counts[k] || 0) + '</b></button>'; }).join('') + '<button type="button" class="cc-btn pri" id="tNew" style="margin-left:auto">+ New transfer</button></div>' +
        card(status === 'open' ? 'Open transfers (planned, requested, on the road)' : 'Transfers ' + rangeText(), slot('t'), { flush: 1, sub: 'Click a transfer to see its products. A transfer on the road for more than 6 hours is flagged.' });
      root.querySelectorAll('[data-s]').forEach(function (b) { b.addEventListener('click', function () { S.p.tStatus = b.getAttribute('data-s'); render(); }); });
      $('tNew').addEventListener('click', function () { transferModal(function () { render(); }); });
      var sc = { planned: 'p-info', requested: 'p-info', in_transit: 'p-warn', received: 'p-ok', declined: 'p-bad', cancelled: 'p-mute' };
      mount(root, 't', T([
        { k: 'ref', t: 'Transfer', f: function (r) { return '<b>' + esc(r.ref) + '</b><small>' + dt(r.created) + ' · ' + esc(r.created_by || '') + '</small>'; } },
        { k: 'from', t: 'From → to', f: function (r) { return esc(r.from) + ' → ' + esc(r.to) + '<small>' + esc(r.from_city) + (r.to_city && r.to_city !== r.from_city ? ' → ' + esc(r.to_city) : '') + '</small>'; } },
        { k: 'status', t: 'Status', f: function (r) { return pill(r.status_label, sc[r.status]) + (r.hours_on_road > 6 ? ' ' + pill(r.hours_on_road + ' h on road', 'p-bad') : ''); }, c: function (r) { return r.status_label; } },
        { k: 'lines', t: 'Products', n: 1, v: function (r) { return r.lines.length; }, f: function (r) { return r.lines.length; } }, { k: 'value', t: 'Value', n: 1, f: function (r) { return money(r.value); } },
        { k: 'rider', t: 'Rider', f: function (r) { return esc(r.rider || '–'); } }, { k: 'instruction', t: 'Instruction', f: function (r) { return '<small>' + esc(r.instruction || r.close_note || '') + '</small>'; } },
        { k: 'a', t: '', nosort: 1, csv: false, f: function (r) { var b = ''; if (r.status === 'requested' || r.status === 'planned') b += '<button type="button" class="cc-btn sm pri" data-dp="' + r.id + '">Dispatch</button> '; if (r.status === 'in_transit') b += '<button type="button" class="cc-btn sm pri" data-rc="' + r.id + '">Receive</button> '; if (r.status === 'requested') b += '<button type="button" class="cc-btn sm" data-dc="' + r.id + '">Decline</button> '; if (['planned', 'requested', 'in_transit'].indexOf(r.status) >= 0) b += '<button type="button" class="cc-btn sm danger" data-c="' + r.id + '">Cancel</button>'; return b; } }
      ], d.rows, { csv: 'transfers.csv', empty: status === 'open' ? 'No open transfers.' : 'No transfers in these dates.', click: function (r) {
        openDrawer('Transfer ' + r.ref, '<div class="cc-chips">' + pill(r.status_label, sc[r.status]) + pill(r.reason || 'transfer', 'p-info') + '</div><dl class="cc-dl"><dt>From</dt><dd>' + esc(r.from) + ', ' + esc(r.from_city) + '</dd><dt>To</dt><dd>' + esc(r.to) + ', ' + esc(r.to_city) + '</dd><dt>Created</dt><dd>' + dt(r.created) + ' · ' + esc(r.created_by) + '</dd><dt>Dispatched</dt><dd>' + dt(r.sent) + (r.rider ? ' · ' + esc(r.rider) : '') + '</dd><dt>Received</dt><dd>' + dt(r.received_at) + '</dd>' + (r.meet ? '<dt>Meeting point</dt><dd>' + esc(r.meet) + '</dd>' : '') + (r.instruction ? '<dt>Instruction</dt><dd>' + esc(r.instruction) + '</dd>' : '') + (r.close_note ? '<dt>Closed because</dt><dd>' + esc(r.close_note) + '</dd>' : '') + (r.receive_note ? '<dt>Receive note</dt><dd>' + esc(r.receive_note) + '</dd>' : '') + '</dl>' +
          '<table class="cc-t"><thead><tr><th>Product</th><th class="n">Asked</th><th class="n">Sent</th><th class="n">Received</th></tr></thead><tbody>' + r.lines.map(function (l) { return '<tr><td>' + esc(l.name) + '<small>' + esc(l.pack) + '</small></td><td class="n">' + num(l.requested) + '</td><td class="n">' + num(l.sent) + '</td><td class="n">' + (l.received != null && l.sent != null && l.received < l.sent ? '<b style="color:var(--bad)">' + num(l.received) + '</b>' : num(l.received)) + '</td></tr>'; }).join('') + '</tbody></table>');
      }, after: function (w) {
        var find = function (id) { return d.rows.filter(function (x) { return String(x.id) === String(id); })[0]; };
        w.querySelectorAll('[data-c]').forEach(function (b) { b.addEventListener('click', function () { var n = prompt('Why are you cancelling this transfer? (stock on the road goes back to the sending hub)'); if (!n || !n.trim()) return; act(b, '/transfers/' + b.getAttribute('data-c') + '/cancel', { note: n.trim() }, function () { render(); }); }); });
        w.querySelectorAll('[data-dc]').forEach(function (b) { b.addEventListener('click', function () { var n = prompt('Why can the sending hub not send it?'); if (!n || !n.trim()) return; act(b, '/transfers/' + b.getAttribute('data-dc') + '/decline', { note: n.trim() }, function () { render(); }); }); });
        w.querySelectorAll('[data-dp]').forEach(function (b) { b.addEventListener('click', function () { dispatchModal(find(b.getAttribute('data-dp'))); }); });
        w.querySelectorAll('[data-rc]').forEach(function (b) { b.addEventListener('click', function () { receiveModal(find(b.getAttribute('data-rc'))); }); });
      } }));
    });
  } };

  V.cash = { t: 'Cash at hubs', dates: true, run: function (root) {
    return api('/cash').then(function (d) {
      badge('cash', d.over);
      var t = d.total;
      root.innerHTML = '<div class="cc-kpis">' + kpi('Cash held at hubs now', d.available ? money(t.in_hand) : '–', d.over ? '<span class="down">' + d.over + ' hub(s) over ' + money(d.limit) + '</span>' : 'all within ' + money(d.limit), d.over ? 'warn' : '') + kpi('Cash (COD) sales', money(t.cod_sales), rangeText()) + kpi('Online sales', money(t.online_sales)) + kpi('Wallet sales', money(t.wallet_sales)) + kpi('Collected (cash / UPI / card)', money(t.collected_cash + t.collected_upi + t.collected_card), money(t.collected_cash) + ' cash') + kpi('Deposited to bank / CMS', money(t.deposited)) + '</div>' +
        card('Hubs', slot('h'), { flush: 1, sub: 'Cash held = the hub\'s cash book now (cash collected minus deposits). Day closes = days the hub submitted its day-end report in the dates.' }) + card('Deposits', slot('d'), { flush: 1 });
      mount(root, 'h', T([{ k: 'hub', t: 'Hub', f: function (r) { return esc(r.hub) + '<small>' + esc(r.city) + '</small>'; } }, { k: 'in_hand', t: 'Cash held', n: 1, f: function (r) { return r.in_hand == null ? '–' : (r.over ? '<b style="color:var(--bad)">' + money(r.in_hand) + '</b>' : money(r.in_hand)); } },
        { k: 'cod_sales', t: 'COD sales', n: 1, f: function (r) { return money(r.cod_sales); } }, { k: 'online_sales', t: 'Online', n: 1, f: function (r) { return money(r.online_sales); } }, { k: 'wallet_sales', t: 'Wallet', n: 1, f: function (r) { return money(r.wallet_sales); } },
        { k: 'collected_cash', t: 'Collected cash', n: 1, f: function (r) { return money(r.collected_cash); } }, { k: 'collected_upi', t: 'UPI', n: 1, f: function (r) { return money(r.collected_upi); } }, { k: 'deposited', t: 'Deposited', n: 1, f: function (r) { return money(r.deposited); } },
        { k: 'day_closes', t: 'Day closes', n: 1, f: function (r) { return r.day_closes + ' / ' + r.days + (r.day_close_diff ? '<small class="' + (r.day_close_diff < 0 ? 'down' : '') + '">diff ' + money(r.day_close_diff) + '</small>' : ''); } }], d.rows, { per: 100, csv: 'cash-by-hub-' + S.from + '-' + S.to + '.csv' }));
      mount(root, 'd', T([{ k: 'date', t: 'Date' }, { k: 'hub', t: 'Hub' }, { k: 'type', t: 'Type' }, { k: 'amount', t: 'Amount', n: 1, f: function (r) { return r.voided ? '<s>' + money2(r.amount) + '</s> ' + pill('voided') : money2(r.amount); } }, { k: 'reference', t: 'Slip / ref' }, { k: 'receipt', t: 'Receipt', f: function (r) { return r.receipt ? pill('uploaded', 'p-ok') : pill('none', 'p-warn'); } }, { k: 'note', t: 'Note' }, { k: 'by', t: 'By' }], d.deposits, { per: 25, csv: 'deposits.csv', empty: 'No deposits in these dates.' }));
    });
  } };

  V.customers = { t: 'Customers', dates: false, run: function (root) {
    var p = S.p.cust = Object.assign({ q: '', grade: '', segment: '', page: 1 }, S.p.cust || {});
    return api('/customers', p, false).then(function (d) {
      var g = d.grades || {}, sg = d.segments || {};
      root.innerHTML = '<div class="cc-chips" style="align-items:center"><input class="cc-in" id="cQ" placeholder="Name, email or phone" value="' + esc(p.q) + '" style="width:220px"><button type="button" class="cc-btn" id="cGo">Search</button>' +
        ['gold', 'silver', 'bronze', 'none'].map(function (k) { return '<button type="button" class="cc-chip' + (p.grade === k ? ' is-on' : '') + '" data-g="' + k + '">' + (k === 'none' ? 'No grade' : k.charAt(0).toUpperCase() + k.slice(1)) + ' <b>' + num(g[k] || 0, 0) + '</b></button>'; }).join('') +
        '<a class="cc-btn" style="margin-left:auto" href="' + C.base + '/customers?' + qs(Object.assign({}, p, { format: 'csv' }), false) + '">Download Excel (CSV)</a></div>' +
        '<div class="cc-chips">' + [['new', 'New (first order in 30 days)'], ['regular', 'Regular'], ['loyal', 'Loyal (5+ orders)'], ['lapsed', 'Lapsed (60+ days)']].map(function (x) { return '<button type="button" class="cc-chip' + (p.segment === x[0] ? ' is-on' : '') + '" data-sg="' + x[0] + '">' + x[1] + ' <b>' + num(sg[x[0]] || 0, 0) + '</b></button>'; }).join('') + '</div>' +
        card(num(d.total, 0) + ' customers', slot('t'), { flush: 1, sub: 'Grades use the last 90 days: Gold = ' + (d.rules && d.rules.gold ? d.rules.gold.orders + '+ orders or ₹' + num(d.rules.gold.spend, 0) : '') + ', Silver = ' + (d.rules && d.rules.silver ? d.rules.silver.orders + '+ or ₹' + num(d.rules.silver.spend, 0) : '') + ', Bronze = at least one order. Lapsed customers are good targets for a win-back offer.' });
      var go = function () { p.q = $('cQ').value.trim(); p.page = 1; render(); };
      $('cGo').addEventListener('click', go); $('cQ').addEventListener('keydown', function (e) { if (e.key === 'Enter') go(); });
      root.querySelectorAll('[data-g]').forEach(function (b) { b.addEventListener('click', function () { p.grade = p.grade === b.getAttribute('data-g') ? '' : b.getAttribute('data-g'); p.page = 1; render(); }); });
      root.querySelectorAll('[data-sg]').forEach(function (b) { b.addEventListener('click', function () { p.segment = p.segment === b.getAttribute('data-sg') ? '' : b.getAttribute('data-sg'); p.page = 1; render(); }); });
      var gc = { gold: 'p-warn', silver: 'p-info', bronze: 'p-mute', none: 'p-mute' };
      var t = T([{ k: 'name', t: 'Customer', f: function (r) { return '<b>' + esc(r.name) + '</b><small>' + esc(r.phone) + (r.email ? ' · ' + esc(r.email) : '') + '</small>'; } }, { k: 'grade', t: 'Grade', f: function (r) { return pill(r.grade === 'none' ? '–' : r.grade, gc[r.grade]) + ' ' + pill(r.segment, r.segment === 'lapsed' ? 'p-bad' : r.segment === 'new' ? 'p-ok' : 'p-mute'); } },
        { k: 'hub', t: 'Main hub', f: function (r) { return esc(r.hub) + '<small>' + esc(r.city) + (r.hubs > 1 ? ' · ' + r.hubs + ' hubs' : '') + '</small>'; } }, { k: 'orders', t: 'Orders', n: 1 }, { k: 'spend', t: 'Spend', n: 1, f: function (r) { return money(r.spend); } },
        { k: 'spend90', t: '90 days', n: 1, f: function (r) { return money(r.spend90) + '<small>' + r.orders90 + ' orders</small>'; } }, { k: 'last', t: 'Last order', n: 1, f: function (r) { return ago(r.last); } }, { k: 'wallet', t: 'Wallet', n: 1, f: function (r) { return r.wallet == null ? '–' : money2(r.wallet); } }],
        d.rows, { noPage: 1, click: function (r) { customerDrawer(r.id); } });
      mount(root, 't', t);
      if (d.pages > 1) { var pg = el('<div class="cc-pager"><button type="button" class="cc-btn sm" data-p="-1"' + (d.page <= 1 ? ' disabled' : '') + '>‹ Prev</button><span>Page ' + d.page + ' of ' + d.pages + '</span><button type="button" class="cc-btn sm" data-p="1"' + (d.page >= d.pages ? ' disabled' : '') + '>Next ›</button></div>'); t.appendChild(pg); pg.querySelectorAll('[data-p]').forEach(function (b) { b.addEventListener('click', function () { p.page = d.page + +b.getAttribute('data-p'); render(); }); }); }
    });
  } };

  V.riders = { t: 'Riders', dates: false, run: function (root) {
    return api('/riders', {}, false).then(function (d) {
      root.innerHTML = '<div class="cc-kpis">' + kpi('Riders', num(d.total.riders, 0), num(d.total.active, 0) + ' active') + kpi('Out on delivery now', num(d.total.out_now, 0)) + kpi('Delivered today', num(d.total.today, 0)) + '</div>' +
        card('All delivery boys', slot('t'), { flush: 1, actions: '<button type="button" class="cc-btn pri" id="rAdd">+ Add delivery boy</button>', sub: 'Add, edit, switch on/off or move a rider to another hub. Passwords are stored encrypted. A rider signs in to the rider app with the account made when they register there.' });
      $('rAdd').addEventListener('click', function () { riderModal(null); });
      mount(root, 't', T([{ k: 'name', t: 'Rider', f: function (r) { return '<b>' + esc(r.name) + '</b><small>' + esc(r.phone) + (r.employee_id ? ' · ' + esc(r.employee_id) : '') + '</small>'; } }, { k: 'hub', t: 'Hub', f: function (r) { return esc(r.hub) + '<small>' + esc(r.city) + '</small>'; } }, { k: 'active', t: 'Status', f: function (r) { return r.active ? pill('Active', 'p-ok') : pill('Off', 'p-mute'); }, v: function (r) { return r.active ? 1 : 0; } }, { k: 'duty', t: 'Duty', f: function (r) { return esc(r.duty || '–'); } }, { k: 'out_now', t: 'Out now', n: 1 }, { k: 'today', t: 'Delivered today', n: 1 }, { k: 'last30', t: 'Last 30 days', n: 1 }, { k: 'joined', t: 'Joined', n: 1, f: function (r) { return r.joined ? dOnly(r.joined) : '–'; } },
        { k: 'a', t: '', nosort: 1, csv: false, f: function (r) { return '<button type="button" class="cc-btn sm" data-re="' + r.id + '">Edit</button> <button type="button" class="cc-btn sm" data-rt="' + r.id + '">' + (r.active ? 'Switch off' : 'Switch on') + '</button> <button type="button" class="cc-btn sm" data-rm="' + r.id + '">Move</button>'; } }], d.rows, { per: 100, csv: 'delivery-boys.csv', after: function (w) {
          var find = function (id) { return d.rows.filter(function (x) { return String(x.id) === String(id); })[0]; };
          w.querySelectorAll('[data-re]').forEach(function (b) { b.addEventListener('click', function () { riderModal(find(b.getAttribute('data-re'))); }); });
          w.querySelectorAll('[data-rt]').forEach(function (b) { b.addEventListener('click', function () { var r = find(b.getAttribute('data-rt')); if (!confirm((r.active ? 'Switch off ' : 'Switch on ') + r.name + '?')) return; act(b, '/riders/' + r.id + '/toggle', {}, function () { render(); }); }); });
          w.querySelectorAll('[data-rm]').forEach(function (b) { b.addEventListener('click', function () { var r = find(b.getAttribute('data-rm')); openModal('Move ' + r.name + ' to another hub', '<label class="cc-f"><span>New hub</span><select class="cc-sel" id="rmH">' + hubOptions('', true) + '</select></label><p class="cc-note">Now in ' + esc(r.hub) + '. A rider with orders out for delivery cannot be moved.</p>', [{ t: 'Move rider', cls: 'pri', fn: function (bt) { act(bt, '/riders/' + r.id + '/move', { to_hub: $('rmH').value }, function () { closeModal(); render(); }); } }]); }); });
        } }));
      if (S.p.addRider) { S.p.addRider = false; riderModal(null); }
    });
  } };

  V.grievances = { t: 'Grievances', dates: true, run: function (root) {
    return api('/grievances').then(function (d) {
      if (!d.available) { root.innerHTML = card('Grievances', '<div class="cc-empty">' + esc(d.reason) + '</div>'); return; }
      root.innerHTML = '<div class="cc-kpis">' + kpi('Grievances', num(d.total, 0), rangeText()) + kpi('Open', d.open == null ? '–' : num(d.open, 0), '', d.open ? 'warn' : '') + kpi('Top category', esc(d.by_category[0] ? d.by_category[0].name : '–'), d.by_category[0] ? d.by_category[0].count + ' complaints' : '') + '</div>' +
        '<div class="cc-grid g2">' + card('By category', hbars(d.by_category.map(function (x) { return { label: x.name, value: x.count }; }), 'bb', function (v) { return num(v, 0); })) + card('By hub', hbars(d.by_hub.map(function (x) { return { label: x.name, value: x.count }; }), 'b4', function (v) { return num(v, 0); })) + '</div>' + card('All grievances', slot('t'), { flush: 1 });
      mount(root, 't', T([{ k: 'at', t: 'Date', n: 1, f: function (r) { return dt(r.at); }, c: function (r) { return dt(r.at); } }, { k: 'customer', t: 'Customer', f: function (r) { return esc(r.customer || '–') + '<small>' + esc(r.phone) + '</small>'; } }, { k: 'hub', t: 'Hub', f: function (r) { return esc(r.hub || '–') + '<small>' + esc(r.city) + '</small>'; } }, { k: 'category', t: 'Category' }, { k: 'text', t: 'Complaint', f: function (r) { return '<small style="color:var(--ink)">' + esc(r.text) + '</small>'; } }, { k: 'order_id', t: 'Order', f: function (r) { return r.order_id ? '<a href="#" data-o="' + esc(r.order_id) + '">' + esc(r.order_id) + '</a>' : '–'; } }, { k: 'status', t: 'Status', f: function (r) { return r.open === null ? esc(r.status) : pill(r.status || (r.open ? 'open' : 'closed'), r.open ? 'p-warn' : 'p-ok'); } }],
        d.rows, { csv: 'grievances.csv', after: function (w) { w.querySelectorAll('[data-o]').forEach(function (a) { a.addEventListener('click', function (e) { e.preventDefault(); orderDrawer(a.getAttribute('data-o')); }); }); } }));
    });
  } };

  V.reports = { t: 'Sales report', dates: true, run: function (root) {
    var g = S.p.group || 'day';
    return api('/reports/sales', { group: g }).then(function (d) {
      var groups = C.groups || {};
      root.innerHTML = '<div class="cc-chips" style="align-items:center"><span class="muted" style="font-weight:700">Group by</span>' + Object.keys(groups).map(function (k) { return '<button type="button" class="cc-chip' + (k === g ? ' is-on' : '') + '" data-g="' + k + '">' + esc(groups[k]) + '</button>'; }).join('') + '</div>' +
        '<div class="cc-kpis">' + kpi('Sales', money(d.total.sales), d.basis === 'list price' ? 'items at list price' : 'order value') + (d.total.orders != null ? kpi('Orders', num(d.total.orders, 0)) + kpi('Cancelled', num(d.total.cancelled, 0)) : kpi('Quantity', num(d.total.qty))) + kpi('Rows', num(d.rows.length, 0)) + '</div>' +
        (g === 'day' || g === 'hour' ? card('Sales by ' + d.label.toLowerCase(), slot('c')) : '') + card(esc(d.scope) + ' · ' + rangeText() + ' · by ' + d.label.toLowerCase(), slot('t'), { flush: 1 });
      root.querySelectorAll('[data-g]').forEach(function (b) { b.addEventListener('click', function () { S.p.group = b.getAttribute('data-g'); render(); }); });
      if (g === 'day' || g === 'hour') mount(root, 'c', chartBarLine(d.rows.map(function (r) { return r.key; }), d.rows.map(function (r) { return r.orders; }), d.rows.map(function (r) { return r.sales; }), { xl: function (s) { return g === 'day' ? s.slice(8, 10) + '/' + s.slice(5, 7) : s.slice(0, 2); }, fl: function (v) { return v >= 1000 ? Math.round(v / 1000) + 'k' : v; }, legend: '<span><i style="background:var(--c1-soft)"></i>Orders</span><span><i style="background:var(--brand)"></i>Sales ₹</span>', tip: function (i) { var r = d.rows[i]; return '<b>' + esc(r.key) + '</b><br>' + num(r.orders, 0) + ' orders · ' + money(r.sales); } }));
      var cols = d.basis === 'list price'
        ? [{ k: 'key', t: d.label, f: function (r) { return esc(r.key) + (r.sub ? '<small>' + esc(r.sub) + '</small>' : ''); } }, { k: 'qty', t: 'Qty', n: 1, f: function (r) { return num(r.qty); } }, { k: 'orders', t: 'Orders', n: 1 }, { k: 'sales', t: 'Sales', n: 1, f: function (r) { return money(r.sales); } }, { k: 'share', t: 'Share', n: 1, f: function (r) { return pct(r.share); } }]
        : [{ k: 'key', t: d.label, f: function (r) { return esc(r.key) + (r.sub ? '<small>' + esc(r.sub) + '</small>' : ''); } }, { k: 'orders', t: 'Orders', n: 1 }, { k: 'sales', t: 'Sales', n: 1, f: function (r) { return money(r.sales); } }, { k: 'aov', t: 'AOV', n: 1, f: function (r) { return money(r.aov); } }, { k: 'customers', t: 'Customers', n: 1 }, { k: 'cancelled', t: 'Cancelled', n: 1 }, { k: 'cancel_rate', t: 'Cancel %', n: 1, f: function (r) { return pct(r.cancel_rate); } }, { k: 'share', t: 'Share', n: 1, f: function (r) { return pct(r.share); } }];
      mount(root, 't', T(cols, d.rows, { per: 100, csv: 'sales-by-' + g + '-' + S.from + '-' + S.to + '.csv', sort: g === 'day' || g === 'hour' ? null : null }));
    });
  } };

  V.targets = { t: 'Targets', dates: false, run: function (root) {
    if (!C.targetsOn) { root.innerHTML = card('Targets', '<div class="cc-empty">Targets are not installed yet.</div>'); return Promise.resolve(); }
    var month = S.p.month || C.today.slice(0, 7);
    return api('/targets', { month: month }, false).then(function (d) {
      var head = C.role === 'country';
      root.innerHTML = '<div class="cc-chips" style="align-items:center"><label class="cc-f" style="flex-direction:row;align-items:center;gap:8px"><span>Month</span><input type="month" class="cc-in" id="tgM" value="' + esc(d.month) + '"></label>' + (head ? '<button type="button" class="cc-btn pri" id="tgNew" style="margin-left:auto">+ Set a target</button>' : '<span class="cc-note" style="margin-left:auto">Targets are set by the country manager.</span>') + '</div>' +
        card('Monthly targets', slot('t'), { flush: 1, sub: 'Pace = where the month will end at the current speed, as % of the target (100% = on track). The line on each bar marks how much of the month has passed.' });
      $('tgM').addEventListener('change', function () { S.p.month = this.value; render(); });
      if (head) $('tgNew').addEventListener('click', function () { targetModal(d.month); });
      mount(root, 't', T([{ k: 'label', t: 'For', f: function (r) { return '<b>' + esc(r.label) + '</b><small>' + esc((d.scopes || {})[r.scope] || r.scope) + (r.note ? ' · ' + esc(r.note) : '') + '</small>'; } },
        { k: 'sales', t: 'Sales so far', n: 1, f: function (r) { return money(r.sales); } }, { k: 'sales_target', t: 'Sales target', n: 1, f: function (r) { return r.sales_target ? money(r.sales_target) : '–'; } },
        { k: 'sales_pct', t: 'Progress', nosort: 1, f: function (r) { return r.sales_target ? '<div style="min-width:140px">' + prog(r.sales_pct, r.days ? r.days_passed * 100 / r.days : null) + '<small>' + pct(r.sales_pct) + '</small></div>' : '–'; }, c: function (r) { return r.sales_pct; } },
        { k: 'projected', t: 'Projected', n: 1, f: function (r) { return money(r.projected); } }, { k: 'pace', t: 'Pace', n: 1, f: function (r) { return r.pace == null ? '–' : '<b class="' + (r.pace >= 100 ? 'up' : 'down') + '">' + r.pace + '%</b>'; } },
        { k: 'needed_per_day', t: 'Needed / day', n: 1, f: function (r) { return r.needed_per_day == null ? '–' : money(r.needed_per_day); } },
        { k: 'orders', t: 'Orders', n: 1, f: function (r) { return num(r.orders, 0) + (r.orders_target ? ' / ' + num(r.orders_target, 0) : ''); } },
        { k: 'a', t: '', nosort: 1, csv: false, f: function (r) { return head ? '<button type="button" class="cc-btn sm" data-e="' + r.id + '">Edit</button> <button type="button" class="cc-btn sm danger" data-d="' + r.id + '">Remove</button>' : ''; } }],
        d.rows, { noPage: 1, csv: 'targets-' + d.month + '.csv', empty: 'No targets for this month yet. Set one for All India, a state, a city or a hub.', after: function (w) {
          w.querySelectorAll('[data-e]').forEach(function (b) { b.addEventListener('click', function () { targetModal(d.month, d.rows.filter(function (r) { return String(r.id) === b.getAttribute('data-e'); })[0]); }); });
          w.querySelectorAll('[data-d]').forEach(function (b) { b.addEventListener('click', function () { if (confirm('Remove this target?')) act(b, '/targets/' + b.getAttribute('data-d') + '/delete', {}, function () { render(); }); }); });
        } }));
    });
  } };
  function targetModal(month, row) {
    var box = el('<div style="display:flex;flex-direction:column;gap:12px"><div class="cc-row"><label class="cc-f"><span>For</span><select class="cc-sel" id="gS"><option value="india">All India</option>' + (M.states.length ? '<option value="state">A state</option>' : '') + '<option value="city">A city</option><option value="hub">A hub</option></select></label><label class="cc-f" id="gIW" hidden><span>Which</span><select class="cc-sel" id="gI"></select></label></div>' +
      '<div class="cc-row"><label class="cc-f"><span>Month</span><input type="month" class="cc-in" id="gM" value="' + esc(month) + '"></label><label class="cc-f"><span>Sales target (₹)</span><input type="number" class="cc-in" id="gV" min="0" step="1000"></label><label class="cc-f"><span>Orders target</span><input type="number" class="cc-in" id="gO" min="0" step="1"></label></div><label class="cc-f"><span>Note</span><input class="cc-in" id="gN" maxlength="200"></label></div>');
    openModal(row ? 'Edit target' : 'Set a monthly target', box, [{ t: 'Save target', cls: 'pri', fn: function (b) { act(b, '/targets', { scope: $('gS').value, scope_id: $('gI').value || 0, month: $('gM').value, sales_target: $('gV').value || 0, orders_target: $('gO').value || 0, note: $('gN').value }, function () { closeModal(); render(); }); } }]);
    var fillI = function () { var s = $('gS').value, list = s === 'state' ? M.states : s === 'city' ? M.cities : s === 'hub' ? M.hubs : []; $('gIW').hidden = s === 'india'; $('gI').innerHTML = list.map(function (x) { return '<option value="' + x.id + '">' + esc(x.name + (s === 'hub' && x.city ? ' · ' + x.city : '')) + '</option>'; }).join(''); };
    $('gS').addEventListener('change', fillI);
    if (row) { $('gS').value = row.scope; fillI(); $('gI').value = row.scope_id; $('gV').value = row.sales_target || ''; $('gO').value = row.orders_target || ''; $('gN').value = row.note || ''; $('gM').value = row.month; }
    else { if (S.f.hub) { $('gS').value = 'hub'; fillI(); $('gI').value = S.f.hub; } else if (S.f.city) { $('gS').value = 'city'; fillI(); $('gI').value = S.f.city; } else if (S.f.state && M.states.length) { $('gS').value = 'state'; fillI(); $('gI').value = S.f.state; } else fillI(); }
  }

  function dispatchModal(t) {
    var box = el('<div style="display:flex;flex-direction:column;gap:12px"><p class="cc-note">' + esc(t.from) + ' → ' + esc(t.to) + '. Enter what is really sent (0 = not sending that one). Stock leaves ' + esc(t.from) + ' now.</p><table class="cc-t"><thead><tr><th>Product</th><th class="n">Asked</th><th class="n">Send</th></tr></thead><tbody>' +
      t.lines.map(function (l, i) { return '<tr><td>' + esc(l.name) + '<small>' + esc(l.pack) + '</small></td><td class="n">' + num(l.requested) + '</td><td class="n"><input class="cc-in" style="width:90px" type="number" min="0" step="0.001" data-i="' + i + '" value="' + (l.requested || 0) + '"></td></tr>'; }).join('') + '</tbody></table>' +
      '<div class="cc-row"><label class="cc-f"><span>Rider (of ' + esc(t.from) + ')</span><select class="cc-sel" id="dR"><option value="">Loading…</option></select></label><label class="cc-f"><span>Outward / challan no.</span><input class="cc-in" id="dC" maxlength="40"></label></div><label class="cc-f"><span>Instruction</span><input class="cc-in" id="dN" maxlength="200"></label></div>');
    openModal('Dispatch ' + t.ref, box, [{ t: 'Dispatch now', cls: 'pri', fn: function (b) { var lines = t.lines.map(function (l, i) { return { code: l.code, qty: parseFloat(box.querySelector('[data-i="' + i + '"]').value) || 0 }; }); act(b, '/transfers/' + t.id + '/dispatch', { lines: lines, rider_id: $('dR').value, challan: $('dC').value, note: $('dN').value }, function () { closeModal(); render(); }); } }]);
    api('/riders/hub/' + t.from_id, {}, false).then(function (r) { $('dR').innerHTML = '<option value="">No rider (hub hands over)</option>' + (r.rows || []).map(function (x) { return '<option value="' + x.id + '">' + esc(x.name + ' · ' + x.phone) + '</option>'; }).join(''); });
  }
  function receiveModal(t) {
    var box = el('<div style="display:flex;flex-direction:column;gap:12px"><p class="cc-note">' + esc(t.from) + ' → ' + esc(t.to) + '. Count what arrived at ' + esc(t.to) + '. A difference needs a note. The stock keeps its age (danger stock stays danger stock).</p><table class="cc-t"><thead><tr><th>Product</th><th class="n">Sent</th><th class="n">Received</th></tr></thead><tbody>' +
      t.lines.map(function (l, i) { return '<tr><td>' + esc(l.name) + '<small>' + esc(l.pack) + '</small></td><td class="n">' + num(l.sent) + '</td><td class="n"><input class="cc-in" style="width:90px" type="number" min="0" step="0.001" data-i="' + i + '" value="' + (l.sent || 0) + '"></td></tr>'; }).join('') + '</tbody></table><label class="cc-f"><span>Note</span><input class="cc-in" id="rN" maxlength="200" placeholder="e.g. 1 kg short, packet damaged"></label></div>');
    openModal('Receive ' + t.ref + ' at ' + t.to, box, [{ t: 'Receive stock', cls: 'pri', fn: function (b) { var lines = t.lines.map(function (l, i) { return { code: l.code, qty: parseFloat(box.querySelector('[data-i="' + i + '"]').value) || 0 }; }); act(b, '/transfers/' + t.id + '/receive', { lines: lines, note: $('rN').value }, function () { closeModal(); render(); }); } }]);
  }
  function riderModal(r) {
    var box = el('<div style="display:flex;flex-direction:column;gap:12px"><div class="cc-row"><label class="cc-f"><span>Hub</span><select class="cc-sel" id="rH"' + (r ? ' disabled' : '') + '><option value="">Choose the hub</option>' + hubOptions(r ? r.hub_id : S.f.hub, true) + '</select></label><label class="cc-f"><span>Status</span><select class="cc-sel" id="rS"' + (r ? ' disabled' : '') + '><option value="active">Active</option><option value="inactive">Off</option></select></label></div>' +
      '<div class="cc-row"><label class="cc-f"><span>Name</span><input class="cc-in" id="rNm" maxlength="60"></label><label class="cc-f"><span>Mobile (10 digits)</span><input class="cc-in" id="rPh" maxlength="13" inputmode="numeric"></label></div>' +
      '<div class="cc-row"><label class="cc-f"><span>Employee ID</span><input class="cc-in" id="rE" maxlength="20"' + (r && r.employee_id ? ' disabled' : '') + '></label><label class="cc-f"><span>Gender</span><select class="cc-sel" id="rG"><option value="">–</option><option>Male</option><option>Female</option><option>Other</option></select></label></div>' +
      '<label class="cc-f"><span>' + (r ? 'New password (leave empty to keep)' : 'Password (6+ characters)') + '</span><input class="cc-in" id="rPw" type="password" autocomplete="new-password"></label>' + (r ? '<p class="cc-note">To move this rider to another hub use Move. Employee ID cannot be changed once set.</p>' : '') + '</div>');
    openModal(r ? 'Edit ' + r.name : 'Add delivery boy', box, [{ t: r ? 'Save' : 'Add rider', cls: 'pri', fn: function (b) { act(b, '/riders', { id: r ? r.id : 0, hub_id: $('rH').value, status: $('rS').value, name: $('rNm').value, phone: $('rPh').value, employee_id: $('rE').value, gender: $('rG').value, password: $('rPw').value }, function () { closeModal(); render(); }); } }]);
    if (r) { $('rNm').value = r.name; $('rPh').value = r.phone; $('rE').value = r.employee_id; $('rG').value = r.gender || ''; $('rS').value = r.active ? 'active' : 'inactive'; }
  }
  V['rider-add'] = { t: 'Add delivery boy', dates: false, run: function (root) { S.p.addRider = true; location.replace('#riders'); return Promise.resolve(); } };

  function transferList(root, status, mode, title, sub) {
    S.p.tStatus = status;
    return V.transfers.run(root).then(function () {
      var bar = root.querySelector('#tNew'); if (bar) { bar.textContent = mode === 'send' ? '+ Send stock now' : '+ New request'; var nb = bar.cloneNode(true); bar.replaceWith(nb); nb.addEventListener('click', function () { transferModal(function () { render(); }, mode); }); }
      var h = root.querySelector('.cc-card > header h2'); if (h) h.textContent = title;
      var p = root.querySelector('.cc-card > header p'); if (p) p.textContent = sub;
    });
  }
  V.request = { t: 'Request outward stocks', dates: true, run: function (root) { return transferList(root, 'requested', 'request', 'Open stock requests', 'A request asks one hub to send stock to another. The sending hub dispatches it with its rider; you can also dispatch or decline it here on its behalf.'); } };
  V.ihsend = { t: 'InterHub orders (send stock)', dates: true, run: function (root) { return transferList(root, 'in_transit', 'send', 'Stock on the road between hubs', 'Send stock from one hub to another right now. It stays "on the road" until the receiving hub counts it in; you can receive it here on the hub\'s behalf.'); } };

  V.wallets = { t: 'Wallet transactions', dates: true, run: function (root) {
    var p = S.p.wal = Object.assign({ q: '' }, S.p.wal || {});
    return api('/wallets', p).then(function (d) {
      if (!d.available) { root.innerHTML = card('Wallet transactions', '<div class="cc-empty">No wallet transaction table was found.</div>'); return; }
      root.innerHTML = '<div class="cc-chips" style="align-items:center"><input class="cc-in" id="wQ2" placeholder="Order, name or phone" value="' + esc(p.q) + '" style="width:220px"><button type="button" class="cc-btn" id="wGo2">Search</button></div>' +
        '<div class="cc-kpis">' + kpi('Wallet used on orders', money(d.summary.wallet), num(d.rows.length, 0) + ' transaction(s)') + kpi('Credited', money(d.summary.credit)) + kpi('Wallet ledger entries', num(d.ledger.length, 0), 'top-ups and rewards') + '</div>' +
        card('Wallet use on orders (old wallet history)', slot('t'), { flush: 1 }) + (d.ledger.length ? card('Wallet ledger (top-ups, referral rewards)', slot('l'), { flush: 1 }) : '');
      var go = function () { p.q = $('wQ2').value.trim(); render(); };
      $('wGo2').addEventListener('click', go); $('wQ2').addEventListener('keydown', function (e) { if (e.key === 'Enter') go(); });
      mount(root, 't', T([{ k: 'at', t: 'Date', n: 1, f: function (r) { return dt(r.at); }, c: function (r) { return dt(r.at); } }, { k: 'customer', t: 'Customer', f: function (r) { return '<a href="#" data-cu="' + r.buyer_id + '">' + esc(r.customer) + '</a><small>' + esc(r.phone) + '</small>'; }, c: function (r) { return r.customer; } }, { k: 'hub', t: 'Hub', f: function (r) { return esc(r.hub) + '<small>' + esc(r.city) + '</small>'; } }, { k: 'order_id', t: 'Order', f: function (r) { return '<a href="#" data-o="' + esc(r.order_id) + '">' + esc(r.order_id) + '</a>'; }, c: function (r) { return r.order_id; } },
        { k: 'order_amount', t: 'Order amount', n: 1, f: function (r) { return money2(r.order_amount); } }, { k: 'wallet_amount', t: 'Wallet used', n: 1, f: function (r) { return money2(r.wallet_amount); } }, { k: 'credit_amount', t: 'Credit', n: 1, f: function (r) { return money2(r.credit_amount); } }, { k: 'payment_type', t: 'Payment' }],
        d.rows, { csv: 'wallet-transactions-' + S.from + '-' + S.to + '.csv', empty: 'No wallet transactions in these dates.', after: function (w) { w.querySelectorAll('[data-o]').forEach(function (a) { a.addEventListener('click', function (e) { e.preventDefault(); orderDrawer(a.getAttribute('data-o')); }); }); w.querySelectorAll('[data-cu]').forEach(function (a) { a.addEventListener('click', function (e) { e.preventDefault(); customerDrawer(a.getAttribute('data-cu')); }); }); } }));
      if (d.ledger.length) mount(root, 'l', T([{ k: 'at', t: 'Date', n: 1, f: function (r) { return dt(r.at); } }, { k: 'customer', t: 'Customer', f: function (r) { return esc(r.customer) + '<small>' + esc(r.phone) + '</small>'; } }, { k: 'type', t: 'Type' }, { k: 'amount', t: 'Amount', n: 1, f: function (r) { return money2(r.amount); } }, { k: 'balance', t: 'Balance after', n: 1, f: function (r) { return money2(r.balance); } }, { k: 'note', t: 'Note' }, { k: 'ref', t: 'Ref' }], d.ledger, { csv: 'wallet-ledger.csv' }));
    });
  } };

  V.sheet = { t: 'Hub inventory sheet', dates: false, run: function (root) {
    var p = S.p.sheet = Object.assign({ date: C.today, metric: 'all', category: '', q: '', stocked: '' }, S.p.sheet || {});
    var params = { date: p.date, category: p.category, q: p.q, stocked: p.stocked };
    return api('/stock/sheet', params, false).then(function (d) {
      var M2 = d.metrics, keys = p.metric === 'all' ? ['live', 'fresh', 'danger', 'in', 'out'] : [p.metric];
      var xl = C.base + '/stock/sheet?' + qs(Object.assign({}, params, { format: 'xlsx', metric: p.metric }), false);
      var tone = { danger: 'color:var(--warn);font-weight:800', expired: 'color:var(--bad);font-weight:800', 'in': 'color:var(--info);font-weight:700', out: 'color:var(--c4);font-weight:700', waste: 'color:var(--bad)', live: 'font-weight:800' };
      var cell = function (v, k) { return !v ? '<span class="muted">·</span>' : '<span style="' + (tone[k] || '') + '">' + num(v) + '</span>'; };
      var h = '<div class="cc-chips" style="align-items:center"><label class="cc-f" style="flex-direction:row;align-items:center;gap:6px"><span>Date</span><input type="date" class="cc-in" id="shD" max="' + C.today + '" value="' + esc(d.date) + '"></label>' +
        '<select class="cc-sel" id="shM"><option value="all">Live · Fresh · Danger · Incoming · Out</option>' + Object.keys(M2).map(function (k) { return '<option value="' + k + '"' + (k === p.metric ? ' selected' : '') + '>Only ' + esc(M2[k]) + '</option>'; }).join('') + '</select>' +
        '<select class="cc-sel" id="shC"><option value="">All categories</option>' + d.categories.map(function (c) { return '<option' + (c === p.category ? ' selected' : '') + '>' + esc(c) + '</option>'; }).join('') + '</select>' +
        '<input class="cc-in" id="shQ" placeholder="Search product or ID" value="' + esc(p.q) + '" style="width:190px"><label class="cc-chip" style="cursor:pointer"><input type="checkbox" id="shS"' + (p.stocked ? ' checked' : '') + '> Only with stock</label>' +
        '<a class="cc-btn pri" style="margin-left:auto" href="' + esc(xl) + '">Download Excel (.xlsx)</a></div>';
      if (d.note) h += '<div class="cc-card"><div class="cc-body" style="color:var(--warn);font-weight:700">' + esc(d.note) + '</div></div>';
      h += '<div class="cc-kpis">' + kpi('Live stock', num(d.total.live), money(d.value) + ' at list price') + kpi('Fresh', num(d.total.fresh), d.today ? 'received today' : 'received that day') + kpi('Danger', num(d.total.danger), 'received the day before', d.total.danger ? 'warn' : '') + kpi('Expired', num(d.total.expired), '2+ days old', d.total.expired ? 'bad' : '') +
        kpi('Incoming', num(d.total['in']), d.today ? 'sent to hubs + transfers on the way' : 'transfers received that day') + kpi('Transferring out', num(d.total.out), d.today ? 'planned, requested or on the road' : 'sent to other hubs that day') + kpi('Received / sold / wasted', num(d.total.recv) + ' / ' + num(d.total.sold) + ' / ' + num(d.total.waste), d.today ? 'today' : 'that day') + '</div>';
      var hubs = d.hubs;
      var t = '<div class="cc-tw" style="max-height:70vh"><table class="cc-t" id="shT" style="font-size:12.5px"><thead><tr>' +
        '<th rowspan="' + (keys.length > 1 ? 2 : 1) + '" style="position:sticky;left:0;z-index:3;background:var(--surface-2)">S.No</th><th rowspan="' + (keys.length > 1 ? 2 : 1) + '" style="position:sticky;left:46px;z-index:3;background:var(--surface-2);min-width:220px">Products</th><th rowspan="' + (keys.length > 1 ? 2 : 1) + '">Ids</th><th rowspan="' + (keys.length > 1 ? 2 : 1) + '">Category</th><th rowspan="' + (keys.length > 1 ? 2 : 1) + '">UOM</th>' +
        [{ name: 'Total' }].concat(hubs).map(function (x) { return '<th class="n" colspan="' + keys.length + '" style="text-align:center;border-left:1px solid var(--line)" title="' + esc(x.city || '') + '">' + esc(x.name) + '</th>'; }).join('') + '</tr>' +
        (keys.length > 1 ? '<tr>' + [0].concat(hubs).map(function () { return keys.map(function (k, i) { return '<th class="n" style="top:33px;' + (i === 0 ? 'border-left:1px solid var(--line)' : '') + '">' + esc(M2[k] === 'Transferring out' ? 'Out' : M2[k]) + '</th>'; }).join(''); }).join('') + '</tr>' : '') + '</thead><tbody>';
      d.rows.forEach(function (r) {
        t += '<tr><td style="position:sticky;left:0;background:var(--surface)">' + r.sn + '</td><td style="position:sticky;left:46px;background:var(--surface)"><a href="#" data-pd="' + r.id + '" style="font-weight:700;text-decoration:none">' + esc(r.name) + '</a>' + (r.active ? '' : ' ' + pill('inactive')) + '<small>' + esc(r.pack) + '</small></td><td>' + esc(r.code) + '</td><td>' + esc(r.category) + '</td><td>' + esc(r.uom) + '</td>' +
          keys.map(function (k, i) { return '<td class="n" style="background:var(--surface-2);' + (i === 0 ? 'border-left:1px solid var(--line)' : '') + '">' + cell(r.total[k], k) + '</td>'; }).join('') +
          hubs.map(function (x) { var c = r.cells[x.id]; return keys.map(function (k, i) { return '<td class="n"' + (i === 0 ? ' style="border-left:1px solid var(--line)"' : '') + '>' + cell(c ? c[k] : 0, k) + '</td>'; }).join(''); }).join('') + '</tr>';
      });
      if (!d.rows.length) t += '<tr><td colspan="' + (5 + keys.length * (hubs.length + 1)) + '"><div class="cc-empty">No products for these filters.</div></td></tr>';
      t += '</tbody><tfoot><tr><td style="position:sticky;left:0;background:var(--surface-2)"></td><td style="position:sticky;left:46px;background:var(--surface-2)">Total</td><td></td><td></td><td></td>' + keys.map(function (k, i) { return '<td class="n"' + (i === 0 ? ' style="border-left:1px solid var(--line)"' : '') + '>' + num(d.total[k]) + '</td>'; }).join('') +
        hubs.map(function (x) { return keys.map(function (k, i) { return '<td class="n"' + (i === 0 ? ' style="border-left:1px solid var(--line)"' : '') + '>' + num(x.total[k]) + '</td>'; }).join(''); }).join('') + '</tr></tfoot></table></div>';
      h += card(esc(d.scope) + ' · ' + dOnly(Date.parse(d.date + 'T12:00:00') / 1000) + (d.today ? ' (now)' : ' (end of day)'), t, { flush: 1, sub: d.today ? 'Live = on the shelf now. Fresh = received today, Danger = received yesterday (sell today). Incoming = sent to the hub and not yet accepted, plus transfers on the way. Out = transfers planned, requested or on the road to other hubs. Use the State / City / Hub filters at the top.' : 'Position at the end of the day, rebuilt from the stock log: what was left is the newest stock (oldest is sold first). Incoming / Out = transfers received / sent that day.' });
      root.innerHTML = h;
      var go = function () { p.date = $('shD').value || C.today; p.metric = $('shM').value; p.category = $('shC').value; p.q = $('shQ').value.trim(); p.stocked = $('shS').checked ? '1' : ''; render(); };
      ['shD', 'shM', 'shC', 'shS'].forEach(function (id) { $(id).addEventListener('change', go); });
      $('shQ').addEventListener('keydown', function (e) { if (e.key === 'Enter') go(); });
      root.querySelectorAll('[data-pd]').forEach(function (a) { a.addEventListener('click', function (e) { e.preventDefault(); productDrawer(+a.getAttribute('data-pd'), a.textContent); }); });
    });
  } };

  /* ------------------------------------------------------------------ router */
  function badge(view, n) { var b = $('nb-' + view); if (!b) return; b.hidden = !n; b.textContent = n > 99 ? '99+' : n; }
  function refreshSoft() { if (['orders', 'sched', 'live', 'overview', 'cancels'].indexOf(S.view) >= 0) render(true); }
  function render(quiet) {
    var key = (location.hash || '#overview').slice(1).split('?')[0];
    if (!V[key]) key = 'overview';
    var changed = key !== S.view; S.view = key;
    var v = V[key];
    document.querySelectorAll('.cc-nav a[data-view]').forEach(function (a) { a.classList.toggle('is-on', a.getAttribute('data-view') === key); });
    $('ccTitle').textContent = v.t; document.title = v.t + ' · Freshful country';
    $('ccDates').hidden = !v.dates;
    $('ccSub').textContent = v.dates ? rangeText() : new Date().toLocaleDateString('en-IN', { weekday: 'long', day: '2-digit', month: 'short', year: 'numeric' });
    if (S.timer) { clearInterval(S.timer); S.timer = null; }
    var root = $('ccView'), my = ++S.seq;
    if (!quiet || changed) loading(root);
    var target = quiet && !changed ? document.createElement('div') : root;
    var done = function () { if (my !== S.seq) return; if (target !== root) { root.innerHTML = ''; while (target.firstChild) root.appendChild(target.firstChild); } };
    Promise.resolve(v.run(target)).then(done).catch(function (e) { if (my !== S.seq || e.message === 'login') return; root.innerHTML = card('Could not load', '<div class="cc-empty">Something went wrong loading this screen. Press refresh to try again.<br><small>' + esc(e.message) + '</small></div>'); });
    if (v.auto) S.timer = setInterval(function () { if (!document.hidden && !$('ccDrawer').classList.contains('is-on') && !$('ccModal').classList.contains('is-on')) render(true); }, v.auto * 1000);
    $('ccSide').classList.remove('is-open');
    if (changed) window.scrollTo(0, 0);
  }
  window.addEventListener('hashchange', function () { render(); });
  $('ccRefresh').addEventListener('click', function () { render(); });
  $('ccMenu').addEventListener('click', function () { $('ccSide').classList.toggle('is-open'); });
  $('ccTheme').addEventListener('click', function () { var cur = document.documentElement.getAttribute('data-theme') || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'); var n = cur === 'dark' ? 'light' : 'dark'; document.documentElement.setAttribute('data-theme', n); try { localStorage.setItem('ff-theme', n); } catch (e) {} });
  fillFilters();
  render();
})();

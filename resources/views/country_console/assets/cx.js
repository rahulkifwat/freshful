/* Freshful Control Centre update 1 (15 Nov 2026): Alert centre, Data health, Metric definitions.
   Plugs into cc.js through window.CCX (after ad2.js). Read only except the alert actions, which are logged. */
(function () {
  'use strict';
  var prev = window.CCX;
  window.CCX = function (K) {
    if (typeof prev === 'function') { try { prev(K); } catch (e) { if (window.console) console.error(e); } }
    var S = K.S, V = K.V, esc = K.esc, money = K.money, num = K.num, dt = K.dt, pill = K.pill, api = K.api, post = K.post, card = K.card, kpi = K.kpi,
      slot = K.slot, mount = K.mount, T = K.T, ask = K.ask, toast = K.toast, loading = K.loading;

    var css = document.createElement('style');
    css.textContent = '.cx-note{color:var(--mute,#64748b);font-size:12.5px;margin:2px 0 12px}.cx-row{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:12px}' +
      '.cx-act{display:flex;gap:6px;flex-wrap:wrap}.cx-why{font-size:12.5px;color:var(--mute,#64748b)}.cx-hist{margin:0;padding:0;list-style:none}.cx-hist li{padding:7px 0;border-bottom:1px solid var(--line,#e5e7eb);font-size:13px}';
    document.head.appendChild(css);

    function fresh(f) {
      if (!f) return '';
      return '<p class="cx-note">Data read live at ' + esc(f.now_text) + '. ' + (f.last_order_at ? 'Latest order in the system: ' + dt(f.last_order_at) + '. ' : 'No orders found. ') +
        (f.last_stock_update ? 'Latest stock update: ' + dt(f.last_stock_update) + '. ' : '') + esc(f.note || '') + '</p>';
    }
    var SEV = { bad: ['High', 'p-bad'], warn: ['Medium', 'p-warn'], info: ['Info', 'p-info'] };
    var CONF = { high: 'High confidence', medium: 'Medium confidence', low: 'Low confidence (little data)' };
    var STAT = { open: ['Open', 'p-bad'], acknowledged: ['Acknowledged', 'p-warn'], dismissed: ['Dismissed', 'p-mute'], resolved: ['Resolved', 'p-ok'] };

    /* ---------------------------------------------------------------- Alert centre */
    V['cc-alerts'] = { t: 'Alert centre', dates: false, auto: 180, run: function (root) {
      var st = (V['cc-alerts'].st = V['cc-alerts'].st || 'active');
      loading(root);
      return api('/ad/cc/alerts', { status: st }, false).then(function (d) {
        if (!d.ok) { root.innerHTML = card('Alert centre', '<div class="cc-empty">' + esc(d.message || 'Could not load.') + '</div>'); return; }
        var c = d.counts || {}, open = (c.open || 0), ackd = (c.acknowledged || 0);
        var tabs = [['active', 'Needs attention'], ['dismissed', 'Dismissed'], ['resolved', 'Resolved']].map(function (t) { return '<button type="button" class="cc-btn sm' + (t[0] === st ? ' pri' : '') + '" data-st="' + t[0] + '">' + t[1] + '</button>'; }).join('');
        root.innerHTML = '<div class="cx">' + '<div class="cc-kpis">' + kpi('Open', num(open), 'not yet looked at', open ? 'bad' : '') + kpi('Acknowledged', num(ackd), 'someone is on it') + kpi('Dismissed', num(c.dismissed || 0), 'with a reason') + kpi('Resolved', num(c.resolved || 0), 'condition cleared') + '</div>' +
          '<div class="cx-row">' + tabs + '<button type="button" class="cc-btn sm" data-scan style="margin-left:auto">Check again now</button></div>' +
          fresh(d.freshness) + (d.last_scan ? '<p class="cx-note">Alerts last refreshed ' + dt(d.last_scan) + '. Each alert is worked out by a fixed rule from real records, not guessed.</p>' : '<p class="cx-note">No check has run yet. Press "Check again now".</p>') +
          card(st === 'active' ? 'Alerts that need attention' : st === 'dismissed' ? 'Dismissed alerts' : 'Resolved alerts', slot('t'), { flush: 1 }) + '</div>';
        mount(root, 't', T([
          { k: 'severity', t: 'Level', f: function (r) { var s = SEV[r.severity] || SEV.info; return pill(s[0], s[1]); }, v: function (r) { return r.severity === 'bad' ? 2 : r.severity === 'warn' ? 1 : 0; }, n: 1 },
          { k: 'title', t: 'What happened', f: function (r) { return '<b>' + esc(r.title) + '</b><small>' + esc(r.detail || '') + '</small><small>Next step: ' + esc(r.next_step || '') + '</small>'; } },
          { k: 'impact_value', t: 'Impact', n: 1, f: function (r) { return (r.impact_value != null ? money(r.impact_value) : '-') + '<small>' + esc(r.impact_label || '') + '</small>'; } },
          { k: 'records', t: 'Records', n: 1, f: function (r) { return r.link ? '<a href="#' + esc(r.link) + '">' + num(r.records) + ' record' + (r.records === 1 ? '' : 's') + '</a>' : num(r.records); } },
          { k: 'period_from', t: 'Period / confidence', f: function (r) { return (r.period_from ? esc(r.period_from) + ' to ' + esc(r.period_to) : 'Right now') + '<small>' + esc(CONF[r.confidence] || '') + '</small>'; } },
          { k: 'status', t: 'Status', f: function (r) { var s = STAT[r.status] || STAT.open; return pill(s[0], s[1]) + (r.assigned_name ? '<small>Assigned: ' + esc(r.assigned_name) + '</small>' : ''); } },
          { k: 'id', t: '', nosort: 1, csv: false, f: function (r) {
            var b = '';
            if (r.status === 'open') b += '<button type="button" class="cc-btn sm" data-a="acknowledge" data-id="' + r.id + '">Acknowledge</button>';
            if (r.status === 'open' || r.status === 'acknowledged') b += '<button type="button" class="cc-btn sm" data-a="assign" data-id="' + r.id + '">Assign</button><button type="button" class="cc-btn sm" data-a="dismiss" data-id="' + r.id + '">Dismiss</button>';
            else b += '<button type="button" class="cc-btn sm" data-a="reopen" data-id="' + r.id + '">Reopen</button>';
            return '<div class="cx-act">' + b + '<button type="button" class="cc-btn sm" data-a="history" data-id="' + r.id + '">History</button></div>';
          } }
        ], d.alerts, { sort: 0, dir: -1, per: 25, csv: 'alerts.csv', empty: st === 'active' ? 'No alerts need attention right now.' : 'Nothing here.' }));
        root.querySelectorAll('[data-st]').forEach(function (b) { b.addEventListener('click', function () { V['cc-alerts'].st = b.getAttribute('data-st'); K.render(); }); });
        root.querySelector('[data-scan]').addEventListener('click', function (e) { var b = e.currentTarget; b.disabled = true; b.textContent = 'Checking...'; post('/ad/cc/alerts/scan', {}).then(function (r) { toast(r.ok ? 'Check done. New: ' + r.new + ', cleared: ' + r.resolved + '.' : (r.message || 'Could not check.'), r.ok ? 'ok' : 'bad'); K.render(); }); });
        root.querySelector('.cx').addEventListener('click', function (e) {
          var b = e.target.closest('[data-a]'); if (!b) return;
          var id = b.getAttribute('data-id'), a = b.getAttribute('data-a'), done = function (r) { toast(r.message || (r.ok ? 'Done.' : 'Could not do that.'), r.ok ? 'ok' : 'bad'); if (r.ok) K.render(); };
          if (a === 'acknowledge' || a === 'reopen') { post('/ad/cc/alerts/' + id + '/' + a, {}).then(done); }
          else if (a === 'assign') { ask({ title: 'Assign this alert', text: 'Type the name of the person who will handle it.', input: { label: 'Name', placeholder: 'e.g. Hub manager Kochi' }, ok: 'Assign' }).then(function (v) { if (v) post('/ad/cc/alerts/' + id + '/assign', { assignee: v }).then(done); }); }
          else if (a === 'dismiss') { ask({ title: 'Dismiss this alert?', text: 'It will stay hidden until you reopen it. The reason is saved.', input: { label: 'Reason', placeholder: 'Why is this not a problem?' }, ok: 'Dismiss', tone: 'warn' }).then(function (v) { if (v) post('/ad/cc/alerts/' + id + '/dismiss', { note: v }).then(done); }); }
          else if (a === 'history') {
            api('/ad/cc/alerts/' + id + '/history', {}, false).then(function (h) {
              var li = (h.events || []).map(function (x) { return '<li><b>' + esc(x.action) + '</b> by ' + esc(x.actor) + ' · ' + dt(x.at) + (x.note ? '<br>' + esc(x.note) : '') + '</li>'; }).join('') || '<li>No history.</li>';
              K.openModal ? K.openModal('Alert history', '<ul class="cx-hist">' + li + '</ul>') : toast('History: ' + (h.events || []).length + ' entries');
            });
          }
        });
      });
    } };

    /* ---------------------------------------------------------------- Data health */
    V['cc-health'] = { t: 'Data health', dates: false, run: function (root) {
      loading(root);
      return api('/ad/cc/health', {}, false).then(function (d) {
        if (!d.ok) { root.innerHTML = card('Data health', '<div class="cc-empty">' + esc(d.message || 'Could not load.') + '</div>'); return; }
        var s = d.summary, L = { ok: ['Good', 'p-ok'], warn: ['Check', 'p-warn'], bad: ['Fix', 'p-bad'], na: ['Cannot check', 'p-mute'] };
        root.innerHTML = '<div class="cc-kpis">' + kpi('Good', num(s.ok), 'checks passed') + kpi('To check', num(s.warn), 'small problems', s.warn ? 'warn' : '') + kpi('To fix', num(s.bad), 'affect reports', s.bad ? 'bad' : '') + kpi('Cannot check', num(s.na), 'missing table or column') + '</div>' +
          fresh(d.freshness) + card('Checks', slot('t'), { flush: 1, sub: 'These look for records that make reports wrong. Nothing is changed here.' });
        mount(root, 't', T([
          { k: 'status', t: 'Result', f: function (r) { var l = L[r.status]; return pill(l[0], l[1]); } },
          { k: 'label', t: 'Check', f: function (r) { return '<b>' + esc(r.label) + '</b><small>' + esc(r.detail) + '</small>'; } },
          { k: 'affects', t: 'Numbers it can make wrong', f: function (r) { return '<span class="cx-why">' + esc(r.affects) + '</span>'; } },
          { k: 'hint', t: 'What to do', f: function (r) { return r.status === 'ok' ? '' : '<span class="cx-why">' + esc(r.hint) + '</span>'; } }
        ], d.checks, { noPage: 1, csv: 'data-health.csv' }));
      });
    } };

    /* ---------------------------------------------------------------- Metric definitions */
    V['cc-metrics'] = { t: 'Metric definitions', dates: false, run: function (root) {
      loading(root);
      return api('/ad/cc/metrics', {}, false).then(function (d) {
        if (!d.ok) { root.innerHTML = card('Metric definitions', '<div class="cc-empty">' + esc(d.message || 'Could not load.') + '</div>'); return; }
        root.innerHTML = fresh(d.freshness) + card('What every number means', slot('t'), { flush: 1, sub: 'A metric the database cannot support is shown as unavailable, with the reason. Nothing is estimated silently.' });
        mount(root, 't', T([
          { k: 'label', t: 'Metric', f: function (r) { return '<b>' + esc(r.label) + '</b>'; } },
          { k: 'definition', t: 'How it is worked out' },
          { k: 'source', t: 'Source' },
          { k: 'available', t: 'Status', f: function (r) { return r.available ? pill('Available', 'p-ok') : pill('Unavailable', 'p-warn') + '<small>' + esc(r.why || '') + '</small>'; } }
        ], d.metrics, { noPage: 1, csv: 'metric-definitions.csv' }));
      });
    } };

    /* ---------------------------------------------------------------- AI analyst (update 2). Read only: it answers and links, it never changes anything. */
    var MODE = { ai: ['AI-written from your numbers', 'p-info'], numbers: ['Worked out from the numbers', 'p-ok'] };
    function answerCard(a) {
      var b = a.basis || {}, per = b.period_from === b.period_to ? b.period_from : b.period_from + ' to ' + b.period_to, m = MODE[a.mode] || MODE.numbers;
      var src = (a.sources || []).map(function (x) {
        var v = (x.unit === 'Rs' ? money(x.value) : (x.unit === '%' ? x.value + '%' : num(x.value)));
        return '<tr><td>' + esc(x.label) + '</td><td class="num">' + esc(v) + '</td><td>' + (x.link ? '<a href="#' + esc(x.link) + '">Open</a>' : '') + '</td></tr>';
      }).join('');
      var acts = (a.actions || []).map(function (x) { return '<a class="cc-btn sm" href="#' + esc(x.key) + '">' + esc(x.label) + '</a>'; }).join(' ');
      var warn = (a.warnings || []).map(function (w) { return '<p class="cx-note" style="color:#B45309">' + esc(w) + '</p>'; }).join('');
      var miss = (a.missing || []).length ? '<p class="cx-note">Not available: ' + (a.missing || []).map(esc).join('; ') + '</p>' : '';
      return '<div class="cx-ans"><p style="font-size:15px;margin:0 0 10px"><b>' + esc(a.question) + '</b></p><p style="margin:0 0 10px;line-height:1.55">' + esc(a.answer) + '</p>' + warn +
        '<div class="cx-row">' + pill(m[0], m[1]) + pill(CONF[a.confidence] || a.confidence, a.confidence === 'low' ? 'p-warn' : 'p-ok') + '</div>' +
        '<p class="cx-note">Period ' + esc(per) + (b.compared_with ? ' (compared with ' + esc(b.compared_with) + ')' : '') + '. Scope: ' + esc(b.scope || '') + '. ' + num(b.orders_in_period || 0) + ' orders in the period. Data read ' + esc(b.data_read_at || '') + '. ' + esc(a.note || '') + '</p>' + miss +
        (src ? '<table class="cc-tbl" style="margin:6px 0 10px"><thead><tr><th>Figure used</th><th class="num">Value</th><th></th></tr></thead><tbody>' + src + '</tbody></table>' : '') +
        (acts ? '<div class="cx-act">' + acts + '</div>' : '') + '</div>';
    }
    V['cc-ask'] = { t: 'AI analyst', dates: true, run: function (root) {
      loading(root);
      return Promise.all([api('/ad/cc/ai/status', {}, false), api('/ad/cc/ai/history', {}, false)]).then(function (res) {
        var st = res[0] || {}, h = res[1] || {};
        var samples = ['How are sales in this period?', 'Why might the cancellation rate be high?', 'Which hub sells the most?', 'How much rider cash is pending?', 'What stock is expired or in danger?', 'What needs my attention today?'];
        root.innerHTML = '<div class="cx">' + card('Ask about the business', '<p class="cx-note">' + esc(st.note || '') + ' It reads your current filters (dates, hub) and can only suggest screens to open. It never changes anything.</p>' +
          '<div class="cx-row"><input id="cxQ" maxlength="400" placeholder="Type a question, for example: how are sales this week?" style="flex:1;min-width:240px;padding:9px 11px;border:1px solid var(--line,#d1d5db);border-radius:8px;font:inherit">' +
          '<button type="button" class="cc-btn pri" id="cxGo">Ask</button></div><div class="cx-row">' + samples.map(function (q) { return '<button type="button" class="cc-btn sm" data-q="' + esc(q) + '">' + esc(q) + '</button>'; }).join('') + '</div>' +
          '<div id="cxOut"></div>') + card('Recent questions', '<div id="cxHist"></div>', { flush: 1, sub: 'Every question and answer is kept for audit. The AI key is never stored here.' }) + '</div>';
        var out = root.querySelector('#cxOut'), box = root.querySelector('#cxQ'), go = root.querySelector('#cxGo');
        function submit(q) {
          q = (q || '').trim(); if (!q) { toast('Type a question first.'); return; }
          go.disabled = true; go.textContent = 'Working...'; out.innerHTML = '<p class="cx-note">Reading the figures...</p>';
          post('/ad/cc/ai/ask', { question: q, from: S.from, to: S.to }).then(function (r) {
            go.disabled = false; go.textContent = 'Ask';
            out.innerHTML = r.ok ? answerCard(r) : '<p class="cx-note" style="color:#B91C1C">' + esc(r.message || 'Could not answer.') + '</p>';
            if (r.ok) api('/ad/cc/ai/history', {}, false).then(showHist);
          });
        }
        go.addEventListener('click', function () { submit(box.value); });
        box.addEventListener('keydown', function (e) { if (e.key === 'Enter') submit(box.value); });
        root.querySelectorAll('[data-q]').forEach(function (b) { b.addEventListener('click', function () { box.value = b.getAttribute('data-q'); submit(box.value); }); });
        function showHist(h) {
          var w = root.querySelector('#cxHist'); if (!w) return; w.innerHTML = '';
          if (!h || !h.ok) { w.innerHTML = '<div class="cc-empty">' + esc((h && h.message) || 'No history yet.') + '</div>'; return; }
          w.appendChild(T([
            { k: 'at', t: 'When', f: function (r) { return dt(r.at); }, v: function (r) { return r.at; } },
            { k: 'question', t: 'Question', f: function (r) { return '<b>' + esc(r.question) + '</b><small>' + esc((r.answer || '').slice(0, 220)) + '</small>'; } },
            { k: 'mode', t: 'How', f: function (r) { var m = MODE[r.mode] || MODE.numbers; return pill(r.mode === 'ai' ? 'AI' : 'Numbers', m[1]) + (r.status === 'fallback' ? '<small>AI not used: ' + esc(r.note || '') + '</small>' : ''); } },
            { k: 'period', t: 'Period', f: function (r) { return esc(r.period) + '<small>' + esc(r.scope || '') + '</small>'; } },
            { k: 'actor', t: 'By' }
          ], h.rows, { noPage: 1, csv: 'ai-questions.csv' }));
        }
        showHist(h);
      });
    } };
  };
})();

/* Freshful admin console: Driver management (drivers update 1, 28 Oct 2026).
   Pending approvals (document check), driver list, driver file, order assignment and driver wallets.
   Plugs into cc.js through window.CCX (after ad.js) and uses the same helpers, styles and dialogs. */
(function () {
  'use strict';
  var prev = window.CCX;
  window.CCX = function (K) {
    if (typeof prev === 'function') { try { prev(K); } catch (e) { if (window.console) console.error(e); } }
    var C = K.C, V = K.V, S = K.S, $ = K.$, esc = K.esc, money = K.money, money2 = K.money2, num = K.num, dt = K.dt, dOnly = K.dOnly, ago = K.ago,
      pill = K.pill, api = K.api, act = K.act, ask = K.ask, toast = K.toast, card = K.card, kpi = K.kpi, slot = K.slot, mount = K.mount, T = K.T, el = K.el,
      openDrawer = K.openDrawer, closeDrawer = K.closeDrawer, openModal = K.openModal, closeModal = K.closeModal, stagePill = K.stagePill;

    var STATE = { pending: ['Waiting approval', 'p-warn'], approved: ['Approved', 'p-ok'], rejected: ['Rejected', 'p-bad'], incomplete: ['Not finished', 'p-mute'] };
    var PROBLEMS = ['Image blurry. Please retake.', 'Part of the card is cut off. Show the full card.', 'Name does not match the other documents.', 'Document has expired.', 'Wrong document uploaded.', 'Photo is not clear. Face must be visible, no sunglasses.'];
    var fileUrl = function (id, field) { return C.base + '/ad/drivers/' + id + '/file/' + field; };
    var statePill = function (s) { var x = STATE[s] || STATE.incomplete; return pill(x[0], x[1]); };
    var dutyPill = function (d, susp) { return susp ? pill('Deactivated', 'p-bad') : d === 'on_duty' ? pill('On duty', 'p-info') : pill('Off duty'); };
    var avatar = function (r, big) { return r.photo ? '<img class="dr-av' + (big ? ' big' : '') + '" alt="" loading="lazy" src="' + esc(fileUrl(r.id, 'profile_photo')) + '" onerror="this.replaceWith(Object.assign(document.createElement(\'span\'),{className:\'dr-av none' + (big ? ' big' : '') + '\',textContent:\'No photo\'}))">' : '<span class="dr-av none' + (big ? ' big' : '') + '">No photo</span>'; };
    var since = function (ts) { return ts ? ago(ts) : '–'; };
    var plural = function (n, w) { return n + ' ' + w + (n === 1 ? '' : 's'); };

    function refreshBadge() { api('/ad/drivers/counts', {}, false).then(function (d) { if (d && d.ok) K.badge('dr-pending', d.pending); }).catch(function () {}); }
    setTimeout(refreshBadge, 400);

    /* ------------------------------------------------------------------ image viewer (zoom, rotate, next) */
    function viewer(items, start) {
      var i = start || 0, z = 1, rot = 0;
      var w = el('<div class="dr-lb" role="dialog" aria-modal="true" aria-label="Document"><div class="dr-lb-bar"><b></b><span style="flex:1"></span>' +
        '<button type="button" class="cc-btn sm" data-a="out" aria-label="Zoom out">−</button><button type="button" class="cc-btn sm" data-a="in" aria-label="Zoom in">+</button><button type="button" class="cc-btn sm" data-a="rot">Rotate</button>' +
        '<a class="cc-btn sm" data-a="open" target="_blank" rel="noopener">Open full size</a><button type="button" class="cc-btn sm" data-a="x">Close</button></div>' +
        '<div class="dr-lb-stage"><button type="button" class="dr-lb-nav l" data-a="p" aria-label="Previous">‹</button><img alt=""><div class="dr-lb-miss" hidden>This file is not on the server.</div><button type="button" class="dr-lb-nav r" data-a="n" aria-label="Next">›</button></div></div>');
      document.body.appendChild(w);
      var img = w.querySelector('img'), miss = w.querySelector('.dr-lb-miss');
      function draw() { var it = items[i]; w.querySelector('b').textContent = it.label + '  (' + (i + 1) + ' of ' + items.length + ')'; miss.hidden = true; img.hidden = false; img.src = it.src; w.querySelector('[data-a=open]').href = it.src; z = 1; rot = 0; tf(); w.querySelectorAll('.dr-lb-nav').forEach(function (b) { b.hidden = items.length < 2; }); }
      function tf() { img.style.transform = 'scale(' + z + ') rotate(' + rot + 'deg)'; }
      img.onerror = function () { img.hidden = true; miss.hidden = false; };
      function close() { document.removeEventListener('keydown', key, true); w.remove(); }
      function key(e) { if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); close(); } else if (e.key === 'ArrowRight') { i = (i + 1) % items.length; draw(); } else if (e.key === 'ArrowLeft') { i = (i - 1 + items.length) % items.length; draw(); } }
      w.addEventListener('click', function (e) {
        var a = e.target.closest('[data-a]'); if (!a) { if (e.target === w || e.target.classList.contains('dr-lb-stage')) close(); return; }
        var k = a.getAttribute('data-a');
        if (k === 'x') close(); else if (k === 'in') { z = Math.min(4, z + .5); tf(); } else if (k === 'out') { z = Math.max(.5, z - .5); tf(); } else if (k === 'rot') { rot = (rot + 90) % 360; tf(); }
        else if (k === 'n') { i = (i + 1) % items.length; draw(); } else if (k === 'p') { i = (i - 1 + items.length) % items.length; draw(); }
      });
      document.addEventListener('keydown', key, true);
      draw();
    }

    /* ------------------------------------------------------------------ actions shared by every screen */
    function approveFlow(d, btn, after) {
      var ch = d.checks || {}, notOk = [], probs = [];
      Object.keys(ch).forEach(function (k) { var c = ch[k]; if (c.decision === 'problem') probs.push(c.label); else if (c.decision !== 'ok' || c.changed) notOk.push(c.label); });
      var go = function () { act(btn, '/ad/drivers/' + d.id + '/approve', {}, function () { refreshBadge(); if (after) after(); }); };
      if (probs.length) {
        ask({ title: 'Approve with problems marked?', text: 'You marked a problem with: ' + probs.join(', ') + '. Usually you reject so the driver can fix it. Approve anyway?', ok: 'Approve anyway', tone: 'danger' }).then(function (y) { if (y) go(); });
      } else if (notOk.length) {
        ask({ title: 'Approve ' + d.name + '?', text: 'Not checked yet: ' + notOk.join(', ') + '. After approval the driver can go on duty and receive orders.', ok: 'Approve', tone: 'warn' }).then(function (y) { if (y) go(); });
      } else {
        ask({ title: 'Approve ' + d.name + '?', text: 'Everything is checked. The driver can go on duty and receive orders, and the driver wallet is made.', ok: 'Approve' }).then(function (y) { if (y) go(); });
      }
    }
    function rejectFlow(d, after) {
      var ch = d.checks || {}, lines = [];
      Object.keys(ch).forEach(function (k) { if (ch[k].decision === 'problem') lines.push(ch[k].label + ': ' + ch[k].note); });
      openModal('Reject ' + d.name, '<p class="cc-note">The driver sees this message in the app, fixes it and sends again. Write clearly what to change.</p>' +
        '<label class="cc-f"><span>Message to the driver</span><textarea class="cc-in dr-ta" id="drRj" maxlength="1000" rows="5" placeholder="e.g. Aadhaar card: Image blurry. Please retake.">' + esc(lines.join('\n')) + '</textarea></label>' +
        '<div class="dr-quick">' + PROBLEMS.map(function (p) { return '<button type="button" class="cc-chip" data-q="' + esc(p) + '">' + esc(p) + '</button>'; }).join('') + '</div>',
        [{ t: 'Reject', cls: 'solid-bad', fn: function (b) { var v = $('drRj').value.trim(); if (v.length < 5) { toast('Write the message to the driver.', 'bad'); return; } act(b, '/ad/drivers/' + d.id + '/reject', { reason: v }, function () { closeModal(); refreshBadge(); if (after) after(); }); } }]);
      document.querySelectorAll('#ccMBody [data-q]').forEach(function (b) { b.addEventListener('click', function () { var t = $('drRj'); t.value = (t.value.trim() ? t.value.trim() + '\n' : '') + b.getAttribute('data-q'); t.focus(); }); });
    }
    function toggleFlow(r, btn, after) {
      var off = !r.suspended;
      var go = function () { act(btn, '/ad/drivers/' + r.id + '/toggle', {}, function () { if (after) after(); }); };
      if (!off) { go(); return; }
      ask({ title: 'Deactivate ' + r.name + '?', text: 'The driver is set off duty and cannot go on duty or get new orders until you activate again.' + (r.open ? ' This driver still has ' + plural(r.open, 'open order') + '; change their rider in Order assignment.' : ''), ok: 'Deactivate', tone: 'danger' }).then(function (y) { if (y) go(); });
    }
    function walletModal(id, name, moves, after) {
      openModal('Wallet entry: ' + name, '<div class="cc-row"><label class="cc-f"><span>What is it</span><select class="cc-sel" id="dwK">' + Object.keys(moves).map(function (k) { return '<option value="' + esc(k) + '">' + esc((moves[k].sign > 0 ? '+ ' : '− ') + moves[k].label) + '</option>'; }).join('') + '</select></label>' +
        '<label class="cc-f"><span>Amount (₹)</span><input class="cc-in" id="dwA" type="number" min="0.01" step="0.01" inputmode="decimal"></label></div>' +
        '<label class="cc-f"><span>Reason (the driver sees it in the wallet history)</span><input class="cc-in" id="dwN" maxlength="300" placeholder="e.g. Incentive for 20 deliveries, week 43"></label>' +
        '<p class="cc-note">Saved at once, with your name in the change log. A debit can not take the balance below ₹0.</p>',
        [{ t: 'Save entry', cls: 'pri', fn: function (b) {
          var k = $('dwK').value, a = parseFloat($('dwA').value), n = $('dwN').value.trim();
          if (!(a > 0)) { toast('Enter the amount.', 'bad'); return; } if (n.length < 3) { toast('Write the reason.', 'bad'); return; }
          ask({ title: (moves[k].sign > 0 ? 'Add ' : 'Take ') + money2(a) + (moves[k].sign > 0 ? ' to ' : ' from ') + name + '?', text: moves[k].label + ': ' + n, ok: 'Save', tone: moves[k].sign > 0 ? 'info' : 'warn' }).then(function (y) {
            if (y) act(b, '/ad/drivers/' + id + '/wallet', { kind: k, amount: a, note: n }, function () { closeModal(); if (after) after(); });
          });
        } }]);
    }

    /* ------------------------------------------------------------------ driver file (review + everything about the driver) */
    function driverDrawer(id, after, focus, keep) {
      if (keep == null) openDrawer('Driver', '<div class="cc-load"><i></i>Loading…</div>');
      var reload = function () { driverDrawer(id, after, null, $('ccDBody').scrollTop); if (after) after(); };
      api('/ad/drivers/' + id, {}, false).then(function (r) {
        if (!r.ok) { $('ccDBody').innerHTML = '<div class="cc-empty">' + esc(r.message) + '</div>'; return; }
        var d = r.driver, b = $('ccDBody'), p = d.profile || {}, ch = d.checks;
        $('ccDTitle').textContent = d.name + ' · #' + d.id;
        var row = function (k, v) { return '<dt>' + esc(k) + '</dt><dd>' + (v === '' || v == null ? '<span class="muted">–</span>' : v) + '</dd>'; };
        var checkBox = function (k, inner) {
          var c = ch[k];
          var st = !c.present ? pill('Missing', 'p-bad') : c.decision === 'ok' ? (c.changed ? pill('Changed since checked', 'p-warn') : pill('Checked OK', 'p-ok')) : c.decision === 'problem' ? (c.changed ? pill('Changed since checked', 'p-warn') : pill('Problem', 'p-bad')) : pill('Not checked', 'p-mute');
          return '<section class="dr-chk' + (c.decision === 'problem' && !c.changed ? ' is-bad' : c.decision === 'ok' && !c.changed ? ' is-ok' : '') + '" data-k="' + k + '"><header><b>' + esc(c.label) + '</b>' + st + '<span style="flex:1"></span>' +
            (c.present ? '<button type="button" class="cc-btn sm" data-ok="' + k + '">Looks good</button><button type="button" class="cc-btn sm danger" data-bad="' + k + '">Problem</button>' + (c.decision ? '<button type="button" class="cc-btn sm" data-clr="' + k + '" title="Remove this check">Undo</button>' : '') : '') + '</header>' +
            '<div class="dr-chk-b">' + inner + (c.decision ? '<p class="cc-note">' + (c.decision === 'problem' ? '<b>Problem:</b> ' + esc(c.note) + ' · ' : '') + 'Checked by ' + esc(c.by) + ' · ' + dt(c.at) + (c.changed ? ' · <b>the driver changed it after this check</b>' : '') + '</p>' : '') + '</div></section>';
        };
        var imgs = [];
        if (d.photo) imgs.push({ label: 'Profile photo', src: fileUrl(d.id, 'profile_photo') });
        d.docs.forEach(function (x) { if (x.front) imgs.push({ label: x.label + ' · front', src: fileUrl(d.id, x.front) }); if (x.back) imgs.push({ label: x.label + ' · back', src: fileUrl(d.id, x.back) }); });
        var thumb = function (label, src) { var i = imgs.map(function (x) { return x.src; }).indexOf(src); return '<button type="button" class="dr-doc" data-v="' + i + '"><img alt="' + esc(label) + '" loading="lazy" src="' + esc(src) + '" onerror="this.parentNode.classList.add(\'is-miss\')"><span>' + esc(label) + '</span><em>Not on server</em></button>'; };
        var miss = function (t) { return '<div class="dr-doc none"><span>' + esc(t) + '</span></div>'; };
        var docBox = function (x) { return '<div class="dr-docs">' + (x.front ? thumb('Front', fileUrl(d.id, x.front)) : miss('Front missing')) + (x.back ? thumb('Back', fileUrl(d.id, x.back)) : miss('Back missing')) + '</div>' + (x.app_status ? '<p class="cc-note">App status: ' + esc(x.app_status === 'action' ? 'both sides uploaded' : x.app_status) + '</p>' : ''); };

        var pend = d.state === 'pending', allOk = Object.keys(ch).every(function (k) { return ch[k].decision === 'ok' && !ch[k].changed; });
        var nOk = Object.keys(ch).filter(function (k) { return ch[k].decision === 'ok' && !ch[k].changed; }).length, nAll = Object.keys(ch).length;
        var h = '<div class="dr-head">' + avatar(d, true) + '<div><div class="cc-chips">' + statePill(d.state) + dutyPill(d.duty, d.suspended) + (d.boy ? pill(d.boy.employee_id || ('Rider #' + d.boy.id), 'p-info') : '') + '</div>' +
          '<p class="dr-sub">' + (d.phone ? '<a href="tel:' + esc(d.phone) + '">' + esc(d.phone) + '</a> · ' : '') + esc(d.email) + '</p>' +
          '<p class="dr-sub">' + (d.submitted ? 'Sent for approval ' + dt(d.submitted) : 'Not sent for approval yet') + (d.decided && d.state !== 'pending' ? ' · ' + (d.state === 'rejected' ? 'rejected ' : 'approved ') + dt(d.decided) + (d.decided_by ? ' by ' + esc(d.decided_by) : '') : '') + '</p></div></div>';
        if (d.state === 'rejected' && d.reason) h += '<div class="dr-box bad"><b>Rejection message the driver sees</b><p>' + esc(d.reason).replace(/\n/g, '<br>') + '</p></div>';
        if (d.warnings.length) h += '<div class="dr-box warn"><b>Check before you decide</b><ul>' + d.warnings.map(function (w) { return '<li>' + esc(w) + '</li>'; }).join('') + '</ul></div>';
        if (pend) h += '<div class="dr-bar"><div><b>' + nOk + ' of ' + nAll + ' checked OK</b>' + K.prog(nOk * 100 / nAll) + '</div><button type="button" class="cc-btn solid-bad" id="drRej">Reject…</button><button type="button" class="cc-btn pri" id="drApp">' + (allOk ? 'Approve driver' : 'Approve…') + '</button></div>';
        else if (d.state === 'incomplete') h += '<p class="cc-note">This driver has not finished the sign-up in the app (vehicle, bank and both sides of all three documents are needed), so there is nothing to approve yet.</p>';

        h += '<div class="ad-sec">Checks</div>';
        h += checkBox('photo', d.photo ? '<div class="dr-docs">' + thumb('Selfie', fileUrl(d.id, 'profile_photo')) + '</div>' : '<p class="cc-note">No photo uploaded.</p>');
        h += checkBox('personal', d.profile ? '<dl class="cc-dl">' + row('Full name', esc(p.name)) + row('Father\'s name', esc(p.father)) + row('Date of birth', p.dob ? esc(p.dob) + (p.age != null ? ' (' + p.age + ' years)' : '') : '') + row('Mobile', esc(p.phone)) + row('Blood group', esc(p.blood)) + row('Address', esc(p.address)) + row('Languages', esc(p.language)) +
          row('Emergency contact', esc([p.em_name, p.em_rel ? '(' + p.em_rel + ')' : '', p.em_phone].filter(Boolean).join(' '))) + row('Referral code', esc(p.referral)) + '</dl>' : '<p class="cc-note">The driver has not filled in personal information.</p>');
        d.docs.forEach(function (x) { h += checkBox(x.key, docBox(x)); });
        h += checkBox('vehicle', d.vehicle ? '<dl class="cc-dl">' + row('Type', esc(d.vehicle.type)) + row('Number', esc(d.vehicle.number) || (/cycle/i.test(d.vehicle.type) ? 'Not needed for a bicycle' : '')) + '</dl>' : '<p class="cc-note">No vehicle details.</p>');
        h += checkBox('bank', d.bank ? '<dl class="cc-dl">' + row('Account holder', esc(d.bank.holder)) + row('Account number', '<span id="drAcc">' + esc(d.bank.masked) + '</span> <button type="button" class="cc-btn sm" id="drAccShow">Show</button>') + row('IFSC', esc(d.bank.ifsc)) + row('Bank / branch', esc([d.bank.bank, d.bank.branch].filter(Boolean).join(' / '))) + '</dl>' : '<p class="cc-note">No bank details.</p>');

        h += '<div class="ad-sec">Account</div><dl class="cc-dl">' + row('Driver ID / emp. no.', '#' + d.id + (d.emp_no ? ' · ' + esc(d.emp_no) : '')) +
          row('Account', (d.suspended ? pill('Deactivated', 'p-bad') : pill('Active', 'p-ok')) + ' <button type="button" class="cc-btn sm" id="drTog">' + (d.suspended ? 'Activate' : 'Deactivate') + '</button>') +
          row('Duty (from the app)', (d.duty === 'on_duty' ? 'On duty' : 'Off duty') + (d.duty_at ? ' · since ' + dt(d.duty_at) : '')) +
          row('Hub', d.boy ? esc(d.boy.hub || 'Not linked to a hub yet') + (d.boy.active ? '' : ' ' + pill('switched off at hub', 'p-warn')) : 'No delivery boy record') +
          row('Last location', d.location ? esc(d.location.address || (d.location.lat + ', ' + d.location.lng)) + ' · <a target="_blank" rel="noopener" href="https://www.google.com/maps?q=' + d.location.lat + ',' + d.location.lng + '">map</a> · ' + ago(d.location.at) : '') +
          row('Orders (60 days)', d.stats.open + ' open · ' + d.stats.delivered + ' delivered · ' + d.stats.cancelled + ' cancelled') + '</dl>';

        h += '<div class="ad-sec">Wallet</div>' + (d.wallet ? '<div class="dr-wal"><div><small>Balance</small><b>' + money2(d.wallet.balance) + '</b>' + (d.wallet.status === 'blocked' ? pill('Blocked', 'p-bad') : '') + '</div><button type="button" class="cc-btn" id="drWal">+ / − Wallet entry</button></div>' + slot('wt') :
          '<p class="cc-note">' + (d.state === 'approved' ? 'No wallet yet. Open Driver wallets and press "Make missing wallets".' : 'The wallet is made when the driver is approved.') + '</p>');
        h += '<div class="ad-sec">Orders (last 60 days)</div>' + slot('ot');
        b.innerHTML = h;

        if (d.wallet) mount(b, 'wt', T([{ k: 'at', t: 'When', n: 1, f: function (t) { return dt(t.at); }, c: function (t) { return dt(t.at); } }, { k: 'type', t: 'Type', f: function (t) { return esc(t.type.replace(/_/g, ' ')) + (t.status !== 'completed' ? ' ' + pill(t.status, t.status === 'pending' ? 'p-warn' : 'p-bad') : ''); } },
          { k: 'amount', t: 'Amount', n: 1, f: function (t) { var neg = /debit|penalty|withdrawal_(approved|request)/.test(t.type); return '<b style="color:' + (neg ? 'var(--bad)' : 'var(--ok)') + '">' + (neg ? '−' : '+') + money2(t.amount) + '</b>'; } }, { k: 'note', t: 'Note', f: function (t) { return esc(t.note) + (t.ref ? '<small>' + esc(t.ref) + '</small>' : ''); } }],
          d.txns, { per: 10, empty: 'No wallet entries yet.' }));
        mount(b, 'ot', T([{ k: 'order_id', t: 'Order', f: function (o) { return '<b>' + esc(o.order_id) + '</b><small>' + esc(o.hub) + '</small>'; } }, { k: 'at', t: 'Placed', n: 1, f: function (o) { return dt(o.at); }, c: function (o) { return dt(o.at); } },
          { k: 'stage', t: 'Status', f: function (o) { return stagePill(o.stage); } }, { k: 'amount', t: 'Amount', n: 1, f: function (o) { return money(o.amount) + '<small>' + esc(o.pay) + '</small>'; } }],
          d.orders, { per: 10, empty: 'No orders with this driver in the last 60 days.', click: function (o) { closeDrawer(); setTimeout(function () { K.orderDrawer(o.order_id); }, 150); } }));

        b.querySelectorAll('[data-v]').forEach(function (x) { x.addEventListener('click', function () { viewer(imgs, +x.getAttribute('data-v')); }); });
        b.querySelectorAll('[data-ok]').forEach(function (x) { x.addEventListener('click', function () { act(x, '/ad/drivers/' + d.id + '/review', { doc: x.getAttribute('data-ok'), decision: 'ok' }, reload); }); });
        b.querySelectorAll('[data-clr]').forEach(function (x) { x.addEventListener('click', function () { act(x, '/ad/drivers/' + d.id + '/review', { doc: x.getAttribute('data-clr'), decision: 'clear' }, reload); }); });
        b.querySelectorAll('[data-bad]').forEach(function (x) { x.addEventListener('click', function () {
          var k = x.getAttribute('data-bad');
          openModal('Problem: ' + ch[k].label, '<label class="cc-f"><span>What is wrong (the driver sees this when you reject)</span><input class="cc-in" id="drPb" maxlength="250" value="' + esc(ch[k].decision === 'problem' ? ch[k].note : '') + '"></label><div class="dr-quick">' + PROBLEMS.map(function (pp) { return '<button type="button" class="cc-chip" data-q="' + esc(pp) + '">' + esc(pp) + '</button>'; }).join('') + '</div>',
            [{ t: 'Save problem', cls: 'pri', fn: function (bt) { var v = $('drPb').value.trim(); if (v.length < 3) { toast('Write what is wrong.', 'bad'); return; } act(bt, '/ad/drivers/' + d.id + '/review', { doc: k, decision: 'problem', note: v }, function () { closeModal(); reload(); }); } }]);
          document.querySelectorAll('#ccMBody [data-q]').forEach(function (q) { q.addEventListener('click', function () { $('drPb').value = q.getAttribute('data-q'); $('drPb').focus(); }); });
        }); });
        var sa = $('drAccShow'); if (sa) sa.addEventListener('click', function () { $('drAcc').textContent = d.bank.account; sa.remove(); });
        var ba = $('drApp'); if (ba) ba.addEventListener('click', function () { approveFlow(d, ba, reload); });
        var br = $('drRej'); if (br) br.addEventListener('click', function () { rejectFlow(d, reload); });
        $('drTog').addEventListener('click', function () { toggleFlow({ id: d.id, name: d.name, suspended: d.suspended, open: d.stats.open }, $('drTog'), reload); });
        var bw = $('drWal'); if (bw) bw.addEventListener('click', function () { walletModal(d.id, d.name, r.moves, reload); });
        if (keep != null) b.scrollTop = keep;
        if (focus) { var f = b.querySelector(focus); if (f) setTimeout(function () { f.scrollIntoView({ block: 'start' }); }, 50); }
      });
    }

    /* ------------------------------------------------------------------ Pending approvals */
    V['dr-pending'] = { t: 'Pending driver approvals', dates: false, nofilter: true, auto: 60, run: function (root) {
      return api('/ad/drivers', {}, false).then(function (d) {
        if (!d.ok) { root.innerHTML = card('Pending approvals', '<div class="cc-empty">' + esc(d.message) + '</div>'); return; }
        var rows = d.rows.filter(function (r) { return r.state === 'pending'; }).sort(function (a, b) { return (a.submitted || 0) - (b.submitted || 0); });
        var wk = Date.now() / 1000 - 7 * 86400, decided = d.rows.filter(function (r) { return r.decided && r.decided >= wk; });
        var oldest = rows.length ? rows[0].submitted : null;
        K.badge('dr-pending', rows.length);
        var h = '<div class="cc-grid"><div class="cc-kpis">' + kpi('Waiting approval', num(rows.length, 0), oldest ? 'oldest sent ' + ago(oldest) : 'nobody waiting', rows.length ? 'warn' : 'k-green') +
          kpi('Approved (7 days)', num(decided.filter(function (r) { return r.state === 'approved'; }).length, 0), 'drivers', 'k-blue') + kpi('Rejected (7 days)', num(decided.filter(function (r) { return r.state === 'rejected'; }).length, 0), 'sent back to fix', 'k-slate') +
          kpi('Signing up', num(d.counts.incomplete, 0), 'not finished in the app', 'k-slate', 'dr-list') + '</div>';
        if (!rows.length) h += card('Pending approvals', '<div class="cc-empty">No driver is waiting for approval. New sign-ups from the rider app appear here when the driver presses Submit.</div>');
        else h += '<div class="dr-queue">' + rows.map(function (r) {
          var items = [['Photo', r.photo], ['Personal', r.personal], ['Documents ' + r.docs + '/3', r.docs === 3], ['Vehicle', !!r.vehicle], ['Bank', r.bank]];
          return '<article class="cc-card dr-q" data-id="' + r.id + '"><div class="dr-q-top">' + avatar(r) + '<div class="dr-q-who"><b>' + esc(r.name) + '</b><small>#' + r.id + ' · ' + esc(r.phone || r.email) + '</small><small>' + esc([r.vehicle, r.vehicle_no].filter(Boolean).join(' · ') || 'No vehicle') + (r.hub ? ' · ' + esc(r.hub) : '') + '</small></div>' +
            '<span class="dr-q-age' + (r.submitted && Date.now() / 1000 - r.submitted > 86400 ? ' late' : '') + '">' + (r.submitted ? 'sent ' + ago(r.submitted) : '') + '</span></div>' +
            '<div class="dr-q-items">' + items.map(function (x) { return '<span class="' + (x[1] ? 'ok' : 'no') + '">' + (x[1] ? '✓ ' : '✗ ') + esc(x[0]) + '</span>'; }).join('') + (r.problems ? '<span class="no">' + plural(r.problems, 'problem') + ' marked</span>' : '') + '</div>' +
            '<div class="dr-q-act"><button type="button" class="cc-btn pri" data-rv="' + r.id + '">Review documents</button></div></article>';
        }).join('') + '</div>';
        h += '<p class="cc-note ad-help">Open a driver, look at each photo and document (click to zoom or rotate), press Looks good or Problem for each item, then Approve or Reject. A rejected driver sees your message in the app, fixes it and sends again. Approve and reject use the same rules as the rider app.</p></div>';
        root.innerHTML = h;
        root.querySelectorAll('[data-rv]').forEach(function (b) { b.addEventListener('click', function () { driverDrawer(+b.getAttribute('data-rv'), function () { V['dr-pending'].run(root); }); }); });
        K.wireGo(root);
      });
    } };

    /* ------------------------------------------------------------------ Driver list */
    V['dr-list'] = { t: 'Driver list', dates: false, nofilter: true, run: function (root) {
      var st = S.p.drSt || 'all', q = S.p.drQ || '';
      return api('/ad/drivers', {}, false).then(function (d) {
        if (!d.ok) { root.innerHTML = card('Drivers', '<div class="cc-empty">' + esc(d.message) + '</div>'); return; }
        var c = d.counts;
        var tabs = [['all', 'All', c.all], ['pending', 'Waiting approval', c.pending], ['approved', 'Approved', c.approved], ['rejected', 'Rejected', c.rejected], ['incomplete', 'Not finished', c.incomplete], ['deactive', 'Deactivated', c.deactive], ['on_duty', 'On duty now', c.on_duty]];
        root.innerHTML = '<div class="cc-grid"><div class="cc-kpis">' + kpi('Rider app accounts', num(c.all, 0), num(c.approved, 0) + ' approved', 'k-blue') + kpi('On duty now', num(c.on_duty, 0), 'from the app', 'k-teal') +
          kpi('Waiting approval', num(c.pending, 0), 'open Pending approvals', c.pending ? 'warn' : 'k-green', 'dr-pending') + kpi('Deactivated', num(c.deactive, 0), 'cannot get orders', 'k-slate') + '</div>' +
          card('Drivers', '<div class="ad-tools"><div class="cc-tabs" id="drTabs">' + tabs.map(function (t) { return '<button type="button" data-s="' + t[0] + '"' + (t[0] === st ? ' class="is-on"' : '') + '>' + t[1] + '<small>' + t[2] + '</small></button>'; }).join('') + '</div>' +
            '<span style="flex:1"></span><input class="cc-in" id="drQ" type="search" placeholder="Name, phone, email, vehicle no." value="' + esc(q) + '" style="max-width:260px"></div>' + slot('t'), { flush: true }) +
          (d.blank_boys ? '<div class="dr-box warn"><b>' + plural(d.blank_boys, 'rider app driver') + ' without a name at the hubs</b><p>The rider app does not copy the driver name and phone to the hub rider list, so the hub and POS screens show these riders as "Rider #". Copy them now (only empty fields are filled).</p><button type="button" class="cc-btn pri" id="drFill">Copy names to the hubs</button></div>' : '') +
          '<p class="cc-note ad-help">Rider app accounts. Hub delivery boys added without the app are in Delivery boy > All delivery boys. Click a driver for documents, bank, wallet and orders.</p></div>';
        var draw = function () {
          var qq = q.toLowerCase();
          var rows = d.rows.filter(function (r) {
            var okS = st === 'all' || (st === 'deactive' ? r.suspended : st === 'on_duty' ? r.duty === 'on_duty' && !r.suspended : r.state === st);
            return okS && (!qq || (r.name + ' ' + r.phone + ' ' + r.email + ' ' + r.vehicle_no + ' ' + r.employee_id + ' #' + r.id).toLowerCase().indexOf(qq) >= 0);
          });
          var n = T([{ k: 'name', t: 'Driver', f: function (r) { return '<div class="dr-who">' + avatar(r) + '<div><b>' + esc(r.name) + '</b><small>#' + r.id + (r.employee_id ? ' · ' + esc(r.employee_id) : '') + ' · ' + esc(r.phone || r.email) + '</small></div></div>'; }, c: function (r) { return r.name; } },
            { k: 'state', t: 'Approval', f: function (r) { return statePill(r.state) + (r.state === 'pending' && r.submitted ? '<small>sent ' + ago(r.submitted) + '</small>' : r.decided && r.state !== 'incomplete' ? '<small>' + dOnly(r.decided) + '</small>' : ''); }, c: function (r) { return STATE[r.state][0]; } },
            { k: 'duty', t: 'Now', f: function (r) { return dutyPill(r.duty, r.suspended); }, c: function (r) { return r.suspended ? 'Deactivated' : r.duty; } },
            { k: 'vehicle', t: 'Vehicle', f: function (r) { return esc(r.vehicle || '–') + (r.vehicle_no ? '<small>' + esc(r.vehicle_no) + '</small>' : ''); } },
            { k: 'hub', t: 'Hub', f: function (r) { return r.hub ? esc(r.hub) : '<span class="muted">not linked</span>'; } },
            { k: 'docs', t: 'Documents', n: 1, f: function (r) { return r.docs === 3 ? pill('3 / 3', 'p-ok') : pill(r.docs + ' / 3', r.docs ? 'p-warn' : 'p-mute'); } },
            { k: 'wallet', t: 'Wallet', n: 1, f: function (r) { return r.wallet == null ? '<span class="muted">–</span>' : money2(r.wallet); } },
            { k: 'open', t: 'Orders', n: 1, f: function (r) { return '<b>' + r.open + '</b> open<small>' + r.delivered30 + ' delivered · 30 d</small>'; }, c: function (r) { return r.open; } },
            { k: 'seen', t: 'Last seen', n: 1, f: function (r) { return since(r.seen); }, c: function (r) { return r.seen ? dt(r.seen) : ''; } },
            { k: '_a', t: '', nosort: 1, csv: false, f: function (r) { return '<div class="ad-acts">' + (r.state === 'pending' ? '<button type="button" class="cc-btn pri" data-rv="' + r.id + '">Review</button>' : '') +
              '<button type="button" class="ad-sw' + (r.suspended ? '' : ' is-on') + '" role="switch" aria-checked="' + (r.suspended ? 'false' : 'true') + '" aria-label="Account active" title="' + (r.suspended ? 'Deactivated: click to activate' : 'Active: click to deactivate') + '" data-tg="' + r.id + '"><i></i></button></div>'; } }],
            rows, { per: 50, csv: 'freshful-drivers.csv', empty: q ? 'No driver matches.' : 'No drivers here.', click: function (r) { driverDrawer(r.id, function () { V['dr-list'].run(root); }); }, after: function (w) {
              w.querySelectorAll('[data-rv]').forEach(function (b) { b.addEventListener('click', function (e) { e.stopPropagation(); driverDrawer(+b.getAttribute('data-rv'), function () { V['dr-list'].run(root); }); }); });
              w.querySelectorAll('[data-tg]').forEach(function (x) { x.addEventListener('click', function (e) {
                e.stopPropagation();
                var r = d.rows.filter(function (y) { return y.id === +x.getAttribute('data-tg'); })[0];
                toggleFlow(r, null, function () { V['dr-list'].run(root); });
              }); });
            } });
          var box = root.querySelector('[data-slot="t"]') || root.querySelector('#drBox'); n.id = 'drBox'; box.replaceWith(n);
        };
        draw();
        var fl = $('drFill'); if (fl) fl.addEventListener('click', function () { act(fl, '/ad/drivers/fill-names', {}, function () { V['dr-list'].run(root); }); });
        root.querySelectorAll('#drTabs button').forEach(function (b) { b.addEventListener('click', function () { st = S.p.drSt = b.getAttribute('data-s'); root.querySelectorAll('#drTabs button').forEach(function (x) { x.classList.toggle('is-on', x === b); }); draw(); }); });
        var tmr; $('drQ').addEventListener('input', function () { var v = this.value; clearTimeout(tmr); tmr = setTimeout(function () { q = S.p.drQ = v.trim(); draw(); }, 200); });
        K.wireGo(root);
      });
    } };

    /* ------------------------------------------------------------------ Order assignment */
    function assignModal(o, after) {
      openModal((o.rider_id ? 'Change rider · ' : 'Choose rider · ') + o.order_id, '<div class="cc-load"><i></i>Loading riders…</div>');
      api('/ad/drivers/assign/' + encodeURIComponent(o.order_id) + '/riders', {}, false).then(function (r) {
        var b = $('ccMBody');
        if (!r.ok) { b.innerHTML = '<div class="cc-empty">' + esc(r.message) + '</div>'; return; }
        var list = r.riders;
        var head = '<p class="cc-note">Delivery boys of <b>' + esc(r.hub) + '</b>. ' + (r.stage === 'out' ? 'The order is out for delivery; the new rider takes it over (same as Change rider in the POS).' : 'The order stays with the hub to accept, weigh and dispatch in the POS; the rider you choose is shown on the order.') + '</p>' +
          (o.rider_id ? '<p class="cc-note">Now with <b>' + esc(o.rider) + '</b>.</p>' : '');
        if (!list.length) { b.innerHTML = head + '<div class="cc-empty">This hub has no active delivery boy. Add one in Delivery boy > Add delivery boy, or approve a rider app driver of this hub.</div>'; return; }
        b.innerHTML = head + '<input class="cc-in" id="asQ" type="search" placeholder="Find a rider" style="margin-bottom:8px"><div class="dr-riders">' + list.map(function (x) {
          var tags = [pill(x.app, x.tone)];
          if (x.app_duty) tags.push(x.app_duty === 'on' ? pill('On duty in app', 'p-info') : pill('Off duty in app'));
          if (x.hub_off) tags.push(pill('Off duty at hub', 'p-warn'));
          if (x.out) tags.push(pill(x.out + ' out now', 'p-warn'));
          if (x.pre) tags.push(pill(x.pre + ' waiting', 'p-mute'));
          return '<label class="dr-rider' + (!x.can || x.current ? ' is-off' : '') + '" data-name="' + esc((x.name + ' ' + x.phone + ' ' + x.code).toLowerCase()) + '"><input type="radio" name="asR" value="' + x.id + '"' + (!x.can || x.current ? ' disabled' : '') + '>' +
            '<span><b>' + esc(x.name) + '</b>' + (x.current ? ' ' + pill('current') : '') + '<small>' + esc([x.code, x.phone].filter(Boolean).join(' · ')) + ' · ' + x.today + ' delivered today</small><span class="cc-chips">' + tags.join('') + '</span></span></label>';
        }).join('') + '</div>';
        $('asQ').addEventListener('input', function () { var v = this.value.trim().toLowerCase(); b.querySelectorAll('.dr-rider').forEach(function (l) { l.hidden = v && l.getAttribute('data-name').indexOf(v) < 0; }); });
        var f = $('ccMFoot'), go = document.createElement('button'); go.type = 'button'; go.className = 'cc-btn pri'; go.textContent = o.rider_id ? 'Change rider' : 'Give to rider'; f.insertBefore(go, f.firstChild);
        go.addEventListener('click', function () {
          var c = b.querySelector('input[name=asR]:checked'); if (!c) { toast('Choose a rider.', 'bad'); return; }
          var x = list.filter(function (y) { return y.id === +c.value; })[0];
          var warn = x.hub_off ? ' The hub has marked this rider off duty.' : x.app_duty === 'off' ? ' The rider is off duty in the app.' : '';
          ask({ title: (o.rider_id ? 'Change rider of ' : 'Give ') + o.order_id + (o.rider_id ? ' to ' : ' to ') + x.name + '?', text: (o.rider_id ? 'It is taken from ' + o.rider + '. ' : '') + 'The hub sees the change in its POS.' + warn, ok: 'Confirm', tone: warn ? 'warn' : 'info' }).then(function (y) {
            if (!y) return;
            act(go, '/ad/drivers/assign', { order_id: o.order_id, rider_id: x.id, expected_rider_id: o.rider_id || 0 }).then(function (res) { if (res.ok || res.stale) { closeModal(); if (after) after(); } });
          });
        });
      });
    }
    V['dr-assign'] = { t: 'Order assignment', dates: false, auto: 30, run: function (root) {
      var fl = S.p.asF || 'unassigned', q = S.p.asQ || '';
      return api('/ad/drivers/assign', { filter: fl, q: q }, false).then(function (d) {
        if (!d.ok) { root.innerHTML = card('Order assignment', '<div class="cc-empty">' + esc(d.message) + '</div>'); return; }
        var c = d.counts, now = Date.now() / 1000;
        var tabs = [['unassigned', 'No rider yet', c.unassigned], ['assigned', 'Rider chosen', c.assigned], ['out', 'Out for delivery', c.out], ['all', 'All', c.all]];
        root.innerHTML = '<div class="cc-grid"><div class="cc-kpis">' + kpi('No rider yet', num(c.unassigned, 0), 'new or accepted orders', c.unassigned ? 'warn' : 'k-green') + kpi('Rider chosen', num(c.assigned, 0), 'waiting for the hub to dispatch', 'k-blue') + kpi('Out for delivery', num(c.out, 0), 'rider can be changed', 'k-teal') + '</div>' +
          card('Orders', '<div class="ad-tools"><div class="cc-tabs" id="asTabs">' + tabs.map(function (t) { return '<button type="button" data-s="' + t[0] + '"' + (t[0] === fl ? ' class="is-on"' : '') + '>' + t[1] + '<small>' + t[2] + '</small></button>'; }).join('') + '</div><span style="flex:1"></span>' +
            '<input class="cc-in" id="asQ2" type="search" placeholder="Order no., customer, phone, rider" value="' + esc(q) + '" style="max-width:260px"></div>' + slot('t'), { flush: true }) +
          '<p class="cc-note ad-help">New and accepted orders can get a rider; orders out for delivery can change rider. Delivered orders waiting for cash, delivered and cancelled orders are locked. The rider must be a delivery boy of the order\'s hub. Use the hub filter above to see one hub. Refreshes every 30 seconds.</p></div>';
        mount(root, 't', T([{ k: 'order_id', t: 'Order', f: function (o) { return '<b>' + esc(o.order_id) + '</b><small>' + plural(o.items, 'item') + ' · ' + money(o.amount) + ' · ' + esc(o.pay || '–') + '</small>'; } },
          { k: 'customer', t: 'Customer', f: function (o) { return esc(o.customer || '–') + '<small>' + esc(o.phone) + '</small>'; } },
          { k: 'address', t: 'Deliver to', f: function (o) { return '<span class="dr-addr">' + esc(o.address || '–') + '</span><small>' + esc(o.hub) + '</small>'; } },
          { k: 'due', t: 'Due', n: 1, f: function (o) { var late = o.due && o.due < now; return (o.due ? (late ? '<b style="color:var(--bad)">' + dt(o.due) + '</b>' : dt(o.due)) : '–') + '<small>' + esc(o.type) + (o.slot && o.type !== 'Express' ? ' · ' + esc(o.slot) : '') + '</small>'; }, c: function (o) { return dt(o.due); } },
          { k: 'stage', t: 'Status', f: function (o) { return stagePill(o.stage); } },
          { k: 'rider', t: 'Rider', f: function (o) { return o.rider_id ? '<b>' + esc(o.rider) + '</b><small>' + esc(o.rider_phone) + (o.rider_app ? ' · app' : '') + '</small>' : pill('No rider', 'p-warn'); } },
          { k: '_a', t: '', nosort: 1, csv: false, f: function (o) { return '<div class="ad-acts"><button type="button" class="cc-btn' + (o.rider_id ? '' : ' pri') + '" data-as="' + esc(o.order_id) + '">' + (o.rider_id ? 'Change rider' : 'Choose rider') + '</button></div>'; } }],
          d.rows, { per: 50, csv: 'freshful-order-assignment.csv', empty: q ? 'No order matches.' : fl === 'unassigned' ? 'Every open order has a rider.' : 'No orders here.', click: function (o) { K.orderDrawer(o.order_id); }, after: function (w) {
            w.querySelectorAll('[data-as]').forEach(function (b) { b.addEventListener('click', function (e) { e.stopPropagation(); var o = d.rows.filter(function (x) { return x.order_id === b.getAttribute('data-as'); })[0]; assignModal(o, function () { V['dr-assign'].run(root); }); }); });
          } }));
        root.querySelectorAll('#asTabs button').forEach(function (b) { b.addEventListener('click', function () { S.p.asF = b.getAttribute('data-s'); V['dr-assign'].run(root); }); });
        var tmr; $('asQ2').addEventListener('input', function () { var v = this.value; clearTimeout(tmr); tmr = setTimeout(function () { S.p.asQ = v.trim(); V['dr-assign'].run(root).then(function () { var i = $('asQ2'); if (i) { i.focus(); i.setSelectionRange(i.value.length, i.value.length); } }); }, 350); });
      });
    } };

    /* ------------------------------------------------------------------ Driver wallets */
    V['dr-wallet'] = { t: 'Driver wallets', dates: false, nofilter: true, run: function (root) {
      return api('/ad/drivers/wallets', {}, false).then(function (d) {
        if (!d.ok) { root.innerHTML = card('Driver wallets', '<div class="cc-empty">' + esc(d.message) + '</div>'); return; }
        var t = d.total;
        root.innerHTML = '<div class="cc-grid"><div class="cc-kpis">' + kpi('Owed to drivers', money2(t.balance), plural(t.wallets, 'wallet'), 'k-green') + kpi('Withdrawal requests', money2(t.pending), 'waiting', t.pending ? 'warn' : 'k-slate') +
          kpi('Approved, no wallet', num(t.missing, 0), t.missing ? 'the app shows "Wallet not found"' : 'all fine', t.missing ? 'bad' : 'k-slate') + '</div>' +
          (t.missing ? '<div class="dr-box warn"><b>' + plural(t.missing, 'approved driver') + ' without a wallet</b><p>They were approved before wallets were made automatically, so the rider app shows "Wallet not found". Make the wallets now (balance ₹0).</p><button type="button" class="cc-btn pri" id="dwMk">Make missing wallets</button></div>' : '') +
          card('Wallets', slot('t'), { flush: true }) +
          '<p class="cc-note ad-help">Credits and debits are saved with the same locking as the rider app wallet, and every entry is in the change log with your name. Withdrawal requests are shown here; paying them out comes with the withdraw screen of the rider app.</p></div>';
        mount(root, 't', T([{ k: 'name', t: 'Driver', f: function (r) { return '<b>' + esc(r.name) + '</b><small>#' + r.id + ' · ' + esc(r.phone) + '</small>'; } },
          { k: 'state', t: 'Account', f: function (r) { return statePill(r.state) + (r.suspended ? ' ' + pill('Deactivated', 'p-bad') : ''); }, c: function (r) { return r.state; } },
          { k: 'balance', t: 'Balance', n: 1, f: function (r) { return r.wallet ? '<b>' + money2(r.balance) + '</b>' + (r.status === 'blocked' ? ' ' + pill('Blocked', 'p-bad') : '') : pill('No wallet', 'p-bad'); }, c: function (r) { return r.balance; } },
          { k: 'week', t: 'Earned this week', n: 1, f: function (r) { return money2(r.week); } },
          { k: 'pending', t: 'Withdrawal asked', n: 1, f: function (r) { return r.pending ? '<b style="color:var(--warn)">' + money2(r.pending) + '</b><small>' + plural(r.pending_n, 'request') + '</small>' : '–'; } },
          { k: 'last', t: 'Last entry', n: 1, f: function (r) { return r.last ? dt(r.last) + '<small>' + plural(r.txns, 'entry') + '</small>' : '–'; }, c: function (r) { return dt(r.last); } },
          { k: '_a', t: '', nosort: 1, csv: false, f: function (r) { return r.wallet ? '<div class="ad-acts"><button type="button" class="cc-btn" data-mv="' + r.id + '">+ / − Entry</button></div>' : ''; } }],
          d.rows, { per: 50, csv: 'freshful-driver-wallets.csv', empty: 'No driver wallets yet.', click: function (r) { driverDrawer(r.id, function () { V['dr-wallet'].run(root); }, '.dr-wal'); }, after: function (w) {
            w.querySelectorAll('[data-mv]').forEach(function (b) { b.addEventListener('click', function (e) { e.stopPropagation(); var r = d.rows.filter(function (x) { return x.id === +b.getAttribute('data-mv'); })[0]; walletModal(r.id, r.name, d.moves, function () { V['dr-wallet'].run(root); }); }); });
          } }));
        var mk = $('dwMk'); if (mk) mk.addEventListener('click', function () { ask({ title: 'Make ' + plural(t.missing, 'wallet') + '?', text: 'Each approved driver without a wallet gets one with ₹0, the same as approval does now.', ok: 'Make wallets' }).then(function (y) { if (y) act(mk, '/ad/drivers/wallets/create-missing', {}, function () { V['dr-wallet'].run(root); }); }); });
      });
    } };
  };
})();

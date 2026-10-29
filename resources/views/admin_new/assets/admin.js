/* Freshful new admin console - sidebar behaviour (no libraries) */
(function () {
  'use strict';
  var root = document.documentElement;
  var side = document.getElementById('ffSide');
  if (!side) return;
  var find = document.getElementById('ffFind');
  var noRes = document.getElementById('ffNoRes');
  var groups = Array.prototype.slice.call(side.querySelectorAll('.ff-grp'));
  var DESK = window.matchMedia('(min-width: 1025px)');

  function store(k, v) { try { localStorage.setItem(k, v); } catch (e) {} }
  function read(k) { try { return localStorage.getItem(k); } catch (e) { return null; } }

  /* ---- Show / hide the menu: icon rail on desktop, slide-in panel on tablet and phone ---- */
  function setRail(on) { root.classList.toggle('rail', on); store('ff-rail', on ? '1' : '0'); }
  function setOpen(on) { root.classList.toggle('side-open', on); }
  document.getElementById('ffMenuBtn').addEventListener('click', function () {
    if (DESK.matches) setRail(!root.classList.contains('rail'));
    else setOpen(!root.classList.contains('side-open'));
  });
  document.getElementById('ffScrim').addEventListener('click', function () { setOpen(false); });
  document.getElementById('ffSideClose').addEventListener('click', function () { setOpen(false); });

  /* ---- Light / dark ---- */
  document.getElementById('ffThemeBtn').addEventListener('click', function () {
    var cur = root.getAttribute('data-theme');
    if (!cur) cur = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    var next = cur === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    store('ff-theme', next);
  });

  /* ---- Groups open / closed (remembered); the active group and Overview open by default ---- */
  var saved = {};
  try { saved = JSON.parse(read('ff-groups') || '{}') || {}; } catch (e) { saved = {}; }
  function setGroup(g, open, remember) {
    g.classList.toggle('is-closed', !open);
    g.querySelector('.ff-grp-h').setAttribute('aria-expanded', open ? 'true' : 'false');
    if (remember) { saved[g.dataset.group] = open; store('ff-groups', JSON.stringify(saved)); }
  }
  groups.forEach(function (g) {
    var key = g.dataset.group;
    var open = g.classList.contains('is-active') || (key in saved ? !!saved[key] : key === 'overview');
    setGroup(g, open, false);
    g.querySelector('.ff-grp-h').addEventListener('click', function () {
      if (find.value) return;
      setGroup(g, g.classList.contains('is-closed'), true);
    });
  });

  /* ---- Entries with a sub-list (Staff) ---- */
  side.querySelectorAll('.ff-parent').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (DESK.matches && root.classList.contains('rail')) setRail(false);
      btn.setAttribute('aria-expanded', btn.getAttribute('aria-expanded') === 'true' ? 'false' : 'true');
    });
  });

  /* ---- Find a page ---- */
  function filter(q) {
    q = (q || '').trim().toLowerCase();
    var words = q ? q.split(/\s+/) : [];
    var any = false;
    function hit(label) { return words.every(function (w) { return label.indexOf(w) !== -1; }); }
    groups.forEach(function (g) {
      var gHit = false;
      g.querySelectorAll(':scope > .ff-list > .ff-li').forEach(function (li) {
        var show = !q || hit(li.dataset.label);
        var parent = li.querySelector('.ff-parent');
        if (parent) {
          var kidHit = false;
          li.querySelectorAll('.ff-sub > .ff-li').forEach(function (k) {
            var s = !q || show || hit(k.dataset.label);
            k.hidden = !s; if (q && s) kidHit = true;
          });
          show = show || kidHit;
          parent.setAttribute('aria-expanded', q && show ? 'true' : 'false');
        }
        li.hidden = !show;
        if (show) gHit = true;
      });
      g.hidden = !gHit;
      if (q) g.classList.toggle('is-closed', false);
      if (gHit) any = true;
    });
    if (!q) groups.forEach(function (g) {
      var key = g.dataset.group;
      setGroup(g, g.classList.contains('is-active') || (key in saved ? !!saved[key] : key === 'overview'), false);
    });
    noRes.hidden = any;
  }
  find.addEventListener('input', function () { filter(find.value); });
  find.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
      var first = side.querySelector('.ff-li:not([hidden]) a.ff-item, .ff-li:not([hidden]) a.ff-subitem');
      var visible = first && first.offsetParent !== null ? first : null;
      if (visible) { e.preventDefault(); window.location.href = visible.href; }
    } else if (e.key === 'Escape') {
      find.value = ''; filter(''); find.blur();
      if (!DESK.matches) setOpen(false);
    }
  });
  function focusFind() {
    if (DESK.matches) { if (root.classList.contains('rail')) setRail(false); }
    else setOpen(true);
    setTimeout(function () { find.focus(); find.select(); }, 60);
  }
  document.getElementById('ffSearchBtn').addEventListener('click', focusFind);
  document.addEventListener('keydown', function (e) {
    var t = e.target, typing = t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.tagName === 'SELECT' || t.isContentEditable);
    if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) { e.preventDefault(); focusFind(); }
    else if (e.key === '/' && !typing) { e.preventDefault(); focusFind(); }
    else if (e.key === 'Escape' && root.classList.contains('side-open')) setOpen(false);
  });

  /* ---- Name labels for the icon rail ---- */
  var tip = document.createElement('div');
  tip.className = 'ff-tip';
  tip.setAttribute('role', 'tooltip');
  document.body.appendChild(tip);
  side.addEventListener('mouseover', function (e) {
    var el = e.target.closest ? e.target.closest('[data-tip]') : null;
    if (!el || !root.classList.contains('rail') || !DESK.matches) { tip.classList.remove('is-on'); return; }
    var r = el.getBoundingClientRect();
    tip.textContent = el.getAttribute('data-tip');
    tip.style.top = (r.top + r.height / 2) + 'px';
    tip.classList.add('is-on');
  });
  side.addEventListener('mouseleave', function () { tip.classList.remove('is-on'); });
  side.querySelector('.ff-nav').addEventListener('scroll', function () { tip.classList.remove('is-on'); });

  /* ---- Keep the current page visible in a long menu ---- */
  var on = side.querySelector('.ff-item.is-on');
  if (on && on.scrollIntoView) on.scrollIntoView({ block: 'nearest' });
})();

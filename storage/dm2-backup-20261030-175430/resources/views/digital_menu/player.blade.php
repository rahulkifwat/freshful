<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Freshful menu</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
html,body{width:100%;height:100%;overflow:hidden;background:#14100E;font-family:'Outfit',system-ui,sans-serif}
body.nocursor,body.nocursor *{cursor:none!important}
#stage{position:absolute;left:50%;top:50%;transform-origin:center center;overflow:hidden;color:var(--tx);font-family:var(--font),'Outfit',system-ui,sans-serif;display:flex;flex-direction:column;background:var(--bg1)}
#stage.L{width:1920px;height:1080px}#stage.P{width:1080px;height:1920px}
.bg{position:absolute;inset:0;background:linear-gradient(var(--ang),var(--bg1),var(--bg2))}
.bgi{position:absolute;inset:0;background-size:cover;background-position:center;opacity:var(--bgo,.25)}
.hd,.slides,.more,.tk{position:relative;z-index:2}
.hd{height:104px;display:flex;align-items:center;justify-content:space-between;padding:0 56px;flex:none}
.hd .lg{background:#fff;border-radius:20px;padding:8px 24px;display:flex;align-items:center;height:84px;box-shadow:0 6px 18px rgba(0,0,0,.14)}
.hd .lg img{height:66px;display:block}
.hd .ot{font-size:32px;font-weight:700;letter-spacing:.2px;opacity:.95;margin-left:26px}
.hd .l{display:flex;align-items:center}
.hd .ck{font-size:44px;font-weight:700;font-variant-numeric:tabular-nums;letter-spacing:1px}
.slides{flex:1;min-height:0;position:relative}
.slide{position:absolute;inset:0;padding:6px 56px 14px;opacity:0;transition:opacity .45s ease;pointer-events:none}
.slide.on{opacity:1}.slide.leave{opacity:0}
.card{background:var(--card);color:var(--ct);border-radius:36px;overflow:hidden;position:relative;display:flex;box-shadow:0 18px 44px rgba(20,10,5,.18)}
.card .ct{position:relative;z-index:2;display:flex;flex-direction:column;justify-content:center;gap:12px;padding:30px 34px}
.card .ct>*{flex:none}
.card .im{position:relative;flex:1;min-height:0;min-width:0}
.card .im img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center}
.card .im img.cut{object-fit:contain;object-position:center bottom;padding:10px 14px 0}
.tag{align-self:flex-start;background:linear-gradient(110deg,var(--ac),var(--ac2));color:#fff;border-radius:99px;padding:8px 20px;font-size:24px;font-weight:700;letter-spacing:.3px}
.nm{font-weight:800;line-height:1.05;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden}
.pr{display:flex;align-items:baseline;gap:16px;flex-wrap:wrap}
.pr b{font-weight:800;line-height:1;background:linear-gradient(110deg,var(--ac),var(--ac2));-webkit-background-clip:text;background-clip:text;color:transparent}
.pr s{font-weight:500;opacity:.55}
.pr i{font-style:normal;font-weight:700;color:#fff;background:var(--ac);border-radius:99px;padding:4px 16px;font-size:24px}
.ch{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
.ch span{border:2px solid currentColor;border-radius:99px;padding:5px 18px;font-size:24px;font-weight:600;opacity:.8}
.ch span.few{background:var(--ac);border-color:var(--ac);color:#fff;opacity:1}
.ds{font-size:25px;line-height:1.3;opacity:.72;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden}
/* Showcase */
.L .sc{height:100%;display:flex;flex-direction:row}.L .sc .ct{width:52%;padding:46px 54px;gap:20px}.L .sc .im{flex:1}
.L .sc .nm{font-size:104px}.L .sc .pr b{font-size:176px}.L .sc .pr s{font-size:52px}.L .sc .tag{font-size:30px;padding:10px 26px}.L .sc .ch span{font-size:30px}.L .sc .ds{font-size:32px;-webkit-line-clamp:3}
.P .sc{height:100%;flex-direction:column-reverse}.P .sc .ct{padding:36px 48px;gap:16px}.P .sc .im{flex:none;height:52%}
.P .sc .nm{font-size:92px}.P .sc .pr b{font-size:150px}.P .sc .pr s{font-size:46px}.P .sc .ds{font-size:30px;-webkit-line-clamp:3}
/* Duo and trio */
.grid{height:100%;display:grid;gap:30px}
.L .grid.duo{grid-template-columns:repeat(var(--n),1fr)}.L .grid.trio{grid-template-columns:repeat(var(--n),1fr)}
.L .grid .card{flex-direction:column}.L .grid .card .ct{flex:0 0 43%;min-height:0;overflow:hidden;padding:26px 30px;gap:10px;justify-content:flex-start}.L .grid .card .im{flex:1}
.L .grid.duo .nm{font-size:64px}.L .grid.duo .pr b{font-size:112px}.L .grid.duo .pr s{font-size:36px}
.L .grid.trio .card .ct{padding:20px 28px;gap:8px}.L .grid.trio .nm{font-size:42px}.L .grid.trio .pr b{font-size:74px}.L .grid.trio .pr s{font-size:28px}.L .grid.trio .tag{font-size:20px;padding:5px 15px}.L .grid.trio .ch span,.L .grid.trio .pr i{font-size:20px}.L .grid.trio .ds{display:none}
.P .grid{grid-template-rows:repeat(var(--n),1fr)}.P .grid .card{flex-direction:row}.P .grid .card .ct{flex:0 0 52%;min-height:0;overflow:hidden;padding:26px 32px;gap:10px}.P .grid .card .im{flex:1}
.P .grid.duo .nm{font-size:56px}.P .grid.duo .pr b{font-size:112px}.P .grid.duo .pr s{font-size:34px}.P .grid.trio .nm{font-size:52px}.P .grid.trio .pr b{font-size:92px}.P .grid.trio .pr s{font-size:30px}.P .grid.trio .ds{font-size:22px}
/* Price board */
.board{height:100%;display:flex;flex-direction:column;gap:14px}
.board .bh{flex:none;display:flex;align-items:center;justify-content:space-between;padding:6px 8px}
.board .bh h2{font-size:56px;font-weight:800;letter-spacing:.3px}.board .bh span{font-size:28px;font-weight:700;background:linear-gradient(110deg,var(--ac),var(--ac2));color:#fff;border-radius:99px;padding:8px 24px}
.board .rows{flex:1;min-height:0;display:grid;gap:14px 26px;grid-auto-flow:column;grid-template-rows:repeat(var(--r),minmax(0,1fr));grid-template-columns:repeat(var(--c),minmax(0,1fr))}
.row{background:var(--card);color:var(--ct);border-radius:26px;display:flex;align-items:center;gap:22px;padding:12px 26px 12px 12px;min-height:0;box-shadow:0 8px 22px rgba(20,10,5,.12)}
.row .th{height:100%;aspect-ratio:1/1;border-radius:18px;overflow:hidden;flex:none;background:rgba(127,127,127,.12)}.row .th img{width:100%;height:100%;object-fit:cover}.row .th img.cut{object-fit:contain}
.row{min-width:0}.row .tx{flex:1;min-width:0}.row .tx b{display:block;font-size:40px;font-weight:800;line-height:1.08;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.row .tx small{font-size:25px;opacity:.7;font-weight:600}.row .tx em{font-style:normal;font-size:21px;font-weight:700;color:#fff;background:var(--ac);border-radius:99px;padding:3px 12px;margin-left:10px}
.row .pp{text-align:right;flex:none}.row .pp b{display:block;font-size:60px;font-weight:800;line-height:1;background:linear-gradient(110deg,var(--ac),var(--ac2));-webkit-background-clip:text;background-clip:text;color:transparent}.row .pp s{font-size:26px;opacity:.55}
.P .board .bh h2{font-size:60px}.P .row .tx b{font-size:44px}
/* Offers poster */
.poster{height:100%;display:flex;flex-direction:column;gap:14px}
.poster .bh{flex:none;padding:6px 8px}.poster .bh h2{font-size:64px;font-weight:800}
.poster .tiles{flex:1;min-height:0;display:grid;gap:26px;grid-template-columns:repeat(var(--c),1fr);grid-template-rows:repeat(var(--r),1fr)}
.tile{border-radius:34px;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 14px 34px rgba(20,10,5,.16)}
.tile .ti{flex:1;min-height:0;position:relative}.tile .ti img{position:absolute;inset:0;width:100%;height:100%;object-fit:contain;padding:18px}
.tile .tb{padding:16px 28px 22px;display:flex;align-items:flex-end;justify-content:space-between;gap:12px}.tile .tb b{display:block;font-size:42px;font-weight:800;line-height:1.1}.tile .tb span{display:block;font-size:26px;opacity:.7;font-weight:600;margin-top:4px}.tile .tb strong{font-size:58px;font-weight:800;white-space:nowrap}
/* More today strip */
.more{flex:none;height:168px;padding:6px 56px 14px;display:flex;gap:18px;align-items:stretch}
.more .lab{flex:none;width:210px;border-radius:26px;background:linear-gradient(135deg,var(--ac),var(--ac2));color:#fff;display:flex;align-items:center;justify-content:center;text-align:center;font-size:36px;font-weight:800;line-height:1.1;padding:10px 18px;box-shadow:inset 0 0 0 3px rgba(255,255,255,.55)}
.P .more .lab{width:170px;font-size:30px}.P .more .o b{font-size:24px}.P .more .o strong{font-size:36px}.P .more .o img{height:84%}
.more .o{flex:1;min-width:0;border-radius:26px;display:flex;align-items:center;gap:14px;padding:10px 20px 10px 12px;box-shadow:0 8px 22px rgba(20,10,5,.12)}
.more .o img{height:100%;aspect-ratio:1/1;object-fit:contain;flex:none;border-radius:16px}
.more .o .nn{min-width:0}.more .o b{display:block;font-size:30px;font-weight:800;line-height:1.1;overflow:hidden;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2}.more .o strong{display:block;font-size:44px;font-weight:800;line-height:1.1}.more .o small{font-size:22px;opacity:.7;font-weight:600}
.tk{flex:none;height:66px;background:rgba(0,0,0,.28);color:#fff;display:flex;align-items:center;overflow:hidden;white-space:nowrap;font-size:30px;font-weight:600}
.tk .run{display:inline-block;padding-left:100%;animation:tick var(--td,40s) linear infinite}
@keyframes tick{to{transform:translateX(-100%)}}
.empty{height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:26px;text-align:center}.empty .lg{background:#fff;border-radius:26px;padding:20px 36px}.empty img{height:120px;display:block}.empty p{font-size:44px;font-weight:700;opacity:.85}
#msg{position:fixed;left:0;right:0;bottom:0;z-index:20;display:none;background:#111;color:#fff;font:600 18px/1.3 system-ui,sans-serif;padding:12px 18px;text-align:center}
#go{position:fixed;inset:0;z-index:30;background:rgba(10,6,4,.92);color:#fff;display:none;align-items:center;justify-content:center;flex-direction:column;gap:18px;font:700 34px system-ui,sans-serif;cursor:pointer;text-align:center}
#go small{font-size:18px;font-weight:500;opacity:.7}
/* Motion: staged entrance (cards, title, dish, price, details) */
.mot-rise .slide .card,.mot-rise .slide .row,.mot-rise .slide .tile{opacity:0;transform:translateY(20px)}
.mot-rise .slide .t1{opacity:0;transform:translateY(12px)}.mot-rise .slide .dish{opacity:0;transform:translateY(48px) scale(.88)}.mot-rise .slide .t2{opacity:0;transform:scale(.96)}.mot-rise .slide .t3{opacity:0}
.mot-rise .slide.on .card,.mot-rise .slide.on .row,.mot-rise .slide.on .tile{animation:cin .55s cubic-bezier(.22,.7,.2,1) both;animation-delay:calc(var(--i,0)*120ms)}
.mot-rise .slide.on .t1{animation:r12 .5s ease both;animation-delay:calc(var(--i,0)*120ms + 100ms)}
.mot-rise .slide.on .dish{animation:dsh .72s cubic-bezier(.22,.7,.2,1) both;animation-delay:calc(var(--i,0)*120ms + 180ms)}
.mot-rise .slide.on .t2{animation:prc .45s ease both;animation-delay:calc(var(--i,0)*120ms + 340ms)}
.mot-rise .slide.on .t3{animation:fad .5s ease both;animation-delay:calc(var(--i,0)*120ms + 460ms)}
.mot-fade .slide .card,.mot-fade .slide .row,.mot-fade .slide .tile{opacity:0}.mot-fade .slide.on .card,.mot-fade .slide.on .row,.mot-fade .slide.on .tile{animation:fad .8s ease both;animation-delay:calc(var(--i,0)*120ms)}
.slide.leave .card,.slide.leave .row,.slide.leave .tile,.slide.leave .t1,.slide.leave .t2,.slide.leave .t3,.slide.leave .dish{opacity:1!important;transform:none!important;animation:none!important}
.idle .slide.on.settled .dish{animation:flt 5s ease-in-out infinite alternate!important;opacity:1;transform:none}
@keyframes cin{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:none}}
@keyframes r12{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:none}}
@keyframes dsh{from{opacity:0;transform:translateY(48px) scale(.88)}to{opacity:1;transform:none}}
@keyframes prc{from{opacity:0;transform:scale(.96)}to{opacity:1;transform:none}}
@keyframes fad{from{opacity:0}to{opacity:1}}
@keyframes flt{from{transform:translateY(0)}to{transform:translateY(-3px)}}
</style>
</head>
<body data-key="{{ $key }}" data-preview="{{ $preview ? 1 : 0 }}" data-kiosk="{{ $kiosk ? 1 : 0 }}" data-logo="{{ $logo }}">
<div id="stage" class="L mot-rise"><div class="bg"></div><div class="bgi"></div>
  <header class="hd"><div class="l"><div class="lg"><img id="lg" alt="Freshful"></div><span class="ot" id="ot"></span></div><div class="ck" id="ck"></div></header>
  <main class="slides" id="slides"></main>
  <aside class="more" id="more" style="display:none"></aside>
  <footer class="tk" id="tk" style="display:none"><span class="run" id="run"></span></footer>
</div>
<div id="msg"></div>
<div id="go"><span>Touch to start the menu</span><small>Opens full screen and keeps the display awake</small></div>
<script>
(function () {
  var B = document.body.dataset, KEY = B.key, PREVIEW = B.preview === '1', KIOSK = B.kiosk === '1', LOGO = B.logo;
  var $ = function (id) { return document.getElementById(id); };
  var stage = $('stage'), slidesEl = $('slides'), D = null, ver = '', idx = 0, timer = null, ck = null, slideEls = [], reduce = false;
  try { reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches; } catch (e) {}
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function money(v) { v = +v || 0; return '₹' + (v % 1 === 0 ? v.toLocaleString('en-IN') : v.toFixed(2)); }
  function msg(t) { var m = $('msg'); if (!t) { m.style.display = 'none'; return; } m.textContent = t; m.style.display = 'block'; }
  function fit() {
    var p = stage.className.indexOf('P') > -1 ? [1080, 1920] : [1920, 1080];
    var s = Math.min(innerWidth / p[0], innerHeight / p[1]);
    stage.style.transform = 'translate(-50%,-50%) scale(' + s + ')';
  }
  window.addEventListener('resize', fit);
  function clock() { var d = new Date(); $('ck').textContent = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }); }
  function font(name) {
    var id = 'f-' + name.replace(/\W/g, ''); if (document.getElementById(id)) return;
    var l = document.createElement('link'); l.id = id; l.rel = 'stylesheet'; l.href = 'https://fonts.googleapis.com/css2?family=' + encodeURIComponent(name).replace(/%20/g, '+') + ':wght@400;600;700;800&display=swap'; document.head.appendChild(l);
  }
  function pic(it, cls) { return it.img ? '<img class="' + (cls || '') + (it.cutout ? ' cut' : '') + '" src="' + esc(it.img) + '" alt="" onerror="this.style.visibility=\'hidden\'">' : ''; }
  function tagOf(it) { return it.badge || it.cat || ''; }
  function priceHtml(it, S) {
    return '<div class="pr t2"><b>' + esc(money(it.price)) + '</b>' + (S.show.mrp && it.mrp ? '<s>' + esc(money(it.mrp)) + '</s>' : '') + (S.show.save && it.save ? '<i>Save ' + it.save + '%</i>' : '') + '</div>';
  }
  function chips(it, S) { return '<div class="ch t3">' + (it.pack ? '<span>' + esc(it.pack) + '</span>' : '') + (S.show.stock && it.few ? '<span class="few">Only a few left</span>' : '') + (it.veg === true ? '<span>Veg</span>' : '') + '</div>'; }
  function cardHtml(it, i, S, kind) {
    var t = tagOf(it);
    return '<article class="card" style="--i:' + i + '"><div class="ct">' + (t ? '<div class="tag t1">' + esc(t) + '</div>' : '') + '<h2 class="nm t1">' + esc(it.name) + '</h2>' + priceHtml(it, S) + chips(it, S) + (it.desc ? '<p class="ds t3">' + esc(it.desc) + '</p>' : '') + '</div><div class="im">' + pic(it, 'dish') + '</div></article>';
  }
  function rowHtml(it, i, S) {
    return '<div class="row" style="--i:' + i + '"><div class="th">' + pic(it, '') + '</div><div class="tx"><b>' + esc(it.name) + '</b><small>' + esc(it.pack) + '</small>' + (S.show.stock && it.few ? '<em>Few left</em>' : '') + '</div><div class="pp"><b>' + esc(money(it.price)) + '</b>' + (S.show.mrp && it.mrp ? '<s>' + esc(money(it.mrp)) + '</s>' : '') + '</div></div>';
  }
  function build(d) {
    var S = d.screen, L = S.layout, port = S.orientation === 'portrait', out = [];
    var items = d.items || [];
    if (L === 'offers') {
      var per = port ? 6 : 6, c = port ? 2 : 3, r = port ? 3 : 2, o = d.offers || [];
      for (var a = 0; a < o.length; a += per) {
        var part = o.slice(a, a + per), h = '<div class="poster"><div class="bh"><h2>' + esc(S.offersTitle) + '</h2></div><div class="tiles" style="--c:' + Math.min(c, part.length) + ';--r:' + Math.ceil(part.length / c) + '">';
        part.forEach(function (x, i) { h += '<div class="tile" style="--i:' + i + ';background:' + esc(x.bg) + ';color:' + esc(x.color) + '"><div class="ti">' + (x.img ? '<img class="dish" src="' + esc(x.img) + '" alt="" onerror="this.style.visibility=\'hidden\'">' : '') + '</div><div class="tb"><div><b class="t1">' + esc(x.name) + '</b>' + (x.pack ? '<span class="t3">' + esc(x.pack) + '</span>' : '') + '</div>' + (x.price != null ? '<strong class="t2">' + esc(money(x.price)) + '</strong>' : '') + '</div></div>'; });
        out.push(h + '</div></div>');
      }
    } else if (L === 'board') {
      var n = S.per || 8, cols = port ? 1 : 2;
      for (var b = 0; b < items.length; b += n) {
        var chunk = items.slice(b, b + n), rows = Math.ceil(chunk.length / cols);
        var pg = Math.floor(b / n) + 1, tot = Math.ceil(items.length / n);
        out.push('<div class="board"><div class="bh"><h2>Today’s prices</h2>' + (tot > 1 ? '<span>' + pg + ' / ' + tot + '</span>' : '') + '</div><div class="rows" style="--r:' + rows + ';--c:' + cols + '">' + chunk.map(function (it, i) { return rowHtml(it, i, S); }).join('') + '</div></div>');
      }
    } else {
      var k = L === 'showcase' ? 1 : (L === 'duo' ? 2 : 3);
      for (var q = 0; q < items.length; q += k) {
        var ch = items.slice(q, q + k);
        if (k === 1) { out.push('<div class="card sc" style="--i:0"><div class="ct">' + (tagOf(ch[0]) ? '<div class="tag t1">' + esc(tagOf(ch[0])) + '</div>' : '') + '<h2 class="nm t1">' + esc(ch[0].name) + '</h2>' + priceHtml(ch[0], S) + chips(ch[0], S) + (ch[0].desc ? '<p class="ds t3">' + esc(ch[0].desc) + '</p>' : '') + '</div><div class="im">' + pic(ch[0], 'dish') + '</div></div>'); }
        else { out.push('<div class="grid ' + L + '" style="--n:' + ch.length + '">' + ch.map(function (it, i) { return cardHtml(it, i, S); }).join('') + '</div>'); }
      }
    }
    if (!out.length) out.push('<div class="empty"><div class="lg"><img src="' + esc(LOGO) + '" alt="Freshful"></div><p>Fresh picks are being updated. Please ask our team.</p></div>');
    return out;
  }
  function show(i) {
    if (!slideEls.length) return;
    var prev = slideEls[idx]; idx = (i + slideEls.length) % slideEls.length; var cur = slideEls[idx];
    if (prev && prev !== cur) { prev.classList.remove('on', 'settled'); prev.classList.add('leave'); setTimeout(function () { prev.classList.remove('leave'); }, 520); }
    cur.classList.remove('leave'); void cur.offsetWidth; cur.classList.add('on'); cur.classList.remove('settled');
    setTimeout(function () { cur.classList.add('settled'); }, 1900);
  }
  function apply(d) {
    D = d; var S = d.screen, T = d.theme;
    stage.className = (S.orientation === 'portrait' ? 'P' : 'L') + ' ' + (reduce || S.motion === 'none' ? 'mot-none' : 'mot-' + S.motion) + (S.idle && !reduce ? ' idle' : '');
    var st = stage.style; st.setProperty('--bg1', T.bg1); st.setProperty('--bg2', T.bg2); st.setProperty('--ang', T.angle + 'deg'); st.setProperty('--tx', T.text); st.setProperty('--card', T.card); st.setProperty('--ct', T.cardText);
    st.setProperty('--ac', T.accent); st.setProperty('--ac2', T.accent2); st.setProperty('--font', "'" + T.font + "'"); st.setProperty('--bgo', (T.bgImageOpacity / 100));
    document.documentElement.style.background = T.bg1; document.body.style.background = T.bg1; font(T.font);
    document.querySelector('.bgi').style.backgroundImage = T.bgImage ? 'url(' + T.bgImage + ')' : 'none';
    $('lg').src = LOGO; document.querySelector('.hd .lg').style.display = S.show.logo ? '' : 'none';
    $('ot').textContent = S.show.outlet ? S.outlet : ''; $('ck').style.display = S.show.clock ? '' : 'none';
    var tk = $('tk'); if (S.ticker) { tk.style.display = ''; $('run').textContent = S.ticker; $('run').style.setProperty('--td', Math.max(18, S.ticker.length * 0.55) + 's'); } else tk.style.display = 'none';
    var mo = $('more'); var offers = d.offers || [];
    if (S.layout !== 'offers' && offers.length) {
      mo.style.display = ''; mo.innerHTML = '<div class="lab">' + esc(S.offersTitle) + '</div>' + offers.slice(0, S.orientation === 'portrait' ? 2 : 4).map(function (o) { return '<div class="o" style="background:' + esc(o.bg) + ';color:' + esc(o.color) + '">' + (o.img ? '<img src="' + esc(o.img) + '" alt="" onerror="this.style.display=\'none\'">' : '') + '<div class="nn"><b>' + esc(o.name) + '</b>' + (o.pack ? '<small>' + esc(o.pack) + '</small>' : '') + (o.price != null ? '<strong>' + esc(money(o.price)) + '</strong>' : '') + '</div></div>'; }).join('');
    } else { mo.style.display = 'none'; mo.innerHTML = ''; }
    var html = build(d); slidesEl.innerHTML = html.map(function (h) { return '<section class="slide">' + h + '</section>'; }).join('');
    slideEls = [].slice.call(slidesEl.children); if (idx >= slideEls.length) idx = 0;
    fit(); clearInterval(timer); show(idx);
    if (slideEls.length > 1) timer = setInterval(function () { show(idx + 1); }, Math.max(5, S.seconds) * 1000);
    clock(); clearInterval(ck); ck = setInterval(clock, 20000);
  }
  function note(d) { stage.style.display = 'none'; msg(d.message || 'Menu is not available.'); }
  function load() {
    var h = {}; if (ver && !PREVIEW) h['If-None-Match'] = '"' + ver + '"';
    fetch('/display/' + KEY + '/data' + (PREVIEW ? '?preview=1&_=' + Date.now() : ''), { headers: h, cache: 'no-store', credentials: 'same-origin' }).then(function (r) {
      if (r.status === 304) { msg(''); return null; }
      return r.json().then(function (d) { return { s: r.status, d: d }; });
    }).then(function (x) {
      if (!x) return; var d = x.d;
      if (!d || !d.ok) { if (!D) note(d || {}); else msg(d && d.message ? d.message : ''); return; }
      msg(''); stage.style.display = '';
      if (d.version !== ver) { ver = d.version; try { localStorage.setItem('dm-' + KEY, JSON.stringify(d)); } catch (e) {} apply(d); }
    }).catch(function () {
      if (!D) { try { var c = JSON.parse(localStorage.getItem('dm-' + KEY) || 'null'); if (c && c.ok) { ver = c.version; apply(c); msg('Offline. Showing the last menu.'); return; } } catch (e) {} msg('Waiting for the connection...'); }
      else msg('Offline. Showing the last menu.');
    });
  }
  document.addEventListener('visibilitychange', function () { if (!document.hidden) load(); });
  setInterval(load, 60000);
  var mt = null; document.addEventListener('mousemove', function () { document.body.classList.remove('nocursor'); clearTimeout(mt); mt = setTimeout(function () { document.body.classList.add('nocursor'); }, 2500); });
  function full() { var e = document.documentElement; try { if (!document.fullscreenElement && e.requestFullscreen) e.requestFullscreen(); } catch (x) {} try { if (navigator.wakeLock) navigator.wakeLock.request('screen').catch(function () {}); } catch (x) {} }
  document.addEventListener('keydown', function (e) { if (e.key === 'f' || e.key === 'F') { if (document.fullscreenElement) document.exitFullscreen(); else full(); } });
  document.addEventListener('dblclick', function () { if (document.fullscreenElement) document.exitFullscreen(); else full(); });
  if (KIOSK) { var g = $('go'); g.style.display = 'flex'; g.onclick = function () { g.style.display = 'none'; full(); }; }
  fit(); load();
  window.__dm = { apply: apply, show: show, build: build };
})();
</script>
</body>
</html>

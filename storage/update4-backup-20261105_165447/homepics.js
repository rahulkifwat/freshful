  /* ------------------------------------------------------------------ Home pictures (5 Nov 2026)
     Ribbon banner and picture tiles (Shop by store, Shop by cuisine, recipes) with a picture upload for each tile. */
  V['ad-homepics'] = { t: 'Home pictures and tiles', dates: false, nofilter: true, run: function (root) {
    return api('/ad/apppics', {}, false).then(function (d) {
      var blocks = d.blocks.slice(), gone = [], tagOpts = (d.tag_options || []).slice();
      blocks.forEach(function (b) { if (b.after && tagOpts.map(function (x) { return x.toLowerCase(); }).indexOf(b.after.toLowerCase()) < 0) tagOpts.unshift(b.after); });
      var opt = function (v, t, sel) { return '<option value="' + esc(v) + '"' + (String(sel || '') === String(v) ? ' selected' : '') + '>' + esc(t) + '</option>'; };
      var SHAPES = { store: 'Shop by store (wide tiles)', cuisine: 'Shop by cuisine (medium tiles)', recipe: 'Recipes (tall tiles)' };
      var LINKS = ['categories', 'coupons', 'cart', 'category:12', 'section:bestsellers'];
      var HEX = /^#[0-9a-fA-F]{6}$/;
      var inp = 'style="width:100%;padding:7px 9px;border:1px solid #0003;border-radius:8px;font:inherit"';

      function itemRow(b, bi, it, ii) {
        var thumb = it.image ? '<img src="' + esc(it.image) + '" alt="" style="width:84px;height:56px;object-fit:cover;border-radius:8px;border:1px solid #0002">' : '<div style="width:84px;height:56px;border-radius:8px;border:1px dashed #0004;display:flex;align-items:center;justify-content:center;font-size:11px;color:#888">No picture</div>';
        return '<div data-b="' + bi + '" data-i="' + ii + '" style="display:grid;grid-template-columns:90px 1fr;gap:10px;align-items:start;padding:10px 0;border-top:1px solid #0001">' +
          '<div>' + thumb + '<label class="cc-btn" style="display:block;text-align:center;margin-top:6px;cursor:pointer;font-size:12px">Upload<input type="file" accept="image/jpeg,image/png,image/webp" data-up style="display:none"></label></div>' +
          '<div style="display:grid;gap:6px">' +
          (b.type === 'ribbon' ? '' : '<input ' + inp + ' data-f="title" placeholder="Name of the tile (shown if there is no picture)" value="' + esc(it.title) + '">') +
          '<input ' + inp + ' data-f="image" placeholder="Picture link (filled when you upload)" value="' + esc(it.image) + '">' +
          '<input ' + inp + ' data-f="link" list="hpLinks" placeholder="Opens: categories, coupons, cart, category:12, section:bestsellers" value="' + esc(it.link) + '">' +
          (b.type === 'ribbon' ? '' : '<div style="text-align:right"><button type="button" class="cc-btn" data-rm="1">Remove this tile</button></div>') +
          '</div></div>';
      }
      function blockHtml(b, bi) {
        var ribbon = b.type === 'ribbon';
        return '<div data-bb="' + bi + '" style="border:1px solid #0002;border-radius:12px;padding:14px;margin-bottom:14px;background:#fff' + (b.on ? '' : ';opacity:.65') + '">' +
          '<div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:8px"><b>' + (ribbon ? 'Ribbon banner' : esc(SHAPES[b.shape] || 'Picture tiles')) + '</b>' +
          '<label class="ad-check" style="margin-left:auto"><input type="checkbox" data-bon' + (b.on ? ' checked' : '') + '> Show in the app</label>' +
          '<button type="button" class="cc-btn" data-bdel="1">Delete block</button></div>' +
          '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:6px"><input ' + inp + ' data-bf="title" placeholder="' + (ribbon ? 'Heading (used if there is no picture)' : 'Block heading, for example Shop by store') + '" value="' + esc(b.title) + '">' +
          '<input ' + inp + ' data-bf="subtitle" placeholder="Second line (optional)" value="' + esc(b.subtitle) + '"></div>' +
          '<label class="cc-f" style="margin:6px 0"><span>Show this block</span><select class="cc-sel" data-bf="after">' + opt('', 'In the normal place (arrange in App control)', b.after) + tagOpts.map(function (n) { return opt(n, 'Right after the row: ' + n, b.after); }).join('') + '</select></label>' +
          (ribbon ? '<small class="muted">Best picture size: 1200 x 360 pixels (wide).</small>' : '<small class="muted">Best picture size: ' + (b.shape === 'recipe' ? '600 x 760' : b.shape === 'cuisine' ? '680 x 530' : '840 x 512') + ' pixels. The picture can carry its own words.</small>') +
          b.items.map(function (it, ii) { return itemRow(b, bi, it, ii); }).join('') +
          (ribbon ? '' : '<div style="padding-top:8px"><button type="button" class="cc-btn" data-add="1">+ Add a tile</button></div>') + '</div>';
      }
      function draw() {
        $('hpList').innerHTML = blocks.length ? blocks.map(blockHtml).join('') : '<p class="cc-note">No picture blocks yet. Use the buttons above to add one.</p>';
      }
      function newBlock(type, shape, title) { blocks.push({ id: 0, type: type, shape: shape, title: title, subtitle: '', after: '', on: true, items: type === 'ribbon' ? [{ title: '', image: '', link: '', color: '' }] : [{ title: '', image: '', link: '', color: '' }, { title: '', image: '', link: '', color: '' }] }); draw(); }

      root.innerHTML = '<div style="max-width:880px"><p class="cc-note">Add the ribbon banner and the picture tiles you see on big food apps. Upload a picture for each tile, choose where it opens, then press Save. After saving, open <b>App control</b> to drag the block to the place you want on the home page.</p>' +
        '<datalist id="hpLinks">' + LINKS.map(function (l) { return '<option value="' + esc(l) + '">'; }).join('') + '</datalist>' +
        '<div style="display:flex;gap:8px;flex-wrap:wrap;margin:10px 0 14px"><button type="button" class="cc-btn pri" data-new="ribbon">+ Ribbon banner</button><button type="button" class="cc-btn" data-new="store">+ Shop by store</button><button type="button" class="cc-btn" data-new="cuisine">+ Shop by cuisine</button><button type="button" class="cc-btn" data-new="recipe">+ Recipe tiles</button></div>' +
        '<div id="hpList"></div><div style="position:sticky;bottom:0;background:var(--surface,#fff);padding:10px 0;border-top:1px solid #0002"><button type="button" class="cc-btn pri" id="hpSave">Save</button></div></div>';
      draw();
      var box = root.firstElementChild;

      box.addEventListener('click', function (e) {
        var t = e.target.closest ? e.target.closest('[data-new],[data-add],[data-rm],[data-bdel]') : null; if (!t) return;
        if (t.hasAttribute('data-new')) { var k = t.getAttribute('data-new'); newBlock(k === 'ribbon' ? 'ribbon' : 'tiles', k === 'ribbon' ? '' : k, k === 'store' ? 'Shop by store' : k === 'cuisine' ? 'Shop by cuisine' : k === 'recipe' ? 'Crafted for your recipes' : ''); return; }
        var bb = t.closest('[data-bb]'); if (!bb) return; var bi = +bb.getAttribute('data-bb'), b = blocks[bi];
        if (t.hasAttribute('data-add')) { if (b.items.length >= 12) { toast('Use at most 12 tiles.', 'bad'); return; } b.items.push({ title: '', image: '', link: '', color: '' }); draw(); }
        else if (t.hasAttribute('data-rm')) { var r = t.closest('[data-i]'); b.items.splice(+r.getAttribute('data-i'), 1); draw(); }
        else if (t.hasAttribute('data-bdel')) { if (b.id) gone.push(b.id); blocks.splice(bi, 1); draw(); }
      });
      box.addEventListener('input', function (e) {
        var el = e.target, bb = el.closest ? el.closest('[data-bb]') : null; if (!bb) return; var b = blocks[+bb.getAttribute('data-bb')];
        if (el.hasAttribute('data-bf')) { b[el.getAttribute('data-bf')] = el.value; return; }
        var r = el.closest('[data-i]'); if (r && el.hasAttribute('data-f')) { b.items[+r.getAttribute('data-i')][el.getAttribute('data-f')] = el.value; }
      });
      box.addEventListener('change', function (e) {
        var el = e.target, bb = el.closest ? el.closest('[data-bb]') : null; if (!bb) return; var b = blocks[+bb.getAttribute('data-bb')];
        if (el.hasAttribute('data-bon')) { b.on = el.checked; draw(); return; }
        if (!el.hasAttribute('data-up') || !el.files || !el.files[0]) return;
        var it = b.items[+el.closest('[data-i]').getAttribute('data-i')], fd = new FormData(); fd.append('image', el.files[0]);
        var tok = document.querySelector('meta[name="csrf-token"]');
        toast('Uploading the picture...');
        fetch(location.origin + '/admin/console/api/ad/apppics/image', { method: 'POST', body: fd, credentials: 'same-origin', headers: Object.assign({ Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, tok ? { 'X-CSRF-TOKEN': tok.getAttribute('content') } : {}) })
          .then(function (r) { return r.json(); })
          .then(function (j) { if (j && j.ok && j.url) { it.image = j.url; draw(); toast('Picture added. Press Save to keep it.'); } else { toast((j && j.message) || 'The upload did not work.', 'bad'); } })
          .catch(function () { toast('The upload did not work. Check your internet and try again.', 'bad'); });
      });
      $('hpSave').addEventListener('click', function () {
        for (var i = 0; i < blocks.length; i++) { for (var j = 0; j < blocks[i].items.length; j++) { var c = blocks[i].items[j].color; if (c && !HEX.test(c)) { toast('A colour must look like #FFD9DE.', 'bad'); return; } } }
        act($('hpSave'), '/ad/apppics/save', { blocks: blocks, delete: gone }, function () { gone = []; V['ad-homepics'].run(root); });
      });
    });
  } };


  /* ------------------------------------------------------------------ App control (update 2, 5 Nov 2026)
     One page: drag-to-reorder home blocks, top section colour with live preview, product card style, frame colour per tag. */
  V['ad-appcontrol'] = { t: 'App control', dates: false, nofilter: true, run: function (root) {
    return api('/ad/appcontrol', {}, false).then(function (d) {
      var TYPES = { banners: 'Hero banners', categories: 'Shop by category', products: 'Product row', rewards: 'Rewards strip', strip: 'Strip banner', coupons: 'Coupons', cuts: 'Shop by cut tiles', reviews: 'Customer reviews' };
      var HEX = /^#[0-9a-fA-F]{6}$/;
      var PAIRS = [['#F70202', '#FF5500'], ['#1F2937', '#1F2937'], ['#F4A6B7', '#FDE68A'], ['#FFB300', '#FF5500'], ['#7F1D1D', '#B91C1C'], ['#FFFFFF', '#FFFFFF']];
      var TAGPAIRS = [['#FFF3EC', '#FFD6C2'], ['#EEF6FF', '#CFE3F8'], ['#F4EFFF', '#DCD0F7'], ['#ECFDF3', '#BFEBD2'], ['#FFF8E1', '#FFE49A'], ['#FDECF1', '#F8C4D3']];
      var COLOURS = [['Peach','#FFF3EC','#FFD6C2'],['Sky blue','#EEF6FF','#CFE3F8'],['Lavender','#F4EFFF','#DCD0F7'],['Mint green','#ECFDF3','#BFEBD2'],['Sunshine yellow','#FFF8E1','#FFE49A'],['Rose pink','#FDECF1','#F8C4D3'],['Sand','#FAF3E6','#EBDDBF'],['Aqua','#E8FAFA','#BDEBEB'],['Coral','#FFEDEA','#FFC9C0'],['Lilac pink','#FBEFFA','#EFC9EC'],['Light grey','#F3F4F6','#D9DCE1'],['White','#FFFFFF','#EDEDED']], tagOpts = d.tag_options || [];
      var blocks = d.blocks.slice(), th = d.theme, cs = d.card, tags = d.tags.slice(), dragFrom = -1;
      var opt = function (v, t, sel) { return '<option value="' + esc(v) + '"' + (String(sel) === String(v) ? ' selected' : '') + '>' + esc(t) + '</option>'; };
      var row = function (label, ctl, hint) { return '<label class="cc-f" style="margin:0 0 10px"><span>' + esc(label) + '</span>' + ctl + (hint ? '<small class="muted">' + esc(hint) + '</small>' : '') + '</label>'; };
      var sel = function (name, opts, val) { return '<select class="cc-sel" name="' + name + '">' + opts.map(function (o) { return opt(o[0], o[1], val); }).join('') + '</select>'; };
      var flag = function (name, label, val) { return '<label class="ad-check" style="display:block;margin:0 0 8px"><input type="checkbox" name="' + name + '"' + (+val ? ' checked' : '') + '> ' + esc(label) + '</label>'; };
      var grad = function (a, b) { return 'linear-gradient(135deg,' + (HEX.test(a) ? a : '#ccc') + ',' + (HEX.test(b) ? b : (HEX.test(a) ? a : '#ccc')) + ')'; };

      function blocksHtml() {
        if (!blocks.length) return '<p class="cc-note">There are no blocks yet, so the app shows its normal home page. Create the standard blocks to start arranging them.</p><div><button type="button" class="cc-btn pri" id="acSeed">Create the standard blocks</button></div>';
        return blocks.map(function (b, i) {
          var name = esc(TYPES[b.type] || b.type), sub = [b.title, b.key ? 'row: ' + b.key : ''].filter(Boolean).map(esc).join(' · ');
          return '<div data-i="' + i + '" draggable="true" style="display:flex;align-items:center;gap:10px;padding:9px 10px;border:1px solid #0002;border-radius:10px;margin-bottom:6px;background:#fff' + (b.on ? '' : ';opacity:.6') + '">' +
            '<span style="cursor:grab;color:#888;font-size:18px;letter-spacing:-4px;user-select:none" title="Drag to reorder" aria-hidden="true">&#8942;&#8942;</span>' +
            '<span style="flex:1;min-width:0"><b>' + name + '</b>' + (sub ? '<small class="muted" style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + sub + '</small>' : '') + '</span>' +
            '<button type="button" class="cc-btn" data-up="' + i + '" title="Move up"' + (i === 0 ? ' disabled' : '') + '>&uarr;</button>' +
            '<button type="button" class="cc-btn" data-down="' + i + '" title="Move down"' + (i === blocks.length - 1 ? ' disabled' : '') + '>&darr;</button>' +
            '<label class="ad-check" style="margin:0"><input type="checkbox" data-on="' + i + '"' + (b.on ? ' checked' : '') + '> On</label></div>';
        }).join('') + '<p class="muted" style="margin:8px 0 0"><a href="#ad-apphome">Edit block texts and links, or add a new block</a></p>';
      }
      function drawBlocks() { var c = $('acBlocks'); c.innerHTML = blocksHtml(); var s = $('acSeed'); if (s) s.addEventListener('click', function () { act(s, '/ad/appcontrol/seed', {}, function () { V['ad-appcontrol'].run(root); }); }); }

      function colourIdx(t) { for (var k = 0; k < COLOURS.length; k++) { if (COLOURS[k][1].toLowerCase() === String(t.from).toLowerCase() && COLOURS[k][2].toLowerCase() === String(t.to).toLowerCase()) return k; } return -1; }
      function tagRowHtml(t, i) {
        var names = tagOpts.slice(); if (t.tag && names.map(function (x) { return x.toLowerCase(); }).indexOf(t.tag.toLowerCase()) < 0) names.unshift(t.tag);
        var ci = colourIdx(t), custom = ci < 0 && t.from;
        return '<div data-trow="' + i + '" style="display:flex;gap:8px;align-items:center;margin-bottom:8px;flex-wrap:wrap"><i data-tsw style="width:34px;height:34px;border-radius:9px;border:1px solid #0003;flex:none;background:' + grad(t.from, t.to) + '"></i>' +
          '<select class="cc-sel" data-tag style="flex:1;min-width:150px"><option value="">Choose a tag...</option>' + names.map(function (n) { return opt(n, n, t.tag); }).join('') + '</select>' +
          '<select class="cc-sel" data-tcol style="width:170px"><option value="">Choose a colour...</option>' + COLOURS.map(function (c, k) { return opt(k, c[0], ci); }).join('') + (custom ? opt('keep', 'Custom (kept)', 'keep') : '') + '</select>' +
          '<input type="hidden" data-tfrom value="' + esc(t.from) + '"><input type="hidden" data-tto value="' + esc(t.to) + '">' +
          '<button type="button" class="cc-btn" data-tdel="' + i + '">Remove</button></div>';
      }
      function readTags() {
        tags = [].slice.call(root.querySelectorAll('[data-trow]')).map(function (r) { return { tag: r.querySelector('[data-tag]').value.trim(), from: r.querySelector('[data-tfrom]').value.trim(), to: r.querySelector('[data-tto]').value.trim() }; });
      }
      function drawTags() { $('acTags').innerHTML = tags.map(tagRowHtml).join('') || '<p class="cc-note">No tag colours yet. Cards use the standard peach frame.</p>'; }

      function prev() {
        var f = $('acFrom').value.trim(), t = $('acTo').value.trim(), p = $('acPrev'), dark = $('acText').value === 'dark';
        p.style.background = grad(f, t); p.style.color = dark ? '#1B1B1F' : '#fff';
        p.querySelector('[data-pin]').style.background = dark ? 'rgba(0,0,0,.08)' : 'rgba(255,255,255,.95)';
      }

      var c = cs;
      root.innerHTML = '<div class="cc-grid"><p class="cc-note">Everything here controls the customer app. Change what you want, press <b>Save all changes</b> at the bottom, and the app shows it within a minute. No new app build is needed.</p>' +
        '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(420px,1fr));gap:14px;align-items:start">' +
        card('Home blocks (drag to reorder)', '<div id="acBlocks"></div>') +
        card('Top section colour', '<div style="margin-bottom:10px">' + PAIRS.map(function (p, i) { return '<button type="button" data-pair="' + i + '" title="' + p[0] + ' to ' + p[1] + '" style="width:30px;height:30px;border-radius:50%;border:1px solid #0003;background:' + grad(p[0], p[1]) + ';cursor:pointer;margin:0 6px 6px 0;padding:0"></button>'; }).join('') + '</div>' +
          '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">' + row('Start colour', '<input class="cc-in" id="acFrom" maxlength="7" value="' + esc(th.color_from) + '">') + row('End colour', '<input class="cc-in" id="acTo" maxlength="7" value="' + esc(th.color_to) + '">') +
          row('Text and icons', '<select class="cc-sel" id="acText">' + opt('light', 'Light (on dark colours)', th.text_color) + opt('dark', 'Dark (on light colours)', th.text_color) + '</select>') + '<span></span>' +
          row('Use from (optional)', '<input class="cc-in" type="date" id="acS" value="' + esc(th.starts_on || '') + '">') + row('Use until (optional)', '<input class="cc-in" type="date" id="acE" value="' + esc(th.ends_on || '') + '">') + '</div>' +
          '<small class="muted">Live preview</small><div id="acPrev" style="border-radius:16px;padding:14px;margin-top:6px"><div style="font-size:11px;opacity:.9">Deliver to</div><div style="font-weight:600;margin-bottom:10px">Your location</div><div data-pin style="border-radius:18px;padding:10px 14px;font-size:12px;color:#777">Search "chicken curry cut"</div></div>') +
        card('Product card style', '<div id="acCard" style="display:grid;grid-template-columns:1fr 1fr;gap:0 12px">' +
          row('Photo fit', sel('img_fit', [['cover', 'Fill the frame (crops)'], ['contain', 'Show the whole photo']], c.img_fit)) + row('Photo shape', sel('img_shape', [['1:1', 'Square'], ['4:3', 'Wide 4:3'], ['3:2', 'Wide 3:2']], c.img_shape)) +
          row('Photo background', '<input class="cc-in" name="img_bg" maxlength="7" value="' + esc(c.img_bg) + '">', 'Shows behind "whole photo"') + row('Photo shadow', sel('photo_shadow', [['none', 'None'], ['soft', 'Soft'], ['deep', 'Deep']], c.photo_shadow)) + row('Card (frame) shadow', sel('card_shadow', [['none', 'None'], ['soft', 'Soft'], ['deep', 'Deep']], c.card_shadow)) + '<span></span>' +
          row('Card corner radius (16-40)', '<input class="cc-in" type="number" min="16" max="40" name="card_radius" value="' + esc(c.card_radius) + '">') + row('Photo corner radius (0-40)', '<input class="cc-in" type="number" min="0" max="40" name="img_radius" value="' + esc(c.img_radius) + '">') +
          row('Product name lines', sel('name_lines', [['1', '1 line'], ['2', '2 lines']], c.name_lines)) + row('Description lines', sel('desc_lines', [['1', '1 line'], ['2', '2 lines']], c.desc_lines)) +
          row('When a product has no photo', sel('no_photo', [['category', 'Show the category picture'], ['brand', 'Show the Freshful tile']], c.no_photo)) + '<span></span>' +
          '<div style="grid-column:1/3">' + flag('show_desc', 'Show description', c.show_desc) + flag('show_weight', 'Show weight, pieces and serves under the photo', c.show_weight) + flag('show_ppk', 'Show price per kg under the price', c.show_ppk) + flag('show_chip', 'Show the "30 min" delivery chip', c.show_chip) + flag('show_rating', 'Show rating stars', c.show_rating) + '</div></div>') +
        card('Card frame colour per tag', '<p class="cc-note">A card takes the frame colour of its first tag listed here. Tags not listed use the standard peach frame. Pick the tag and the colour from the lists.</p>' +
          '<div id="acTags"></div><div><button type="button" class="cc-btn" id="acAddTag">Add a tag colour</button></div>') +
        '</div><div style="position:sticky;bottom:0;background:var(--surface,#fff);padding:10px 0;border-top:1px solid #0002"><button type="button" class="cc-btn pri" id="acSave">Save all changes</button></div></div>';

      drawBlocks(); drawTags(); prev();

      var bl = $('acBlocks');
      bl.addEventListener('dragstart', function (e) { var r = e.target.closest ? e.target.closest('[data-i]') : null; if (!r) return; dragFrom = +r.getAttribute('data-i'); if (e.dataTransfer) { e.dataTransfer.effectAllowed = 'move'; try { e.dataTransfer.setData('text/plain', String(dragFrom)); } catch (x) {} } });
      bl.addEventListener('dragover', function (e) { if (dragFrom >= 0) e.preventDefault(); });
      bl.addEventListener('drop', function (e) {
        var r = e.target.closest ? e.target.closest('[data-i]') : null; if (!r || dragFrom < 0) return; e.preventDefault();
        var to = +r.getAttribute('data-i'); if (to !== dragFrom) { blocks.splice(to, 0, blocks.splice(dragFrom, 1)[0]); drawBlocks(); } dragFrom = -1;
      });
      bl.addEventListener('dragend', function () { dragFrom = -1; });
      bl.addEventListener('click', function (e) {
        var u = e.target.closest ? e.target.closest('[data-up],[data-down]') : null; if (!u) return;
        var i = +(u.getAttribute('data-up') || u.getAttribute('data-down')), j = u.hasAttribute('data-up') ? i - 1 : i + 1;
        if (j < 0 || j >= blocks.length) return; blocks.splice(j, 0, blocks.splice(i, 1)[0]); drawBlocks();
      });
      bl.addEventListener('change', function (e) { var t = e.target; if (t && t.hasAttribute && t.hasAttribute('data-on')) { blocks[+t.getAttribute('data-on')].on = t.checked; drawBlocks(); } });

      root.querySelectorAll('[data-pair]').forEach(function (b) { b.addEventListener('click', function () { var p = PAIRS[+b.getAttribute('data-pair')]; $('acFrom').value = p[0]; $('acTo').value = p[1]; $('acText').value = (p[0] === '#FFFFFF' || p[0] === '#F4A6B7') ? 'dark' : 'light'; prev(); }); });
      ['acFrom', 'acTo', 'acText'].forEach(function (id) { $(id).addEventListener('input', prev); $(id).addEventListener('change', prev); });

      var tg = $('acTags');
      tg.addEventListener('change', function (e) { var r = e.target.closest ? e.target.closest('[data-trow]') : null; if (!r || !e.target.hasAttribute('data-tcol')) return; var v = e.target.value; if (v === '' || v === 'keep') return; var c = COLOURS[+v]; r.querySelector('[data-tfrom]').value = c[1]; r.querySelector('[data-tto]').value = c[2]; r.querySelector('[data-tsw]').style.background = grad(c[1], c[2]); });
      tg.addEventListener('click', function (e) { var b = e.target.closest ? e.target.closest('[data-tdel]') : null; if (!b) return; readTags(); tags.splice(+b.getAttribute('data-tdel'), 1); drawTags(); });
      $('acAddTag').addEventListener('click', function () { readTags(); if (tags.length >= 40) { toast('Use at most 40 tag colours.', 'bad'); return; } tags.push({ tag: '', from: '', to: '' }); drawTags(); });

      $('acSave').addEventListener('click', function () {
        var f = $('acFrom').value.trim(), t = $('acTo').value.trim();
        if (!HEX.test(f) || (t && !HEX.test(t))) { toast('The top section colours must look like #F70202 (with the # sign).', 'bad'); return; }
        var bg = root.querySelector('#acCard [name=img_bg]').value.trim();
        if (!HEX.test(bg)) { toast('The photo background must look like #FFFFFF (with the # sign).', 'bad'); return; }
        readTags();
        for (var i = 0; i < tags.length; i++) { if (tags[i].tag && !HEX.test(tags[i].from)) { toast('Tag "' + tags[i].tag + '": choose a colour from the list.', 'bad'); return; } if (tags[i].to && !HEX.test(tags[i].to)) { toast('Tag "' + tags[i].tag + '": the end colour must look like #FFD6C2.', 'bad'); return; } }
        var card = {}; root.querySelectorAll('#acCard [name]').forEach(function (x) { card[x.name] = x.type === 'checkbox' ? (x.checked ? 1 : 0) : x.value; });
        act($('acSave'), '/ad/appcontrol/save', { blocks: blocks.map(function (b) { return { id: b.id, on: b.on ? 1 : 0 }; }), theme: { from: f, to: t || f, text: $('acText').value, starts_on: $('acS').value, ends_on: $('acE').value }, card: card, tags: tags }, function () { V['ad-appcontrol'].run(root); });
      });
    });
  } };


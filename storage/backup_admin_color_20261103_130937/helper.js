  /* Colour field (category tile colour): hex box + live preview + preset buttons. Added by the admin colour installer. */
  var AD_PRESETS = ['#F70202', '#FF5500', '#FFB300', '#F4A6B7', '#FDE68A', '#BFDBFE', '#C7D2FE', '#DDD6FE', '#FBCFE8', '#FED7AA', '#E5E7EB', '#1F2937'];
  function colorField(f, val) {
    var ok = /^#[0-9a-fA-F]{6}$/.test(val || '');
    var sw = AD_PRESETS.map(function (c) { return '<button type="button" data-adc="' + c + '" title="' + c + '" style="width:26px;height:26px;border-radius:50%;border:1px solid #0003;background:' + c + ';cursor:pointer;margin:0 6px 6px 0;padding:0"></button>'; }).join('');
    return '<div class="cc-f ad-color"><span>' + esc(f.label) + '</span>' +
      '<div style="display:flex;gap:10px;align-items:center;margin-bottom:8px"><i class="ad-cdot" style="width:34px;height:34px;border-radius:8px;border:1px solid #0003;display:inline-block;background:' + (ok ? esc(val) : 'transparent') + '"></i>' +
      '<input class="cc-in" type="text" name="' + f.name + '" value="' + esc(val || '') + '" maxlength="7" placeholder="#F70202 (empty = automatic)" style="max-width:240px"></div>' +
      '<div>' + sw + '<button type="button" class="cc-btn" data-adc="">No colour</button></div>' +
      '<small class="muted">Background of this category tile in the customer app. Empty = automatic.</small></div>';
  }
  document.addEventListener('click', function (e) {
    var b = e.target.closest ? e.target.closest('[data-adc]') : null; if (!b) return;
    var w = b.closest('.ad-color'); if (!w) return;
    var i = w.querySelector('input'); i.value = b.getAttribute('data-adc'); i.dispatchEvent(new Event('input', { bubbles: true }));
  });
  document.addEventListener('input', function (e) {
    var t = e.target; if (!t || !t.closest) return; var w = t.closest('.ad-color'); if (!w || t.tagName !== 'INPUT') return;
    var d = w.querySelector('.ad-cdot'); if (d) d.style.background = /^#[0-9a-fA-F]{6}$/.test(t.value) ? t.value : 'transparent';
  });

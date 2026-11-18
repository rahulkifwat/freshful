<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Home page settings | Freshful admin</title>
<style>
*{box-sizing:border-box}
body{margin:0;font-family:system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;background:#f6f4f2;color:#1c1c1e}
.wrap{max-width:1000px;margin:0 auto;padding:24px 16px 60px}
a{color:#d4380d}
h1{font-size:24px;margin:0 0 4px}
h2{font-size:18px;margin:0 0 4px}
.sub{color:#5f5f63;margin:0 0 18px;font-size:14px}
.card{background:#fff;border:1px solid #ece7e3;border-radius:16px;padding:20px;margin-bottom:20px}
.ok{background:#e9f7ef;border:1px solid #bfe6cf;padding:10px 14px;border-radius:10px;margin-bottom:14px}
.err{background:#fdecec;border:1px solid #f5c2c2;padding:10px 14px;border-radius:10px;margin-bottom:14px}
.row{display:flex;gap:16px;flex-wrap:wrap;align-items:flex-end}
label{display:block;font-size:13px;font-weight:600;margin-bottom:6px}
input[type=color]{width:64px;height:42px;border:1px solid #d9d4cf;border-radius:8px;padding:2px;background:#fff}
input[type=range]{width:220px}
.btn{border:0;border-radius:999px;padding:11px 22px;font-weight:700;color:#fff;background:linear-gradient(110deg,#F70202,#FF5500);cursor:pointer;font-size:14px}
.btn.ghost{background:#fff;color:#1c1c1e;border:1px solid #d9d4cf}
.presets{display:flex;gap:8px;flex-wrap:wrap;margin:12px 0}
.preset{border:1px solid #d9d4cf;border-radius:999px;padding:6px 14px 6px 6px;background:#fff;cursor:pointer;display:flex;gap:8px;align-items:center;font-size:13px}
.preset i{width:26px;height:26px;border-radius:50%;display:block;border:1px solid rgba(0,0,0,.08)}
.preview{border-radius:20px;padding:22px;margin-top:14px;border:1px solid rgba(0,0,0,.06);background-image:linear-gradient(45deg,#eee 25%,transparent 25%,transparent 75%,#eee 75%),linear-gradient(45deg,#eee 25%,transparent 25%,transparent 75%,#eee 75%);background-size:20px 20px;background-position:0 0,10px 10px;position:relative}
.preview .layer{position:absolute;inset:0;border-radius:20px}
.preview .in{position:relative;display:flex;gap:16px;flex-wrap:wrap}
.preview .dot{width:64px;height:64px;border-radius:50%;background:#c93;box-shadow:0 8px 12px rgba(40,25,15,.25)}
.cats{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px}
.cat{border:1px solid #ece7e3;border-radius:14px;padding:12px;display:flex;flex-direction:column;gap:8px}
.cat img{width:90px;height:90px;border-radius:50%;object-fit:cover;background:#f4f2ef}
.cat small{color:#5f5f63}
.cat form{display:flex;flex-direction:column;gap:6px}
.cat input[type=file]{font-size:12px;max-width:100%}
.mini{border:0;border-radius:999px;padding:7px 14px;font-weight:700;font-size:12px;color:#fff;background:#1c1c1e;cursor:pointer}
.mini.link{background:#fff;color:#b42318;border:1px solid #f0c7c3}
h3{font-size:15px;margin:22px 0 6px;padding-top:14px;border-top:1px solid #f0ebe7}
h3:first-of-type{border-top:0;padding-top:0;margin-top:14px}
select,input[type=number],input[type=text]{border:1px solid #d9d4cf;border-radius:8px;padding:10px 12px;font:inherit;background:#fff}
.checks{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:8px 18px;margin-top:8px}
.checks label{display:flex;gap:8px;align-items:center;font-weight:500;margin:0}
.tag{font-size:11px;background:#e9f7ef;border-radius:999px;padding:2px 8px;color:#0d6b3c;width:max-content}
</style>
</head>
<body>
<div class="wrap">
    @if (! $embed)<p><a href="{{ url('/admin/console') }}">&larr; Back to admin</a> &nbsp; @endif<a href="{{ url('/') }}" target="_blank" rel="noopener">View home page</a>@if (! $embed)</p>@endif
    <h1>Home page settings</h1>
    <p class="sub">Change how the new home page looks. Changes show on the website straight away.</p>

    @if (session('status'))<div class="ok">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="err">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
    @if (! $ready)<div class="err">One-time setup is missing (the storefront_settings table). Run: php artisan migrate --force</div>@endif

    <div class="card" id="controls">
        <h2>Home page controls</h2>
        <p class="sub">Choose what shows on the home page, and how. Saved changes show on the website straight away.</p>
        <form method="post" action="{{ route('home.settings.controls') }}">
            @csrf
            <h3>1. Main banner (top of the page)</h3>
            <p class="sub">Website banners are the pictures you add in Admin &rarr; Marketing &rarr; Website banners (picture, title, link, on/off). Currently <strong>{{ $banners->count() }}</strong> banner(s) are on. <a href="{{ url('/admin/console#ad-banners') }}">Manage website banners</a></p>
            <div class="row">
                <div><label for="hs">What to show in the main banner</label>
                    <select id="hs" name="hero_source">
                        <option value="products" @selected($settings['hero_source'] === 'products')>Featured products only</option>
                        <option value="banners" @selected($settings['hero_source'] === 'banners')>Website banners only</option>
                        <option value="both" @selected($settings['hero_source'] === 'both')>Website banners first, then products</option>
                    </select></div>
                <div><label for="hsec">Change slide every (seconds)</label><input type="number" id="hsec" name="hero_seconds" min="2" max="30" value="{{ $settings['hero_seconds'] }}"></div>
                <div><label for="hc">Product slides (1 to 8)</label><input type="number" id="hc" name="hero_count" min="1" max="8" value="{{ $settings['hero_count'] }}"></div>
                <div><label for="ht">Product tag used for product slides</label>
                    <select id="ht" name="hero_tag">
                        <option value="">Best Sellers (default)</option>
                        @foreach ($tags as $t)<option value="{{ $t }}" @selected($settings['hero_tag'] === $t)>{{ $t }}</option>@endforeach
                    </select></div>
            </div>

            <h3>2. Sections to show</h3>
            <div class="checks">
                <label><input type="checkbox" name="show_side" value="1" @checked($settings['show_side'] !== '0')> Express and offer tiles next to the banner</label>
                <label><input type="checkbox" name="show_trust" value="1" @checked($settings['show_trust'] !== '0')> Trust strip (fresh, weighed, payments, stores)</label>
                <label><input type="checkbox" name="show_categories" value="1" @checked($settings['show_categories'] !== '0')> Shop by category</label>
                <label><input type="checkbox" name="show_buy_again" value="1" @checked($settings['show_buy_again'] !== '0')> Buy again</label>
                <label><input type="checkbox" name="show_coupons" value="1" @checked($settings['show_coupons'] !== '0')> Coupons for you</label>
                <label><input type="checkbox" name="stock_badges" value="1" @checked($settings['stock_badges'] !== '0')> Store stock signals (Cut today, Only X left, Out of stock)</label>
            </div>

            <h3>3. Shop by category</h3>
            <div class="row">
                <div><label for="ct">Heading</label><input type="text" id="ct" name="cat_title" maxlength="60" value="{{ $settings['cat_title'] }}"></div>
                <div><label for="cs">Line under the heading</label><input type="text" id="cs" name="cat_sub" maxlength="120" value="{{ $settings['cat_sub'] }}" style="min-width:300px"></div>
                <div><label for="cc">Pictures per row (4 to 10)</label><input type="number" id="cc" name="cat_columns" min="4" max="10" value="{{ $settings['cat_columns'] }}"></div>
            </div>

            <h3>4. Product sections</h3>
            <div class="row">
                <div><label for="sl">Products shown in each section</label><input type="number" id="sl" name="section_limit" min="4" max="24" value="{{ $settings['section_limit'] }}"></div>
                <div><label for="ls">Show "Only X left" at or below</label><input type="number" id="ls" name="low_stock_at" min="1" max="50" value="{{ $settings['low_stock_at'] }}"></div>
            </div>
            <p class="sub" style="margin-top:12px">Every product tag is a section on the home page. Untick a tag to hide its section.</p>
            @php $hiddenNow = array_map('mb_strtolower', array_filter(explode('|', (string) $settings['hidden_tags']))); @endphp
            <div class="checks">
                @foreach ($tags as $t)
                    <label><input type="checkbox" name="hidden[]" value="{{ $t }}" @checked(in_array(mb_strtolower($t), $hiddenNow, true))> Hide <strong>{{ $t }}</strong></label>
                @endforeach
            </div>
            <p style="margin-top:16px"><button class="btn" type="submit">Save controls</button></p>
        </form>
    </div>

    <div class="card">
        <h2>Shop by category: background</h2>
        <p class="sub">Pick two colours for a gradient (use the same colour twice for a flat colour). Transparency 100 means solid, 0 means invisible.</p>
        <form method="post" action="{{ route('home.settings.background') }}" id="bgform">
            @csrf
            <div class="presets">
                @foreach ($presets as $name => $pair)
                    <button type="button" class="preset" data-from="{{ $pair[0] }}" data-to="{{ $pair[1] }}"><i style="background:linear-gradient(135deg,{{ $pair[0] }},{{ $pair[1] }})"></i>{{ $name }}</button>
                @endforeach
            </div>
            <div class="row">
                <div><label for="f">Colour 1</label><input type="color" id="f" name="cat_bg_from" value="{{ $settings['cat_bg_from'] }}"></div>
                <div><label for="t">Colour 2</label><input type="color" id="t" name="cat_bg_to" value="{{ $settings['cat_bg_to'] }}"></div>
                <div><label for="a">Direction: <span id="av">{{ $settings['cat_bg_angle'] }}</span> degrees</label><input type="range" id="a" name="cat_bg_angle" min="0" max="360" step="5" value="{{ $settings['cat_bg_angle'] }}"></div>
                <div><label for="o">Transparency: <span id="ov">{{ $settings['cat_bg_opacity'] }}</span> percent</label><input type="range" id="o" name="cat_bg_opacity" min="0" max="100" step="5" value="{{ $settings['cat_bg_opacity'] }}"></div>
            </div>
            <div class="preview"><div class="layer" id="layer" style="background:{{ $background }}"></div><div class="in"><div class="dot"></div><div class="dot" style="background:#c55"></div><div class="dot" style="background:#5a8"></div><strong style="align-self:center">Live preview</strong></div></div>
            <p style="margin-top:16px"><button class="btn" type="submit">Save background</button> &nbsp; <button class="btn ghost" type="submit" name="action" value="reset">Reset to default</button></p>
        </form>
    </div>

    <div class="card">
        <h2>Category pictures (high resolution)</h2>
        <p class="sub">Upload a sharp picture for any category. Use a square JPG, PNG or WebP, at least 600 x 600 pixels (800 to 1200 is best), up to 5 MB. Without an upload, the current picture is used.</p>
        <div class="cats">
            @foreach ($categories as $c)
                <div class="cat" id="cat-{{ $c->id }}">
                    <img src="{{ $hd[$c->id] ?? asset('uploads/images/'.$c->circle_image) }}" alt="">
                    <strong>{{ $c->name }}</strong>
                    @if (isset($hd[$c->id]))<span class="tag">High resolution picture on</span>@else<small>Current picture</small>@endif
                    <form method="post" action="{{ route('home.settings.image', $c->id) }}" enctype="multipart/form-data">
                        @csrf
                        <input type="file" name="image" accept="image/jpeg,image/png,image/webp" required>
                        <button class="mini" type="submit">Upload</button>
                    </form>
                    @if (isset($hd[$c->id]))
                        <form method="post" action="{{ route('home.settings.image.remove', $c->id) }}">@csrf<button class="mini link" type="submit">Use the old picture</button></form>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
<script>
(function(){
    var f=document.getElementById('f'),t=document.getElementById('t'),a=document.getElementById('a'),o=document.getElementById('o'),layer=document.getElementById('layer');
    function rgba(h,p){var r=parseInt(h.substr(1,2),16),g=parseInt(h.substr(3,2),16),b=parseInt(h.substr(5,2),16);return 'rgba('+r+','+g+','+b+','+(p/100)+')';}
    function paint(){document.getElementById('av').textContent=a.value;document.getElementById('ov').textContent=o.value;layer.style.background='linear-gradient('+a.value+'deg,'+rgba(f.value,+o.value)+','+rgba(t.value,+o.value)+')';}
    [f,t,a,o].forEach(function(e){e.addEventListener('input',paint);});
    document.querySelectorAll('.preset').forEach(function(b){b.addEventListener('click',function(){f.value=b.dataset.from;t.value=b.dataset.to;paint();});});
    paint();
})();
</script>
</body>
</html>

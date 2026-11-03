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
.tag{font-size:11px;background:#e9f7ef;border-radius:999px;padding:2px 8px;color:#0d6b3c;width:max-content}
</style>
</head>
<body>
<div class="wrap">
    <p><a href="{{ url('/admin/console') }}">&larr; Back to admin</a> &nbsp; <a href="{{ url('/') }}" target="_blank" rel="noopener">View home page</a></p>
    <h1>Home page settings</h1>
    <p class="sub">Change how the new home page looks. Changes show on the website straight away.</p>

    @if (session('status'))<div class="ok">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="err">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
    @if (! $ready)<div class="err">One-time setup is missing (the storefront_settings table). Run: php artisan migrate --force</div>@endif

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

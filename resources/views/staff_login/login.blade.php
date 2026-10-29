@php
  /* Freshful staff sign-in portal (22 Oct 2026). One design for every staff dashboard login. */
  $cur = $current && isset($roles[$current]) ? $roles[$current] : null;
  $icons = [
    'store' => '<path d="M3 9l1.5-5h15L21 9"/><path d="M3 9h18v2a3 3 0 0 1-6 0 3 3 0 0 1-6 0 3 3 0 0 1-6 0z"/><path d="M5 13v7h14v-7"/><path d="M10 20v-4h4v4"/>',
    'pos' => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 7h8"/><path d="M8 11h2M12 11h2M16 11h0M8 15h2M12 15h2"/>',
    'pin' => '<path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
    'route' => '<circle cx="6" cy="18" r="2.5"/><circle cx="18" cy="6" r="2.5"/><path d="M8.5 18H15a3 3 0 0 0 0-6H9a3 3 0 0 1 0-6h6.5"/>',
    'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a14 14 0 0 1 0 18a14 14 0 0 1 0-18z"/>',
    'wallet' => '<path d="M4 7a2 2 0 0 1 2-2h12v4"/><rect x="4" y="7" width="17" height="12" rx="2"/><path d="M16 13h2"/>',
    'megaphone' => '<path d="M4 10v4h3l7 4V6L7 10z"/><path d="M17.5 9a4 4 0 0 1 0 6"/>',
    'users' => '<circle cx="9" cy="8" r="3.2"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0"/><path d="M16 5.2a3 3 0 0 1 0 5.6M17.5 14a5 5 0 0 1 3 5"/>',
    'calendar' => '<rect x="3.5" y="5" width="17" height="15" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
    'factory' => '<path d="M3 20V10l5 3V10l5 3V6h3l1 14z"/><path d="M3 20h18"/>',
    'shield' => '<path d="M12 3l8 3v6c0 4.5-3.4 8.2-8 9-4.6-.8-8-4.5-8-9V6z"/><path d="M9 12l2 2 4-4"/>',
  ];
  $svg = fn ($n, $s = 20) => '<svg width="'.$s.'" height="'.$s.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($icons[$n] ?? $icons['store']).'</svg>';
  $groups = [];
  foreach ($roles as $r) { $groups[$r['group']][] = $r; }
  $messages = [];
  if (isset($errors) && $errors->any()) { foreach ($errors->all() as $m) { $messages[] = $m; } }
  foreach (['error', 'message', 'msg', 'danger', 'warning'] as $k) { if (is_string(session($k)) && session($k) !== '') { $messages[] = session($k); } }
  $messages = array_values(array_unique($messages));
  $okMsg = null;
  foreach (['success', 'status'] as $k) { if (is_string(session($k)) && session($k) !== '') { $okMsg = session($k); } }
  $sp = $support ?? [];
  $wa = preg_replace('/\D+/', '', (string) ($sp['whatsapp'] ?? ''));
  $phone = trim((string) ($sp['phone'] ?? ''));
  $mail = trim((string) ($sp['email'] ?? ''));
  $logo = asset('assets/image/logo.png');
  $jsRoles = [];
  foreach ($roles as $k => $r) { $jsRoles[$k] = ['name' => $r['name'], 'url' => $r['url'], 'text' => $r['text'], 'icon' => $svg($r['icon'], 14)]; }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#ffffff">
<title>{{ $cur ? $cur['name'].' sign in' : 'Staff sign in' }} | Freshful</title>
<link rel="icon" href="{{ asset('assets/image/Fevi.png') }}">
<style>
:root{--brand:#E63F2E;--brand-d:#C8301F;--brand-t:#FDECEA;--accent:#F7931E;--ink:#26313B;--ink2:#56626D;--muted:#86909A;--line:#E5E8EC;--soft:#F7F8FA;--panel:#FFF8F3;--ok:#127A46;--ok-t:#E7F6EE;--r:12px}
*{box-sizing:border-box}
html,body{margin:0;height:100%}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Noto Sans","Helvetica Neue",Arial,sans-serif;color:var(--ink);background:#fff;-webkit-font-smoothing:antialiased;font-size:15px;line-height:1.5}
a{color:var(--brand)}
.fl-portal{display:grid;grid-template-columns:minmax(0,1.08fr) minmax(0,1fr);min-height:100vh}
/* Left: brand + dashboards */
.fl-side{background:var(--panel);border-right:1px solid #F3E6DC;padding:40px 48px;display:flex;flex-direction:column}
.fl-side .logo{height:60px;width:auto;max-width:240px;object-fit:contain;display:block;align-self:flex-start}
.fl-eyebrow{margin:44px 0 10px;font-size:12px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--brand)}
.fl-side h1{margin:0;font-size:34px;line-height:1.18;font-weight:800;letter-spacing:-.02em;max-width:520px}
.fl-side .lead{margin:12px 0 0;color:var(--ink2);max-width:520px;font-size:16px}
.fl-dash{margin-top:30px}
.fl-dash h2{margin:0 0 4px;font-size:13px;font-weight:700;color:var(--ink2)}
.fl-grp{margin-top:14px}
.fl-grp>span{display:block;font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin:0 0 8px}
.fl-tiles{display:grid;grid-template-columns:repeat(auto-fill,minmax(205px,1fr));gap:10px}
.fl-tile{all:unset;box-sizing:border-box;cursor:pointer;display:flex;gap:11px;align-items:flex-start;background:#fff;border:1.5px solid var(--line);border-radius:var(--r);padding:12px 13px;transition:border-color .15s,box-shadow .15s,transform .15s;position:relative}
.fl-tile:hover{border-color:#F2B7AF;transform:translateY(-1px)}
.fl-tile:focus-visible{outline:3px solid #F9C9C2;outline-offset:2px}
.fl-tile .ic{flex:none;width:36px;height:36px;border-radius:10px;display:grid;place-items:center;background:var(--soft);color:var(--ink2)}
.fl-tile b{display:block;font-size:14px;font-weight:700;line-height:1.25}
.fl-tile small{display:block;font-size:12px;color:var(--muted);line-height:1.35;margin-top:2px}
.fl-tile[aria-pressed="true"]{border-color:var(--brand);box-shadow:0 0 0 3px var(--brand-t)}
.fl-tile[aria-pressed="true"] .ic{background:var(--brand);color:#fff}
.fl-tile[aria-pressed="true"]::after{content:"";position:absolute;top:9px;right:9px;width:16px;height:16px;border-radius:50%;background:var(--brand) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='3.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 12.5l4 4 8-9'/%3E%3C/svg%3E") center/11px no-repeat}
.fl-notes{margin-top:auto;padding-top:28px;display:flex;flex-wrap:wrap;gap:8px 22px;font-size:13px;color:var(--ink2)}
.fl-notes span{display:flex;gap:7px;align-items:center}
.fl-notes svg{color:var(--accent)}
/* Right: form */
.fl-main{display:flex;flex-direction:column;padding:32px 40px}
.fl-top{display:flex;justify-content:flex-end;gap:14px;font-size:13px}
.fl-top a{color:var(--ink2);text-decoration:none;font-weight:600}
.fl-top a:hover{color:var(--brand)}
.fl-form-wrap{flex:1;display:flex;align-items:center;justify-content:center;padding:24px 0}
.fl-form{width:100%;max-width:400px}
.fl-mlogo{display:none;height:46px;width:auto;max-width:200px;object-fit:contain;margin:0 auto 22px}
.fl-badge{display:inline-flex;align-items:center;gap:8px;background:var(--brand-t);color:var(--brand-d);font-weight:700;font-size:13px;padding:6px 12px 6px 8px;border-radius:999px}
.fl-badge .ic{width:24px;height:24px;border-radius:50%;background:var(--brand);color:#fff;display:grid;place-items:center}
.fl-badge.none{background:var(--soft);color:var(--ink2)}
.fl-badge.none .ic{background:var(--muted)}
.fl-form h2{margin:16px 0 6px;font-size:28px;font-weight:800;letter-spacing:-.02em;line-height:1.2}
.fl-form .sub{margin:0 0 24px;color:var(--ink2)}
.fl-alert{display:flex;gap:10px;align-items:flex-start;border-radius:10px;padding:11px 13px;margin-bottom:18px;font-size:14px;background:var(--brand-t);color:#9A2315;border:1px solid #F7C9C2}
.fl-alert.ok{background:var(--ok-t);color:var(--ok);border-color:#BFE6CF}
.fl-alert ul{margin:0;padding:0;list-style:none}
.fl-field{margin-bottom:16px}
.fl-field label{display:block;font-size:13px;font-weight:700;margin-bottom:6px}
.fl-input{position:relative}
.fl-input>svg{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--muted);pointer-events:none}
.fl-input input,.fl-input select{width:100%;height:48px;border:1.5px solid var(--line);border-radius:10px;padding:0 14px 0 42px;font:inherit;font-size:15px;color:var(--ink);background:#fff;transition:border-color .15s,box-shadow .15s;appearance:none;-webkit-appearance:none}
.fl-input select{padding-right:38px;background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2386909A' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E") right 13px center/16px no-repeat;cursor:pointer}
.fl-input input:focus,.fl-input select:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 4px var(--brand-t)}
.fl-input input[aria-invalid="true"]{border-color:var(--brand)}
.fl-input .pw{padding-right:48px}
.fl-eye{position:absolute;right:6px;top:50%;transform:translateY(-50%);width:38px;height:38px;border:0;border-radius:8px;background:transparent;color:var(--muted);cursor:pointer;display:grid;place-items:center}
.fl-eye:hover{background:var(--soft);color:var(--ink)}
.fl-caps{display:none;margin-top:6px;font-size:12.5px;color:#9A5B00;font-weight:600}
.fl-row{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:2px 0 20px;font-size:14px}
.fl-check{display:flex;align-items:center;gap:8px;color:var(--ink2);cursor:pointer;user-select:none}
.fl-check input{width:17px;height:17px;accent-color:var(--brand);margin:0}
.fl-link{background:none;border:0;padding:0;font:inherit;font-weight:700;color:var(--brand);cursor:pointer}
.fl-link:hover{text-decoration:underline}
.fl-btn{width:100%;height:50px;border:0;border-radius:10px;background:var(--brand);color:#fff;font:inherit;font-size:16px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:10px;transition:background .15s}
.fl-btn:hover{background:var(--brand-d)}
.fl-btn:focus-visible{outline:3px solid #F9C9C2;outline-offset:2px}
.fl-btn[disabled]{opacity:.75;cursor:progress}
.fl-spin{width:18px;height:18px;border:2.5px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:flspin .8s linear infinite;display:none}
.fl-btn.busy .fl-spin{display:block}
@keyframes flspin{to{transform:rotate(360deg)}}
.fl-secure{display:flex;gap:8px;align-items:center;justify-content:center;margin-top:16px;font-size:12.5px;color:var(--muted)}
.fl-help{margin-top:26px;padding-top:18px;border-top:1px solid var(--line);font-size:13.5px;color:var(--ink2);text-align:center}
.fl-foot{font-size:12px;color:var(--muted);display:flex;flex-wrap:wrap;gap:6px 16px;justify-content:space-between}
.fl-offline{display:none;position:fixed;left:50%;top:14px;transform:translateX(-50%);background:var(--ink);color:#fff;padding:9px 16px;border-radius:999px;font-size:13px;font-weight:600;z-index:20}
/* Modal */
dialog.fl-modal{border:0;border-radius:16px;padding:0;width:min(440px,calc(100vw - 32px));box-shadow:0 24px 60px rgba(20,30,40,.22);color:var(--ink)}
dialog.fl-modal::backdrop{background:rgba(20,28,36,.45)}
.fl-mbody{padding:24px}
.fl-mbody h3{margin:0 0 8px;font-size:20px}
.fl-mbody p{margin:0 0 14px;color:var(--ink2);font-size:14.5px}
.fl-steps{margin:0 0 18px;padding-left:20px;color:var(--ink2);font-size:14px}
.fl-steps li{margin-bottom:4px}
.fl-contacts{display:grid;gap:8px}
.fl-contact{display:flex;align-items:center;gap:12px;padding:12px 14px;border:1.5px solid var(--line);border-radius:10px;text-decoration:none;color:var(--ink);font-weight:700;font-size:14px}
.fl-contact:hover{border-color:var(--brand)}
.fl-contact small{display:block;font-weight:500;color:var(--muted);font-size:12.5px}
.fl-contact .ic{width:34px;height:34px;border-radius:9px;display:grid;place-items:center;background:var(--soft);color:var(--ink2);flex:none}
.fl-contact.wa .ic{background:#E7F6EE;color:#128C4A}
.fl-mfoot{display:flex;justify-content:flex-end;padding:14px 24px;border-top:1px solid var(--line);background:var(--soft)}
.fl-mfoot button{height:40px;padding:0 18px;border-radius:9px;border:1.5px solid var(--line);background:#fff;font:inherit;font-weight:700;cursor:pointer}
.fl-mobile-pick{display:none}
/* Tablet / phone */
@media (max-width:980px){
  .fl-portal{grid-template-columns:1fr}
  .fl-side{display:none}
  .fl-main{padding:20px 16px}
  .fl-mlogo{display:block}
  .fl-form{text-align:left}
  .fl-badge-wrap{text-align:center}
  .fl-form h2,.fl-form .sub{text-align:center}
  .fl-mobile-pick{display:block}
  .fl-top{justify-content:center}
  .fl-foot{justify-content:center;text-align:center}
}
@media (max-width:380px){.fl-form h2{font-size:24px}.fl-row{flex-direction:column;align-items:flex-start}}
@media (prefers-reduced-motion:reduce){*{transition:none!important;animation:none!important}}
</style>
</head>
<body>
<div class="fl-offline" id="flOffline" role="status">You are offline. Check your internet connection.</div>
<div class="fl-portal" data-fl-portal="1">

  <aside class="fl-side" aria-label="Freshful dashboards">
    <img class="logo" src="{{ $logo }}" alt="Freshful">
    <div class="fl-eyebrow">Operations portal</div>
    <h1>Fresh from farm and sea, run by one team.</h1>
    <p class="lead">One secure sign-in for every Freshful dashboard. Choose yours below, then sign in with the email and password your admin gave you.</p>

    <div class="fl-dash">
      <h2>Choose your dashboard</h2>
      @foreach ($groups as $gname => $list)
        <div class="fl-grp">
          <span>{{ $gname }}</span>
          <div class="fl-tiles">
            @foreach ($list as $r)
              <button type="button" class="fl-tile" data-role="{{ $r['key'] }}" aria-pressed="{{ $current === $r['key'] ? 'true' : 'false' }}">
                <span class="ic">{!! $svg($r['icon'], 19) !!}</span>
                <span><b>{{ $r['name'] }}</b><small>{{ $r['desc'] }}</small></span>
              </button>
            @endforeach
          </div>
        </div>
      @endforeach
    </div>

    <div class="fl-notes">
      <span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="17" r="2.5"/><circle cx="18" cy="17" r="2.5"/><path d="M8.5 17h7M4 12h9l3-5h3"/></svg>Delivery partners: sign in on the Freshful Rider app</span>
      <span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 7h14l-1.5 10h-11z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg>Shopping? Visit <a href="{{ url('/') }}">freshful.in</a></span>
    </div>
  </aside>

  <main class="fl-main">
    <div class="fl-top"><a href="{{ url('/') }}">freshful.in</a><a href="#" data-fl-forgot>Need help?</a></div>

    <div class="fl-form-wrap">
      <div class="fl-form">
        <img class="fl-mlogo" src="{{ $logo }}" alt="Freshful">

        <div class="fl-badge-wrap">
          <span class="fl-badge {{ $cur ? '' : 'none' }}" id="flBadge">
            <span class="ic" id="flBadgeIc">{!! $svg($cur['icon'] ?? 'shield', 14) !!}</span>
            <span id="flBadgeTx">{{ $cur ? $cur['name'].' dashboard' : 'Choose a dashboard' }}</span>
          </span>
        </div>
        <h2>Welcome back</h2>
        <p class="sub" id="flSub">{{ $cur ? 'Sign in to the '.$cur['name'].' dashboard.' : 'Choose your dashboard and sign in.' }}</p>

        @if ($okMsg)
          <div class="fl-alert ok" role="status">{{ $okMsg }}</div>
        @endif
        @if (count($messages))
          <div class="fl-alert" role="alert">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" style="flex:none;margin-top:1px"><circle cx="12" cy="12" r="9"/><path d="M12 7.5v5.5M12 16.5h0"/></svg>
            <ul>@foreach ($messages as $m)<li>{{ $m }}</li>@endforeach</ul>
          </div>
        @endif

        <form method="POST" action="{{ $cur ? $cur['url'] : '' }}" id="flForm" autocomplete="on" novalidate>
          @csrf
          <div class="fl-field fl-mobile-pick">
            <label for="flRole">Dashboard</label>
            <div class="fl-input">
              {!! $svg('store', 18) !!}
              <select id="flRole" aria-label="Dashboard">
                <option value="" {{ $cur ? '' : 'selected' }} disabled>Select your dashboard</option>
                @foreach ($groups as $gname => $list)
                  <optgroup label="{{ $gname }}">
                    @foreach ($list as $r)
                      <option value="{{ $r['key'] }}" {{ $current === $r['key'] ? 'selected' : '' }}>{{ $r['name'] }}</option>
                    @endforeach
                  </optgroup>
                @endforeach
              </select>
            </div>
          </div>

          <div class="fl-field">
            <label for="flEmail" id="flEmailLb">{{ ($cur['text'] ?? false) ? 'Email or mobile number' : 'Email address' }}</label>
            <div class="fl-input">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3.5 6.5l8.5 6.5 8.5-6.5"/></svg>
              <input id="flEmail" name="email" type="{{ ($cur['text'] ?? false) ? 'text' : 'email' }}" value="{{ old('email') }}" placeholder="{{ ($cur['text'] ?? false) ? 'you@freshful.in or 10-digit mobile' : 'you@freshful.in' }}" autocomplete="username" autocapitalize="off" spellcheck="false" required {{ $errors->has('email') ? 'aria-invalid=true' : '' }}>
            </div>
          </div>

          <div class="fl-field">
            <label for="flPw">Password</label>
            <div class="fl-input">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4.5" y="10.5" width="15" height="10" rx="2"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/></svg>
              <input id="flPw" class="pw" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" required {{ $errors->has('password') ? 'aria-invalid=true' : '' }}>
              <button type="button" class="fl-eye" id="flEye" aria-label="Show password" aria-pressed="false">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
            <div class="fl-caps" id="flCaps" role="status">Caps Lock is on</div>
          </div>

          <div class="fl-row">
            <label class="fl-check"><input type="checkbox" id="flRemember"> Remember my email</label>
            <button type="button" class="fl-link" data-fl-forgot>Forgot password?</button>
          </div>

          <button type="submit" class="fl-btn" id="flSubmit"><span class="fl-spin" aria-hidden="true"></span><span id="flSubmitTx">{{ $cur ? 'Sign in to '.$cur['name'] : 'Sign in' }}</span></button>
          <div class="fl-alert" id="flPickMsg" role="alert" style="display:none;margin:12px 0 0">Please choose your dashboard first.</div>

          <div class="fl-secure"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4.5" y="10.5" width="15" height="10" rx="2"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/></svg>Secure connection &middot; Authorised Freshful staff only</div>
        </form>

        <div class="fl-help">Trouble signing in? <button type="button" class="fl-link" data-fl-forgot>Contact admin</button></div>
      </div>
    </div>

    <div class="fl-foot"><span>&copy; {{ date('Y') }} Freshful. All rights reserved.</span><span>Fresh meat &middot; Seafood &middot; Vegetables &middot; Fruits</span></div>
  </main>
</div>

<dialog class="fl-modal" id="flModal" aria-labelledby="flMTitle">
  <div class="fl-mbody">
    <h3 id="flMTitle">Forgot your password?</h3>
    <p>For your security, staff passwords are reset only by the {{ $sp['name'] ?? 'Freshful Admin Team' }}. Contact them and share:</p>
    <ol class="fl-steps">
      <li>Your full name and registered email or mobile</li>
      <li>Your dashboard (<span id="flMRole">{{ $cur['name'] ?? 'Hub, Area, Accounts...' }}</span>) and your hub or city</li>
    </ol>
    <div class="fl-contacts">
      @if ($wa)
        <a class="fl-contact wa" id="flWa" data-num="{{ $wa }}" href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener"><span class="ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20l1.3-3.9A8 8 0 1 1 8 19z"/><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1.2-1.3-2-1-1 .8a4 4 0 0 1-2.2-2.2l.8-1-1-2z"/></svg></span><span>WhatsApp admin<small>Opens a ready message with your details</small></span></a>
      @endif
      @if ($phone)
        <a class="fl-contact" href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}"><span class="ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg></span><span>Call {{ $phone }}<small>{{ $sp['hours'] ?? '' }}</small></span></a>
      @endif
      @if ($mail)
        <a class="fl-contact" id="flMail" data-mail="{{ $mail }}" href="mailto:{{ $mail }}?subject={{ rawurlencode('Password reset request') }}"><span class="ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3.5 6.5l8.5 6.5 8.5-6.5"/></svg></span><span>Email {{ $mail }}<small>Reply usually the same day</small></span></a>
      @endif
      @if (! $wa && ! $phone && ! $mail)
        <div class="fl-contact" style="cursor:default"><span class="ic">{!! $svg('users', 18) !!}</span><span>Ask your reporting manager<small>Or the Freshful admin team at head office</small></span></div>
      @endif
    </div>
  </div>
  <div class="fl-mfoot"><button type="button" id="flMClose">Close</button></div>
</dialog>

<script>
(function () {
  var ROLES = @json($jsRoles);
  var current = @json($current);
  var $ = function (id) { return document.getElementById(id); };
  var form = $('flForm'), email = $('flEmail'), pw = $('flPw'), sel = $('flRole');
  var store = {
    get: function (k) { try { return window.localStorage.getItem(k); } catch (e) { return null; } },
    set: function (k, v) { try { window.localStorage.setItem(k, v); } catch (e) {} },
    del: function (k) { try { window.localStorage.removeItem(k); } catch (e) {} }
  };

  function pick(key, fromUser) {
    var r = ROLES[key]; if (!r) return;
    current = key;
    form.action = r.url;
    document.querySelectorAll('.fl-tile').forEach(function (t) { t.setAttribute('aria-pressed', t.getAttribute('data-role') === key ? 'true' : 'false'); });
    if (sel) sel.value = key;
    var b = $('flBadge'); b.classList.remove('none');
    $('flBadgeIc').innerHTML = r.icon; $('flBadgeTx').textContent = r.name + ' dashboard';
    $('flSub').textContent = 'Sign in to the ' + r.name + ' dashboard.';
    $('flSubmitTx').textContent = 'Sign in to ' + r.name;
    $('flMRole').textContent = r.name;
    email.type = r.text ? 'text' : 'email';
    $('flEmailLb').textContent = r.text ? 'Email or mobile number' : 'Email address';
    email.placeholder = r.text ? 'you@freshful.in or 10-digit mobile' : 'you@freshful.in';
    $('flPickMsg').style.display = 'none';
    document.title = r.name + ' sign in | Freshful';
    // Keep the address bar on this dashboard's own login page, so a wrong password comes back here.
    try { var u = new URL(r.url, location.href); if (u.origin === location.origin && u.pathname !== location.pathname) history.replaceState(null, '', u.pathname); } catch (e) {}
    if (fromUser) { store.set('fl_role', key); (email.value ? pw : email).focus(); }
  }

  document.querySelectorAll('.fl-tile').forEach(function (t) { t.addEventListener('click', function () { pick(t.getAttribute('data-role'), true); }); });
  if (sel) sel.addEventListener('change', function () { pick(sel.value, true); });

  // On the general /staff page, pre-select the dashboard this device used last time.
  if (!current) { var last = store.get('fl_role'); if (last && ROLES[last]) pick(last, false); }

  // Remember email (this device only, off by default).
  var remembered = store.get('fl_email');
  if (remembered && !email.value) { email.value = remembered; $('flRemember').checked = true; }
  (email.value ? pw : email).focus();

  // Show / hide password.
  $('flEye').addEventListener('click', function () {
    var show = pw.type === 'password'; pw.type = show ? 'text' : 'password';
    this.setAttribute('aria-pressed', show ? 'true' : 'false'); this.setAttribute('aria-label', show ? 'Hide password' : 'Show password'); pw.focus();
  });

  // Caps Lock warning.
  function caps(e) { if (e.getModifierState) $('flCaps').style.display = e.getModifierState('CapsLock') ? 'block' : 'none'; }
  pw.addEventListener('keydown', caps); pw.addEventListener('keyup', caps);
  pw.addEventListener('blur', function () { $('flCaps').style.display = 'none'; });

  // Submit: check fields, stop double clicks.
  form.addEventListener('submit', function (e) {
    if (!current || !form.getAttribute('action')) { e.preventDefault(); $('flPickMsg').style.display = 'flex'; if (sel && getComputedStyle(sel.closest('.fl-field')).display !== 'none') sel.focus(); return; }
    email.value = email.value.trim();
    if (!email.value) { e.preventDefault(); email.setAttribute('aria-invalid', 'true'); email.focus(); return; }
    if (!pw.value) { e.preventDefault(); pw.setAttribute('aria-invalid', 'true'); pw.focus(); return; }
    if ($('flRemember').checked) store.set('fl_email', email.value); else store.del('fl_email');
    store.set('fl_role', current);
    var btn = $('flSubmit'); btn.disabled = true; btn.classList.add('busy'); $('flSubmitTx').textContent = 'Signing in...';
  });
  // If the browser restores this page from its back/forward cache, enable the button again.
  window.addEventListener('pageshow', function () { var btn = $('flSubmit'); btn.disabled = false; btn.classList.remove('busy'); if (current && ROLES[current]) $('flSubmitTx').textContent = 'Sign in to ' + ROLES[current].name; });
  [email, pw].forEach(function (i) { i.addEventListener('input', function () { i.removeAttribute('aria-invalid'); }); });

  // Forgot password / help.
  var modal = $('flModal');
  function openHelp(e) {
    if (e) e.preventDefault();
    var who = (email.value || '').trim(), role = current && ROLES[current] ? ROLES[current].name : '';
    var text = 'Hello Freshful Admin, I need a password reset.\nName: \nLogin: ' + (who || '') + '\nDashboard: ' + role + '\nHub / city: ';
    var wa = $('flWa'); if (wa) wa.href = 'https://wa.me/' + wa.getAttribute('data-num') + '?text=' + encodeURIComponent(text);
    var ml = $('flMail'); if (ml) ml.href = 'mailto:' + ml.getAttribute('data-mail') + '?subject=' + encodeURIComponent('Password reset request' + (role ? ' - ' + role : '')) + '&body=' + encodeURIComponent(text);
    if (modal.showModal) modal.showModal(); else modal.setAttribute('open', '');
  }
  document.querySelectorAll('[data-fl-forgot]').forEach(function (b) { b.addEventListener('click', openHelp); });
  $('flMClose').addEventListener('click', function () { modal.close ? modal.close() : modal.removeAttribute('open'); });
  modal.addEventListener('click', function (e) { if (e.target === modal) modal.close(); });

  // Offline notice.
  function net() { $('flOffline').style.display = navigator.onLine ? 'none' : 'block'; }
  window.addEventListener('online', net); window.addEventListener('offline', net); net();
})();
</script>
</body>
</html>

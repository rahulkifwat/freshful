 <head>
   <meta charset="utf-8">
   <meta name="viewport" content="width=device-width, initial-scale=1">
   <meta name="csrf-token" content="{{ csrf_token() }}">
   <meta name="theme-color" content="#F70202">
   <meta name="format-detection" content="telephone=no">

   @php
     $ffCanonical = request()->has('page') ? url()->full() : url()->current();
     $ffTitle = trim($__env->yieldContent('title', 'Freshful | Fresh food for every day'));
     $ffDescription = trim($__env->yieldContent('meta_description', 'Shop fresh meat, seafood, fruits, vegetables and everyday groceries online with Freshful.'));
     $ffOgImage = trim($__env->yieldContent('og_image', asset('assets/image/logo.png')));
   @endphp
   <title>{{ $ffTitle }}</title>
   <meta name="description" content="{{ $ffDescription }}">
   <link rel="canonical" href="{{ $ffCanonical }}">

   <!-- Share previews (WhatsApp, Facebook, LinkedIn, X) -->
   <meta property="og:site_name" content="Freshful">
   <meta property="og:type" content="website">
   <meta property="og:locale" content="en_IN">
   <meta property="og:title" content="{{ $ffTitle }}">
   <meta property="og:description" content="{{ $ffDescription }}">
   <meta property="og:url" content="{{ $ffCanonical }}">
   <meta property="og:image" content="{{ $ffOgImage }}">
   <meta name="twitter:card" content="summary">
   <meta name="twitter:site" content="@Freshfullfoods">
   <meta name="twitter:title" content="{{ $ffTitle }}">
   <meta name="twitter:description" content="{{ $ffDescription }}">
   <meta name="twitter:image" content="{{ $ffOgImage }}">

   <!-- Favicons -->
   <link
     rel="icon"
     type="image/png"
     href="{{ asset('assets/image/Fevi.png')}}"
     sizes="32x32" />

   <!-- Company details for search engines -->
   <script type="application/ld+json">
   {!! json_encode([
       '@'.'context' => 'https://schema.org',
       '@'.'type' => 'Organization',
       'name' => 'Freshful',
       'legalName' => 'Freshful Digital Foods (OPC) Private Limited',
       'url' => url('/'),
       'logo' => asset('assets/image/logo.png'),
       'telephone' => '+91 8124200242',
       'email' => 'talktous@freshful.in',
       'sameAs' => [
           'https://www.instagram.com/freshfulfood',
           'https://www.facebook.com/freshfulfood/',
           'https://x.com/Freshfullfoods',
           'https://www.youtube.com/@Freshfulfoods',
           'https://www.linkedin.com/company/freshfulfoods/home/',
       ],
   ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
   </script>

   <!-- Faster first connection to the services every page uses -->
   <link rel="preconnect" href="https://fonts.googleapis.com">
   <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
   <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
   <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
   <link rel="dns-prefetch" href="https://maps.googleapis.com">

   <!-- Font: Outfit, the same family as the new home page -->
   <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300..900&display=swap" rel="stylesheet">

   <!-- swiper slider -->
   <link
     rel="stylesheet"
     href="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css" />

   <script src="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
   <!-- Bootstrap CSS -->
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
   <!-- Custom Styles -->
   <link rel="stylesheet" href="{{ url('assets/css/custom-bootstrap.css') }}">
   <link rel="stylesheet" href="{{ url('assets/css/app.css') }}">
   @stack('styles')
   <style>
     :root { --ff-font:'Outfit',system-ui,-apple-system,'Segoe UI',Roboto,sans-serif; --bs-body-font-family:var(--ff-font); --ff-accent-gradient:linear-gradient(110deg,#F70202 0%,#FF5500 100%); --ff-accent:#F70202; --ff-accent-end:#FF5500; --ff-ink:#1c1c1e; --ff-muted:#6b6b70; --ff-line:#ece7e3; --ff-warm:#f7f6f4; }
     html { scroll-behavior:smooth; }
     body { font-family:var(--ff-font); color:var(--ff-ink); -webkit-font-smoothing:antialiased; text-rendering:optimizeLegibility; }
     button,input,select,textarea,.btn,.form-control,.form-select,.form-label { font-family:inherit; }
     :focus-visible { outline:2px solid #F70202; outline-offset:2px; }
     @media(prefers-reduced-motion:reduce) { html { scroll-behavior:auto; } *,*::before,*::after { animation-duration:.001ms!important; transition-duration:.001ms!important; } }
     .top-header { background:var(--ff-accent-gradient)!important; }
     .btn-primary,.btn-danger,.btn-theme,.btn-primary-alt,.ff-add,.ff-btn-primary { background:var(--ff-accent-gradient)!important; border-color:#f83208!important; color:#fff!important; }
     .btn-primary:hover,.btn-danger:hover,.btn-theme:hover,.btn-primary-alt:hover,.ff-add:hover,.ff-btn-primary:hover { filter:brightness(.96) saturate(1.08); }
     /* The compare feature was removed: hide any leftover compare button or link. */
     .ff-compare-button,[onclick*="addProductToCompare"],#compare-page-link,#compare-badge { display:none!important; }

     /* ---------- Header ---------- */
     .ff-utility-bar { background:#1c1c1e; color:#fff; }
     .ff-utility-bar .container-fluid { display:flex; align-items:center; justify-content:space-between; gap:1rem; min-height:36px; }
     .ff-utility-contact,.ff-utility-links { display:flex; align-items:center; gap:clamp(.8rem,1.8vw,1.6rem); min-width:0; }
     .ff-utility-bar a { color:rgba(255,255,255,.88)!important; font-size:.82rem; font-weight:500; line-height:1.2; text-decoration:none; white-space:nowrap; transition:color .18s; }
     .ff-utility-bar a:hover { color:#ff7a45!important; }
     .ff-utility-contact i { margin-right:.3rem; color:#ff5500; }
     .ff-utility-social { display:flex; align-items:center; gap:.15rem; margin-left:.35rem; padding-left:1rem; border-left:1px solid rgba(255,255,255,.2); }
     .ff-utility-social a { display:grid; place-items:center; width:28px; height:28px; border-radius:50%; font-size:.95rem; }
     .ff-utility-social a:hover { background:rgba(255,255,255,.12); color:#fff!important; }
     #myHeader.ff-site-header { position:sticky; top:0; z-index:1030; padding-top:.65rem; background:rgba(255,255,255,.97); border-bottom:1px solid var(--ff-line); transition:box-shadow .25s; }
     @supports (backdrop-filter:blur(8px)) { #myHeader.ff-site-header { background:rgba(255,255,255,.9); backdrop-filter:saturate(1.5) blur(12px); -webkit-backdrop-filter:saturate(1.5) blur(12px); } }
     #myHeader.ff-site-header.sticky { box-shadow:0 8px 28px rgba(28,28,30,.1); }
     .ff-header-row { display:grid; grid-template-columns:minmax(150px,200px) minmax(190px,250px) auto minmax(220px,1fr) auto; align-items:center; gap:clamp(.65rem,1.4vw,1.25rem); min-height:74px; padding-bottom:.65rem; }
     .ff-brand { display:flex; align-items:center; min-width:0; }
     .ff-brand .logoImg { display:block; width:auto; max-width:100%; max-height:58px; object-fit:contain; }
     .ff-header-location,.ff-header-store { display:flex; align-items:center; gap:.65rem; min-width:0; padding:.4rem .75rem .4rem .45rem; border:1px solid transparent; border-radius:999px; background:transparent; color:var(--ff-ink); text-align:left; text-decoration:none; transition:background .18s,border-color .18s; }
     .ff-header-location { cursor:pointer; background:var(--ff-warm); border-color:var(--ff-line); }
     .ff-header-location > .bi,.ff-header-store > .bi { display:grid; place-items:center; flex:none; width:36px; height:36px; border-radius:50%; background:#fff0ec; color:#F70202; font-size:1.05rem; }
     .ff-header-location-copy,.ff-header-store > span { display:grid; min-width:0; gap:.1rem; }
     .ff-header-location-copy strong,.ff-header-store strong { overflow:hidden; color:var(--ff-ink); font-size:.9rem; font-weight:700; line-height:1.15; text-overflow:ellipsis; white-space:nowrap; }
     .ff-header-location-copy small { display:flex; align-items:center; gap:.35rem; overflow:hidden; color:var(--ff-muted); font-size:.76rem; line-height:1.15; text-overflow:ellipsis; white-space:nowrap; }
     .ff-header-location-copy small .bi { flex:none; color:#8a8a90; font-size:.62rem; }
     .ff-header-store:hover,.ff-header-location:hover { background:#fff0ec; border-color:#ffd3c4; color:#e83418; }
     .ff-header-search { min-width:0; }
     #myHeader .ff-header-search .input-group { min-height:48px; border:1px solid transparent!important; border-radius:999px!important; background:var(--ff-warm); box-shadow:none; transition:background .2s,box-shadow .2s,border-color .2s; }
     #myHeader .ff-header-search .input-group .input-group-text,#myHeader .ff-header-search #search_product { background:transparent!important; }
     #myHeader .ff-header-search .input-group:focus-within { background:#fff; border-color:#ff7b51!important; box-shadow:0 0 0 4px rgba(247,2,2,.09); }
     #myHeader .ff-header-search #search_product { min-width:0; min-height:42px; box-shadow:none!important; font-size:.95rem; }
     .ff-header-actions { display:flex; align-items:center; justify-content:flex-end; gap:clamp(.35rem,.8vw,.7rem); }
     #myHeader .ff-header-actions .header-btn { display:inline-flex; align-items:center; justify-content:center; gap:.5rem; min-height:44px; padding:.45rem 1.05rem; border:1px solid var(--ff-line)!important; border-radius:999px!important; background:#fff; color:var(--ff-ink); font-size:.9rem; font-weight:600; text-decoration:none; white-space:nowrap; transition:background .18s,border-color .18s,box-shadow .18s,transform .18s; }
     #myHeader .ff-header-actions .header-btn i { color:#59595e; font-size:1.15rem; }
     #myHeader .ff-header-actions .header-btn:hover { background:#fff0ec; border-color:#ffd3c4!important; color:#e83418; transform:translateY(-1px); box-shadow:0 6px 16px rgba(247,2,2,.12); }
     #myHeader .ff-header-actions .header-btn:hover i { color:#e83418; }
     #myHeader .ff-header-actions .ff-cart-link { border-color:transparent!important; background:var(--ff-accent-gradient); color:#fff; box-shadow:0 8px 20px rgba(247,2,2,.24); }
     #myHeader .ff-header-actions .ff-cart-link i { color:#fff; }
     #myHeader .ff-header-actions .ff-cart-link:hover { background:var(--ff-accent-gradient); color:#fff; filter:brightness(.96) saturate(1.08); box-shadow:0 10px 24px rgba(247,2,2,.3); }
     #myHeader .ff-header-actions .ff-cart-link:hover i { color:#fff; }
     .ff-cart-badge { position:static; flex:0 0 auto; align-items:center; justify-content:center; min-width:22px; min-height:22px; padding:0 6px; border-radius:999px; background:#fff; color:#F70202; font-size:.72rem; font-weight:800; line-height:1; text-align:center; white-space:nowrap; box-sizing:border-box; }
     .ff-header-nav { border-top:1px solid #f3eeea; background:#fff; }
     .ff-header-nav .container-fluid { display:flex; align-items:center; gap:clamp(1.1rem,2.5vw,2.4rem); min-height:44px; overflow-x:auto; scrollbar-width:none; }
     .ff-header-nav .container-fluid::-webkit-scrollbar { display:none; }
     .ff-header-nav a { position:relative; display:inline-flex; flex:none; align-items:center; gap:.42rem; height:44px; border-bottom:2px solid transparent; color:#3a3a3f; font-size:.88rem; font-weight:600; text-decoration:none; white-space:nowrap; transition:color .18s,border-color .18s; }
     .ff-header-nav a:hover { border-color:#F70202; color:#e83418; }
     .ff-header-nav a i { color:#e83418; }
     @media(max-width:1199px) { .ff-header-row { grid-template-columns:minmax(130px,175px) minmax(160px,210px) auto minmax(170px,1fr) auto; gap:.65rem; } #myHeader .ff-header-actions .header-btn { gap:.35rem; padding:.4rem .8rem; font-size:.84rem; } }
     @media(max-width:991px) { .ff-utility-social { display:none; } .ff-header-row { grid-template-columns:minmax(130px,1fr) auto; grid-template-areas:'brand actions' 'location store' 'search search'; gap:.4rem 1rem; padding:.45rem 0 .75rem; } .ff-brand { grid-area:brand; } .ff-header-location { grid-area:location; } .ff-header-store { grid-area:store; justify-self:end; } .ff-header-search { grid-area:search; } .ff-header-actions { grid-area:actions; } }
     @media(max-width:575px) { .ff-utility-bar .container-fluid { align-items:flex-start; flex-direction:column; gap:.3rem; padding:.4rem 12px; } .ff-utility-contact,.ff-utility-links { flex-wrap:wrap; gap:.3rem .85rem; } .ff-utility-bar a { font-size:.8rem; } #myHeader.ff-site-header { padding-top:.2rem; } .ff-header-row { grid-template-columns:minmax(90px,1fr) auto; gap:.2rem .45rem; } .ff-brand .logoImg { max-width:145px; max-height:46px; } #myHeader .ff-header-actions { gap:.2rem; } #myHeader .ff-header-actions .header-btn { min-width:42px; min-height:42px; padding:.35rem .6rem; } #myHeader .ff-header-actions .header-btn span:not(.badge):not(.ff-cart-badge) { display:none; } #myHeader .ff-header-actions .header-btn i { font-size:1.2rem; } .ff-header-location,.ff-header-store { padding:.3rem .55rem .3rem .3rem; } .ff-header-location > .bi,.ff-header-store > .bi { width:30px; height:30px; font-size:.95rem; } .ff-header-location-copy strong { max-width:150px; font-size:.82rem; } .ff-header-location-copy small { max-width:150px; font-size:.7rem; } .ff-header-store strong { font-size:.8rem; } .ff-header-nav .container-fluid { min-height:40px; gap:1.15rem; } .ff-header-nav a { height:40px; font-size:.82rem; } }
     #search-results-dropdown { border:1px solid #ecefe9!important; border-radius:14px!important; box-shadow:0 16px 36px rgba(24,35,28,.13)!important; }
     #search-results-dropdown { box-sizing:border-box; max-height:min(420px,70vh)!important; overflow-x:hidden!important; overflow-y:auto!important; }
     .freshful-search-result { min-width:0; min-height:72px; border-bottom:1px solid #f0f0f0; }
     .freshful-search-result img, .freshful-search-placeholder { display:block; width:52px!important; height:52px!important; min-width:52px; flex:0 0 52px; object-fit:contain; border:1px solid #f0f0f0; border-radius:10px; background:#fff; }
     .freshful-search-placeholder { display:grid; place-items:center; color:#f53b13; background:#fff5f1; font-size:1.2rem; }
     .freshful-search-placeholder[hidden], .freshful-search-result img[hidden] { display:none!important; }
     .login-modal-dialog { max-width:430px; }
     .login-modal-content { overflow:hidden; border:0; border-radius:22px; box-shadow:0 24px 80px rgba(17,30,22,.2); }
     .login-modal-content .modal-header { padding:1rem 1.25rem; border-bottom:1px solid #edf0e8; background:#fff; }
     .login-modal-content .modal-body { padding:1.2rem 1.4rem 1.5rem; background:#fff; }
     .login-modal-content .modal-body { max-height:calc(100vh - 115px); overflow-y:auto; }
     .login-modal-content [hidden] { display:none!important; }
     .ff-login-progress { display:flex; gap:6px; margin:-.45rem 0 1.2rem; }
     .ff-login-progress span { height:4px; flex:1; border-radius:99px; background:#ecefea; }
     .ff-login-progress span.active { background:linear-gradient(90deg,#f22b13,#ff6b24); }
     .ff-otp-sent-note { display:flex; align-items:center; gap:.5rem; margin-bottom:1rem; padding:.8rem; border:1px solid #f1e6df; border-radius:13px; background:#fff8f4; color:#536057; font-size:.88rem; }
     .ff-otp-sent-note > i { color:#ee3a19; font-size:1.1rem; }
     .ff-otp-sent-note > span { flex:1; }
     .ff-otp-sent-note .btn-link,.ff-onboarding-form .btn-link { color:#e73816; font-weight:700; text-decoration:none; }
     .ff-verified-mobile { display:flex; align-items:center; gap:.5rem; margin:0 0 1rem; padding:.7rem .85rem; border:1px solid #dcefe1; border-radius:12px; background:#f4fbf5; color:#627168; font-size:.83rem; }
     .ff-verified-mobile > i { color:#20954d; }
     .ff-verified-mobile strong { margin-left:auto; color:#26322a; font-weight:750; }
     .ff-otp-input { letter-spacing:.55rem; font-size:1.15rem; font-weight:750; }
     .ff-onboarding-section-title { display:flex; align-items:center; gap:.7rem; margin:1.2rem 0 .85rem; padding-bottom:.75rem; border-bottom:1px solid #edf0e8; }
     .ff-onboarding-section-title:first-child { margin-top:0; }
     .ff-onboarding-section-title > span { display:grid; place-items:center; width:30px; height:30px; flex:0 0 30px; border-radius:50%; background:#fff1eb; color:#e73816; font-size:.85rem; font-weight:800; }
     .ff-onboarding-section-title strong,.ff-onboarding-section-title small { display:block; }
     .ff-onboarding-section-title strong { color:#26322a; font-size:.96rem; }
     .ff-onboarding-section-title small { margin-top:1px; color:#78837b; font-size:.78rem; }
     .ff-onboarding-form .form-control,.ff-onboarding-form .form-select { min-height:46px; border-color:#e4e9e3; border-radius:11px; }
     .ff-onboarding-form textarea.form-control { min-height:76px; }
     .ff-onboarding-form .form-control:focus,.ff-onboarding-form .form-select:focus { border-color:#ff8a68; box-shadow:0 0 0 3px rgba(245,59,19,.1); }
     .location-modal-content { border:0; border-radius:22px; box-shadow:0 24px 80px rgba(17,30,22,.18); }
     .location-modal-content .modal-header { padding:1.2rem 1.4rem .4rem; border:0; }
     .location-modal-content .modal-body { padding:1rem 1.4rem 1.6rem; }
     .ff-location-search { position:relative; }
     .ff-location-search > .bi-search { position:absolute; z-index:2; left:1rem; top:50%; transform:translateY(-50%); color:#f53b13; pointer-events:none; }
     #locationSearchInput { width:100%; min-height:52px; padding-left:3rem!important; border:1px solid #e6eae4!important; border-radius:13px!important; box-shadow:none!important; }
     #locationSearchInput:focus { border-color:#ff8a68!important; box-shadow:0 0 0 3px rgba(245,59,19,.12)!important; }
     .freshful-header-map { display:block; width:100%; height:300px; min-height:260px; overflow:hidden; border:1px solid #e7ebe6; border-radius:14px; background:#f2f5f0; }
     #location-dropdown .location-modal-dialog { max-width:740px!important; }
     .pac-container, #location-dropdown .pac-container { z-index:2000!important; border-radius:0 0 12px 12px; box-shadow:0 10px 24px rgba(20,30,25,.15); }
     @media(max-width:767px) { .freshful-header-map { height:235px; min-height:235px; } #location-dropdown .location-modal-dialog { width:calc(100% - 1rem)!important; margin:.5rem auto!important; } #location-dropdown .location-modal-content { max-height:calc(100vh - 1rem); } }
     #location-status { color:#68736b; }
     /* ---------- Footer ---------- */
     #footer.ff-footer { position:relative; margin-top:3rem!important; padding:0; border:0; background:var(--ff-warm); color:var(--ff-ink); font-family:var(--ff-font); }
     #footer.ff-footer::before { content:''; display:block; height:4px; background:var(--ff-accent-gradient); }
     #footer.ff-footer a { color:#4b4b50; text-decoration:none; transition:color .18s; }
     #footer.ff-footer a:hover { color:#F70202; }
     .ff-foot-main { padding:3rem 0 2.2rem; }
     .ff-foot-grid { display:grid; grid-template-columns:minmax(260px,1.55fr) repeat(3,minmax(130px,1fr)) minmax(240px,1.15fr); gap:2.2rem clamp(1.4rem,3vw,3rem); align-items:start; }
     .ff-foot-logo { display:inline-block; margin-bottom:1rem; }
     .ff-foot-logo img { display:block; width:auto; max-width:190px; max-height:70px; object-fit:contain; }
     .ff-foot-legal { margin:0 0 .5rem; color:var(--ff-ink); font-size:.95rem; font-weight:800; letter-spacing:.01em; line-height:1.35; }
     .ff-foot-about { max-width:340px; margin:0 0 1.1rem; color:var(--ff-muted); font-size:.92rem; line-height:1.6; }
     .ff-foot-contact { margin:0 0 1.2rem; padding:0; list-style:none; display:grid; gap:.55rem; }
     .ff-foot-contact li { display:flex; align-items:flex-start; gap:.65rem; color:#4b4b50; font-size:.9rem; line-height:1.4; }
     .ff-foot-contact i { flex:none; display:grid; place-items:center; width:28px; height:28px; border-radius:50%; background:#fff0ec; color:#F70202; font-size:.8rem; }
     .ff-foot-title { margin:0 0 1rem; color:var(--ff-ink); font-size:.82rem; font-weight:800; letter-spacing:.12em; text-transform:uppercase; }
     .ff-foot-title::after { content:''; display:block; width:26px; height:3px; margin-top:.5rem; border-radius:3px; background:var(--ff-accent-gradient); }
     .ff-foot-list { margin:0; padding:0; list-style:none; display:grid; gap:.6rem; }
     .ff-foot-list a { display:inline-block; font-size:.93rem; font-weight:500; }
     .ff-foot-list a:hover { transform:translateX(3px); }
     .ff-social { display:flex; flex-wrap:wrap; gap:.6rem; }
     #footer.ff-footer .ff-social a { --brand:#1c1c1e; display:grid; place-items:center; width:44px; height:44px; border:1px solid #e4dfda; border-radius:14px; background:#fff; color:#3a3a3f; font-size:1.2rem; box-shadow:0 2px 6px rgba(28,28,30,.05); transition:transform .2s,box-shadow .2s,background .2s,color .2s,border-color .2s; }
     #footer.ff-footer .ff-social a:hover { transform:translateY(-4px); border-color:transparent; background:var(--brand); color:#fff; box-shadow:0 10px 20px -6px var(--brand); }
     #footer.ff-footer .ff-social a.is-facebook { --brand:#1877F2; }
     #footer.ff-footer .ff-social a.is-instagram:hover { background:linear-gradient(45deg,#f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%); box-shadow:0 10px 20px -6px #dc2743; }
     #footer.ff-footer .ff-social a.is-x { --brand:#000; }
     #footer.ff-footer .ff-social a.is-youtube { --brand:#FF0000; }
     #footer.ff-footer .ff-social a.is-linkedin { --brand:#0A66C2; }
     .ff-foot-care { position:relative; overflow:hidden; padding:1.5rem; border-radius:22px; background:var(--ff-accent-gradient); color:#fff; box-shadow:0 18px 36px -16px rgba(247,2,2,.55); }
     .ff-foot-care::after { content:''; position:absolute; right:-46px; top:-46px; width:150px; height:150px; border-radius:50%; background:rgba(255,255,255,.14); }
     .ff-foot-care h3 { position:relative; margin:0 0 .3rem; color:#fff; font-size:1.25rem; font-weight:800; line-height:1.2; }
     .ff-foot-care p { position:relative; margin:0 0 .35rem; color:rgba(255,255,255,.92); font-size:.9rem; line-height:1.5; }
     .ff-foot-care .ff-foot-hours { display:flex; align-items:center; gap:.6rem; margin:.8rem 0 1.1rem; font-size:.9rem; font-weight:600; line-height:1.4; }
     #footer.ff-footer .ff-foot-care .ff-foot-call { position:relative; display:inline-flex; align-items:center; gap:.5rem; padding:.7rem 1.1rem; border-radius:999px; background:#fff; color:#e83418; font-size:.9rem; white-space:nowrap; font-weight:800; box-shadow:0 8px 18px rgba(0,0,0,.14); }
     #footer.ff-footer .ff-foot-care .ff-foot-call:hover { color:#F70202; transform:translateY(-2px); }
     .ff-foot-bottom { border-top:1px solid #e6e1dc; background:#fff; }
     .ff-foot-bottom .container-fluid { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.5rem 1.5rem; min-height:58px; padding-top:.75rem; padding-bottom:.75rem; }
     .ff-foot-bottom p { margin:0; color:var(--ff-muted); font-size:.84rem; }
     .ff-foot-badges { display:flex; flex-wrap:wrap; align-items:center; gap:.5rem .9rem; color:var(--ff-muted); font-size:.82rem; }
     .ff-foot-badges span { display:inline-flex; align-items:center; gap:.4rem; padding:.3rem .7rem; border:1px solid #ebe6e1; border-radius:999px; background:var(--ff-warm); font-weight:600; }
     .ff-foot-badges i { color:#F70202; }
     @media(max-width:1199px) { .ff-foot-grid { grid-template-columns:1.4fr 1fr 1fr 1fr; } .ff-foot-care { grid-column:1 / -1; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:1rem; } .ff-foot-care .ff-foot-hours { margin:.4rem 0 0; } }
     @media(max-width:767px) { .ff-foot-main { padding:2.2rem 0 1.6rem; } .ff-foot-grid { grid-template-columns:1fr 1fr; gap:1.8rem 1.2rem; } .ff-foot-brand { grid-column:1 / -1; } .ff-foot-care { flex-direction:column; align-items:flex-start; } .ff-foot-bottom .container-fluid { justify-content:center; text-align:center; } }
     @media(max-width:340px) { .ff-foot-grid { grid-template-columns:1fr; } }
     .ff-cart-float { position:fixed; z-index:1040; left:50%; right:auto; bottom:16px; transform:translateX(-50%); width:min(640px,calc(100% - 24px)); }
     .ff-cart-float[hidden] { display:none; }
     .ff-cart-float-inner { display:flex; align-items:center; gap:12px; padding:10px 12px 10px 18px; border:1px solid rgba(255,255,255,.1); border-radius:20px; background:#202d50; color:#fff; box-shadow:0 12px 34px rgba(23,32,57,.24); }
     .ff-cart-float-icon { display:grid; place-items:center; width:35px; height:35px; border-radius:50%; background:rgba(255,255,255,.12); color:#fff; }
     .ff-cart-float-inner strong { display:flex; align-items:center; gap:10px; margin-right:auto; font-size:.95rem; }
     .ff-cart-float-sep { color:rgba(255,255,255,.45); }
     .ff-cart-float-inner button:not(.ff-cart-float-close) { padding:.65rem 1.15rem; border:0; border-radius:12px; background:var(--ff-accent-gradient); color:#fff; font-weight:800; }
     .ff-cart-float-inner .ff-cart-float-close { display:grid; place-items:center; width:34px; height:34px; border:0; border-radius:50%; background:rgba(255,255,255,.15); color:#fff; }
     .ff-cart-drawer { --bs-offcanvas-width:min(920px,100vw); border:0; box-shadow:-15px 0 50px rgba(20,30,25,.13); }
     .ff-cart-drawer-header { padding:1.25rem clamp(1rem,3vw,2rem); border-bottom:1px solid #edf0e8; }
     .ff-cart-drawer-header h2 { margin:0; color:#202923; font-size:1.45rem; font-weight:850; }
     .ff-cart-drawer-body { padding:clamp(1rem,3vw,2rem); background:#f5f6f5; }
     .ff-cart-drawer-grid { display:grid; grid-template-columns:minmax(0,1fr) minmax(260px,340px); align-items:start; gap:1rem; }
     .ff-cart-list,.ff-cart-summary { padding:1rem; border:1px solid #ecefeb; border-radius:19px; background:#fff; box-shadow:0 8px 25px rgba(26,38,29,.055); }
     .ff-cart-row { display:grid; grid-template-columns:64px minmax(0,1fr) auto; align-items:center; gap:.85rem; padding:.85rem 0; border-bottom:1px solid #edf0ee; }
     .ff-cart-row:first-child { padding-top:0; }
     .ff-cart-row:last-child { border-bottom:0; }
     .ff-cart-row-image { width:64px; height:64px; border-radius:13px; object-fit:cover; background:#f3f5f2; }
     .ff-cart-row-title { display:block; color:#202923; font-weight:750; line-height:1.25; }
     .ff-cart-row-meta { display:block; margin-top:.18rem; color:#77817a; font-size:.8rem; }
     .ff-cart-row-price { margin-top:.18rem; color:#ee2814; font-size:.88rem; font-weight:800; }
     .ff-cart-row-tools { display:flex; align-items:center; gap:.6rem; }
     .ff-cart-row-total { min-width:75px; color:#202923; font-weight:800; text-align:right; }
     .ff-cart-stepper { display:flex; align-items:center; gap:.25rem; padding:.18rem; border:1px solid #e7ebe7; border-radius:999px; }
     .ff-cart-stepper button { display:grid; place-items:center; width:27px; height:27px; border:0; border-radius:50%; background:#f1f4f0; color:#202923; font-weight:800; }
     .ff-cart-stepper span { min-width:1.1rem; text-align:center; font-size:.88rem; font-weight:750; }
     .ff-cart-remove { border:0; background:transparent; color:#df4a45; }
     .ff-cart-summary h3 { margin:0 0 1rem; color:#202923; font-size:1.1rem; font-weight:850; }
     .ff-cart-promo-button { width:100%; margin-bottom:.9rem; padding:.65rem; border:1px dashed #17a45a; border-radius:999px; background:#fff; color:#11874a; font-weight:750; }
     .ff-cart-summary-line { display:flex; justify-content:space-between; gap:1rem; padding:.48rem 0; color:#59635c; font-size:.92rem; }
     .ff-cart-summary-total { margin-top:.25rem; padding-top:.8rem; border-top:1px solid #e9ece9; color:#202923; font-size:1.02rem; }
     .ff-cart-summary-total strong:last-child { color:#ef2414; }
     .ff-cart-checkout { display:block; width:100%; margin-top:.9rem; padding:.82rem 1rem; border:0; border-radius:999px; background:var(--ff-accent-gradient); color:#fff!important; font-weight:800; text-align:center; text-decoration:none; }
     .ff-cart-secure { margin:.75rem 0 0; color:#7d887f; font-size:.78rem; text-align:center; }
     .ff-continue-shopping { display:inline-flex; margin-top:1rem; color:#5b6a60; font-size:.88rem; text-decoration:none; }
     @media(max-width:700px) { .ff-cart-drawer-grid { grid-template-columns:1fr; } .ff-cart-summary { order:-1; } .ff-cart-float { bottom:10px; left:50%; right:auto; width:calc(100% - 20px); transform:translateX(-50%); } .ff-cart-float-inner { gap:8px; padding:8px; border-radius:15px; } .ff-cart-float-inner strong { gap:6px; font-size:.8rem; } .ff-cart-float-inner button:not(.ff-cart-float-close) { padding:.65rem .8rem; font-size:.8rem; } .ff-cart-float-close { display:none!important; } }
     @media(max-width:420px) { .ff-cart-row { grid-template-columns:52px minmax(0,1fr); gap:.65rem; } .ff-cart-row-image { width:52px; height:52px; } .ff-cart-row-tools { grid-column:2; justify-content:space-between; } .ff-cart-row-total { text-align:left; } }

     @media(max-width:575px) { .login-modal-dialog { margin:.75rem; } .login-modal-content .modal-body { padding:1rem; } }

     .text-primary-alt { color:#e73816!important; }
     .nav-pills { justify-content:center; }
     #pills-password-tab,#pills-password { display:none!important; }
     .login-modal-content .btn-primary { background:var(--ff-accent-gradient)!important; border:0!important; color:#fff!important; }
     .login-modal-content .nav-pills .nav-link { color:#333; border-radius:999px!important; }
     .login-modal-content .btn { transition:transform .2s,box-shadow .2s; }
     .login-modal-content .btn:hover { transform:translateY(-1px); box-shadow:0 7px 18px rgba(247,2,2,.15); }
     .location-dropdown { position:fixed!important; inset:0!important; z-index:1080!important; width:100%!important; height:100%!important; overflow-x:hidden!important; overflow-y:auto!important; background:rgba(20,28,23,.42); }
     #location-dropdown .location-modal-dialog { width:calc(100% - 2rem)!important; max-width:520px!important; margin:1rem auto!important; }
     #location-dropdown .location-modal-content { width:100%!important; min-height:0!important; height:auto!important; max-height:calc(100vh - 2rem); overflow:auto; background:#fff!important; }
     #location-dropdown .modal-header { padding:1.2rem 1.3rem .5rem!important; }
     #location-dropdown .modal-body { padding:1rem 1.3rem 1.4rem!important; }
     .login-modal-content .modal-dialog { max-width:430px; }
     #myHeader .input-group { min-height:48px; border-color:#e6eae4!important; transition:box-shadow .2s,border-color .2s; }
     #myHeader .input-group:focus-within { border-color:#ff7b51!important; box-shadow:0 0 0 4px rgba(247,2,2,.09); }
     #search_product { min-height:42px; box-shadow:none!important; }
     #search-results-dropdown { max-height:420px!important; padding:.45rem!important; }
     .ff-search-result img { width:58px!important; height:58px!important; object-fit:cover; background:#f5f6f2; }
     .location-modal-content { animation:ff-simple-fade .16s ease-out both; }
     @keyframes ff-simple-fade { from { opacity:0; } to { opacity:1; } }
     .ff-product-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:1rem; }
     .ff-product-card { display:flex; min-width:0; height:100%; flex-direction:column; overflow:hidden; border:1px solid #edf0e8; border-radius:18px; background:#fff; box-shadow:0 6px 20px rgba(31,49,37,.045); transition:transform .2s,box-shadow .2s; }
     .ff-product-card:hover { transform:translateY(-3px); box-shadow:0 14px 30px rgba(31,49,37,.1); }
     .ff-product-photo { position:relative; display:block; aspect-ratio:1.25; overflow:hidden; background:#f5f6f2; }
     .ff-product-photo img { width:100%; height:100%; object-fit:cover; transition:transform .3s; }
     .ff-product-card:hover .ff-product-photo img { transform:scale(1.035); }
     .ff-product-tag { position:absolute; top:.65rem; left:.65rem; padding:.32rem .6rem; border-radius:999px; background:#fff0eb; color:#cf3216; font-size:.72rem; font-weight:750; }
     .ff-product-info { display:flex; flex:1; flex-direction:column; padding:.9rem; }
     .ff-product-name { display:-webkit-box; min-height:2.8em; overflow:hidden; margin:.1rem 0 .35rem; color:#202923!important; font-size:.98rem; font-weight:750; line-height:1.4; text-decoration:none; -webkit-line-clamp:2; -webkit-box-orient:vertical; }
     .ff-product-unit { color:#647168; font-size:.82rem; }
     .ff-product-bottom { display:flex; align-items:center; justify-content:space-between; gap:.45rem; margin-top:auto; padding-top:.9rem; }
     .ff-price { color:#151515; font-size:1.1rem; font-weight:800; white-space:nowrap; }
     .ff-price small { display:block; color:#879188; font-size:.72rem; font-weight:500; }
     .ff-old-price { text-decoration:line-through; }
     .ff-add { flex:none; border:0; border-radius:999px; padding:.55rem .8rem; background:var(--ff-accent-gradient); color:#fff; font-size:.82rem; font-weight:700; }
     .ff-quantity-stepper { display:flex; align-items:center; gap:.35rem; padding:.18rem; border:1px solid #f4cec2; border-radius:999px; background:#fff8f5; }
     .ff-quantity-stepper[hidden] { display:none; }
     .ff-quantity-button { display:grid; place-items:center; width:28px; height:28px; border:0; border-radius:50%; background:var(--ff-accent-gradient); color:#fff; font-size:1.05rem; font-weight:700; line-height:1; }
     .ff-quantity-value { min-width:1rem; color:#151515; font-weight:800; text-align:center; }
     @media(max-width:1199px) { .ff-product-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } }
     @media(max-width:767px) { .ff-product-grid { grid-template-columns:repeat(2,minmax(0,1fr)); gap:.65rem; } .ff-product-info { padding:.65rem; } .ff-product-name { font-size:.86rem; } .ff-product-bottom { flex-wrap:wrap; } .ff-price { font-size:1rem; } .ff-add { padding:.45rem .65rem; } }
     .ff-compare-page .ff-compare-table { border-radius:18px; }
     .ff-compare-page .ff-compare-table td { min-width:190px; vertical-align:middle; }
     .ff-compare-page .ff-compare-table th { min-width:155px; font-weight:750; }
     .ff-compare-page .ff-compare-table tbody tr:nth-child(even) td { background:#fffaf8; }
     .ff-compare-page .ff-compare-table a { color:#252a26; font-weight:700; text-decoration:none; }
     .ff-compare-page .ff-compare-table a:hover { color:#e53b18; }
     .catalog-products .btn-primary,.catalog-products .btn-primary-alt { background:var(--ff-accent-gradient)!important; border-color:#f83208!important; }
     .catalog-products .pro-card-price h4,.catalog-products .pro-card-price .text-primary { color:#151515!important; }
     .catalog-products .pro-card { height:100%; border:1px solid #edf0e8; border-radius:17px; overflow:hidden; box-shadow:0 5px 18px rgba(31,49,37,.045); transition:transform .2s,box-shadow .2s; }
     .catalog-products .pro-card:hover { transform:translateY(-3px); box-shadow:0 12px 28px rgba(31,49,37,.1); }
     #search-results-dropdown { padding:.35rem; animation:ff-simple-fade .12s ease-out both; }
     .ff-search-result { min-height:66px; border-radius:10px; transition:background .16s,transform .16s; }
     .ff-search-result:hover { background:#fff7f3!important; transform:translateX(2px); }
     .ff-search-result img { width:50px; height:50px; flex:none; border-radius:9px; object-fit:cover; background:#f5f6f2; }
     @media(max-width:575px) { .catalog-products { gap:.55rem!important; } .freshful-catalog { padding-left:12px; padding-right:12px; } }
   </style>
 </head>

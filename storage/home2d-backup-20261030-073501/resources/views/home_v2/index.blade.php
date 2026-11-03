@extends('layout.master')

@section('title', 'Freshful | Fresh food for every day')
@section('meta_description', 'Shop fresh meat, seafood, fruits, vegetables and everyday groceries from Freshful. Find your favourites and order online.')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300..900&display=swap" rel="stylesheet">
<style>
/* Freshful home v2 (update 2). Scoped to .ffh, plus the chip bar and the floating cart at the end. */
.ffh{--ink:#1c1c1e;--muted:#5f5f63;--line:#ece7e3;--bg:#f7f6f4;--accent:linear-gradient(110deg,#F70202 0%,#FF5500 100%);font-family:'Outfit',system-ui,-apple-system,'Segoe UI',sans-serif;color:var(--ink);background:var(--bg);padding-bottom:48px}
.ffh *{box-sizing:border-box}
.ffh a{text-decoration:none}
.ffh .ffh-wrap{max-width:1440px;margin:0 auto;padding:0 20px}
.ffh .ffh-section{margin-top:40px}
.ffh .ffh-head{display:flex;justify-content:space-between;align-items:flex-end;gap:16px;flex-wrap:wrap;margin-bottom:18px}
.ffh .ffh-head h2{margin:0;font-size:clamp(1.45rem,2.4vw,1.9rem);font-weight:800;letter-spacing:-.01em;color:var(--ink)}
.ffh .ffh-head p{margin:4px 0 0;color:var(--muted);font-size:15px}
.ffh .ffh-link{display:inline-flex;align-items:center;gap:6px;color:var(--ink);font-weight:700;font-size:15px;border:1px solid #e3dfdb;border-radius:999px;padding:9px 16px;background:#fff}
.ffh .ffh-link:hover{border-color:var(--ink)}
.ffh .ffh-round{width:42px;height:42px;border-radius:50%;border:0;background:#fff;color:var(--ink);box-shadow:0 6px 16px rgba(0,0,0,.14);display:grid;place-items:center;cursor:pointer}

/* Hero: featured product (left) + Express and offer tiles (right) */
.ffh .ffh-hero{display:grid;grid-template-columns:minmax(0,2fr) minmax(0,1fr);gap:20px;margin-top:22px}
.ffh .ffh-hero-main{position:relative;border-radius:24px;overflow:hidden;background:#fff1ea;min-height:330px}
.ffh .ffh-hero-track{display:flex;height:100%;overflow-x:auto;scroll-snap-type:x mandatory;scrollbar-width:none;scroll-behavior:smooth}
.ffh .ffh-hero-track::-webkit-scrollbar{display:none}
.ffh .ffh-hero-slide{flex:0 0 100%;scroll-snap-align:start;display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);min-height:330px}
.ffh .ffh-hero-copy{padding:36px 34px 44px;display:flex;flex-direction:column;justify-content:center;gap:14px;min-width:0}
.ffh .ffh-hero-kicker{align-self:flex-start;background:#fff;color:#b42808;font-size:13px;font-weight:700;padding:6px 12px;border-radius:999px}
.ffh .ffh-hero-copy h1{margin:0;font-size:clamp(1.9rem,3.2vw,2.75rem);line-height:1.05;letter-spacing:-.02em;font-weight:800;color:var(--ink)}
.ffh .ffh-hero-copy p{margin:0;font-size:16px;line-height:1.55;color:#4d4d4d;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
.ffh .ffh-hero-cta{display:flex;align-items:center;gap:18px;flex-wrap:wrap;margin-top:6px}
.ffh .ffh-btn{display:inline-flex;align-items:center;gap:8px;background:var(--accent);color:#fff;font-weight:700;font-size:16px;padding:14px 24px;border-radius:999px;box-shadow:0 10px 22px rgba(247,2,2,.22);transition:transform .2s,filter .2s}
.ffh .ffh-btn:hover{color:#fff;filter:brightness(.96);transform:translateY(-1px)}
.ffh .ffh-hero-price{font-size:15px;color:#4d4d4d}
.ffh .ffh-hero-price strong{font-size:24px;color:var(--ink)}
.ffh .ffh-hero-photo{display:block;position:relative;background:#f4e7df}
.ffh .ffh-hero-photo img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.ffh .ffh-hero-nav{position:absolute;left:34px;bottom:16px;display:flex;align-items:center;gap:6px;z-index:2}
.ffh .ffh-hero-nav button.dot{width:8px;height:8px;border-radius:999px;border:0;padding:0;background:rgba(28,28,30,.25);transition:width .25s}
.ffh .ffh-hero-nav button.dot.on{width:22px;background:var(--ink)}
.ffh .ffh-side{display:flex;flex-direction:column;gap:20px}
.ffh .ffh-tile{flex:1;border-radius:24px;padding:24px;display:flex;flex-direction:column;justify-content:space-between;gap:12px}
.ffh .ffh-tile h3{margin:0;font-size:21px;font-weight:800}
.ffh .ffh-tile p{margin:4px 0 0;font-size:14px}
.ffh .ffh-tile-white{background:#fff;border:1px solid var(--line)}
.ffh .ffh-tile-white p{color:#555}
.ffh .ffh-tile-icon{width:48px;height:48px;border-radius:14px;background:var(--accent);color:#fff;display:grid;place-items:center}
.ffh .ffh-tile-hot{background:var(--accent);color:#fff}
.ffh .ffh-tile-hot .k{font-size:13px;font-weight:700;letter-spacing:.06em}
.ffh .ffh-code{align-self:flex-start;display:inline-flex;align-items:center;gap:8px;border:1.5px dashed #fff;background:rgba(255,255,255,.16);color:#fff;font:inherit;font-weight:800;font-size:14px;letter-spacing:.05em;padding:0 16px;height:44px;border-radius:12px;cursor:pointer}

/* Trust strip */
.ffh .ffh-trust{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-top:16px}
.ffh .ffh-trust-item{display:flex;align-items:center;gap:12px;background:#fff;border:1px solid var(--line);border-radius:16px;padding:14px 16px}
.ffh .ffh-trust-item svg{flex:none;color:#e0300a}
.ffh .ffh-trust-item strong{display:block;font-size:15px;font-weight:700}
.ffh .ffh-trust-item small{display:block;font-size:13px;color:var(--muted)}

/* Shop by category */
.ffh .ffh-cat-panel{background:linear-gradient(135deg,#FFF1E6,#FDE6EF);border:1px solid rgba(0,0,0,.05);border-radius:28px;padding:28px 28px 26px}
.ffh .ffh-tabs{display:flex;gap:6px;background:rgba(255,255,255,.85);border:1px solid var(--line);border-radius:999px;padding:5px;flex-wrap:wrap}
.ffh .ffh-tabs button{border:0;border-radius:999px;padding:0 18px;height:40px;font:inherit;font-size:14px;font-weight:600;color:#4d4d4d;background:transparent;cursor:pointer}
.ffh .ffh-tabs button.on{background:var(--accent);color:#fff;font-weight:700}
.ffh .ffh-cats{display:grid;grid-template-columns:repeat(8,minmax(0,1fr));gap:26px 12px}
.ffh .ffh-cat{display:flex;flex-direction:column;align-items:center;gap:12px;color:var(--ink);text-align:center}
.ffh .ffh-cat[hidden]{display:none}
.ffh .ffh-cat img{width:min(150px,100%);image-rendering:auto;aspect-ratio:1;border-radius:50%;object-fit:cover;display:block;background:#f4f2ef;filter:drop-shadow(0 12px 14px rgba(40,25,15,.26));transition:transform .35s cubic-bezier(.2,.8,.2,1),filter .35s}
.ffh .ffh-cat:hover img{transform:translateY(-6px) rotate(-6deg) scale(1.04);filter:drop-shadow(0 20px 18px rgba(40,25,15,.32))}
.ffh .ffh-cat span{font-size:16px;font-weight:600;line-height:1.25}
.ffh .ffh-cat:hover span{text-decoration:underline;text-underline-offset:4px}

/* Admin banners */
.ffh .ffh-banner{position:relative;border-radius:24px;overflow:hidden;background:#fff;border:1px solid var(--line)}
.ffh .ffh-banner-track{display:flex;overflow-x:auto;scroll-snap-type:x mandatory;scrollbar-width:none;scroll-behavior:smooth}
.ffh .ffh-banner-track::-webkit-scrollbar{display:none}
.ffh .ffh-banner-slide{flex:0 0 100%;scroll-snap-align:start;display:block}
.ffh .ffh-banner-slide img{display:block;width:100%;height:clamp(150px,18vw,260px);object-fit:cover}
.ffh .ffh-banner .ffh-round{position:absolute;top:50%;transform:translateY(-50%);z-index:2}
.ffh .ffh-banner .prev{left:14px}.ffh .ffh-banner .next{right:14px}

/* Buy again */
.ffh .ffh-again{background:#fff;border:1px solid var(--line);border-radius:24px;padding:20px 22px}
.ffh .ffh-again-title{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.ffh .ffh-again-title .ic{width:34px;height:34px;border-radius:10px;background:#1c1c1e;color:#fff;display:grid;place-items:center}
.ffh .ffh-again-title h2{font-size:22px}
.ffh .ffh-again-title small{color:var(--muted);font-size:14px}
.ffh .ffh-again-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
.ffh .ffh-again-item{display:flex;align-items:center;gap:12px;border:1px solid #f0ece8;border-radius:16px;padding:10px}
.ffh .ffh-again-item img{width:62px;height:62px;border-radius:12px;object-fit:cover;flex:none;background:#f4f2ef}
.ffh .ffh-again-item .t{flex:1;min-width:0}
.ffh .ffh-again-item .t a{display:block;color:var(--ink);font-weight:600;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ffh .ffh-again-item .t small{color:var(--muted);font-size:13px}
.ffh .ffh-login-btn{display:inline-flex;align-items:center;gap:6px;border:0;border-radius:999px;background:#1c1c1e;color:#fff;font:inherit;font-weight:700;font-size:14px;height:40px;padding:0 16px;cursor:pointer}

/* Product grid and card */
.ffh .ffh-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:22px;perspective:1200px}
.ffh .ffv-card{display:flex;flex-direction:column;background:#fff;border-radius:20px;overflow:hidden;transform-style:preserve-3d;transition:transform .4s cubic-bezier(.2,.8,.2,1),box-shadow .4s;box-shadow:0 1px 2px rgba(30,20,10,.04),0 10px 24px -10px rgba(30,20,10,.14)}
.ffh .ffv-card:hover{transform:translateY(-10px) rotateX(6deg) rotateY(-6deg);box-shadow:0 34px 50px -18px rgba(30,20,10,.30),0 4px 10px rgba(30,20,10,.06)}
.ffh .ffv-photo{position:relative;display:block;aspect-ratio:1.45;overflow:hidden;background:#f4f2ef}
.ffh .ffv-photo img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .5s cubic-bezier(.2,.8,.2,1)}
.ffh .ffv-card:hover .ffv-photo img{transform:scale(1.07)}
.ffh .ffv-off{position:absolute;top:0;left:0;background:var(--accent);color:#fff;font-size:13px;font-weight:700;padding:6px 12px;border-radius:20px 0 14px 0}
.ffh .ffv-ribbon{position:absolute;top:0;right:0;color:#fff;font-size:12px;font-weight:700;padding:6px 12px;border-radius:0 20px 0 14px;max-width:60%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ffh .ffv-photo-label{position:absolute;bottom:0;left:0;background:#d81e32;color:#fff;font-size:12px;font-weight:600;padding:5px 12px;border-radius:0 12px 0 0}
.ffh .ffv-out-band{position:absolute;top:0;left:0;right:0;background:rgba(38,38,40,.86);color:#fff;text-align:center;font-size:15px;font-weight:600;padding:9px 0}
.ffh .ffv-card.is-out .ffv-photo img{filter:grayscale(1);opacity:.85}
.ffh .ffv-body{padding:14px 16px 16px;display:flex;flex-direction:column;gap:8px;flex:1}
.ffh .ffv-chips{display:flex;gap:6px;flex-wrap:wrap}
.ffh .ffv-chip{font-size:12px;font-weight:600;padding:5px 10px;border-radius:8px;line-height:1.1}
.ffh .ffv-chip-fresh{background:#e4f4e8;color:#1e6b36}
.ffh .ffv-chip-low{background:#fff1d6;color:#8a4b00}
.ffh .ffv-name{color:var(--ink);font-size:17px;font-weight:600;line-height:1.3}
.ffh .ffv-name:hover{color:var(--ink);text-decoration:underline}
.ffh .ffv-weight{display:flex;align-items:center;gap:6px;font-size:13px;color:var(--muted)}
.ffh .ffv-foot{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:auto;padding-top:10px;border-top:1px dashed #ece6e1;transition:transform .4s cubic-bezier(.2,.8,.2,1)}
.ffh .ffv-card:hover .ffv-foot{transform:translateZ(30px)}
.ffh .ffv-prices{display:flex;align-items:flex-end;gap:10px}
.ffh .ffv-mrp{line-height:1.1;color:#8a8a8a;font-size:13px}
.ffh .ffv-mrp small{display:block;font-size:10px;font-weight:700}
.ffh .ffv-price{font-size:21px;font-weight:800;line-height:1}
.ffh .ffv-foot-out{flex-direction:column;align-items:stretch}
.ffh .ffv-foot-out .ffv-price{color:#555}
.ffh .ffv-notify-row{display:flex;align-items:center;justify-content:space-between;gap:8px}
.ffh .ffv-notify-text{font-size:13px;color:#555}
.ffh .ffv-notify{display:inline-flex;align-items:center;gap:6px;border:1px solid #f1d4d4;background:#fff;color:#c41c3b;font:inherit;font-weight:700;font-size:14px;height:40px;padding:0 14px;border-radius:12px;cursor:pointer;box-shadow:0 2px 6px rgba(0,0,0,.06)}
.ffh .ffv-notify[disabled]{color:#1e6b36;border-color:#cfe8d6;cursor:default}
/* Add button and stepper: same look as the current site */
.ffh .ff-product-actions{display:flex;align-items:center;gap:8px;flex:none}
.ffh .ff-compare-button{display:grid;place-items:center;width:34px;height:34px;border:1px solid #ecebe8;border-radius:50%;background:#fff;color:#595959}
.ffh .ff-compare-button:hover{border-color:#ffbca8;color:#e63a17}
.ffh .ff-add{border:0;border-radius:999px;padding:.6rem 1.15rem;background:var(--accent);color:#fff;font-size:.88rem;font-weight:700;box-shadow:0 8px 16px rgba(247,2,2,.18)}
.ffh .ff-add:hover{filter:brightness(.96)}
.ffh .ff-quantity-stepper{display:flex;align-items:center;gap:.5rem;padding:.22rem;border:1px solid #ffd2c4;border-radius:999px;background:#fff6f2}
.ffh .ff-quantity-stepper[hidden]{display:none}
.ffh .ff-quantity-button{display:grid;place-items:center;width:30px;height:30px;border:0;border-radius:50%;background:var(--accent);color:#fff;font-size:1.1rem;font-weight:700;line-height:1}
.ffh .ff-quantity-value{min-width:1.1rem;font-weight:800;text-align:center}

/* Coupons: compact tickets, one colour each, no barcode */
.ffh .ffh-coupons{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
.ffh .ffh-coupon{position:relative;display:flex;align-items:center;justify-content:space-between;gap:12px;min-height:104px;padding:14px 18px;border-radius:16px;color:#fff;box-shadow:0 10px 20px -12px rgba(30,20,10,.35)}
.ffh .ffh-coupon::before,.ffh .ffh-coupon::after{content:"";position:absolute;top:50%;width:18px;height:18px;margin-top:-9px;border-radius:50%;background:var(--bg)}
.ffh .ffh-coupon::before{left:-9px}.ffh .ffh-coupon::after{right:-9px}
.ffh .ffh-coupon-main{min-width:0;display:flex;flex-direction:column;gap:2px}
.ffh .ffh-coupon-kicker{font-size:11px;font-weight:600;letter-spacing:.14em;text-transform:uppercase;opacity:.9}
.ffh .ffh-coupon-big{font-size:26px;font-weight:900;line-height:1.05}
.ffh .ffh-coupon-desc{font-size:13px;opacity:.95;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ffh .ffh-coupon-side{flex:none;display:flex;flex-direction:column;align-items:flex-end;gap:6px;padding-left:12px;border-left:2px dashed rgba(255,255,255,.45)}
.ffh .ffh-coupon-code{display:inline-flex;align-items:center;gap:6px;background:#fff;border:0;border-radius:8px;height:34px;padding:0 10px;font:inherit;font-size:13px;font-weight:800;letter-spacing:.05em;cursor:pointer;max-width:150px;overflow:hidden;white-space:nowrap}
.ffh .ffh-coupon-exp{font-size:11px;opacity:.9}
.ffh .ffh-coupon-refer{background:#2d2a6e}
.ffh .ffh-coupon-refer:hover{color:#fff}
.ffh .ffh-coupon-img{display:block;border-radius:16px;overflow:hidden;border:1px solid var(--line);background:#fff}
.ffh .ffh-coupon-img img{display:block;width:100%;aspect-ratio:2/1;object-fit:cover}

/* Sticky category chips with arrows (appear under the header after the category section) */
.ffh-chipbar{position:fixed;left:0;right:0;top:var(--ffh-top,0px);z-index:1029;background:#fff;border-bottom:1px solid #ebe8e4;box-shadow:0 10px 24px -16px rgba(0,0,0,.35);transform:translateY(-120%);visibility:hidden;transition:transform .28s cubic-bezier(.2,.8,.2,1),visibility .28s;font-family:'Outfit',system-ui,sans-serif}
.ffh-chipbar.on{transform:none;visibility:visible}
.ffh-chipbar .ffh-chip-wrap{max-width:1440px;margin:0 auto;padding:8px 20px;display:flex;align-items:center;gap:8px}
.ffh-chipbar .ffh-chip-track{flex:1;min-width:0;display:flex;gap:8px;overflow-x:auto;scrollbar-width:none;scroll-behavior:smooth}
.ffh-chipbar .ffh-chip-track::-webkit-scrollbar{display:none}
.ffh-chipbar .arrow{flex:none;width:36px;height:36px;border-radius:50%;border:1px solid #e3dfdb;background:#fff;color:#1c1c1e;display:grid;place-items:center;cursor:pointer}
.ffh-chipbar .arrow:hover{border-color:#1c1c1e}
.ffh-chipbar .arrow[disabled]{opacity:.35;cursor:default}
.ffh-chipbar a{flex:none;display:inline-flex;align-items:center;gap:8px;height:40px;padding:0 14px 0 6px;border-radius:999px;border:1px solid #e3dfdb;background:#fff;color:#1c1c1e;font-size:14px;font-weight:600;white-space:nowrap;text-decoration:none}
.ffh-chipbar a:hover{border-color:#1c1c1e;color:#1c1c1e}
.ffh-chipbar a.all{padding:0 18px;background:#1c1c1e;border-color:#1c1c1e;color:#fff}
.ffh-chipbar img{width:28px;height:28px;border-radius:50%;object-fit:cover}


/*ffh-v4*/ .ffh-chipbar.in-strip{position:static;transform:none;box-shadow:none;border:0;background:transparent;flex:1 1 auto;min-width:0;visibility:visible;opacity:0;pointer-events:none;transition:opacity .25s;z-index:auto;margin-left:14px}
.ffh-chipbar.in-strip.on{opacity:1;pointer-events:auto}
.ffh-chipbar.in-strip .ffh-chip-wrap{max-width:none;padding:0}
.ffh-chipbar.in-strip a{height:34px;font-size:13px;background:rgba(255,255,255,.9)}
.ffh-chipbar.in-strip a.all{background:#1c1c1e;color:#fff}
.ffh-chipbar.in-strip img{width:24px;height:24px}
.ffh-chipbar.in-strip .arrow{width:30px;height:30px}
@media (prefers-reduced-motion: reduce){.ffh .ffv-card,.ffh .ffv-card:hover,.ffh .ffv-card:hover .ffv-foot,.ffh .ffh-cat:hover img{transform:none}.ffh-chipbar{transition:none}}
@media (max-width:1200px){.ffh .ffh-cats{grid-template-columns:repeat(6,minmax(0,1fr))}.ffh .ffh-coupons{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (max-width:991px){.ffh .ffh-hero{grid-template-columns:1fr}.ffh .ffh-side{flex-direction:row}.ffh .ffh-grid,.ffh .ffh-again-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.ffh .ffh-trust{grid-template-columns:repeat(2,minmax(0,1fr))}.ffh .ffh-cats{grid-template-columns:repeat(4,minmax(0,1fr))}}
@media (max-width:575px){.ffh .ffh-wrap{padding:0 12px}.ffh .ffh-section{margin-top:28px}.ffh .ffh-hero-slide{grid-template-columns:1fr 1fr;min-height:220px}.ffh .ffh-hero-main{min-height:220px}.ffh .ffh-hero-copy{padding:18px 16px 34px;gap:8px}.ffh .ffh-hero-copy h1{font-size:1.35rem}.ffh .ffh-hero-kicker{font-size:11px}.ffh .ffh-tile{padding:18px}.ffh .ffh-hero-copy p{display:none}.ffh .ffh-btn{padding:10px 16px;font-size:14px}.ffh .ffh-hero-nav{left:16px;bottom:10px}.ffh .ffh-side{flex-direction:column}.ffh .ffh-grid{gap:10px}.ffh .ffv-body{padding:10px}.ffh .ffv-name{font-size:14px}.ffh .ffv-price{font-size:17px}.ffh .ffv-foot{flex-wrap:wrap}.ffh .ffh-cat-panel{padding:18px 12px;border-radius:20px}.ffh .ffh-cats{gap:16px 6px}.ffh .ffh-cat span{font-size:12px}.ffh .ffh-coupons,.ffh .ffh-again-grid{grid-template-columns:1fr}.ffh .ffv-card:hover{transform:none}.ffh-chipbar .ffh-chip-wrap{padding:6px 10px}.ffh-chipbar .arrow{display:none}}

/* Floating cart bar (it lives in the shared footer; only its look changes, and only on this page). */
.ff-cart-float .ff-cart-float-inner{display:flex!important;align-items:center!important;gap:14px!important;background:#1c1c1e!important;color:#fff!important;border:0!important;border-radius:999px!important;padding:8px 8px 8px 12px!important;box-shadow:0 20px 40px -12px rgba(0,0,0,.45)!important;font-family:'Outfit',system-ui,sans-serif}
.ff-cart-float .ff-cart-float-icon{width:44px;height:44px;flex:none;border-radius:50%;display:grid!important;place-items:center;background:#2e2e31!important;color:#fff!important;font-size:1.15rem}
.ff-cart-float .ffh-cart-thumbs{display:flex;flex:none;padding-left:10px}
.ff-cart-float .ffh-cart-thumbs img{width:42px;height:42px;border-radius:50%;object-fit:cover;border:3px solid #1c1c1e;margin-left:-12px;background:#2e2e31}
.ff-cart-float strong{flex:1;min-width:0;font-size:16px;font-weight:700;color:#fff!important;line-height:1.25}
.ff-cart-float .ffh-cart-sub{display:block;font-size:12px;font-weight:400;color:#c8c8c8}
.ff-cart-float .ff-cart-float-sep{opacity:.4;margin:0 8px}
.ff-cart-float button[data-bs-toggle]{border:0!important;border-radius:999px!important;background:linear-gradient(110deg,#F70202 0%,#FF5500 100%)!important;color:#fff!important;font-weight:700!important;height:48px;padding:0 22px!important;white-space:nowrap}
.ff-cart-float .ff-cart-float-close{background:transparent!important;border:0!important;color:#bdbdbd!important;width:36px;height:36px}
/*ffh-v3*/.ffh .ffv-ribbon{color:#3b2b35!important;font-weight:700}.ffh .ffv-ribbon[style*=d4ea8]{background:#dcecff!important}.ffh .ffv-ribbon[style*=b21a6]{background:#ece3ff!important}.ffh .ffv-ribbon[style*=a8174f]{background:#ffe0ec!important}.ffh .ffv-ribbon[style*=b6b5f]{background:#d9f6ea!important}.ffh .ffv-ribbon[style*=f3a8f]{background:#e2e6ff!important}.ffh .ffv-ribbon[style*=a4b00]{background:#fff0c2!important}.ffh .ffh-coupon{color:#33262e!important;box-shadow:0 12px 24px -16px rgba(120,60,40,.45)}.ffh .ffh-coupon-side{border-left-color:rgba(60,30,40,.2)!important}.ffh .ffh-coupon-code{box-shadow:0 2px 8px rgba(0,0,0,.08)}.ffh .ffh-coupons>:nth-child(5n+1){background:linear-gradient(135deg,#ffe3cf,#ffc4d6)!important}.ffh .ffh-coupons>:nth-child(5n+2){background:linear-gradient(135deg,#d9f7e8,#cde6ff)!important}.ffh .ffh-coupons>:nth-child(5n+3){background:linear-gradient(135deg,#ece0ff,#ffd5ea)!important}.ffh .ffh-coupons>:nth-child(5n+4){background:linear-gradient(135deg,#fff3c4,#ffd7bf)!important}.ffh .ffh-coupons>:nth-child(5n+5){background:linear-gradient(135deg,#d7f0ff,#e4dbff)!important}.ffh .ffh-coupons>.ffh-coupon-refer{background:linear-gradient(135deg,#e9e2ff,#d3ecff)!important}</style>
@endpush

@section('content')
@php
    $ffhMoney = fn ($v) => \App\Services\Storefront\HomeV2Data::money($v);
    $ffhCutWords = (array) config('storefront.cut_words', []);
@endphp
<main class="ffh">
    <div class="ffh-wrap">

        {{-- 1. Hero: featured product + Express + offer --}}
        <section class="ffh-hero" aria-label="Featured">
            <div class="ffh-hero-main">
                @if ($heroProducts->isNotEmpty())
                    <div class="ffh-hero-track" id="ffh-hero-track">
                        @foreach ($heroProducts as $hp)
                            @php
                                $hpStock = $stock[$hp->id] ?? null;
                                $hpCat = \Illuminate\Support\Str::lower((string) (optional($hp->category)->name ?? '').' '.$hp->product_name);
                                $hpIsCut = \Illuminate\Support\Str::contains($hpCat, $ffhCutWords);
                                $hpKicker = ($hpStock && $hpStock['fresh']) ? ($hpIsCut ? 'Cut fresh this morning' : 'Fresh today') : 'Customer favourite';
                                $hpDesc = trim(strip_tags((string) ($hp->description ?? '')));
                                if ($hpDesc === '') { $hpDesc = $hpIsCut ? 'Cleaned, cut and packed at your nearest Freshful store. At your door in 30 minutes.' : 'Picked fresh and packed at your nearest Freshful store. At your door in 30 minutes.'; }
                                $hpUnit = trim((string) (($hp->gross_quantity ?? '').' '.($hp->product_unit ?? '')));
                            @endphp
                            <div class="ffh-hero-slide">
                                <div class="ffh-hero-copy">
                                    <span class="ffh-hero-kicker">{{ $hpKicker }}</span>
                                    <h1>{{ $hp->product_name }}</h1>
                                    <p>{{ \Illuminate\Support\Str::limit($hpDesc, 140) }}</p>
                                    <div class="ffh-hero-cta">
                                        <a class="ffh-btn" href="{{ route('product_detail', ['id' => $hp->id]) }}">Order now <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                                        <span class="ffh-hero-price"><strong>{{ $ffhMoney($hp->main_price) }}</strong> {{ $hpUnit !== '' ? '/ '.$hpUnit : '' }}</span>
                                    </div>
                                </div>
                                <a class="ffh-hero-photo" href="{{ route('product_detail', ['id' => $hp->id]) }}" tabindex="-1" aria-hidden="true">
                                    <img src="{{ asset('uploads/images/products/'.$hp->product_image) }}" alt="" @if (! $loop->first) loading="lazy" @endif onerror="this.style.visibility='hidden'">
                                </a>
                            </div>
                        @endforeach
                    </div>
                    @if ($heroProducts->count() > 1)
                        <div class="ffh-hero-nav">
                            @foreach ($heroProducts as $hp)
                                <button type="button" class="dot {{ $loop->first ? 'on' : '' }}" data-ffh-hero-dot="{{ $loop->index }}" aria-label="Show {{ $hp->product_name }}"></button>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>
            <div class="ffh-side">
                <div class="ffh-tile ffh-tile-white">
                    <span class="ffh-tile-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13 2 4 14h7l-1 8 9-12h-7l1-8z"/></svg></span>
                    <div>
                        <h3>Express in 30 minutes</h3>
                        <p>From your nearest store, 7 AM to 9 PM. Or choose a time slot that suits you.</p>
                    </div>
                </div>
                @if ($firstOrderOffer)
                    <div class="ffh-tile ffh-tile-hot">
                        <span class="k">{{ ($firstOrderOffer->user_eligible ?? '') === 'new user' ? 'FIRST ORDER OFFER' : "TODAY'S OFFER" }}</span>
                        <h3>{{ rtrim(rtrim(number_format((float) $firstOrderOffer->percentage, 2), '0'), '.') }}% off{{ ($firstOrderOffer->user_eligible ?? '') === 'new user' ? ' your first order' : ((int) $firstOrderOffer->min_amount > 0 ? ' above ₹'.number_format((int) $firstOrderOffer->min_amount) : '') }}</h3>
                        <button type="button" class="ffh-code" data-ffh-copy="{{ $firstOrderOffer->title }}">{{ $firstOrderOffer->title }} <i class="bi bi-copy" aria-hidden="true"></i></button>
                    </div>
                @else
                    <a class="ffh-tile ffh-tile-hot" href="{{ url('/refer_earn') }}" style="color:#fff">
                        <span class="k">REFER &amp; EARN</span>
                        <h3>Refer a friend</h3>
                        <p>Earn wallet money when their first order is ₹400 or more.</p>
                    </a>
                @endif
            </div>
        </section>

        <section class="ffh-trust" aria-label="Why Freshful">
            <div class="ffh-trust-item"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 21c0-9 6-15 16-16-1 10-7 16-16 16z"/><path d="M5 21 14 12"/></svg><div><strong>Never older than 2 days</strong><small>Stock is sold fresh or removed</small></div></div>
            <div class="ffh-trust-item"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6l-8-3z"/><path d="m9 12 2 2 4-4"/></svg><div><strong>Weighed before dispatch</strong><small>Pay only for what you get</small></div></div>
            <div class="ffh-trust-item"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18"/></svg><div><strong>Cash, UPI or card</strong><small>Pay online or at your door</small></div></div>
            <div class="ffh-trust-item"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9 4.5 4h15L21 9"/><path d="M4 9v11h16V9"/><path d="M3 9h18"/></svg><div><strong>{{ $storeCount > 0 ? $storeCount.' stores near you' : 'Stores near you' }}</strong><small>Ambur, Vaniyambadi, Bengaluru</small></div></div>
        </section>

        {{-- 2. Shop by category (tabs from the main categories) --}}
        @if ($categories->isNotEmpty())
            <section class="ffh-section ffh-cat-panel" aria-labelledby="ffh-cat-title" style="background:{{ $catBackground ?? 'linear-gradient(135deg,#FFF1E6,#FDE6EF)' }}">
                <div class="ffh-head">
                    <div><h2 id="ffh-cat-title">Shop by category</h2><p>Find what you need for today's meal</p></div>
                    @if (count($categoryGroups))
                        <div class="ffh-tabs" role="tablist" aria-label="Category groups">
                            @foreach ($categoryGroups as $g)
                                <button type="button" role="tab" class="{{ $loop->first ? 'on' : '' }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" data-ffh-tab="{{ $g['id'] }}">{{ $g['name'] }}</button>
                            @endforeach
                        </div>
                    @else
                        <a class="ffh-link" href="{{ route('categories') }}">All {{ $categories->count() }} categories <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    @endif
                </div>
                @php
                    $ffhFirstGroup = $categoryGroups[0]['id'] ?? null;
                    $ffhGroupOf = [];
                    foreach ($categoryGroups as $g) { foreach ($g['ids'] as $cid) { $ffhGroupOf[$cid][] = $g['id']; } }
                @endphp
                <div class="ffh-cats">
                    @foreach ($categories as $category)
                        @php
                            $cg = $ffhGroupOf[(int) $category->id] ?? [];
                            $cHidden = $ffhFirstGroup !== null ? ! in_array($ffhFirstGroup, $cg, true) : $loop->index >= 16;
                        @endphp
                        <a class="ffh-cat" href="{{ url('/view_product/'.$category->id) }}" data-ffh-groups="{{ implode(',', $cg) }}" @if ($cHidden) hidden @endif>
                            <img src="{{ $categoryHd[(int) $category->id] ?? asset('uploads/images/'.$category->circle_image) }}" alt="{{ $category->name }}" width="160" height="160" loading="lazy" decoding="async" onerror="this.style.visibility='hidden'">
                            <span>{{ $category->name }}</span>
                        </a>
                    @endforeach
                </div>
                @if (count($categoryGroups))
                    <div style="text-align:center;margin-top:20px"><a class="ffh-link" href="{{ route('categories') }}">All {{ $categories->count() }} categories <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div>
                @endif
            </section>
            <div id="ffh-cat-end" aria-hidden="true" style="height:1px"></div>
        @endif

        {{-- 3. Admin banners --}}
        @if (false)
            <section class="ffh-section ffh-banner" aria-label="Freshful offers">
                <div class="ffh-banner-track" id="ffh-banner-track">
                    @foreach ($banners as $banner)
                        <a class="ffh-banner-slide" href="{{ filled($banner->url) ? $banner->url : url('/view_product/all') }}" aria-label="{{ $banner->title ?: 'Shop Freshful' }}">
                            <img src="{{ asset('uploads/images/banners/'.$banner->image) }}" alt="{{ $banner->title ?: 'Freshful offer' }}" loading="lazy">
                        </a>
                    @endforeach
                </div>
                @if ($banners->count() > 1)
                    <button class="ffh-round prev" type="button" data-ffh-banner="-1" aria-label="Previous banner"><i class="bi bi-chevron-left"></i></button>
                    <button class="ffh-round next" type="button" data-ffh-banner="1" aria-label="Next banner"><i class="bi bi-chevron-right"></i></button>
                @endif
            </section>
        @endif

        {{-- 4. Buy again (logged in) / popular picks with a login prompt (guests) --}}
        @php $ffhAgainList = $buyAgain->isNotEmpty() ? $buyAgain : $favourites; @endphp
        @if ($ffhAgainList->isNotEmpty())
            <section class="ffh-section ffh-again" aria-labelledby="ffh-again-title">
                <div class="ffh-head" style="margin-bottom:14px">
                    <div class="ffh-again-title">
                        <span class="ic"><i class="bi bi-arrow-repeat" aria-hidden="true"></i></span>
                        <h2 id="ffh-again-title">Buy again</h2>
                        <small>{{ $buyAgain->isNotEmpty() ? 'From your last orders' : (auth()->check() ? 'Popular right now. Your past orders will show here.' : 'Log in to see your past orders. Popular right now:') }}</small>
                    </div>
                    @if ($buyAgain->isNotEmpty())
                        @if (\Illuminate\Support\Facades\Route::has('myaccount'))
                            <a class="ffh-link" href="{{ route('myaccount') }}">My orders</a>
                        @endif
                    @elseif (! auth()->check())
                        <button type="button" class="ffh-login-btn" data-ffh-login><i class="bi bi-person" aria-hidden="true"></i> Log in</button>
                    @endif
                </div>
                <div class="ffh-again-grid">
                    @foreach ($ffhAgainList as $product)
                        @php $againOut = ($stock[$product->id]['out'] ?? false); @endphp
                        <div class="ffh-again-item">
                            <img src="{{ asset('uploads/images/products/'.$product->product_image) }}" alt="" loading="lazy" onerror="this.style.visibility='hidden'">
                            <div class="t">
                                <a href="{{ route('product_detail', ['id' => $product->id]) }}">{{ $product->product_name }}</a>
                                <small>{{ trim(($product->gross_quantity ?? '').' '.($product->product_unit ?? '')) }} · <strong style="color:var(--ink)">{{ $ffhMoney($product->main_price) }}</strong></small>
                            </div>
                            @if ($againOut)
                                <button type="button" class="ffv-notify" data-stock-alert="{{ $product->id }}" style="height:36px;padding:0 10px">Notify</button>
                            @else
                                <div class="ff-quantity-control" data-product-quantity="{{ $product->id }}">
                                    <button type="button" class="ff-add product-add-button" onclick="addToCart({{ $product->id }})">Add</button>
                                    <div class="ff-quantity-stepper" hidden aria-label="Change quantity for {{ $product->product_name }}">
                                        <button type="button" class="ff-quantity-button" onclick="changeProductQuantity({{ $product->id }}, -1)" aria-label="Decrease quantity">&minus;</button>
                                        <span class="ff-quantity-value" aria-live="polite">0</span>
                                        <button type="button" class="ff-quantity-button" onclick="changeProductQuantity({{ $product->id }}, 1)" aria-label="Increase quantity">+</button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- 5. One section per product tag (same rule as the old page), coupons after the second --}}
        @php $sfCouponsShown = false; @endphp
        @forelse ($homeTagSections as $sectionIndex => $section)
            <section class="ffh-section" aria-labelledby="ffh-tag-{{ $sectionIndex }}">
                <div class="ffh-head">
                    <div><h2 id="ffh-tag-{{ $sectionIndex }}">{{ $section['name'] }}</h2></div>
                    <a class="ffh-link" href="{{ url('/view_product/all/'.rawurlencode($section['name'])) }}">View all <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </div>
                <div class="ffh-grid">
                    @foreach ($section['products'] as $product)
                        @include('home_v2.card', ['product' => $product, 'stock' => $stock])
                    @endforeach
                </div>
            </section>
            @if ($sectionIndex === 1)
                @include('home_v2.coupons')
                @php $sfCouponsShown = true; @endphp
            @endif
        @empty
            @if ($bestSellers->isNotEmpty())
                <section class="ffh-section" aria-labelledby="ffh-best">
                    <div class="ffh-head">
                        <div><h2 id="ffh-best">Customer favourites</h2></div>
                        <a class="ffh-link" href="{{ url('/view_product/all') }}">Shop all <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    </div>
                    <div class="ffh-grid">
                        @foreach ($bestSellers as $product)
                            @include('home_v2.card', ['product' => $product, 'stock' => $stock])
                        @endforeach
                    </div>
                </section>
            @endif
        @endforelse
        @if (! $sfCouponsShown)
            @include('home_v2.coupons')
        @endif
    </div>
</main>

@if ($categories->isNotEmpty())
    <nav class="ffh-chipbar" id="ffh-chipbar" aria-label="Jump to a category">
        <div class="ffh-chip-wrap">
            <button type="button" class="arrow" data-ffh-chips="-1" aria-label="Scroll categories left"><i class="bi bi-chevron-left"></i></button>
            <div class="ffh-chip-track" id="ffh-chip-track">
                <a class="all" href="{{ route('categories') }}">All</a>
                @foreach ($categories as $category)
                    <a href="{{ url('/view_product/'.$category->id) }}"><img src="{{ $categoryHd[(int) $category->id] ?? asset('uploads/images/'.$category->circle_image) }}" alt="" width="28" height="28" loading="lazy">{{ $category->name }}</a>
                @endforeach
            </div>
            <button type="button" class="arrow" data-ffh-chips="1" aria-label="Scroll categories right"><i class="bi bi-chevron-right"></i></button>
        </div>
    </nav>
@endif
@endsection

@push('scripts')
<script>
(function () {
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // A horizontal slider (hero and banners): arrows, dots and auto-advance that pauses on hover.
    function slider(track, dotAttr, arrowAttr, auto) {
        if (!track) return;
        var count = track.children.length, timer = null;
        var cur = function () { return Math.round(track.scrollLeft / Math.max(1, track.clientWidth)); };
        var go = function (i) { track.scrollTo({ left: ((i + count) % count) * track.clientWidth, behavior: 'smooth' }); };
        if (arrowAttr) document.querySelectorAll('[' + arrowAttr + ']').forEach(function (b) { b.addEventListener('click', function () { go(cur() + Number(b.getAttribute(arrowAttr))); }); });
        if (dotAttr) {
            var dots = document.querySelectorAll('[' + dotAttr + ']');
            dots.forEach(function (b) { b.addEventListener('click', function () { go(Number(b.getAttribute(dotAttr))); }); });
            track.addEventListener('scroll', function () { var i = cur(); dots.forEach(function (b) { b.classList.toggle('on', Number(b.getAttribute(dotAttr)) === i); }); }, { passive: true });
        }
        var start = function () { if (auto && !reduce && count > 1) { clearInterval(timer); timer = setInterval(function () { go(cur() + 1); }, auto); } };
        var stop = function () { clearInterval(timer); };
        track.parentNode.addEventListener('mouseenter', stop);
        track.parentNode.addEventListener('mouseleave', start);
        start();
    }
    slider(document.getElementById('ffh-hero-track'), 'data-ffh-hero-dot', null, 6000);
    slider(document.getElementById('ffh-banner-track'), null, 'data-ffh-banner', 5000);

    // Category tabs.
    document.querySelectorAll('[data-ffh-tab]').forEach(function (tab) {
        tab.addEventListener('click', function () {
            var id = tab.getAttribute('data-ffh-tab');
            document.querySelectorAll('[data-ffh-tab]').forEach(function (t) { var on = t === tab; t.classList.toggle('on', on); t.setAttribute('aria-selected', on ? 'true' : 'false'); });
            document.querySelectorAll('.ffh-cat').forEach(function (c) { var g = (c.getAttribute('data-ffh-groups') || '').split(','); c.hidden = g.indexOf(id) === -1; });
        });
    });

    // Copy a promo code.
    document.querySelectorAll('[data-ffh-copy]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var code = btn.getAttribute('data-ffh-copy'), original = btn.innerHTML;
            var done = function () { btn.innerHTML = 'Copied <i class="bi bi-check2" aria-hidden="true"></i>'; setTimeout(function () { btn.innerHTML = original; }, 1400); };
            if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(code).then(done); }
            else { var f = document.createElement('textarea'); f.value = code; document.body.appendChild(f); f.select(); document.execCommand('copy'); f.remove(); done(); }
        });
    });

    // Open the site's login window.
    function openLogin() {
        var el = document.getElementById('loginoffcanvas');
        if (el && window.bootstrap) { bootstrap.Modal.getOrCreateInstance(el).show(); return true; }
        return false;
    }
    document.querySelectorAll('[data-ffh-login]').forEach(function (b) { b.addEventListener('click', openLogin); });

    // Sticky category chips under the header, with arrows.
    var bar = document.getElementById('ffh-chipbar'), end = document.getElementById('ffh-cat-end'), chips = document.getElementById('ffh-chip-track');
    if (bar && end && chips) {
        var header = document.getElementById('myHeader');
        // Put the chips INSIDE the existing "Shop all / Categories" row of the header (no extra bar).
        var inStrip = false;
        (function () {
            if (!header) return;
            var link = null;
            header.querySelectorAll('a,button,span,li,div').forEach(function (n) {
                if (!link && n.children.length < 3 && /^\s*Shop all\s*$/i.test(n.textContent || '')) link = n;
            });
            if (!link) return;
            var row = link.parentElement, guard = 0;
            while (row && row !== header && guard++ < 5) {
                if (/Categories/i.test(row.textContent || '') && row.getBoundingClientRect().width > 600) break;
                row = row.parentElement;
            }
            if (!row || row === header) return;
            var cs = getComputedStyle(row);
            if (cs.display !== 'flex') { row.style.display = 'flex'; row.style.alignItems = 'center'; }
            row.style.flexWrap = 'nowrap';
            Array.prototype.forEach.call(row.children, function (k) { if (k !== bar) { k.style.flex = '0 0 auto'; k.style.whiteSpace = 'nowrap'; } });
            bar.classList.add('in-strip');
            row.appendChild(bar);
            inStrip = true;
        })();
        var arrows = bar.querySelectorAll('[data-ffh-chips]');
        var setArrows = function () {
            var max = chips.scrollWidth - chips.clientWidth - 2;
            arrows.forEach(function (a) { var dir = Number(a.getAttribute('data-ffh-chips')); a.disabled = dir < 0 ? chips.scrollLeft <= 2 : chips.scrollLeft >= max; });
        };
        arrows.forEach(function (a) { a.addEventListener('click', function () { chips.scrollBy({ left: Number(a.getAttribute('data-ffh-chips')) * Math.max(240, chips.clientWidth * 0.7), behavior: 'smooth' }); }); });
        chips.addEventListener('scroll', setArrows, { passive: true });
        var update = function () {
            var h = header ? Math.max(0, header.getBoundingClientRect().bottom) : 0;
            if (!inStrip) bar.style.setProperty('--ffh-top', Math.max(0, Math.round(h)) + 'px');
            bar.classList.toggle('on', end.getBoundingClientRect().top < h + 20);
            setArrows();
        };
        window.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update);
        update();
    }

    // Floating cart: show photos of what is in the cart.
    var ffhImages = @json($imageMap);
    function cartThumbs() {
        var float = document.getElementById('ff-cart-float');
        if (!float) return;
        var icon = float.querySelector('.ff-cart-float-icon'), strong = float.querySelector('strong');
        var srcs = [];
        document.querySelectorAll('#cart-items img').forEach(function (img) { var s = img.getAttribute('src'); if (s && srcs.length < 3 && srcs.indexOf(s) === -1) srcs.push(s); });
        if (!srcs.length && window.cartQuantities) {
            Object.keys(window.cartQuantities).forEach(function (id) { if (Number(window.cartQuantities[id]) > 0 && ffhImages[id] && srcs.length < 3) srcs.push(ffhImages[id]); });
        }
        var box = float.querySelector('.ffh-cart-thumbs');
        if (!box && icon) { box = document.createElement('span'); box.className = 'ffh-cart-thumbs'; icon.parentNode.insertBefore(box, icon); }
        if (box) {
            box.innerHTML = '';
            srcs.forEach(function (s) { var im = document.createElement('img'); im.src = s; im.alt = ''; box.appendChild(im); });
            box.style.display = srcs.length ? '' : 'none';
        }
        if (icon) icon.style.display = srcs.length ? 'none' : '';
        if (strong && !strong.querySelector('.ffh-cart-sub')) { var sub = document.createElement('small'); sub.className = 'ffh-cart-sub'; sub.textContent = 'Express in 30 min'; strong.appendChild(sub); }
    }
    var watch = function (el, opts) { if (el && window.MutationObserver) new MutationObserver(function () { setTimeout(cartThumbs, 30); }).observe(el, opts); };
    watch(document.getElementById('cart-items'), { childList: true, subtree: true });
    watch(document.getElementById('ff-cart-float-count'), { childList: true, characterData: true, subtree: true });
    watch(document.getElementById('ff-cart-float'), { attributes: true, attributeFilter: ['hidden'] });
    cartThumbs();
    setTimeout(cartThumbs, 1500);

    // "Notify me" for out-of-stock products.
    var csrf = function () { var m = document.querySelector('meta[name="csrf-token"]'); if (m && m.content) return m.content; var i = document.querySelector('input[name="_token"]'); return i ? i.value : ''; };
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-stock-alert]');
        if (!btn || btn.disabled) return;
        btn.disabled = true;
        fetch(@json(route('storefront.stock_alert')), {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
            body: JSON.stringify({ product_id: Number(btn.getAttribute('data-stock-alert')) })
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (data && data.result) {
                btn.innerHTML = 'Done <i class="bi bi-check2" aria-hidden="true"></i>';
                var row = btn.closest('.ffv-notify-row'); var t = row && row.querySelector('.ffv-notify-text'); if (t) t.textContent = 'We will tell you when it is back';
                if (typeof notify === 'function') notify(data.message, 'success');
                return;
            }
            btn.disabled = false;
            if (data && data.login) { if (!openLogin() && typeof notify === 'function') notify(data.message, 'error'); return; }
            if (typeof notify === 'function') notify((data && data.message) || 'Please try again', 'error');
        }).catch(function () { btn.disabled = false; if (typeof notify === 'function') notify('Network error, please try again', 'error'); });
    });
})();
</script>
@endpush

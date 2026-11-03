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

/* ===== Cinematic layout ===== */
.cinema .slide{transition:none}
.cn{position:absolute;inset:0;overflow:hidden}
.cn-box{position:absolute;top:0;right:2%;width:var(--bs);height:var(--bs)}
.P .cn-box{right:auto;left:50%;margin-left:calc(var(--bs) / -2)}
.cn-rays{position:absolute;left:50%;top:50%;width:170%;height:170%;margin:-85% 0 0 -85%;border-radius:50%;background:repeating-conic-gradient(from 0deg,rgba(var(--ac-rgb),.30) 0deg 6deg,rgba(var(--ac-rgb),0) 6deg 15deg);-webkit-mask-image:radial-gradient(circle,#000 0,rgba(0,0,0,.85) 28%,transparent 62%);mask-image:radial-gradient(circle,#000 0,rgba(0,0,0,.85) 28%,transparent 62%);animation:cnspin 60s linear infinite}
.cn-glow{position:absolute;left:50%;top:50%;width:86%;height:86%;margin:-43% 0 0 -43%;border-radius:50%;background:radial-gradient(circle,rgba(var(--ac2-rgb),.55) 0,rgba(var(--ac-rgb),.28) 45%,rgba(var(--ac-rgb),0) 70%)}
.cn-ring{position:absolute;left:50%;top:50%;border-radius:50%;border:3px solid rgba(var(--tx-rgb),.16)}
.cn-ring.a{width:74%;height:74%;margin:-37% 0 0 -37%}.cn-ring.b{width:96%;height:96%;margin:-48% 0 0 -48%;border-style:dashed;border-color:rgba(var(--tx-rgb),.10)}
.cn-dish{position:absolute;left:8%;top:6%;width:84%;height:84%}
.cn-in{position:absolute;inset:0}
.cn-in img{position:absolute;inset:0;width:100%;height:100%;object-fit:contain;filter:drop-shadow(0 38px 34px rgba(0,0,0,.5))}
.cn-in img.photo{border-radius:50%;object-fit:cover;border:14px solid rgba(255,255,255,.92);box-shadow:0 38px 70px rgba(0,0,0,.5);filter:none}
.cn-in .ini{position:absolute;inset:8%;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:300px;font-weight:800;color:#fff;background:linear-gradient(135deg,var(--ac),var(--ac2));box-shadow:0 38px 70px rgba(0,0,0,.45)}
.cn-shadow{position:absolute;left:50%;bottom:1%;width:62%;height:7%;margin-left:-31%;border-radius:50%;background:radial-gradient(ellipse,rgba(0,0,0,.55),rgba(0,0,0,0) 70%)}
.cn-shine{position:absolute;inset:0;overflow:hidden;pointer-events:none;-webkit-mask-image:radial-gradient(circle,#000 56%,transparent 57%);mask-image:radial-gradient(circle,#000 56%,transparent 57%)}
.cn-shine:after{content:"";position:absolute;top:-20%;bottom:-20%;width:18%;left:-30%;background:linear-gradient(100deg,transparent,rgba(255,255,255,.55),transparent);transform:skewX(-18deg)}
.cn-ghost{position:absolute;left:2%;top:50%;margin-top:-.52em;font-size:340px;font-weight:800;line-height:1;white-space:nowrap;text-transform:uppercase;color:transparent;-webkit-text-stroke:3px rgba(var(--tx-rgb),.10);letter-spacing:-6px}
.P .cn-ghost{top:auto;bottom:6%;margin-top:0;font-size:300px}
.cn-part{position:absolute;inset:0;pointer-events:none}
.cn-part i{position:absolute;left:var(--x);top:var(--y);width:var(--s);height:var(--s);border-radius:50%;background:rgba(var(--tx-rgb),.28);animation:cnrise var(--d) ease-in-out infinite alternate;animation-delay:var(--w)}
.cn-part i.o{background:rgba(var(--ac2-rgb),.55)}
.cn-tx{position:absolute;left:70px;top:0;bottom:0;width:calc(100% - var(--bs) - 90px);display:flex;flex-direction:column;justify-content:center;gap:20px;z-index:3}
.P .cn-tx{left:60px;right:60px;width:auto;top:calc(var(--bs) - 20px);bottom:70px;align-items:center;text-align:center;justify-content:flex-start;gap:16px}
.cn-tag{align-self:flex-start;display:inline-flex;align-items:center;gap:14px;font-size:34px;font-weight:700;letter-spacing:5px;text-transform:uppercase;color:var(--tx)}
.cn-tag:before{content:"";width:62px;height:6px;border-radius:3px;background:linear-gradient(90deg,var(--ac),var(--ac2))}
.P .cn-tag{align-self:center}
.cn-nm{font-weight:800;line-height:1;color:var(--tx);text-shadow:0 6px 30px rgba(0,0,0,.25);letter-spacing:-1px}
.cn-nm .w{display:inline-block;overflow:hidden;vertical-align:top;padding:0 .12em .1em 0;margin-right:.04em}.cn-nm .w span{display:inline-block}
.cn-n1{font-size:150px}.cn-n2{font-size:118px}.cn-n3{font-size:92px}
.P .cn-n1{font-size:132px}.P .cn-n2{font-size:106px}.P .cn-n3{font-size:84px}
.cn-pack{font-size:44px;font-weight:600;color:var(--tx);opacity:.82;display:flex;gap:14px;align-items:center}
.P .cn-pack{justify-content:center}
.cn-pack span{border:3px solid rgba(var(--tx-rgb),.45);border-radius:99px;padding:6px 28px}
.cn-row{display:flex;align-items:center;gap:34px;margin-top:6px}
.P .cn-row{justify-content:center}
.cn-price{position:relative;display:inline-flex;align-items:center;gap:8px;padding:6px 58px 6px 42px;border-radius:34px;background:linear-gradient(110deg,var(--ac),var(--ac2));color:#fff;box-shadow:0 26px 60px rgba(var(--ac-rgb),.5),inset 0 3px 0 rgba(255,255,255,.35);transform-origin:left center;overflow:hidden}
.cn-price .cu{font-size:84px;font-weight:700;align-self:flex-start;margin-top:30px}
.cn-price b{font-size:214px;font-weight:800;line-height:1.02;letter-spacing:-5px;font-variant-numeric:tabular-nums}
.P .cn-price b{font-size:190px}
.cn-price:after{content:"";position:absolute;top:0;bottom:0;left:-30%;width:20%;background:linear-gradient(100deg,transparent,rgba(255,255,255,.55),transparent);transform:skewX(-18deg);opacity:0}
.cn-mrp{position:relative;font-size:62px;font-weight:600;color:var(--tx);opacity:.78;white-space:nowrap}
.cn-mrp:after{content:"";position:absolute;left:-6px;right:-6px;top:54%;height:6px;border-radius:3px;background:var(--ac2);transform-origin:left center}
.cn-save{position:absolute;right:6%;top:2%;width:210px;height:210px;border-radius:50%;background:linear-gradient(135deg,#FFFFFF,#FFE9D6);color:var(--ac);display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;font-weight:800;line-height:1;box-shadow:0 18px 40px rgba(0,0,0,.4);border:6px dashed var(--ac);z-index:4}
.cn-save small{font-size:34px;letter-spacing:3px}.cn-save strong{font-size:76px;margin-top:4px}
.cn-few{align-self:flex-start;font-size:34px;font-weight:700;color:#fff;background:rgba(0,0,0,.35);border:3px solid var(--ac2);border-radius:99px;padding:8px 30px;display:inline-flex;align-items:center;gap:14px}
.cn-few:before{content:"";width:16px;height:16px;border-radius:50%;background:var(--ac2)}
.P .cn-few{align-self:center}
.cn-ds{font-size:34px;line-height:1.35;color:var(--tx);opacity:.8;max-width:820px;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden}
.cn-next{position:absolute;right:46px;bottom:18px;display:flex;align-items:center;gap:14px;z-index:5;color:var(--tx);font-size:26px;font-weight:700;letter-spacing:3px;text-transform:uppercase;opacity:.85}
.cn-next i{width:78px;height:78px;border-radius:50%;background:rgba(var(--tx-rgb),.14) center/contain no-repeat;border:3px solid rgba(var(--tx-rgb),.35);display:block}
.P .cn-next{right:auto;left:50%;transform:translateX(-50%);bottom:16px}
.cn-count{position:absolute;right:50px;top:12px;font-size:30px;font-weight:700;letter-spacing:4px;color:var(--tx);opacity:.7;z-index:5}
.P .cn-count{right:44px}
.cn-prog{position:absolute;left:0;bottom:0;height:8px;width:100%;background:rgba(var(--tx-rgb),.12);z-index:6}
.cn-prog b{display:block;height:100%;width:100%;background:linear-gradient(90deg,var(--ac),var(--ac2));transform-origin:left center}
#wipe{position:absolute;left:-30%;top:-10%;width:160%;height:120%;z-index:9;pointer-events:none;transform:translateX(-120%) skewX(-16deg);background:linear-gradient(100deg,var(--ac),var(--ac2) 60%,var(--bg2))}
#wipe.go{animation:cnwipe .95s cubic-bezier(.7,0,.25,1) both}
@keyframes cnwipe{0%{transform:translateX(-120%) skewX(-16deg)}48%{transform:translateX(0) skewX(-16deg)}52%{transform:translateX(0) skewX(-16deg)}100%{transform:translateX(120%) skewX(-16deg)}}
@keyframes cnspin{to{transform:rotate(360deg)}}
@keyframes cnrise{from{transform:translate3d(0,0,0) scale(.8);opacity:.2}to{transform:translate3d(18px,-70px,0) scale(1.1);opacity:.9}}
/* sequence (only while the slide is on and motion is on) */
.cin-anim .slide.on .cn-rays{animation:cnrays 1.1s cubic-bezier(.2,.8,.2,1) .05s both,cnspin 60s linear 1.2s infinite}
.cin-anim .slide.on .cn-glow{animation:cnfade 1s ease .05s both}
.cin-anim .slide.on .cn-ring.a{animation:cnringin 1s cubic-bezier(.2,.8,.2,1) .2s both,cnpulse 5s ease-in-out 1.3s infinite alternate}
.cin-anim .slide.on .cn-ring.b{animation:cnringin 1.2s cubic-bezier(.2,.8,.2,1) .3s both,cnpulse 7s ease-in-out 1.5s infinite alternate-reverse}
.cin-anim .slide.on .cn-ghost{animation:cnghost 1.6s cubic-bezier(.2,.8,.2,1) .1s both,cndrift 18s ease-in-out 1.7s infinite alternate}
.cin-anim .slide.on .cn-dish{animation:cndish 1.25s cubic-bezier(.2,.9,.2,1) .15s both}
.cin-anim .slide.on .cn-in{animation:cnfloat 6s ease-in-out 1.5s infinite alternate}
.cin-anim .slide.on .cn-shadow{animation:cnshadow 1s ease .9s both,cnshadow2 6s ease-in-out 1.9s infinite alternate}
.cin-anim .slide.on .cn-shine:after{animation:cnshine 1.3s ease 1.4s both,cnshine 1.4s ease 6.5s infinite}
.cin-anim .slide.on .cn-tag{animation:cnslide .7s cubic-bezier(.2,.8,.2,1) .35s both}
.cin-anim .slide.on .cn-nm .w span{animation:cnword .8s cubic-bezier(.2,.9,.2,1) both;animation-delay:calc(.55s + var(--k) * 130ms)}
.cin-anim .slide.on .cn-pack{animation:cnslide .6s ease 1.15s both}
.cin-anim .slide.on .cn-price{animation:cnplate .8s cubic-bezier(.2,1.4,.3,1) 1.45s both,cnburst 1s ease-out 1.75s both}
.cin-anim .slide.on .cn-price:after{animation:cnsheen 1s ease 2.6s both}
.cin-anim .slide.on .cn-mrp{animation:cnslide .6s ease 2.35s both}
.cin-anim .slide.on .cn-mrp:after{animation:cnstrike .5s ease 2.75s both}
.cin-anim .slide.on .cn-save{animation:cnsticker .9s cubic-bezier(.2,1.5,.3,1) 2.6s both,cnwob 3s ease-in-out 3.6s infinite alternate}
.cin-anim .slide.on .cn-few{animation:cnslide .6s ease 3s both}
.cin-anim .slide.on .cn-ds{animation:cnfade .8s ease 3.2s both}
.cin-anim .slide.on .cn-part i{animation:cnfade .6s ease 1s both,cnrise var(--d) ease-in-out 1.2s infinite alternate}
.cin-anim .slide.on .cn-next,.cin-anim .slide.on .cn-count{animation:cnfade .8s ease 1.2s both}
.cin-anim .slide.on .cn-prog b{animation:cnprog var(--sec) linear both}
.cin-soft .slide.on .cn{animation:cnfade .8s ease both}
@keyframes cnrays{from{opacity:0;transform:rotate(-40deg) scale(.3)}to{opacity:1;transform:rotate(0) scale(1)}}
@keyframes cnfade{from{opacity:0}to{opacity:1}}
@keyframes cnringin{from{opacity:0;transform:scale(.5)}to{opacity:1;transform:scale(1)}}
@keyframes cnpulse{from{transform:scale(.97)}to{transform:scale(1.04)}}
@keyframes cnghost{from{opacity:0;transform:translateX(220px)}to{opacity:1;transform:none}}
@keyframes cndrift{from{transform:translateX(0)}to{transform:translateX(-90px)}}
@keyframes cndish{0%{opacity:0;transform:translate(420px,-170px) scale(1.9) rotate(-22deg);filter:blur(16px)}55%{opacity:1;transform:translate(-14px,10px) scale(.96) rotate(3deg);filter:blur(0)}78%{transform:translate(4px,-4px) scale(1.03) rotate(-1deg)}100%{opacity:1;transform:none;filter:blur(0)}}
@keyframes cnfloat{from{transform:translateY(0) rotate(0)}to{transform:translateY(-16px) rotate(1.4deg)}}
@keyframes cnshadow{from{opacity:0;transform:scale(.3)}to{opacity:1;transform:scale(1)}}
@keyframes cnshadow2{from{transform:scale(1)}to{transform:scale(.9);opacity:.8}}
@keyframes cnshine{from{left:-30%}to{left:120%}}
@keyframes cnslide{from{opacity:0;transform:translateX(-70px)}to{opacity:1;transform:none}}
@keyframes cnword{from{transform:translateY(115%) rotate(4deg)}to{transform:none}}
@keyframes cnplate{0%{opacity:0;transform:scale(.2) rotate(-6deg)}100%{opacity:1;transform:none}}
@keyframes cnburst{0%{box-shadow:0 26px 60px rgba(var(--ac-rgb),.5),inset 0 3px 0 rgba(255,255,255,.35),0 0 0 0 rgba(var(--ac2-rgb),.85)}100%{box-shadow:0 26px 60px rgba(var(--ac-rgb),.5),inset 0 3px 0 rgba(255,255,255,.35),0 0 0 70px rgba(var(--ac2-rgb),0)}}
@keyframes cnsheen{0%{opacity:1;left:-30%}100%{opacity:1;left:130%}}
@keyframes cnstrike{from{transform:scaleX(0)}to{transform:scaleX(1)}}
@keyframes cnsticker{0%{opacity:0;transform:scale(0) rotate(-120deg)}100%{opacity:1;transform:rotate(-12deg)}}
@keyframes cnwob{from{transform:rotate(-12deg) scale(1)}to{transform:rotate(-8deg) scale(1.06)}}
@keyframes cnprog{from{transform:scaleX(0)}to{transform:scaleX(1)}}
</style>
</head>
<body data-key="{{ $key }}" data-preview="{{ $preview ? 1 : 0 }}" data-kiosk="{{ $kiosk ? 1 : 0 }}" data-logo="{{ $logo }}">
<div id="stage" class="L mot-rise"><div class="bg"></div><div class="bgi"></div>
  <header class="hd"><div class="l"><div class="lg"><img id="lg" alt="Freshful"></div><span class="ot" id="ot"></span></div><div class="ck" id="ck"></div></header>
  <main class="slides" id="slides"></main>
  <aside class="more" id="more" style="display:none"></aside>
  <footer class="tk" id="tk" style="display:none"><span class="run" id="run"></span></footer>
  <div id="wipe"></div>
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
  function sizes() {
    var h = slidesEl.clientHeight || 800, port = stage.className.indexOf('P') > -1;
    stage.style.setProperty('--sh', h + 'px'); stage.style.setProperty('--bs', (port ? Math.min(980, Math.round(h * 0.5)) : h) + 'px');
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

  function rgb(h) { h = String(h || '#000000').replace('#', ''); return parseInt(h.substr(0, 2), 16) + ',' + parseInt(h.substr(2, 2), 16) + ',' + parseInt(h.substr(4, 2), 16); }
  function words(name) {
    var n = name.length, cls = n <= 12 ? 'cn-n1' : (n <= 22 ? 'cn-n2' : 'cn-n3');
    return '<h2 class="cn-nm ' + cls + '">' + name.split(/\s+/).map(function (w, k) { return '<span class="w"><span style="--k:' + k + '">' + esc(w) + '</span></span> '; }).join('') + '</h2>';
  }
  function cinemaSlide(it, i, all, S) {
    var port = S.orientation === 'portrait', ghost = String(it.name).split(/\s+/)[0], parts = '';
    var seeds = [[8, 70, 14, 7, 0], [18, 22, 20, 9, 1], [30, 84, 10, 6, 0], [44, 12, 16, 8, 1], [58, 78, 12, 7, 0], [70, 30, 22, 10, 1], [82, 66, 12, 6, 0], [90, 18, 16, 9, 1], [14, 46, 9, 6, 0], [76, 90, 14, 8, 1], [52, 50, 8, 5, 0], [36, 40, 10, 7, 1]];
    seeds.forEach(function (s, k) { parts += '<i class="' + (s[4] ? 'o' : '') + '" style="--x:' + s[0] + '%;--y:' + s[1] + '%;--s:' + s[2] + 'px;--d:' + s[3] + 's;--w:' + (k * 0.2) + 's"></i>'; });
    var img = it.img ? '<img class="' + (it.cutout ? '' : 'photo') + '" src="' + esc(it.img) + '" alt="" onerror="this.style.visibility=\'hidden\'">' : '<div class="ini">' + esc(String(it.name).charAt(0).toUpperCase()) + '</div>';
    var nx = '';
    if (all.length > 1) { for (var k = 1; k <= 2 && k < all.length; k++) { var o = all[(i + k) % all.length]; nx += '<i style="background-image:url(' + (o.img ? esc(o.img) : '') + ')"></i>'; } nx = '<div class="cn-next"><span>Up next</span>' + nx + '</div>'; }
    var cnt = (it.price % 1 === 0) ? it.price : it.price.toFixed(2);
    return '<div class="cn" data-i="' + i + '">' +
      '<div class="cn-ghost">' + esc(ghost) + '</div>' +
      '<div class="cn-box"><div class="cn-rays"></div><div class="cn-glow"></div><div class="cn-ring a"></div><div class="cn-ring b"></div><div class="cn-part">' + parts + '</div>' +
      '<div class="cn-dish"><div class="cn-shadow"></div><div class="cn-in">' + img + '</div>' + (it.cutout ? '<div class="cn-shine"></div>' : '') + '</div>' +
      (S.show.save && it.save ? '<div class="cn-save"><small>SAVE</small><strong>' + it.save + '%</strong></div>' : '') + '</div>' +
      '<div class="cn-tx">' + (tagOf(it) ? '<div class="cn-tag">' + esc(tagOf(it)) + '</div>' : '') + words(it.name) +
      (it.pack ? '<div class="cn-pack"><span>' + esc(it.pack) + '</span></div>' : '') +
      '<div class="cn-row"><div class="cn-price"><span class="cu">₹</span><b class="cnt" data-v="' + esc(String(it.price)) + '">' + esc(Number(it.price).toLocaleString('en-IN')) + '</b></div>' + (S.show.mrp && it.mrp ? '<div class="cn-mrp">' + esc(money(it.mrp)) + '</div>' : '') + '</div>' +
      (S.show.stock && it.few ? '<div class="cn-few">Only a few left</div>' : '') + (it.desc ? '<p class="cn-ds">' + esc(it.desc) + '</p>' : '') + '</div>' +
      '<div class="cn-count">' + ('0' + (i + 1)).slice(-2) + ' / ' + ('0' + all.length).slice(-2) + '</div>' + nx + '<div class="cn-prog"><b></b></div></div>';
  }
  function build(d) {
    var S = d.screen, L = S.layout, port = S.orientation === 'portrait', out = [];
    var items = d.items || [];
    if (L === 'cinema') {
      items.forEach(function (it, i) { out.push(cinemaSlide(it, i, items, S)); });
    } else if (L === 'offers') {
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
  var wipeT = null;
  function countUp(cur) {
    [].forEach.call(cur.querySelectorAll('.cnt'), function (el) {
      var v = parseFloat(el.getAttribute('data-v')) || 0, dec = v % 1 !== 0, fmt = function (x) { return dec ? x.toFixed(2) : Math.round(x).toLocaleString('en-IN'); };
      if (reduce || stage.className.indexOf('cin-anim') < 0) { el.textContent = fmt(v); return; }
      el.textContent = fmt(0);
      setTimeout(function () { var t0 = Date.now(), dur = 950; (function step() { var k = Math.min(1, (Date.now() - t0) / dur), e = 1 - Math.pow(1 - k, 3); el.textContent = fmt(v * e); if (k < 1 && cur.classList.contains('on')) requestAnimationFrame(step); else el.textContent = fmt(v); })(); }, 1700);
    });
  }
  function swap(i) {
    var prev = slideEls[idx]; idx = (i + slideEls.length) % slideEls.length; var cur = slideEls[idx];
    var cin = D && D.screen.layout === 'cinema';
    if (prev && prev !== cur) { prev.classList.remove('on', 'settled'); if (!cin) { prev.classList.add('leave'); setTimeout(function () { prev.classList.remove('leave'); }, 520); } }
    cur.classList.remove('leave'); void cur.offsetWidth; cur.classList.add('on'); cur.classList.remove('settled');
    if (cin) countUp(cur);
    setTimeout(function () { cur.classList.add('settled'); }, 1900);
  }
  var shown = false;
  function show(i) {
    if (!slideEls.length) return;
    var cin = D && D.screen.layout === 'cinema', w = $('wipe');
    if (cin && shown && slideEls.length > 1 && !reduce && stage.className.indexOf('cin-anim') > -1) {
      w.classList.remove('go'); void w.offsetWidth; w.classList.add('go'); clearTimeout(wipeT);
      wipeT = setTimeout(function () { swap(i); }, 470);
    } else swap(i);
    shown = true;
  }
  function apply(d) {
    D = d; var S = d.screen, T = d.theme;
    stage.className = (S.orientation === 'portrait' ? 'P' : 'L') + ' ' + (reduce || S.motion === 'none' ? 'mot-none' : 'mot-' + S.motion) + (S.idle && !reduce ? ' idle' : '') + (S.layout === 'cinema' ? ' cinema' + (reduce || S.motion === 'none' ? '' : (S.motion === 'fade' ? ' cin-soft' : ' cin-anim')) : '');
    shown = false;
    var st = stage.style; st.setProperty('--bg1', T.bg1); st.setProperty('--bg2', T.bg2); st.setProperty('--ang', T.angle + 'deg'); st.setProperty('--tx', T.text); st.setProperty('--card', T.card); st.setProperty('--ct', T.cardText);
    st.setProperty('--ac', T.accent); st.setProperty('--ac2', T.accent2); st.setProperty('--font', "'" + T.font + "'"); st.setProperty('--bgo', (T.bgImageOpacity / 100)); st.setProperty('--ac-rgb', rgb(T.accent)); st.setProperty('--ac2-rgb', rgb(T.accent2)); st.setProperty('--tx-rgb', rgb(T.text)); st.setProperty('--sec', Math.max(5, S.seconds) + 's');
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
    fit(); sizes(); clearInterval(timer); show(idx);
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

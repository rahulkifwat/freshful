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
/* Motion: choices for picture, name, price and description (see the animation library below) */
.mot-fade .slide .card,.mot-fade .slide .row,.mot-fade .slide .tile{opacity:0}.mot-fade .slide.on .card,.mot-fade .slide.on .row,.mot-fade .slide.on .tile{animation:fad .8s ease both;animation-delay:calc(var(--i,0)*120ms)}
.slide.leave *{animation:none!important}
.idle .slide.on.settled .dish{animation:flt 5s ease-in-out infinite alternate!important;opacity:1;transform:none;clip-path:none;filter:none}
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
.cin-anim.ap-fly .slide.on .cn-dish{animation:cndish 1.25s cubic-bezier(.2,.9,.2,1) .15s both}
.cin-anim .slide.on .cn-in{animation:cnfloat 6s ease-in-out 1.5s infinite alternate}
.cin-anim .slide.on .cn-shadow{animation:cnshadow 1s ease .9s both,cnshadow2 6s ease-in-out 1.9s infinite alternate}
.cin-anim .slide.on .cn-shine:after{animation:cnshine 1.3s ease 1.4s both,cnshine 1.4s ease 6.5s infinite}
.cin-anim .slide.on .cn-tag{animation:cnslide .7s cubic-bezier(.2,.8,.2,1) .35s both}
.cin-anim.at-words .slide.on .cn-nm .w span{animation:cnword .8s cubic-bezier(.2,.9,.2,1) both;animation-delay:calc(.55s + var(--k) * 130ms)}
.cin-anim .slide.on .cn-pack{animation:cnslide .6s ease 1.15s both}
.cin-anim.apr-stamp .slide.on .cn-price{animation:cnplate .8s cubic-bezier(.2,1.4,.3,1) 1.45s both,cnburst 1s ease-out 1.75s both}
.cin-anim .slide.on .cn-price:after{animation:cnsheen 1s ease 2.6s both}
.cin-anim .slide.on .cn-mrp{animation:cnslide .6s ease 2.35s both}
.cin-anim .slide.on .cn-mrp:after{animation:cnstrike .5s ease 2.75s both}
.cin-anim .slide.on .cn-save{animation:cnsticker .9s cubic-bezier(.2,1.5,.3,1) 2.6s both,cnwob 3s ease-in-out 3.6s infinite alternate}
.cin-anim .slide.on .cn-few{animation:cnslide .6s ease 3s both}
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

/* ===== Animation library ===== */
#stage{--dp:.2s;--dt:.75s;--dpr:1.25s;--dd:1.85s;--cs:.12s}
#stage.cinema{--dp:.15s;--dt:.55s;--dpr:1.45s;--dd:3.2s;--cs:0s}
.mot-rise .slide.on .card{animation:cin .6s cubic-bezier(.22,.7,.2,1) both;animation-delay:calc(var(--i,0)*var(--cs))}
.mot-rise .slide.on .row,.mot-rise .slide.on .tile{animation:cin .55s cubic-bezier(.22,.7,.2,1) both;animation-delay:calc(var(--i,0)*120ms)}
.mot-rise .slide.on .tag.t1{animation:gt-slide .5s ease both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dt) - .2s)}
.mot-rise .slide.on .ch.t3,.mot-rise .slide.on .tile .t3{animation:gd-fade .5s ease both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dd) - .3s)}
.mot-rise .slide.on .tile .t1{animation:gt-slide .5s ease both;animation-delay:calc(var(--i,0)*120ms + .3s)}
.mot-rise .slide.on .tile .t2{animation:gpr-pop .5s ease both;animation-delay:calc(var(--i,0)*120ms + .5s)}
.mot-rise .slide.on .tile .dish{animation:gp-rise .7s cubic-bezier(.22,.7,.2,1) both;animation-delay:calc(var(--i,0)*120ms + .15s)}
/* picture */
.mot-rise.ap-fly .slide.on .dish{animation:gp-fly 1.1s cubic-bezier(.2,.9,.2,1) both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dp))}
.mot-rise.ap-rise .slide.on .dish,.mot-rise.ap-rise .slide.on .cn-dish{animation:gp-rise .8s cubic-bezier(.22,.7,.2,1) both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dp))}
.mot-rise.ap-bounce .slide.on .dish,.mot-rise.ap-bounce .slide.on .cn-dish{animation:gp-bounce 1.1s cubic-bezier(.3,1.2,.4,1) both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dp))}
.mot-rise.ap-slide .slide.on .dish,.mot-rise.ap-slide .slide.on .cn-dish{animation:gp-slide .9s cubic-bezier(.2,.9,.2,1) both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dp))}
.mot-rise.ap-zoom .slide.on .dish,.mot-rise.ap-zoom .slide.on .cn-dish{animation:gp-zoom 1s cubic-bezier(.2,.8,.2,1) both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dp))}
.mot-rise.ap-spin .slide.on .dish,.mot-rise.ap-spin .slide.on .cn-dish{animation:gp-spin 1.1s cubic-bezier(.2,.8,.2,1) both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dp))}
.mot-rise.ap-float .slide.on .dish,.mot-rise.ap-float .slide.on .cn-dish{animation:gp-float 1.4s ease-out both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dp))}
.mot-rise.ap-flip .slide.on .dish,.mot-rise.ap-flip .slide.on .cn-dish{animation:gp-flip 1s cubic-bezier(.2,.8,.2,1) both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dp))}
.mot-rise.ap-drop .slide.on .dish,.mot-rise.ap-drop .slide.on .cn-dish{animation:gp-drop 1.1s cubic-bezier(.4,0,.6,1) both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dp))}
.mot-rise.ap-wipe .slide.on .dish,.mot-rise.ap-wipe .slide.on .cn-dish{animation:gp-wipe .9s ease-out both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dp))}
.mot-rise.ap-elastic .slide.on .dish,.mot-rise.ap-elastic .slide.on .cn-dish{animation:gp-elastic 1.3s ease-out both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dp))}
.mot-rise.ap-swing .slide.on .dish,.mot-rise.ap-swing .slide.on .cn-dish{animation:gp-swing 1.3s ease-out both;transform-origin:50% 0;animation-delay:calc(var(--i,0)*var(--cs) + var(--dp))}
.mot-rise.ap-roll .slide.on .dish,.mot-rise.ap-roll .slide.on .cn-dish{animation:gp-roll 1.1s cubic-bezier(.2,.8,.2,1) both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dp))}
.mot-rise.ap-pulse .slide.on .dish,.mot-rise.ap-pulse .slide.on .cn-dish{animation:gp-pulse 1s ease-out both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dp))}
.mot-rise.ap-stomp .slide.on .dish,.mot-rise.ap-stomp .slide.on .cn-dish{animation:gp-stomp .8s cubic-bezier(.5,0,.3,1) both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dp))}
.mot-rise.ap-drift .slide.on .dish,.mot-rise.ap-drift .slide.on .cn-dish{animation:gp-drift 1.1s cubic-bezier(.2,.8,.2,1) both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dp))}
.mot-rise.ap-focus .slide.on .dish,.mot-rise.ap-focus .slide.on .cn-dish{animation:gp-focus 1.1s ease-out both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dp))}
.mot-rise.ap-whoosh .slide.on .dish,.mot-rise.ap-whoosh .slide.on .cn-dish{animation:gp-whoosh 1.05s cubic-bezier(.15,.85,.25,1) both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dp))}
.mot-rise.ap-spot .slide.on .dish,.mot-rise.ap-spot .slide.on .cn-dish{animation:gp-spot 1s cubic-bezier(.2,1.3,.4,1) both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dp) + .25s)}
/* spotlight halo and light streak (KFC style) */
.card .im:before{content:"";position:absolute;left:50%;top:50%;width:90%;aspect-ratio:1/1;margin:-45% 0 0 -45%;border-radius:50%;background:radial-gradient(circle,rgba(255,255,255,.7),rgba(255,255,255,0) 66%);opacity:0;pointer-events:none}
.card .im:after{content:"";position:absolute;top:-10%;bottom:-10%;left:-45%;width:32%;background:linear-gradient(100deg,transparent,rgba(255,255,255,.9),transparent);transform:skewX(-18deg);opacity:0;pointer-events:none;z-index:3}
.mot-rise.ap-spot .slide.on .card .im:before,.mot-rise.ap-whoosh .slide.on .card .im:before{animation:g-halo 1.6s ease-out both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dp))}
.mot-rise.ap-whoosh .slide.on .card .im:after{animation:g-streak .8s ease-in both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dp) + .15s)}
.cn-sw{position:absolute;left:50%;top:50%;width:150%;height:36%;margin:-18% 0 0 -75%;border:7px solid rgba(255,255,255,.9);border-radius:50%;opacity:0;pointer-events:none;filter:blur(1px);z-index:5}
.mot-rise.ap-whoosh .slide.on .cn-sw{animation:g-ring 1s ease-in-out both;animation-delay:calc(var(--dp) + .05s)}
/* name */
.mot-rise.at-slide .slide.on .nm,.mot-rise.at-slide .slide.on .cn-nm,.mot-rise.at-words .slide.on .nm{animation:gt-slide .7s cubic-bezier(.2,.9,.2,1) both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dt))}
.mot-rise.at-sweep .slide.on .nm,.mot-rise.at-sweep .slide.on .cn-nm{animation:gt-sweep .9s cubic-bezier(.6,0,.2,1) both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dt))}
.mot-rise.at-zoom .slide.on .nm,.mot-rise.at-zoom .slide.on .cn-nm{animation:gt-zoom .7s cubic-bezier(.2,.9,.2,1) both;transform-origin:left center;animation-delay:calc(var(--i,0)*var(--cs) + var(--dt))}
.mot-rise.at-blur .slide.on .nm,.mot-rise.at-blur .slide.on .cn-nm{animation:gt-blur .9s ease-out both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dt))}
.mot-rise.at-bounce .slide.on .nm,.mot-rise.at-bounce .slide.on .cn-nm{animation:gt-bounce .9s cubic-bezier(.3,1.2,.4,1) both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dt))}
.mot-rise.at-flip .slide.on .nm,.mot-rise.at-flip .slide.on .cn-nm{animation:gt-flip .8s cubic-bezier(.2,.9,.2,1) both;transform-origin:left bottom;animation-delay:calc(var(--i,0)*var(--cs) + var(--dt))}
.mot-rise.at-type .slide.on .nm,.mot-rise.at-type .slide.on .cn-nm{animation:gt-type 1.3s steps(16) both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dt))}
.mot-rise.at-fade .slide.on .nm,.mot-rise.at-fade .slide.on .cn-nm{animation:gd-fade .9s ease both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dt))}
/* price */
.mot-rise.apr-pop .slide.on .pr.t2{animation:gpr-pop .6s cubic-bezier(.2,1.4,.3,1) both;transform-origin:left center;animation-delay:calc(var(--i,0)*var(--cs) + var(--dpr))}
.mot-rise.apr-stamp .slide.on .pr.t2{animation:gpr-stamp .6s cubic-bezier(.3,1.3,.4,1) both;transform-origin:left center;animation-delay:calc(var(--i,0)*var(--cs) + var(--dpr))}
.mot-rise.apr-slide .slide.on .pr.t2,.mot-rise.apr-slide .slide.on .cn-price{animation:gt-slide .7s cubic-bezier(.2,.9,.2,1) both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dpr))}
.mot-rise.apr-flip .slide.on .pr.t2,.mot-rise.apr-flip .slide.on .cn-price{animation:gt-flip .8s cubic-bezier(.2,.9,.2,1) both;transform-origin:left bottom;animation-delay:calc(var(--i,0)*var(--cs) + var(--dpr))}
.mot-rise.apr-bounce .slide.on .pr.t2,.mot-rise.apr-bounce .slide.on .cn-price{animation:gp-drop .9s cubic-bezier(.4,0,.6,1) both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dpr))}
.mot-rise.apr-glow .slide.on .pr.t2,.mot-rise.apr-glow .slide.on .cn-price{animation:gpr-glow 1s ease-out both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dpr))}
.mot-rise.apr-wipe .slide.on .pr.t2,.mot-rise.apr-wipe .slide.on .cn-price{animation:gt-sweep .8s cubic-bezier(.6,0,.2,1) both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dpr))}
.mot-rise.apr-fade .slide.on .pr.t2,.mot-rise.apr-fade .slide.on .cn-price{animation:gd-fade .8s ease both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dpr))}
.mot-rise.apr-pop.cinema .slide.on .cn-price{animation:gpr-pop .6s cubic-bezier(.2,1.4,.3,1) both;transform-origin:left center;animation-delay:var(--dpr)}
/* description and chips */
.mot-rise.ad-fade .slide.on .ds,.mot-rise.ad-fade .slide.on .cn-ds{animation:gd-fade .8s ease both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dd))}
.mot-rise.ad-slide .slide.on .ds,.mot-rise.ad-slide .slide.on .cn-ds{animation:gd-slide .7s ease both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dd))}
.mot-rise.ad-blur .slide.on .ds,.mot-rise.ad-blur .slide.on .cn-ds{animation:gt-blur .9s ease-out both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dd))}
.mot-rise.ad-wipe .slide.on .ds,.mot-rise.ad-wipe .slide.on .cn-ds{animation:gt-sweep .9s cubic-bezier(.6,0,.2,1) both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dd))}
.mot-rise.ad-type .slide.on .ds,.mot-rise.ad-type .slide.on .cn-ds{animation:gt-type 1.6s steps(30) both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dd))}
.mot-rise.ad-zoom .slide.on .ds,.mot-rise.ad-zoom .slide.on .cn-ds{animation:gpr-pop .6s ease-out both;transform-origin:left center;animation-delay:calc(var(--i,0)*var(--cs) + var(--dd))}
@keyframes gp-rise{from{opacity:0;transform:translateY(70px) scale(.88)}to{opacity:1;transform:none}}
@keyframes gp-fly{0%{opacity:0;transform:translate(300px,-130px) scale(1.7) rotate(-20deg);filter:blur(12px)}55%{opacity:1;transform:translate(-10px,8px) scale(.97) rotate(3deg);filter:blur(0)}78%{transform:translate(3px,-3px) scale(1.03)}100%{opacity:1;transform:none;filter:blur(0)}}
@keyframes gp-bounce{0%{opacity:0;transform:scale(0)}45%{opacity:1;transform:scale(1.2)}65%{transform:scale(.9)}82%{transform:scale(1.06)}100%{opacity:1;transform:none}}
@keyframes gp-slide{0%{opacity:0;transform:translateX(-220px)}65%{opacity:1;transform:translateX(16px)}100%{opacity:1;transform:none}}
@keyframes gp-zoom{from{opacity:0;transform:scale(2.4);filter:blur(10px)}to{opacity:1;transform:none;filter:blur(0)}}
@keyframes gp-spin{from{opacity:0;transform:rotate(-360deg) scale(.15)}to{opacity:1;transform:none}}
@keyframes gp-float{from{opacity:0;transform:translateY(46px)}to{opacity:1;transform:none}}
@keyframes gp-flip{from{opacity:0;transform:perspective(900px) rotateY(-95deg)}to{opacity:1;transform:perspective(900px) rotateY(0)}}
@keyframes gp-drop{0%{opacity:0;transform:translateY(-420px)}55%{opacity:1;transform:translateY(0)}70%{transform:translateY(-28px)}85%{transform:translateY(0)}93%{transform:translateY(-8px)}100%{opacity:1;transform:none}}
@keyframes gp-wipe{from{clip-path:inset(0 100% 0 0)}to{clip-path:inset(0 0 0 0)}}
@keyframes gp-elastic{0%{opacity:0;transform:scale(0)}30%{opacity:1;transform:scale(1.32)}48%{transform:scale(.84)}64%{transform:scale(1.1)}80%{transform:scale(.97)}100%{opacity:1;transform:none}}
@keyframes gp-swing{0%{opacity:0;transform:rotate(-80deg) translateY(-50px)}50%{opacity:1;transform:rotate(10deg)}75%{transform:rotate(-5deg)}100%{opacity:1;transform:none}}
@keyframes gp-roll{from{opacity:0;transform:translateX(360px) rotate(540deg)}to{opacity:1;transform:none}}
@keyframes gp-pulse{0%{opacity:0;transform:scale(.5)}50%{opacity:1;transform:scale(1.14)}72%{transform:scale(.96)}100%{opacity:1;transform:none}}
@keyframes gp-stomp{0%{opacity:0;transform:scale(3.2)}60%{opacity:1;transform:scale(.9)}80%{transform:scale(1.04)}100%{opacity:1;transform:none}}
@keyframes gp-drift{from{opacity:0;transform:translate(-240px,-150px) rotate(-8deg)}to{opacity:1;transform:none}}
@keyframes gp-focus{from{opacity:0;transform:scale(1.18);filter:blur(34px)}to{opacity:1;transform:none;filter:blur(0)}}
@keyframes gp-whoosh{0%{opacity:0;transform:translateX(900px) skewX(-18deg);filter:blur(22px)}65%{opacity:1;transform:translateX(-26px) skewX(3deg);filter:blur(0)}100%{opacity:1;transform:none;filter:blur(0)}}
@keyframes gp-spot{0%{opacity:0;transform:scale(.2);filter:brightness(2.6)}60%{opacity:1;transform:scale(1.1);filter:brightness(1.35)}100%{opacity:1;transform:none;filter:brightness(1)}}
@keyframes g-halo{0%{opacity:0;transform:scale(.4)}35%{opacity:1;transform:scale(1)}100%{opacity:.35;transform:scale(1.1)}}
@keyframes g-streak{0%{opacity:0;left:-45%}15%{opacity:1}100%{opacity:0;left:125%}}
@keyframes g-ring{0%{opacity:0;transform:translateX(70%) rotate(-18deg) scale(.6)}30%{opacity:1}100%{opacity:0;transform:translateX(-70%) rotate(-18deg) scale(1.1)}}
@keyframes gt-slide{from{opacity:0;transform:translateX(-90px)}to{opacity:1;transform:none}}
@keyframes gt-sweep{from{clip-path:inset(0 100% 0 0)}to{clip-path:inset(0 0 0 0)}}
@keyframes gt-zoom{from{opacity:0;transform:scale(.5)}to{opacity:1;transform:none}}
@keyframes gt-blur{from{opacity:0;filter:blur(24px);transform:translateY(12px)}to{opacity:1;filter:blur(0);transform:none}}
@keyframes gt-bounce{0%{opacity:0;transform:translateY(-90px)}55%{opacity:1;transform:translateY(8px)}75%{transform:translateY(-10px)}100%{opacity:1;transform:none}}
@keyframes gt-flip{from{opacity:0;transform:perspective(700px) rotateX(-90deg)}to{opacity:1;transform:perspective(700px) rotateX(0)}}
@keyframes gt-type{from{clip-path:inset(0 100% 0 0)}to{clip-path:inset(0 0 0 0)}}
@keyframes gd-fade{from{opacity:0}to{opacity:1}}
@keyframes gd-slide{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:none}}
@keyframes gpr-pop{0%{opacity:0;transform:scale(.3)}60%{opacity:1;transform:scale(1.14)}100%{opacity:1;transform:none}}
@keyframes gpr-stamp{0%{opacity:0;transform:scale(2.6) rotate(-8deg)}65%{opacity:1;transform:scale(.92) rotate(1deg)}100%{opacity:1;transform:none}}
@keyframes gpr-glow{0%{opacity:0;filter:blur(18px) brightness(2.2)}100%{opacity:1;filter:blur(0) brightness(1)}}
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
  function seqOn(S) { return !!(S.anim && S.anim.flow === 'seq') && (S.layout === 'duo' || S.layout === 'trio'); }
  function slideSecs(S) {
    var k = S.layout === 'duo' ? 2 : (S.layout === 'trio' ? 3 : 1);
    return Math.max(5, S.seconds, seqOn(S) ? (k - 1) * (CARD_MS / 1000) + 5.5 : 0);
  }
  function animClasses(S) {
    var A = S.anim || {}, cin = S.layout === 'cinema';
    function o(k, def) { var v = A[k] || 'auto'; return v === 'auto' ? def : v; }
    return ' ap-' + o('pic', cin ? 'fly' : 'rise') + ' at-' + o('title', cin ? 'words' : 'slide') + ' apr-' + o('price', cin ? 'stamp' : 'pop') + ' ad-' + o('desc', 'fade');
  }
  function sizes() {
    var h = slidesEl.clientHeight || 800, port = stage.className.indexOf('P') > -1;
    stage.style.setProperty('--sh', h + 'px'); stage.style.setProperty('--bs', (port ? Math.min(980, Math.round(h * 0.5)) : h) + 'px');
  }
  window.addEventListener('resize', fit);
  function clock() { var d = new Date(); $('ck').textContent = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }); }
  function font(name) {
    var id = 'f-' + name.replace(/\W/g, ''); if (document.getElementById(id)) return;
    var l = document.createElement('link'); l.id = id; l.rel = 'stylesheet'; var W = { 'Archivo Black': '', 'Bebas Neue': '', 'Oswald': ':wght@400;600;700', 'Space Grotesk': ':wght@400;600;700' }; var w = W.hasOwnProperty(name) ? W[name] : ':wght@400;600;700;800';
    l.href = 'https://fonts.googleapis.com/css2?family=' + encodeURIComponent(name).replace(/%20/g, '+') + w + '&display=swap'; document.head.appendChild(l);
  }
  function pic(it, cls) { return it.img ? '<img class="' + (cls || '') + (it.cutout ? ' cut' : '') + '" src="' + esc(it.img) + '" alt="" onerror="this.style.visibility=\'hidden\'">' : ''; }
  function tagOf(it) { return it.badge || it.cat || ''; }
  var CARD_MS = 3400;
  function priceHtml(it, S) {
    return '<div class="pr t2"><b class="cnt" data-pre="₹" data-d="1350" data-v="' + esc(String(it.price)) + '">' + esc(money(it.price)) + '</b>' + (S.show.mrp && it.mrp ? '<s>' + esc(money(it.mrp)) + '</s>' : '') + (S.show.save && it.save ? '<i>Save ' + it.save + '%</i>' : '') + '</div>';
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
  function lum(h) { var c = rgb(h).split(',').map(function (v) { v = +v / 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); }); return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2]; }
  function ratio(a, b) { var x = lum(a), y = lum(b); return (Math.max(x, y) + 0.05) / (Math.min(x, y) + 0.05); }
  function mid(a, b) { var p = rgb(a).split(',').map(Number), q = rgb(b).split(',').map(Number); return '#' + [0, 1, 2].map(function (i) { return ('0' + Math.round((p[i] + q[i]) / 2).toString(16)).slice(-2); }).join(''); }
  function readable(fg, bg) { if (ratio(fg, bg) >= 3) return fg; return ratio('#FFFFFF', bg) >= ratio('#1C1411', bg) ? '#FFFFFF' : '#1C1411'; }
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
      '<div class="cn-box"><div class="cn-rays"></div><div class="cn-sw"></div><div class="cn-glow"></div><div class="cn-ring a"></div><div class="cn-ring b"></div><div class="cn-part">' + parts + '</div>' +
      '<div class="cn-dish"><div class="cn-shadow"></div><div class="cn-in">' + img + '</div>' + (it.cutout ? '<div class="cn-shine"></div>' : '') + '</div>' +
      (S.show.save && it.save ? '<div class="cn-save"><small>SAVE</small><strong>' + it.save + '%</strong></div>' : '') + '</div>' +
      '<div class="cn-tx">' + (tagOf(it) ? '<div class="cn-tag">' + esc(tagOf(it)) + '</div>' : '') + words(it.name) +
      (it.pack ? '<div class="cn-pack"><span>' + esc(it.pack) + '</span></div>' : '') +
      '<div class="cn-row"><div class="cn-price"><span class="cu">₹</span><b class="cnt" data-pre="" data-d="1700" data-v="' + esc(String(it.price)) + '">' + esc(Number(it.price).toLocaleString('en-IN')) + '</b></div>' + (S.show.mrp && it.mrp ? '<div class="cn-mrp">' + esc(money(it.mrp)) + '</div>' : '') + '</div>' +
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
      var v = parseFloat(el.getAttribute('data-v')) || 0, dec = v % 1 !== 0, pre = el.getAttribute('data-pre') || '';
      var fmt = function (x) { return pre + (dec ? x.toFixed(2) : Math.round(x).toLocaleString('en-IN')); };
      var off = el.closest ? el.closest('.card') : null, i = off ? (parseInt(off.style.getPropertyValue('--i'), 10) || 0) : 0;
      var base = +el.getAttribute('data-d') || 1350, stride = D && D.screen.anim && D.screen.anim.flow === 'seq' ? CARD_MS : 120;
      if (reduce || stage.className.indexOf('mot-rise') < 0 || (D && D.screen.anim && D.screen.anim.count === false)) { el.textContent = fmt(v); return; }
      el.textContent = fmt(0);
      setTimeout(function () { var t0 = Date.now(), dur = 900; (function step() { var k = Math.min(1, (Date.now() - t0) / dur), e = 1 - Math.pow(1 - k, 3); el.textContent = fmt(v * e); if (k < 1 && cur.classList.contains('on')) requestAnimationFrame(step); else el.textContent = fmt(v); })(); }, base + i * stride);
    });
  }
  function swap(i) {
    var prev = slideEls[idx]; idx = (i + slideEls.length) % slideEls.length; var cur = slideEls[idx];
    var cin = D && D.screen.layout === 'cinema';
    if (prev && prev !== cur) { prev.classList.remove('on', 'settled'); if (!cin) { prev.classList.add('leave'); setTimeout(function () { prev.classList.remove('leave'); }, 520); } }
    cur.classList.remove('leave'); void cur.offsetWidth; cur.classList.add('on'); cur.classList.remove('settled');
    countUp(cur);
    var kc = cur.querySelectorAll('.card').length, seq = D && D.screen.anim && D.screen.anim.flow === 'seq' && D.screen.layout !== 'cinema';
    setTimeout(function () { cur.classList.add('settled'); }, seq ? (kc - 1) * CARD_MS + 3200 : 1900);
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
    stage.className = (S.orientation === 'portrait' ? 'P' : 'L') + ' ' + (reduce || S.motion === 'none' ? 'mot-none' : 'mot-' + S.motion) + (S.idle && !reduce ? ' idle' : '') + (S.layout === 'cinema' ? ' cinema' + (reduce || S.motion === 'none' ? '' : (S.motion === 'fade' ? ' cin-soft' : ' cin-anim')) : '') + animClasses(S);
    shown = false;
    var st = stage.style; var txc = T.text, ctc = T.cardText; if (S.contrast !== false) { txc = readable(T.text, mid(T.bg1, T.bg2)); ctc = readable(T.cardText, T.card); } T = Object.assign({}, T, { text: txc, cardText: ctc });
    st.setProperty('--bg1', T.bg1); st.setProperty('--bg2', T.bg2); st.setProperty('--ang', T.angle + 'deg'); st.setProperty('--tx', T.text); st.setProperty('--card', T.card); st.setProperty('--ct', T.cardText);
    st.setProperty('--ac', T.accent); st.setProperty('--ac2', T.accent2); st.setProperty('--font', "'" + T.font + "'"); st.setProperty('--bgo', (T.bgImageOpacity / 100)); st.setProperty('--cs', S.layout === 'cinema' ? '0s' : (seqOn(S) ? (CARD_MS / 1000) + 's' : '.12s')); st.setProperty('--ac-rgb', rgb(T.accent)); st.setProperty('--ac2-rgb', rgb(T.accent2)); st.setProperty('--tx-rgb', rgb(T.text)); st.setProperty('--sec', slideSecs(S) + 's');
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
    if (slideEls.length > 1) timer = setInterval(function () { show(idx + 1); }, slideSecs(S) * 1000);
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

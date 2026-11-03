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

/* ===== Update 4: effects engine, Menu wall, slide changes, rolling price ===== */
#fxb,#fxf{position:absolute;left:0;top:0;width:100%;height:100%;pointer-events:none}
#fxb{z-index:1}#fxf{z-index:8}
/* dish wrapper + light shine that follows the dish outline */
.dw{position:absolute;inset:0}
.gl{position:absolute;inset:0;pointer-events:none;opacity:0;background:linear-gradient(105deg,transparent 38%,rgba(255,255,255,.75) 50%,transparent 62%) no-repeat;background-size:300% 100%;background-position:150% 0}
.gl.m{-webkit-mask-image:var(--src);mask-image:var(--src);-webkit-mask-size:contain;mask-size:contain;-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat;-webkit-mask-position:center bottom;mask-position:center bottom}
.card .im .gl.m{inset:10px 14px 0}
.fx-glint .slide.on.settled .gl{animation:glint 6s ease-in-out infinite;animation-delay:calc(var(--i,0) * .45s + .4s)}
@keyframes glint{0%{opacity:1;background-position:150% 0}22%{opacity:1;background-position:-50% 0}23%,100%{opacity:0;background-position:-50% 0}}
/* new picture animations */
@keyframes gp-mat{0%{opacity:0;transform:scale(.9);filter:blur(22px) brightness(3) saturate(.3)}35%{opacity:1;filter:blur(7px) brightness(1.8) saturate(.8)}100%{opacity:1;transform:none;filter:none}}
@keyframes gp-land{0%{opacity:0;filter:brightness(1.8)}100%{opacity:1;filter:none}}
.mot-rise.ap-mat .slide.on .dish,.mot-rise.ap-mat .slide.on .cn-dish{animation:gp-mat 1.1s cubic-bezier(.2,.8,.2,1) both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dp))}
.mot-rise.ap-land .slide.on .dish,.mot-rise.ap-land .slide.on .cn-dish{animation:gp-land .6s ease-in both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dp) + .05s)}
/* rolling digits */
.mot-rise.apr-roll .slide.on .pr.t2{animation:gpr-pop .55s cubic-bezier(.2,1.4,.3,1) both;transform-origin:left center;animation-delay:calc(var(--i,0)*var(--cs) + var(--dpr))}
.cin-anim.apr-roll .slide.on .cn-price{animation:cnplate .8s cubic-bezier(.2,1.4,.3,1) 1.45s both,cnburst 1s ease-out 1.75s both}
.od{display:inline-block;height:1.16em;line-height:1.16em;overflow:hidden;vertical-align:top;margin:-.08em 0}
.os{display:block;transition:transform 1.25s cubic-bezier(.16,.9,.2,1);transition-delay:calc(var(--k) * 85ms)}
.os>span{display:block;height:1.16em;line-height:1.16em}
.rolling{white-space:nowrap}
.pr b,.wl-p,.cn-price b{font-variant-numeric:tabular-nums}
.pr b.rolling{background:none;color:transparent}
.pr b.rolling>span,.pr b.rolling .os>span{background:linear-gradient(110deg,var(--ac),var(--ac2));-webkit-background-clip:text;background-clip:text;color:transparent}
/* slide changes */
#stage.tc .slide{transition:none}
.slide.on{z-index:2}.slide.leave{z-index:1}
.slide.on.tr-zoom{animation:sz-in .75s cubic-bezier(.2,.8,.2,1) both}.slide.leave.tr-zoom{animation:sz-out .6s ease-in both}
.slide.on.tr-slide{animation:ss-in .7s cubic-bezier(.2,.8,.2,1) both}.slide.leave.tr-slide{animation:ss-out .6s cubic-bezier(.6,0,.8,.4) both}
.slide.on.tr-iris{animation:si-in .95s cubic-bezier(.6,0,.2,1) both}.slide.leave.tr-iris{opacity:1;animation:hold .95s linear both}
@keyframes sz-in{from{opacity:0;transform:scale(.93);filter:blur(10px)}to{opacity:1;transform:none;filter:none}}
@keyframes sz-out{from{opacity:1;transform:none;filter:none}to{opacity:0;transform:scale(1.12);filter:blur(12px)}}
@keyframes ss-in{from{opacity:0;transform:translateX(9%)}to{opacity:1;transform:none}}
@keyframes ss-out{from{opacity:1;transform:none}to{opacity:0;transform:translateX(-9%)}}
@keyframes si-in{from{opacity:1;clip-path:circle(0% at 50% 55%)}to{opacity:1;clip-path:circle(75% at 50% 55%)}}
@keyframes hold{from{opacity:1}to{opacity:1}}
#flash{position:absolute;inset:0;z-index:9;pointer-events:none;opacity:0;background:radial-gradient(ellipse at 50% 50%,rgba(255,255,255,.95),rgba(255,255,255,.55) 35%,rgba(255,255,255,0) 70%)}
#flash:before{content:"";position:absolute;top:-20%;bottom:-20%;left:-40%;width:22%;background:linear-gradient(100deg,transparent,#fff,transparent);transform:skewX(-18deg)}
#flash.go{animation:fl-o .75s ease both}#flash.go:before{animation:fl-b .75s cubic-bezier(.6,0,.3,1) both}
@keyframes fl-o{0%{opacity:0}40%{opacity:.9}100%{opacity:0}}
@keyframes fl-b{from{left:-40%}to{left:130%}}
#blinds{position:absolute;inset:0;z-index:9;pointer-events:none;display:flex;flex-direction:column}
#blinds i{flex:1;background:linear-gradient(90deg,var(--ac),var(--ac2));transform:scaleY(0)}
#blinds.go i{animation:bl .9s cubic-bezier(.7,0,.3,1) both;animation-delay:calc(var(--b) * 40ms)}
@keyframes bl{0%{transform:scaleY(0);transform-origin:top}45%,55%{transform:scaleY(1.02);transform-origin:top}55.1%{transform-origin:bottom}100%{transform:scaleY(0);transform-origin:bottom}}
/* ===== Menu wall (KFC style) ===== */
.wall .slide,.wall-on .slide{padding:0 50px 10px}
.wall{height:100%;display:flex;flex-direction:column}
.wl-head{flex:none;height:160px;text-align:center;display:flex;flex-direction:column;align-items:center;justify-content:center;position:relative}
.wl-k{font-size:30px;font-weight:700;letter-spacing:7px;text-transform:uppercase;color:var(--tx);opacity:.88;font-style:italic}
.wl-h{position:relative;font-size:128px;font-weight:800;line-height:.95;letter-spacing:-2px;font-style:italic;color:var(--tx);text-shadow:0 7px 0 rgba(var(--ac-rgb),.85),0 18px 40px rgba(0,0,0,.35);white-space:nowrap}
.wl-h:after{content:attr(data-t);position:absolute;inset:0;color:transparent;text-shadow:none;background:linear-gradient(105deg,transparent 40%,rgba(255,255,255,.95) 50%,transparent 60%) no-repeat;background-size:300% 100%;background-position:150% 0;-webkit-background-clip:text;background-clip:text}
.fx-glint .slide.on.settled .wl-h:after{animation:hglint 5.5s ease-in-out 1s infinite}
@keyframes hglint{0%{background-position:150% 0}25%,100%{background-position:-50% 0}}
.mot-rise .slide.on .wl-k{animation:gt-blur .8s ease-out .05s both}
.mot-rise .slide.on .wl-h{animation:wl-hin .9s cubic-bezier(.2,1.3,.3,1) .15s both}
@keyframes wl-hin{0%{opacity:0;transform:scale(1.6) skewX(-8deg);filter:blur(14px)}60%{opacity:1;transform:scale(.96);filter:none}100%{opacity:1;transform:none;filter:none}}
.wall.nohead .wl-cols{padding-top:18px}
.wl-cols{flex:1;min-height:0;display:grid;grid-template-columns:repeat(var(--n),minmax(0,1fr));gap:30px}
.wl-col{min-width:0;min-height:0;display:flex;flex-direction:column}
.wl-pic{position:relative;flex:1;min-height:0}
.wl-ph{position:absolute;left:8%;right:8%;top:6%;bottom:4%;border-radius:50%;opacity:.22;background:radial-gradient(ellipse at 50% 55%,rgba(255,236,214,.55),rgba(255,236,214,.18) 45%,rgba(255,236,214,0) 70%)}
.mot-rise.fx .slide.on .wl-ph{animation:phout .9s ease both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dp))}
@keyframes phout{from{opacity:1}to{opacity:.22}}
.wl-pic:after{content:"";position:absolute;left:14%;right:14%;bottom:1%;height:9%;border-radius:50%;background:radial-gradient(ellipse,rgba(0,0,0,.5),rgba(0,0,0,0) 70%);z-index:0}
.wl-in{position:absolute;inset:0;z-index:1}
.wl-in img{position:absolute;inset:0;width:100%;height:100%;object-fit:contain;object-position:center bottom;filter:drop-shadow(0 26px 22px rgba(0,0,0,.45))}
.wl-in img.photo{object-fit:cover;object-position:center;border-radius:30px;inset:6% 4% 4%;width:92%;height:90%;box-shadow:0 26px 50px rgba(0,0,0,.45);filter:none;border:6px solid rgba(255,255,255,.9)}
.wl-in .gl.m{inset:0}.wl-in .gl.p{inset:6% 4% 4%;border-radius:30px}
.wl-ini{position:absolute;inset:14%;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:150px;font-weight:800;color:#fff;background:linear-gradient(135deg,var(--ac),var(--ac2));box-shadow:0 26px 50px rgba(0,0,0,.4)}
.wl-stk{position:absolute;right:2%;top:2%;z-index:3;width:150px;height:150px;border-radius:50%;background:radial-gradient(circle at 35% 30%,#FFE27A,#FFB800 60%,#F08A00);color:#7A1200;display:flex;align-items:center;justify-content:center;text-align:center;font-weight:800;font-size:28px;line-height:1.02;padding:16px;text-transform:uppercase;box-shadow:0 12px 30px rgba(0,0,0,.4),inset 0 0 0 5px rgba(255,255,255,.6);transform:rotate(-12deg)}
.wl-stk span{display:block;overflow:hidden;max-height:3.1em}
.mot-rise .slide.on .wl-stk{animation:stk .8s cubic-bezier(.2,1.6,.3,1) both,cnwob 2.6s ease-in-out infinite alternate;animation-delay:calc(var(--i,0)*var(--cs) + var(--dp) + .75s),calc(var(--i,0)*var(--cs) + var(--dp) + 1.6s)}
@keyframes stk{0%{opacity:0;transform:scale(0) rotate(-140deg)}100%{opacity:1;transform:rotate(-12deg)}}
.wl-tx{flex:none;position:relative;z-index:2;padding-top:12px;color:var(--tx)}
.L .wl-tx{height:292px;overflow:hidden}.L .wl-tx.one{height:330px}
.wl-cat{font-size:44px;font-weight:800;letter-spacing:1px;text-transform:uppercase;font-style:italic;line-height:1.05;margin-bottom:8px;background:linear-gradient(100deg,#FFD24A,#FF9A1F);-webkit-background-clip:text;background-clip:text;color:transparent;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.light .wl-cat{background:linear-gradient(100deg,var(--ac),var(--ac2));-webkit-background-clip:text;background-clip:text}
.wl-row{display:flex;align-items:baseline;justify-content:space-between;gap:14px;padding:5px 0;border-bottom:2px dashed rgba(var(--tx-rgb),.16)}
.wl-row:last-child{border-bottom:0}
.wl-n{min-width:0;flex:1;display:flex;align-items:baseline;gap:10px}
.wl-n b{font-size:29px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;min-width:0}
.wl-n small{font-size:22px;font-weight:600;opacity:.7;white-space:nowrap;flex:none}
.wl-n em{flex:none;font-style:normal;font-size:18px;font-weight:700;color:#fff;background:var(--ac);border-radius:99px;padding:2px 10px}
.wl-p{flex:none;font-size:36px;font-weight:800;font-variant-numeric:tabular-nums}
.wl-row.hero .wl-n b{font-size:32px;font-weight:800}.wl-row.hero .wl-p{font-size:44px;color:#FFD24A}
.light .wl-row.hero .wl-p{color:var(--ac)}
.mot-rise .slide.on .wl-cat{animation:gt-slide .6s cubic-bezier(.2,.9,.2,1) both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dt))}
.mot-rise .slide.on .wl-row{animation:gd-slide .55s ease both;animation-delay:calc(var(--i,0)*var(--cs) + var(--dt) + .18s + var(--r,0) * 90ms)}
.wl-tx.one{display:flex;flex-direction:column;gap:8px}
.wl-tx.one .nm{font-size:50px;color:var(--tx)}
.wl-tx.one .pr b{font-size:96px}.wl-tx.one .pr s{font-size:32px;color:var(--tx)}.wl-tx.one .pr i{font-size:22px}
.wl-tx.one .ch{color:var(--tx)}.wl-tx.one .ch span{font-size:22px}
.wl-tx.one .ds{font-size:24px;color:var(--tx);-webkit-line-clamp:2}
.wl-tx.one .tag{font-size:20px;padding:5px 16px}
.P .wl-head{height:220px}.P .wl-h{font-size:118px}
.P .wl-cols{grid-template-columns:1fr;grid-template-rows:repeat(var(--n),minmax(0,1fr));gap:26px}
.P .wl-col{flex-direction:row;gap:26px}.P .wl-pic{flex:0 0 46%}.P .wl-tx{flex:1;display:flex;flex-direction:column;justify-content:center;padding-top:0}
.wall-on .more{display:none!important}
</style>
</head>
<body data-key="{{ $key }}" data-preview="{{ $preview ? 1 : 0 }}" data-kiosk="{{ $kiosk ? 1 : 0 }}" data-logo="{{ $logo }}">
<div id="stage" class="L mot-rise"><div class="bg"></div><div class="bgi"></div>
  <header class="hd"><div class="l"><div class="lg"><img id="lg" alt="Freshful"></div><span class="ot" id="ot"></span></div><div class="ck" id="ck"></div></header>
  <main class="slides" id="slides"></main>
  <aside class="more" id="more" style="display:none"></aside>
  <footer class="tk" id="tk" style="display:none"><span class="run" id="run"></span></footer>
  <div id="wipe"></div><div id="flash"></div><div id="blinds"><i style="--b:0"></i><i style="--b:1"></i><i style="--b:2"></i><i style="--b:3"></i><i style="--b:4"></i><i style="--b:5"></i><i style="--b:6"></i></div>
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
  function fxOn(S) { return !reduce && S.motion === 'rise' && !!S.fx && S.fx.intro !== 'none' && S.layout !== 'board'; }
  function speedK(S) { return ({ quick: .7, normal: 1, relaxed: 1.4 })[(S.fx || {}).speed] || 1; }
  function timing(S) {
    var L = S.layout, sh = 0, m = (S.fx || {}).intro || 'auto', T;
    if (fxOn(S)) sh = m === 'assemble' ? .85 : (m === 'auto' || m === 'streak' ? .4 : (m === 'smoke' || m === 'beam' ? .3 : .15));
    if (L === 'wall') T = { dp: .3, cs: .82 * speedK(S), dt: .85, dpr: 1.05, dd: 1.3 };
    else if (L === 'cinema') T = { dp: .15, cs: 0, dt: .55, dpr: 1.45, dd: 3.2 };
    else T = { dp: .2, cs: seqOn(S) ? CARD_MS / 1000 * speedK(S) : (fxOn(S) ? .55 * speedK(S) : .12), dt: .75, dpr: 1.25, dd: 1.85 };
    T.dp += sh; T.dt += sh; T.dpr += sh; T.dd += sh; return T;
  }
  function wallCount(S) { return S.orientation === 'portrait' ? 3 : 4; }
  function seqOn(S) { return !!(S.anim && S.anim.flow === 'seq') && (S.layout === 'duo' || S.layout === 'trio'); }
  function slideSecs(S) {
    var k = S.layout === 'duo' ? 2 : (S.layout === 'trio' ? 3 : 1);
    var T = timing(S), n = S.layout === 'wall' ? wallCount(S) : k;
    return Math.max(5, S.seconds, Math.ceil(T.dp + (n - 1) * T.cs + 4.5));
  }
  function animClasses(S) {
    var A = S.anim || {}, cin = S.layout === 'cinema';
    function o(k, def) { var v = A[k] || 'auto'; return v === 'auto' ? def : v; }
    var fxp = fxOn(S) ? (((S.fx || {}).intro === 'assemble') ? 'land' : 'mat') : null;
    return ' ap-' + o('pic', fxp || (cin ? 'fly' : 'rise')) + ' at-' + o('title', cin ? 'words' : 'slide') + ' apr-' + o('price', cin ? 'stamp' : (S.layout === 'wall' ? 'roll' : 'pop')) + ' ad-' + o('desc', 'fade');
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
  function gl(it) { return it.cutout ? '<i class="gl m" style="--src:url(&quot;' + esc(encodeURI(it.img)) + '&quot;)"></i>' : '<i class="gl p"></i>'; }
  function pic(it, cls) {
    if (!it.img) return '';
    var im = '<img class="' + (it.cutout ? 'cut' : '') + '" src="' + esc(it.img) + '" alt="" onerror="this.style.visibility=\'hidden\'">';
    return cls === 'dish' ? '<div class="dish dw">' + im + gl(it) + '</div>' : '<img class="' + (cls || '') + (it.cutout ? ' cut' : '') + '" src="' + esc(it.img) + '" alt="" onerror="this.style.visibility=\'hidden\'">';
  }
  function tagOf(it) { return it.badge || it.cat || ''; }
  var CARD_MS = 3400;
  function priceHtml(it, S) {
    return '<div class="pr t2"><b class="cnt" data-pre="₹" data-d="1350" data-v="' + esc(String(it.price)) + '">' + esc(money(it.price)) + '</b>' + (S.show.mrp && it.mrp ? '<s>' + esc(money(it.mrp)) + '</s>' : '') + (S.show.save && it.save ? '<i>Save ' + it.save + '%</i>' : '') + '</div>';
  }
  function chips(it, S) { return '<div class="ch t3">' + (it.pack ? '<span>' + esc(it.pack) + '</span>' : '') + (S.show.stock && it.few ? '<span class="few">Only a few left</span>' : '') + (it.veg === true ? '<span>Veg</span>' : '') + '</div>'; }
  function cardHtml(it, i, S, kind) {
    var t = tagOf(it);
    return '<article class="card" style="--i:' + i + '"><div class="ct">' + (t ? '<div class="tag t1">' + esc(t) + '</div>' : '') + '<h2 class="nm t1">' + esc(it.name) + '</h2>' + priceHtml(it, S) + chips(it, S) + (it.desc ? '<p class="ds t3">' + esc(it.desc) + '</p>' : '') + '</div><div class="im" data-slot>' + pic(it, 'dish') + '</div></article>';
  }
  function rowHtml(it, i, S) {
    return '<div class="row" style="--i:' + i + '"><div class="th">' + pic(it, '') + '</div><div class="tx"><b>' + esc(it.name) + '</b><small>' + esc(it.pack) + '</small>' + (S.show.stock && it.few ? '<em>Few left</em>' : '') + '</div><div class="pp"><b>' + esc(money(it.price)) + '</b>' + (S.show.mrp && it.mrp ? '<s>' + esc(money(it.mrp)) + '</s>' : '') + '</div></div>';
  }

  function rgb(h) { h = String(h || '#000000').replace('#', ''); return parseInt(h.substr(0, 2), 16) + ',' + parseInt(h.substr(2, 2), 16) + ',' + parseInt(h.substr(4, 2), 16); }
  function lum(h) { var c = rgb(h).split(',').map(function (v) { v = +v / 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); }); return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2]; }
  function ratio(a, b) { var x = lum(a), y = lum(b); return (Math.max(x, y) + 0.05) / (Math.min(x, y) + 0.05); }
  function mid(a, b) { var p = rgb(a).split(',').map(Number), q = rgb(b).split(',').map(Number); return '#' + [0, 1, 2].map(function (i) { return ('0' + Math.round((p[i] + q[i]) / 2).toString(16)).slice(-2); }).join(''); }
  function readable(fg, bg) { if (ratio(fg, bg) >= 3) return fg; return ratio('#FFFFFF', bg) >= ratio('#1C1411', bg) ? '#FFFFFF' : '#1C1411'; }

  /* ===== Effects engine (update 4) =====
     Two canvases: #fxb behind the menu (background effect), #fxf in front (light streak, dust, sparks, particles).
     Every effect is timed from the same delays the CSS uses, so the light reaches each dish exactly when it appears.
     Drawn at reduced resolution and with automatic quality steps, so a TV stick stays smooth. */
  var FX = (function () {
    var fb, ff, cb, cf, R = 0.5, W = 1920, H = 1080, run = false, raf = 0, last = 0, q = 1, frames = 0, slow = 0;
    var P = [], A = [], streak = null, beams = [], rings = [], t0 = 0, cfg = null, dark = true, acc = '255,85,0', acc2 = '255,85,0', ambient = 'none', ambT = 0, rays = [];
    var spr = {};
    function now() { return (window.performance ? performance.now() : Date.now()) / 1000; }
    function sprite(rgbs) {
      if (spr[rgbs]) return spr[rgbs];
      var c = document.createElement('canvas'); c.width = c.height = 64; var x = c.getContext('2d');
      var g = x.createRadialGradient(32, 32, 0, 32, 32, 32); g.addColorStop(0, 'rgba(' + rgbs + ',1)'); g.addColorStop(.35, 'rgba(' + rgbs + ',.55)'); g.addColorStop(1, 'rgba(' + rgbs + ',0)');
      x.fillStyle = g; x.fillRect(0, 0, 64, 64); spr[rgbs] = c; return c;
    }
    function star(rgbs) {
      var k = '*' + rgbs; if (spr[k]) return spr[k];
      var c = document.createElement('canvas'); c.width = c.height = 64; var x = c.getContext('2d');
      x.drawImage(sprite(rgbs), 16, 16, 32, 32); x.globalCompositeOperation = 'lighter';
      var g = x.createLinearGradient(0, 32, 64, 32); g.addColorStop(0, 'rgba(' + rgbs + ',0)'); g.addColorStop(.5, 'rgba(255,255,255,.95)'); g.addColorStop(1, 'rgba(' + rgbs + ',0)');
      x.fillStyle = g; x.fillRect(0, 30, 64, 4); x.save(); x.translate(32, 32); x.rotate(Math.PI / 2); x.fillRect(-32, -2, 64, 4); x.restore();
      spr[k] = c; return c;
    }
    function init() {
      if (fb) return;
      fb = document.createElement('canvas'); fb.id = 'fxb'; ff = document.createElement('canvas'); ff.id = 'fxf';
      stage.insertBefore(fb, stage.children[2] || null); stage.appendChild(ff);
      cb = fb.getContext('2d'); cf = ff.getContext('2d');
      document.addEventListener('visibilitychange', function () { if (document.hidden) stop(); else kick(); });
    }
    function size(port) {
      W = port ? 1080 : 1920; H = port ? 1920 : 1080;
      [fb, ff].forEach(function (c) { c.width = Math.round(W * R); c.height = Math.round(H * R); });
    }
    function rectOf(el) {
      var x = 0, y = 0, e = el;
      while (e && e !== stage) { x += e.offsetLeft; y += e.offsetTop; e = e.offsetParent; }
      return { x: x, y: y, w: el.offsetWidth, h: el.offsetHeight };
    }
    /* where the picture really sits inside its box (object-fit: contain, bottom aligned) */
    function imgRect(box, img) {
      var r = rectOf(box);
      if (!img || !img.naturalWidth) return { x: r.x + r.w * .15, y: r.y + r.h * .15, w: r.w * .7, h: r.h * .7 };
      var cs = getComputedStyle(img), fit = cs.objectFit, ir = rectOf(img);
      if (fit !== 'contain') return ir;
      var k = Math.min(ir.w / img.naturalWidth, ir.h / img.naturalHeight), w = img.naturalWidth * k, h = img.naturalHeight * k;
      var pb = parseFloat(cs.paddingBottom) || 0, pl = parseFloat(cs.paddingLeft) || 0, pr = parseFloat(cs.paddingRight) || 0, pt = parseFloat(cs.paddingTop) || 0;
      var cw = ir.w - pl - pr, ch = ir.h - pt - pb; k = Math.min(cw / img.naturalWidth, ch / img.naturalHeight); w = img.naturalWidth * k; h = img.naturalHeight * k;
      var bottom = (cs.objectPosition || '').indexOf('bottom') > -1 || /100%$/.test(cs.objectPosition || '');
      return { x: ir.x + pl + (cw - w) / 2, y: ir.y + pt + (bottom ? ch - h : (ch - h) / 2), w: w, h: h };
    }
    function avg(img) {
      try {
        var c = document.createElement('canvas'); c.width = c.height = 24; var x = c.getContext('2d'); x.drawImage(img, 0, 0, 24, 24);
        var d = x.getImageData(0, 0, 24, 24).data, r = 0, g = 0, b = 0, n = 0;
        for (var i = 0; i < d.length; i += 4) if (d[i + 3] > 120) { r += d[i]; g += d[i + 1]; b += d[i + 2]; n++; }
        if (!n) return null; r /= n; g /= n; b /= n;
        var m = Math.max(r, g, b) || 1, s = 235 / m; return Math.round(Math.min(255, r * s)) + ',' + Math.round(Math.min(255, g * s)) + ',' + Math.round(Math.min(255, b * s));
      } catch (e) { return null; }
    }
    function pixels(img, rect, max) {
      try {
        var lw = 120, k = lw / Math.max(img.naturalWidth, img.naturalHeight), w = Math.max(8, Math.round(img.naturalWidth * k)), h = Math.max(8, Math.round(img.naturalHeight * k));
        var c = document.createElement('canvas'); c.width = w; c.height = h; var x = c.getContext('2d'); x.drawImage(img, 0, 0, w, h);
        var d = x.getImageData(0, 0, w, h).data, out = [], st = 2;
        for (var yy = 0; yy < h; yy += st) for (var xx = 0; xx < w; xx += st) { var i = (yy * w + xx) * 4; if (d[i + 3] > 90) out.push([xx, yy, d[i], d[i + 1], d[i + 2]]); }
        for (var j = out.length - 1; j > 0; j--) { var z = Math.floor(Math.random() * (j + 1)), tmp = out[j]; out[j] = out[z]; out[z] = tmp; }
        out = out.slice(0, max); var sx = rect.w / w, sy = rect.h / h;
        return out.map(function (p) { return { x: rect.x + p[0] * sx, y: rect.y + p[1] * sy, c: 'rgb(' + p[2] + ',' + p[3] + ',' + p[4] + ')', s: Math.max(3, sx * st * 1.15) }; });
      } catch (e) { return null; }
    }
    function rnd(a, b) { return a + Math.random() * (b - a); }
    function ease(u) { return 1 - Math.pow(1 - Math.max(0, Math.min(1, u)), 3); }
    /* ---- particles ---- */
    function add(o) { if (P.length < 2600) P.push(o); }
    function dust(c, T, col, n) {
      n = Math.round(n * q);
      for (var i = 0; i < n; i++) { var a = rnd(0, Math.PI * 2), sp = rnd(60, 420); add({ k: 'd', at: T + rnd(-.05, .12), x: c.x + rnd(-c.w * .12, c.w * .12), y: c.y + rnd(-c.h * .1, c.h * .14), vx: Math.cos(a) * sp, vy: Math.sin(a) * sp * .55 - rnd(20, 90), life: rnd(.7, 1.5), s: rnd(26, 70) * (c.w / 400), g: -40, dr: 2.6, col: Math.random() < .3 ? '255,255,255' : col, al: rnd(.25, .55) }); }
    }
    function sparks(c, T, col, n, speed) {
      n = Math.round(n * q);
      for (var i = 0; i < n; i++) { var a = rnd(0, Math.PI * 2), sp = rnd(.4, 1) * (speed || 900); add({ k: 's', at: T + rnd(0, .08), x: c.x, y: c.y, vx: Math.cos(a) * sp, vy: Math.sin(a) * sp - 120, life: rnd(.45, 1.1), s: rnd(5, 11), g: 900, dr: 2.2, col: Math.random() < .5 ? '255,244,214' : col, al: 1 }); }
    }
    function twinkle(c, T, n) {
      for (var i = 0; i < n; i++) add({ k: 't', at: T + rnd(0, .5), x: c.x + rnd(-c.w * .4, c.w * .4), y: c.y + rnd(-c.h * .35, c.h * .3), vx: 0, vy: -10, life: rnd(.5, .9), s: rnd(26, 50), g: 0, dr: 0, col: '255,250,235', al: 1 });
    }
    function smoke(c, T, n) {
      n = Math.round(n * q);
      for (var i = 0; i < n; i++) { var a = rnd(0, Math.PI * 2); add({ k: 'm', at: T - .35 + rnd(0, .25), x: c.x + Math.cos(a) * c.w * .18, y: c.y + Math.sin(a) * c.h * .12, vx: Math.cos(a) * rnd(30, 120), vy: Math.sin(a) * rnd(20, 70) - 30, life: rnd(1.3, 2.1), s: rnd(.45, .8) * c.w, g: -15, dr: .6, col: dark ? '235,228,220' : '255,255,255', al: dark ? rnd(.16, .28) : rnd(.35, .55), rot: rnd(-1, 1) }); }
    }
    function assemble(box, img, T) {
      var r = imgRect(box, img), px = pixels(img, r, Math.round(1100 * q)); if (!px) return false;
      px.forEach(function (p, i) { var a = rnd(0, Math.PI * 2), d = rnd(.5, 1.4) * Math.max(r.w, r.h); add({ k: 'p', at: T - .85 - rnd(0, .25), arr: T + rnd(-.05, .2), x0: p.x + Math.cos(a) * d + rnd(-80, 260), y0: p.y + Math.sin(a) * d * .6, x: 0, y: 0, tx: p.x, ty: p.y, life: 9, s: p.s, c: p.c, sw: rnd(-1, 1) }); });
      return true;
    }
    function ring(c, T, col) { rings.push({ at: T, x: c.x, y: c.y + c.h * .12, rx: c.w * .55, ry: c.h * .17, col: col }); }
    function beam(c, T) { beams.push({ at: T - .55, x: c.x, y: c.y, w: c.w, h: c.h }); }
    /* ---- the light streak path: travel from the last dish, loop around this one ---- */
    function plan(slots, pass) {
      var segs = [], prev = null;
      slots.forEach(function (s, k) {
        var c = s.c, T = s.T, lead = pass ? .32 : .55;
        var from = prev || { x: (k === 0 && slots.length > 1 && slots[1].c.x < c.x) ? W + 120 : (slots.length > 1 ? -120 : W + 120), y: c.y - c.h * .35 };
        var to = { x: c.x + c.w * .5, y: c.y + c.h * .12 };
        (function (a, b, mx, my) { segs.push({ t0: T - lead, t1: T - .06, f: function (u) { u = ease(u); var v = 1 - u; return { x: v * v * a.x + 2 * v * u * mx + u * u * b.x, y: v * v * a.y + 2 * v * u * my + u * u * b.y }; } }); })(from, to, (from.x + to.x) / 2, Math.min(from.y, to.y) - 160);
        var cx = c.x, cy = c.y + c.h * .12, rx = c.w * .5, ry = c.h * .16, tl = -0.2, turns = pass ? .9 : 1.12;
        segs.push({ t0: T - .06, t1: T + (pass ? .38 : .5), f: function (u) { var a = -u * Math.PI * 2 * turns, k2 = 1 + u * .12, x = Math.cos(a) * rx * k2, y = Math.sin(a) * ry * k2; return { x: cx + x * Math.cos(tl) - y * Math.sin(tl), y: cy + x * Math.sin(tl) + y * Math.cos(tl) }; } });
        prev = segs[segs.length - 1].f(1);
        if (k === slots.length - 1) { var p0 = prev; segs.push({ t0: T + (pass ? .38 : .5), t1: T + (pass ? .8 : .95), fade: 1, f: function (u) { return { x: p0.x - u * 420, y: p0.y - u * 260 }; } }); }
      });
      return segs;
    }
    function head(t) {
      if (!streak) return null;
      for (var i = 0; i < streak.length; i++) { var s = streak[i]; if (t >= s.t0 && t <= s.t1) { var p = s.f((t - s.t0) / Math.max(.001, s.t1 - s.t0)); p.a = s.fade ? 1 - (t - s.t0) / (s.t1 - s.t0) : 1; return p; } }
      return null;
    }
    /* ---- background effects ---- */
    function ambientTick(dt) {
      if (ambient === 'none') return;
      var cap = { bokeh: 26, sparks: 70, steam: 22, ice: 90, rays: 0, stars: 40 }[ambient] * q;
      ambT += dt;
      var rate = { bokeh: 3, sparks: 14, steam: 3.5, ice: 16, stars: 7 }[ambient] || 0;
      while (ambT > 1 / Math.max(.1, rate * q) && A.length < cap) {
        ambT -= 1 / Math.max(.1, rate * q);
        if (ambient === 'bokeh') A.push({ x: rnd(0, W), y: rnd(0, H), vx: rnd(-12, 12), vy: rnd(-14, -4), s: rnd(60, 190), life: rnd(7, 13), age: 0, col: Math.random() < .5 ? acc2 : '255,240,220', al: rnd(.05, .13) });
        else if (ambient === 'sparks') A.push({ x: rnd(0, W), y: H + 20, vx: rnd(-20, 20), vy: rnd(-180, -70), s: rnd(5, 12), life: rnd(3, 7), age: 0, col: Math.random() < .6 ? '255,170,60' : '255,220,140', al: rnd(.5, 1), wob: rnd(0, 6) });
        else if (ambient === 'steam') A.push({ x: rnd(W * .1, W * .9), y: H + 100, vx: rnd(-10, 10), vy: rnd(-60, -30), s: rnd(220, 420), life: rnd(8, 12), age: 0, col: '255,255,255', al: rnd(.035, .07), wob: rnd(0, 6) });
        else if (ambient === 'ice') A.push({ x: rnd(0, W), y: -20, vx: rnd(-15, 15), vy: rnd(40, 110), s: rnd(4, 10), life: rnd(9, 16), age: 0, col: '225,245,255', al: rnd(.4, .9), wob: rnd(0, 6) });
        else if (ambient === 'stars') A.push({ x: rnd(0, W), y: rnd(0, H), vx: 0, vy: 0, s: rnd(14, 34), life: rnd(1.2, 2.6), age: 0, col: '255,245,225', al: 1, tw: 1 });
      }
      if (ambient === 'ice' && A.length < cap) A.length = A.length;
      if (ambient === 'rays' && !rays.length) for (var i = 0; i < 3; i++) rays.push({ x: rnd(.15, .85) * W, a: rnd(-.35, .35), sp: rnd(.03, .07) * (Math.random() < .5 ? -1 : 1), w: rnd(.08, .16) });
    }
    function drawAmbient(t, dt) {
      var c = cb; c.setTransform(1, 0, 0, 1, 0, 0); c.clearRect(0, 0, fb.width, fb.height);
      if (ambient === 'none') return;
      c.setTransform(R, 0, 0, R, 0, 0); c.globalCompositeOperation = dark ? 'lighter' : 'source-over';
      if (ambient === 'rays') {
        rays.forEach(function (r) {
          r.a += r.sp * dt; if (r.a > .5 || r.a < -.5) r.sp = -r.sp;
          c.save(); c.translate(r.x, -60); c.rotate(r.a);
          var g = c.createLinearGradient(0, 0, 0, H * 1.2); g.addColorStop(0, 'rgba(255,240,220,' + (dark ? .14 : .35) + ')'); g.addColorStop(1, 'rgba(255,240,220,0)');
          c.fillStyle = g; c.beginPath(); c.moveTo(-20, 0); c.lineTo(20, 0); c.lineTo(W * r.w, H * 1.2); c.lineTo(-W * r.w, H * 1.2); c.closePath(); c.fill(); c.restore();
        });
      }
      if (ambient === 'ice') { var g2 = c.createLinearGradient(0, H * .72, 0, H); g2.addColorStop(0, 'rgba(210,235,255,0)'); g2.addColorStop(1, 'rgba(210,235,255,' + (dark ? .10 : .25) + ')'); c.fillStyle = g2; c.fillRect(0, H * .72, W, H * .28); }
      for (var i = A.length - 1; i >= 0; i--) {
        var p = A[i]; p.age += dt; if (p.age > p.life) { A.splice(i, 1); continue; }
        var k = p.age / p.life, fade = Math.min(1, k * 5) * Math.min(1, (1 - k) * 4);
        if (p.wob != null) p.x += Math.sin(p.age * 1.6 + p.wob) * 14 * dt;
        p.x += p.vx * dt; p.y += p.vy * dt;
        c.globalAlpha = p.al * fade * (p.tw ? Math.sin(k * Math.PI) : 1);
        var sz = p.s * (ambient === 'steam' ? (1 + k * .8) : 1);
        c.drawImage(p.tw ? star(p.col) : sprite(p.col), p.x - sz / 2, p.y - sz / 2, sz, sz);
      }
      c.globalAlpha = 1;
    }
    /* ---- front layer ---- */
    function drawFront(t, dt) {
      var c = cf; c.setTransform(1, 0, 0, 1, 0, 0); c.clearRect(0, 0, ff.width, ff.height); c.setTransform(R, 0, 0, R, 0, 0);
      var busy = false;
      // spotlight beams
      beams = beams.filter(function (b) {
        var age = t - b.at; if (age > 2.4) return false; busy = true; if (age < 0) return true;
        var a = age < .5 ? age / .5 : Math.max(0, 1 - (age - .5) / 1.9);
        c.globalCompositeOperation = dark ? 'lighter' : 'source-over';
        var g = c.createLinearGradient(0, -40, 0, b.y + b.h * .55); var bc = dark ? '255,248,230' : acc2; g.addColorStop(0, 'rgba(' + bc + ',0)'); g.addColorStop(1, 'rgba(' + bc + ',' + ((dark ? .32 : .16) * a) + ')');
        c.fillStyle = g; c.beginPath(); c.moveTo(b.x - 40, -40); c.lineTo(b.x + 40, -40); c.lineTo(b.x + b.w * .55, b.y + b.h * .62); c.lineTo(b.x - b.w * .55, b.y + b.h * .62); c.closePath(); c.fill();
        c.globalAlpha = (dark ? .55 : .3) * a; c.drawImage(sprite(dark ? '255,246,226' : acc2), b.x - b.w * .6, b.y + b.h * .42, b.w * 1.2, b.h * .38); c.globalAlpha = 1; return true;
      });
      // shock rings
      rings = rings.filter(function (r) {
        var age = t - r.at; if (age > .9) return false; busy = true; if (age < 0) return true;
        var k = ease(age / .9); c.globalCompositeOperation = dark ? 'lighter' : 'source-over';
        c.strokeStyle = 'rgba(' + (dark ? '255,250,240' : r.col) + ',' + (1 - k) * .9 + ')'; c.lineWidth = 10 * (1 - k) + 1;
        c.beginPath(); c.ellipse(r.x, r.y, r.rx * (.25 + k), r.ry * (.25 + k), 0, 0, Math.PI * 2); c.stroke(); return true;
      });
      // particles
      for (var i = P.length - 1; i >= 0; i--) {
        var p = P[i]; if (t < p.at) { busy = true; continue; }
        var age = t - p.at;
        if (p.k === 'p') {
          if (t > p.arr + .5) { P.splice(i, 1); continue; }
          busy = true; var u = ease((t - p.at) / Math.max(.05, p.arr - p.at)), sw = (1 - u) * p.sw * 160;
          var x = p.x0 + (p.tx - p.x0) * u + sw, y = p.y0 + (p.ty - p.y0) * u - Math.sin(u * Math.PI) * 60;
          c.globalCompositeOperation = 'source-over'; c.globalAlpha = Math.min(1, (t - p.at) * 4) * (t > p.arr ? Math.max(0, 1 - (t - p.arr) / .5) : 1);
          c.fillStyle = p.c; c.fillRect(x - p.s / 2, y - p.s / 2, p.s, p.s);
          if (u < .85 && dark && (i % 9 === 0)) { c.globalCompositeOperation = 'lighter'; c.globalAlpha *= .5; c.drawImage(sprite('255,240,210'), x - 9, y - 9, 18, 18); }
          continue;
        }
        if (age > p.life) { P.splice(i, 1); continue; }
        busy = true; var k2 = age / p.life;
        var drag = Math.exp(-p.dr * dt); p.vx *= drag; p.vy *= drag; p.vy += p.g * dt; p.x += p.vx * dt; p.y += p.vy * dt;
        if (p.k === 'd') { c.globalCompositeOperation = dark ? 'lighter' : 'source-over'; c.globalAlpha = p.al * (1 - k2) * Math.min(1, age * 8); var s = p.s * (1 + k2 * 1.6); c.drawImage(sprite(p.col), p.x - s / 2, p.y - s / 2, s, s); }
        else if (p.k === 'm') { c.globalCompositeOperation = 'source-over'; c.globalAlpha = p.al * Math.sin(Math.min(1, k2) * Math.PI); var s3 = p.s * (.6 + k2 * .9); c.drawImage(sprite(p.col), p.x - s3 / 2, p.y - s3 / 2, s3, s3); }
        else if (p.k === 't') { c.globalCompositeOperation = dark ? 'lighter' : 'source-over'; c.globalAlpha = Math.sin(k2 * Math.PI); c.drawImage(star(dark ? p.col : acc), p.x - p.s / 2, p.y - p.s / 2, p.s, p.s); }
        else { c.globalCompositeOperation = dark ? 'lighter' : 'source-over'; c.globalAlpha = 1 - k2; c.strokeStyle = 'rgba(' + p.col + ',1)'; c.lineWidth = p.s * (1 - k2 * .6) * .55; c.lineCap = 'round'; c.beginPath(); c.moveTo(p.x, p.y); c.lineTo(p.x - p.vx * .035, p.y - p.vy * .035); c.stroke(); }
      }
      c.globalAlpha = 1;
      // the light streak: drawn from the path itself, so it is smooth at any frame rate
      if (streak) {
        var tEnd = streak[streak.length - 1].t1; if (t < tEnd + .3) busy = true; else streak = null;
        var N = 26, dtS = .011, pts = [];
        for (var j = 0; j < N; j++) pts.push(head(t - j * dtS));
        c.globalCompositeOperation = dark ? 'lighter' : 'source-over'; c.lineCap = 'round';
        var core = dark ? '255,255,255' : acc;
        for (var pass2 = 0; pass2 < 2; pass2++) {
          for (j = 1; j < N; j++) {
            var a1 = pts[j - 1], a2 = pts[j]; if (!a1 || !a2) continue; if (Math.abs(a1.x - a2.x) + Math.abs(a1.y - a2.y) > 220) continue;
            var f = 1 - j / N;
            c.strokeStyle = pass2 ? 'rgba(' + core + ',' + (.95 * f * a1.a) + ')' : 'rgba(' + acc2 + ',' + (.32 * f * a1.a) + ')';
            c.lineWidth = pass2 ? 2 + 7 * f : 10 + 26 * f;
            c.beginPath(); c.moveTo(a1.x, a1.y); c.lineTo(a2.x, a2.y); c.stroke();
          }
        }
        var h0 = pts[0];
        if (h0) {
          c.globalAlpha = h0.a; c.drawImage(sprite(dark ? '255,255,255' : acc), h0.x - 45, h0.y - 45, 90, 90); c.drawImage(star('255,250,235'), h0.x - 38, h0.y - 38, 76, 76); c.globalAlpha = 1;
          if (Math.random() < .8 * q) add({ k: 's', at: t, x: h0.x, y: h0.y, vx: rnd(-160, 160), vy: rnd(-160, 80), life: rnd(.3, .6), s: rnd(3, 6), g: 300, dr: 3, col: '255,240,200', al: 1 });
        }
      }
      c.globalAlpha = 1; c.globalCompositeOperation = 'source-over';
      return busy;
    }
    function loop() {
      raf = 0; if (!run) return;
      var tn = now(), dt = Math.min(.05, tn - (last || tn)); last = tn; var t = tn - t0;
      frames++; if (frames > 20 && frames < 200) { if (dt > .026) slow++; if (frames === 199) { if (slow > 110) { q = .45; if (slow > 160) { ambient = 'none'; A = []; } } else if (slow > 60) q = .7; } }
      ambientTick(dt); drawAmbient(t, dt); var busy = drawFront(t, dt);
      if (busy || ambient !== 'none') raf = requestAnimationFrame(loop); else run = false;
    }
    function kick() { if (!fb) return; if (!run) { run = true; last = 0; } if (!raf) raf = requestAnimationFrame(loop); }
    function stop() { run = false; if (raf) cancelAnimationFrame(raf); raf = 0; }
    function slotsOf(el, S) {
      var T = timing(S), list = [].slice.call(el.querySelectorAll('[data-slot]'));
      return list.map(function (box, n) {
        var a = box.querySelector('.dish') || box, cs = getComputedStyle(a), nm = (cs.animationName || 'none').split(',')[0].trim();
        var i = parseInt(getComputedStyle(box).getPropertyValue('--i'), 10) || 0;
        var d = nm !== 'none' ? parseFloat((cs.animationDelay || '0').split(',')[0]) : T.dp + i * T.cs;
        var img = box.querySelector('img'), r = img && img.naturalWidth ? imgRect(box, img) : rectOf(box);
        if (r.w < 10) r = rectOf(box);
        return { box: box, img: img, T: (isNaN(d) ? 0 : d) + .08, c: { x: r.x + r.w / 2, y: r.y + r.h / 2, w: r.w, h: r.h }, col: (img && img.naturalWidth ? avg(img) : null) || acc2 };
      }).sort(function (x, y) { return x.T - y.T; });
    }
    function intro(el, S) {
      var mode = (S.fx && S.fx.intro) || 'auto'; if (mode === 'auto') mode = 'streak';
      var slots = slotsOf(el, S); if (!slots.length) return;
      if (mode === 'streak') streak = plan(slots, false);
      slots.forEach(function (s) {
        if (mode === 'streak') { dust(s.c, s.T, s.col, 46); sparks(s.c, s.T, s.col, 26, 700); ring(s.c, s.T + .02, acc2); }
        else if (mode === 'assemble') {
          var go = function () { if (!assemble(s.box, s.img, s.T)) dust(s.c, s.T, s.col, 50); twinkle(s.c, s.T + .1, 6); };
          if (s.img && s.img.complete && s.img.naturalWidth) go(); else if (s.img) { s.img.addEventListener('load', function () { if (now() - t0 < s.T) go(); }, { once: true }); } else dust(s.c, s.T, s.col, 50);
        }
        else if (mode === 'burst') { sparks(s.c, s.T, s.col, 80, 1300); dust(s.c, s.T, '255,236,200', 14); ring(s.c, s.T, acc2); }
        else if (mode === 'smoke') { smoke(s.c, s.T, 16); twinkle(s.c, s.T + .3, 5); }
        else if (mode === 'beam') { beam(s.c, s.T); twinkle(s.c, s.T + .05, 7); }
        else if (mode === 'ring') { ring(s.c, s.T, acc2); rings.push({ at: s.T + .14, x: s.c.x, y: s.c.y + s.c.h * .12, rx: s.c.w * .4, ry: s.c.h * .12, col: acc2 }); sparks(s.c, s.T, s.col, 30, 800); }
      });
      kick();
    }
    var api = {
      set: function (S, T, port) {
        init(); size(port); cfg = S; dark = lum(mid(T.bg1, T.bg2)) < .35; acc = rgb(T.accent); acc2 = rgb(T.accent2);
        var amb = (S.fx && S.fx.ambient) || 'none'; if (reduce || S.motion === 'none') amb = 'none';
        if (amb !== ambient) { A = []; rays = []; } ambient = amb; frames = 0; slow = 0; kick();
      },
      slide: function (el, S) {
        P = []; streak = null; beams = []; rings = []; t0 = now();
        if (!fxOn(S)) { kick(); return; }
        requestAnimationFrame(function () { t0 = now(); intro(el, S); });
      },
      pass: function (el, S) {
        if (!fxOn(S) || !S.fx.pass) return;
        var t = now() - t0, slots = slotsOf(el, S);
        if (!slots.length) return;
        slots.forEach(function (s, k) { s.T = t + .45 + k * .42; twinkle(s.c, s.T, 6); });
        streak = plan(slots, true); kick();
      },
      clear: function () { P = []; streak = null; beams = []; rings = []; }
    };
    return api;
  })();

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
      '<div class="cn-dish" data-slot><div class="cn-shadow"></div><div class="cn-in">' + img + (it.img && it.cutout ? gl(it) : '') + '</div>' + (it.cutout ? '<div class="cn-shine"></div>' : '') + '</div>' +
      (S.show.save && it.save ? '<div class="cn-save"><small>SAVE</small><strong>' + it.save + '%</strong></div>' : '') + '</div>' +
      '<div class="cn-tx">' + (tagOf(it) ? '<div class="cn-tag">' + esc(tagOf(it)) + '</div>' : '') + words(it.name) +
      (it.pack ? '<div class="cn-pack"><span>' + esc(it.pack) + '</span></div>' : '') +
      '<div class="cn-row"><div class="cn-price"><span class="cu">₹</span><b class="cnt" data-pre="" data-d="1700" data-v="' + esc(String(it.price)) + '">' + esc(Number(it.price).toLocaleString('en-IN')) + '</b></div>' + (S.show.mrp && it.mrp ? '<div class="cn-mrp">' + esc(money(it.mrp)) + '</div>' : '') + '</div>' +
      (S.show.stock && it.few ? '<div class="cn-few">Only a few left</div>' : '') + (it.desc ? '<p class="cn-ds">' + esc(it.desc) + '</p>' : '') + '</div>' +
      '<div class="cn-count">' + ('0' + (i + 1)).slice(-2) + ' / ' + ('0' + all.length).slice(-2) + '</div>' + nx + '<div class="cn-prog"><b></b></div></div>';
  }
  function wallCol(c, i, S, port) {
    var hero = c.items.filter(function (x) { return x.star; })[0] || c.items.filter(function (x) { return x.img; })[0] || c.items[0];
    var im = hero.img ? '<div class="dish wl-in"><img class="' + (hero.cutout ? '' : 'photo') + '" src="' + esc(hero.img) + '" alt="" onerror="this.style.visibility=\'hidden\'">' + gl(hero) + '</div>' : '<div class="dish wl-in"><div class="wl-ini">' + esc(String(hero.name).charAt(0).toUpperCase()) + '</div></div>';
    var stk = hero.badge ? hero.badge : (!c.cat && S.show.save && hero.save ? 'Save ' + hero.save + '%' : '');
    var h = '<div class="wl-col" style="--i:' + i + '"><div class="wl-pic" data-slot><div class="wl-ph"></div>' + im + (stk ? '<div class="wl-stk"><span>' + esc(stk) + '</span></div>' : '') + '</div>';
    if (c.cat) {
      var rows = [hero].concat(c.items.filter(function (x) { return x !== hero; }));
      h += '<div class="wl-tx"><div class="wl-cat">' + esc(c.cat) + '</div>' + rows.map(function (it, r) {
        return '<div class="wl-row' + (r === 0 ? ' hero' : '') + '" style="--r:' + r + '"><span class="wl-n"><b>' + esc(it.name) + '</b>' + (it.pack ? '<small>' + esc(it.pack) + '</small>' : '') + (S.show.stock && it.few ? '<em>Few left</em>' : '') + '</span><span class="wl-p"><span class="cnt" data-pre="₹" data-d="1500" data-v="' + esc(String(it.price)) + '">' + esc(money(it.price)) + '</span></span></div>';
      }).join('') + '</div>';
    } else {
      var t = hero.badge ? '' : (hero.cat || '');
      h += '<div class="wl-tx one">' + (t ? '<div class="tag t1">' + esc(t) + '</div>' : '') + '<h2 class="nm t1">' + esc(hero.name) + '</h2>' + priceHtml(hero, S) + chips(hero, S) + (hero.desc ? '<p class="ds t3">' + esc(hero.desc) + '</p>' : '') + '</div>';
    }
    return h + '</div>';
  }
  function wallSlides(items, S) {
    var port = S.orientation === 'portrait', groups = [], gi = {}, out = [];
    items.forEach(function (it) { var nm = String(it.cat || '').trim(), key = nm.toLowerCase(); if (!gi.hasOwnProperty(key)) { gi[key] = groups.length; groups.push({ name: nm, items: [] }); } groups[gi[key]].items.push(it); });
    var cols = [];
    if (groups.length >= 2) groups.forEach(function (g) { for (var a = 0; a < g.items.length; a += 4) cols.push({ cat: g.name || 'Fresh picks', items: g.items.slice(a, a + 4) }); });
    else items.forEach(function (it) { cols.push({ cat: '', items: [it] }); });
    var per = wallCount(S), head = (S.headline || S.kicker) ? '<div class="wl-head">' + (S.kicker ? '<small class="wl-k">' + esc(S.kicker) + '</small>' : '') + (S.headline ? '<h1 class="wl-h" data-t="' + esc(S.headline) + '">' + esc(S.headline) + '</h1>' : '') + '</div>' : '';
    for (var q = 0; q < cols.length; q += per) {
      var ch = cols.slice(q, q + per), n = ch.length;
      out.push('<div class="wall' + (head ? '' : ' nohead') + '">' + head + '<div class="wl-cols" style="--n:' + n + '">' + ch.map(function (c, k) { return wallCol(c, port ? k : n - 1 - k, S, port); }).join('') + '</div></div>');
    }
    return out;
  }
  function build(d) {
    var S = d.screen, L = S.layout, port = S.orientation === 'portrait', out = [];
    var items = d.items || [];
    if (L === 'wall') {
      out = wallSlides(items, S);
    } else if (L === 'cinema') {
      items.forEach(function (it, i) { out.push(cinemaSlide(it, i, items, S)); });
    } else if (L === 'offers') {
      var per = port ? 6 : 6, c = port ? 2 : 3, r = port ? 3 : 2, o = d.offers || [];
      for (var a = 0; a < o.length; a += per) {
        var part = o.slice(a, a + per), h = '<div class="poster"><div class="bh"><h2>' + esc(S.offersTitle) + '</h2></div><div class="tiles" style="--c:' + Math.min(c, part.length) + ';--r:' + Math.ceil(part.length / c) + '">';
        part.forEach(function (x, i) { h += '<div class="tile" style="--i:' + i + ';background:' + esc(x.bg) + ';color:' + esc(x.color) + '"><div class="ti" data-slot>' + (x.img ? '<img class="dish" src="' + esc(x.img) + '" alt="" onerror="this.style.visibility=\'hidden\'">' : '') + '</div><div class="tb"><div><b class="t1">' + esc(x.name) + '</b>' + (x.pack ? '<span class="t3">' + esc(x.pack) + '</span>' : '') + '</div>' + (x.price != null ? '<strong class="t2">' + esc(money(x.price)) + '</strong>' : '') + '</div></div>'; });
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
        if (k === 1) { out.push('<div class="card sc" style="--i:0"><div class="ct">' + (tagOf(ch[0]) ? '<div class="tag t1">' + esc(tagOf(ch[0])) + '</div>' : '') + '<h2 class="nm t1">' + esc(ch[0].name) + '</h2>' + priceHtml(ch[0], S) + chips(ch[0], S) + (ch[0].desc ? '<p class="ds t3">' + esc(ch[0].desc) + '</p>' : '') + '</div><div class="im" data-slot>' + pic(ch[0], 'dish') + '</div></div>'); }
        else { out.push('<div class="grid ' + L + '" style="--n:' + ch.length + '">' + ch.map(function (it, i) { return cardHtml(it, i, S); }).join('') + '</div>'); }
      }
    }
    if (!out.length) out.push('<div class="empty"><div class="lg"><img src="' + esc(LOGO) + '" alt="Freshful"></div><p>Fresh picks are being updated. Please ask our team.</p></div>');
    return out;
  }
  var wipeT = null;
  function roll(el, txt, wait) {
    var k = 0;
    el.classList.add('rolling');
    el.innerHTML = txt.split('').map(function (ch) {
      if (!/[0-9]/.test(ch)) return '<span>' + esc(ch) + '</span>';
      var strip = ''; for (var n = 0; n <= 9; n++) strip += '<span>' + n + '</span>';
      return '<span class="od"><span class="os" style="--k:' + (k++) + '" data-d="' + ch + '">' + strip + '</span></span>';
    }).join('');
    setTimeout(function () { [].forEach.call(el.querySelectorAll('.os'), function (s) { s.style.transform = 'translateY(-' + (s.getAttribute('data-d') * 1.16).toFixed(2) + 'em)'; }); }, wait);
    setTimeout(function () { el.classList.remove('rolling'); el.textContent = txt; }, wait + 1350 + k * 85);
  }
  function countUp(cur) {
    var S = D ? D.screen : {}, A = S.anim || {}, pr = (A.price && A.price !== 'auto') ? A.price : (S.layout === 'wall' ? 'roll' : '');
    [].forEach.call(cur.querySelectorAll('.cnt'), function (el) {
      var v = parseFloat(el.getAttribute('data-v')) || 0, dec = v % 1 !== 0, pre = el.getAttribute('data-pre') || '';
      var fmt = function (x) { return pre + (dec ? x.toFixed(2) : Math.round(x).toLocaleString('en-IN')); };
      var off = el.closest ? el.closest('.card') : null, i = off ? (parseInt(off.style.getPropertyValue('--i'), 10) || 0) : 0;
      var base = +el.getAttribute('data-d') || 1350, stride = D && D.screen.anim && D.screen.anim.flow === 'seq' ? CARD_MS : 120;
      var host = el.closest ? el.closest('.pr, .cn-price, .wl-row') : null, wait = base + i * stride;
      if (host) { var hs = getComputedStyle(host); if ((hs.animationName || 'none').split(',')[0].trim() !== 'none') wait = parseFloat(String(hs.animationDelay).split(',')[0]) * 1000 + (pr === 'roll' ? 40 : 250); }
      if (reduce || stage.className.indexOf('mot-rise') < 0) { el.textContent = fmt(v); return; }
      if (pr === 'roll') { roll(el, fmt(v), wait); return; }
      if (A.count === false) { el.textContent = fmt(v); return; }
      el.textContent = fmt(0); base = wait; i = 0; stride = 0;
      setTimeout(function () { var t0 = Date.now(), dur = 900; (function step() { var k = Math.min(1, (Date.now() - t0) / dur), e = 1 - Math.pow(1 - k, 3); el.textContent = fmt(v * e); if (k < 1 && cur.classList.contains('on')) requestAnimationFrame(step); else el.textContent = fmt(v); })(); }, base + i * stride);
    });
  }
  function transOf(S) {
    var t = (S.fx && S.fx.trans) || 'auto';
    if (t === 'auto') t = S.layout === 'cinema' ? 'wipe' : (S.layout === 'wall' ? 'flash' : 'fade');
    if (reduce || S.motion === 'none') t = 'none'; else if (S.motion === 'fade') t = 'fade';
    return t;
  }
  var settleT = null, passT = null, TRS = ['tr-fade', 'tr-zoom', 'tr-slide', 'tr-iris', 'tr-none'];
  function swap(i, tr) {
    var prev = slideEls[idx]; idx = (i + slideEls.length) % slideEls.length; var cur = slideEls[idx], S = D.screen;
    tr = tr || 'none';
    if (prev && prev !== cur) {
      prev.classList.remove('on', 'settled'); TRS.forEach(function (c) { prev.classList.remove(c); });
      if (tr === 'fade' || tr === 'zoom' || tr === 'slide' || tr === 'iris') { prev.classList.add('leave', 'tr-' + tr); setTimeout(function () { prev.classList.remove('leave', 'tr-' + tr); }, 1000); }
    }
    TRS.forEach(function (c) { cur.classList.remove(c); });
    cur.classList.remove('leave'); void cur.offsetWidth; if (shown && tr !== 'none') cur.classList.add('tr-' + tr); cur.classList.add('on'); cur.classList.remove('settled');
    countUp(cur);
    FX.slide(cur, S);
    var T = timing(S), n = S.layout === 'wall' ? cur.querySelectorAll('.wl-col').length : (S.layout === 'cinema' ? 1 : cur.querySelectorAll('.card').length || 1);
    var at = (T.dp + Math.max(0, n - 1) * T.cs + (S.layout === 'cinema' ? 2.2 : 2.4)) * 1000;
    clearTimeout(settleT); clearInterval(passT);
    settleT = setTimeout(function () {
      cur.classList.add('settled');
      if (S.fx && S.fx.pass && fxOn(S)) passT = setInterval(function () { if (cur.classList.contains('on')) FX.pass(cur, S); }, 12000);
    }, at);
  }
  var shown = false;
  function show(i) {
    if (!slideEls.length) return;
    var S = D.screen, tr = transOf(S), cover = { wipe: ['wipe', 470], flash: ['flash', 300], blinds: ['blinds', 450] }[tr];
    clearTimeout(wipeT);
    if (shown && slideEls.length > 1 && cover) {
      var w = $(cover[0]); w.classList.remove('go'); void w.offsetWidth; w.classList.add('go');
      wipeT = setTimeout(function () { swap(i, 'none'); }, cover[1]);
    } else swap(i, tr);
    shown = true;
  }
  function apply(d) {
    D = d; var S = d.screen, T = d.theme;
    S.fx = S.fx || { intro: 'auto', ambient: 'bokeh', trans: 'auto', speed: 'normal', glint: true, pass: true };
    var tr0 = transOf(S);
    stage.className = (S.orientation === 'portrait' ? 'P' : 'L') + ' ' + (reduce || S.motion === 'none' ? 'mot-none' : 'mot-' + S.motion) + (S.idle && !reduce ? ' idle' : '') + (S.layout === 'cinema' ? ' cinema' + (reduce || S.motion === 'none' ? '' : (S.motion === 'fade' ? ' cin-soft' : ' cin-anim')) : '') + animClasses(S) +
      (fxOn(S) ? ' fx' : '') + (S.fx.glint && !reduce && S.motion !== 'none' ? ' fx-glint' : '') + (S.layout === 'wall' ? ' wall-on' : '') + (tr0 !== 'fade' ? ' tc' : '') + (lum(mid(T.bg1, T.bg2)) < .35 ? ' dark' : ' light');
    shown = false;
    var st = stage.style; var txc = T.text, ctc = T.cardText; if (S.contrast !== false) { txc = readable(T.text, mid(T.bg1, T.bg2)); ctc = readable(T.cardText, T.card); } T = Object.assign({}, T, { text: txc, cardText: ctc });
    st.setProperty('--bg1', T.bg1); st.setProperty('--bg2', T.bg2); st.setProperty('--ang', T.angle + 'deg'); st.setProperty('--tx', T.text); st.setProperty('--card', T.card); st.setProperty('--ct', T.cardText);
    st.setProperty('--ac', T.accent); st.setProperty('--ac2', T.accent2); st.setProperty('--font', "'" + T.font + "'"); st.setProperty('--bgo', (T.bgImageOpacity / 100)); var TM = timing(S); ['dp', 'cs', 'dt', 'dpr', 'dd'].forEach(function (k) { st.setProperty('--' + k, TM[k] + 's'); }); st.setProperty('--ac-rgb', rgb(T.accent)); st.setProperty('--ac2-rgb', rgb(T.accent2)); st.setProperty('--tx-rgb', rgb(T.text)); st.setProperty('--sec', slideSecs(S) + 's');
    document.documentElement.style.background = T.bg1; document.body.style.background = T.bg1; font(T.font);
    document.querySelector('.bgi').style.backgroundImage = T.bgImage ? 'url(' + T.bgImage + ')' : 'none';
    $('lg').src = LOGO; document.querySelector('.hd .lg').style.display = S.show.logo ? '' : 'none';
    $('ot').textContent = S.show.outlet ? S.outlet : ''; $('ck').style.display = S.show.clock ? '' : 'none';
    var tk = $('tk'); if (S.ticker) { tk.style.display = ''; $('run').textContent = S.ticker; $('run').style.setProperty('--td', Math.max(18, S.ticker.length * 0.55) + 's'); } else tk.style.display = 'none';
    var mo = $('more'); var offers = d.offers || [];
    if (S.layout !== 'offers' && S.layout !== 'wall' && offers.length) {
      mo.style.display = ''; mo.innerHTML = '<div class="lab">' + esc(S.offersTitle) + '</div>' + offers.slice(0, S.orientation === 'portrait' ? 2 : 4).map(function (o) { return '<div class="o" style="background:' + esc(o.bg) + ';color:' + esc(o.color) + '">' + (o.img ? '<img src="' + esc(o.img) + '" alt="" onerror="this.style.display=\'none\'">' : '') + '<div class="nn"><b>' + esc(o.name) + '</b>' + (o.pack ? '<small>' + esc(o.pack) + '</small>' : '') + (o.price != null ? '<strong>' + esc(money(o.price)) + '</strong>' : '') + '</div></div>'; }).join('');
    } else { mo.style.display = 'none'; mo.innerHTML = ''; }
    var html = build(d); slidesEl.innerHTML = html.map(function (h) { return '<section class="slide">' + h + '</section>'; }).join('');
    slideEls = [].slice.call(slidesEl.children); if (idx >= slideEls.length) idx = 0;
    fit(); sizes(); FX.set(S, T, S.orientation === 'portrait'); clearInterval(timer); shown = false; show(idx);
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
  window.__dm = { apply: apply, show: show, build: build, fx: FX };
})();
</script>
</body>
</html>

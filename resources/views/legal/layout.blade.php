<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>@yield('title') | Freshful</title>
<style>
body{margin:0;font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;color:#1b1b1b;background:#fafafa;line-height:1.65}
header{background:#F70202;color:#fff;padding:22px 20px}header div{max-width:820px;margin:0 auto}
header b{font-size:22px;letter-spacing:.3px}header span{display:block;font-size:13px;opacity:.9;margin-top:2px}
main{max-width:820px;margin:0 auto;padding:26px 20px 60px}
h1{font-size:28px;margin:6px 0 4px}h2{font-size:19px;margin:30px 0 8px}
p,li{font-size:15.5px}li{margin:5px 0}.muted{color:#666;font-size:14px}
.card{background:#fff;border:1px solid #eee;border-radius:12px;padding:16px 18px;margin:14px 0}
a{color:#c40000}footer{max-width:820px;margin:0 auto;padding:0 20px 40px;color:#777;font-size:13px}
</style></head><body>
<header><div><b>Freshful</b><span>Freshful Digital Foods (OPC) Private Limited</span></div></header>
<main>@yield('body')</main>
<footer>&copy; {{ date('Y') }} Freshful Digital Foods (OPC) Private Limited. Support: talktous@freshful.in | +91 81242 00242</footer>
</body></html>

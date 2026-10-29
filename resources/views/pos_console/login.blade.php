<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Freshful POS · Log in</title>
<link rel="icon" href="{{ url('img/Fevi.png') }}">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<style>{!! file_get_contents(resource_path('views/pos_console/assets/pos.css')) !!}</style>
</head>
<body>
<main class="pc-login">
  <div class="pc-login-box">
    <div class="pc-logo" aria-hidden="true">F</div>
    <h1>Freshful POS</h1>
    <p>Log in with your POS or hub account.</p>
    @if (session('error'))<div class="pc-msg bad" role="alert">{{ session('error') }}</div>@endif
    @if (session('success'))<div class="pc-msg ok">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="pc-msg bad" role="alert">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('posc.login.submit') }}" autocomplete="on" style="margin-top:14px">
      @csrf
      <div class="pc-field">
        <label for="email">Email or mobile number</label>
        <input id="email" name="email" class="pc-input" value="{{ old('email') }}" required autofocus autocomplete="username">
      </div>
      <div class="pc-field">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" class="pc-input" required autocomplete="current-password">
      </div>
      <button type="submit" class="pc-btn pri lg block">Log in</button>
    </form>
  </div>
</main>
</body>
</html>

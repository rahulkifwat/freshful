<nav class="ff-utility-bar" aria-label="Contact and company links">
    <div class="container-fluid container-xl">
        <div class="ff-utility-contact">
            <a href="tel:+918124200242"><i class="bi bi-telephone-fill" aria-hidden="true"></i> +91 8124200242</a>
            <a href="mailto:talktous@freshful.in"><i class="bi bi-envelope-fill" aria-hidden="true"></i> talktous@freshful.in</a>
        </div>
        <div class="ff-utility-links">
            <a href="{{ url('/about') }}">About us</a>
            <a href="{{ url('/franchisee') }}">Franchise</a>
            <a href="{{ route('certificate') }}">Certificate</a>
            <a href="{{ url('/refer_earn') }}">Refer &amp; Earn</a>
            <a href="{{ url('/contact_us') }}">Contact Us</a>
        </div>
    </div>
</nav>

<header id="myHeader" class="ff-site-header">
    <div class="container-fluid container-xl">
        <div class="ff-header-row">
            <a class="ff-brand" href="{{ url('/') }}" aria-label="Freshful home">
                <img src="{{ asset('assets/image/logo.png') }}" alt="Freshful" class="logoImg img-fluid">
            </a>

            <button id="open-location" class="ff-header-location" type="button" data-bs-toggle="modal" data-bs-target="#location-dropdown" aria-label="Choose delivery location">
                <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
                <span class="ff-header-location-copy">
                    <strong id="ff-loc-title">Choose your location</strong>
                    <small><span id="ff-loc-sub">Set delivery area</span><i class="bi bi-chevron-down" aria-hidden="true"></i></small>
                </span>
            </button>
            <script>/* Show the chosen delivery location */(function(){function c(n){var m=document.cookie.match('(?:^|; )'+n+'=([^;]*)');try{return m?decodeURIComponent(m[1]):'';}catch(e){return m?m[1]:'';}}var a=c('address'),city=c('city');if(!a&&!city)return;var t=document.getElementById('ff-loc-title'),s=document.getElementById('ff-loc-sub');if(!t||!s)return;var short=a.replace(/^[23456789CFGHJMPQRVWX]{4,8}\+[23456789CFGHJMPQRVWX]{2,3},\s*/i,'').split(',').slice(0,2).join(',').trim();t.textContent='Deliver to '+(city||'your area');s.textContent=short||city;s.title=a;})();</script>

            <a class="ff-header-store" href="{{ route('maps') }}" aria-label="Find Freshful stores">
                <i class="bi bi-shop-window" aria-hidden="true"></i>
                <strong>Stores</strong>
            </a>

            <div class="ff-header-search position-relative">
                <div class="input-group rounded-pill overflow-hidden border py-1">
                    <span class="input-group-text bg-white border-0"><i class="bi bi-search text-primary-alt" aria-hidden="true"></i></span>
                    <input type="search" id="search_product" class="form-control border-0 outline-0 bg-white" placeholder="Search products..." aria-label="Search products" autocomplete="off">
                </div>
                <div id="search-results-dropdown" style="display:none;position:absolute;top:100%;left:0;right:0;z-index:9999;background:#fff;border:1px solid #ddd;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.12);max-height:360px;overflow-y:auto;margin-top:4px;"></div>
            </div>

            <div class="ff-header-actions">
                @if(Auth::check())
                    <a href="{{ route('myaccount') }}" class="header-btn"><i class="bi bi-person-circle" aria-hidden="true"></i><span>My Account</span></a>
                @else
                    <a data-bs-toggle="modal" href="#loginoffcanvas" role="button" aria-controls="loginoffcanvas" class="header-btn top-login-btn"><i class="bi bi-person-circle" aria-hidden="true"></i><span>Login</span></a>
                @endif
                <a href="{{ route('compare_product') }}" id="compare-page-link" class="header-btn" aria-label="Compare products"><i class="bi bi-arrow-left-right" aria-hidden="true"></i><span>Compare</span><span id="compare-badge" class="badge rounded-pill text-bg-danger" hidden>0</span></a>
                <a data-bs-toggle="offcanvas" href="#cardoffcanvas" role="button" aria-controls="cardoffcanvas" class="header-btn ff-cart-link"><i class="bi bi-cart3" aria-hidden="true"></i><span>Cart</span><span id="cart-badge-count" class="ff-cart-badge" aria-label="Items in cart" aria-live="polite" style="display:none"></span></a>
            </div>
        </div>
    </div>
    <nav class="ff-header-nav" aria-label="Main navigation">
        <div class="container-fluid container-xl">
            <a href="{{ url('/view_product/all') }}"><i class="bi bi-grid" aria-hidden="true"></i> Shop all</a>
            <a href="{{ route('categories') }}"><i class="bi bi-list-ul" aria-hidden="true"></i> Categories</a>
        </div>
    </nav>
</header>

    <div class="modal fade location-dropdown" id="location-dropdown" tabindex="-1" aria-labelledby="location-dropdown-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered location-modal-dialog">
          <div class="modal-content location-modal-content">
            <div class="modal-header">
                <div><h5 class="modal-title fw-bold" id="location-dropdown-title">Choose your delivery area</h5><p class="text-muted small mb-0">Check Freshful delivery availability near you.</p></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label for="locationSearchInput" class="form-label fw-semibold mb-2">Search or pin your location</label>
                <div class="ff-location-search position-relative mb-3">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="text" id="locationSearchInput" class="form-control bg-white" placeholder="Enter area, street or landmark" autocomplete="off" aria-label="Search delivery location">
                </div>
                <div id="freshful-header-map" class="freshful-header-map" role="application" aria-label="Choose delivery location on map"></div>
                <div class="d-flex justify-content-between align-items-center gap-2 mt-2">
                    <small id="freshful-header-map-hint" class="text-muted">Search an address or click the map to drop the pin.</small>
                    <button type="button" class="btn btn-sm btn-outline-success flex-shrink-0" onclick="useMyLocation()"><i class="bi bi-crosshair me-1"></i>Use my location</button>
                </div>
                <div id="location-status" class="mt-2 small text-center" style="min-height:20px;"></div>
            </div>
          </div>
        </div>
    </div>

<!-- Login modal -->
<div class="modal fade" tabindex="-1" id="loginoffcanvas" aria-labelledby="loginoffcanvasLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered login-modal-dialog">
    <div class="modal-content login-modal-content">
      <div class="modal-header">
        <div class="modal-title" id="loginoffcanvasLabel"><img src="{{ asset('assets/image/logo.png') }}" alt="Freshful" class="logoImg img-fluid"></div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="text-center mb-4">
          <h5 class="fs-5 mt-1 fw-bolder mb-2">{{ session('complete_profile') ? 'Set up your Freshful account' : 'Welcome to Freshful' }}</h5>
          <p class="text-muted small mb-0">{{ session('complete_profile') ? 'Add your details and delivery address to get started.' : 'Sign in securely with a one-time password.' }}</p>
        </div>
        @if ($errors->any())
          <div class="alert alert-danger py-2"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        @if(session('error'))<div class="alert alert-danger py-2" role="alert">{{ session('error') }}</div>@endif
        @if(session()->has('success'))<div class="alert alert-success py-2">{{ session()->get('success') }}</div>@endif

        @if(session('complete_profile'))
          <form action="{{ route('user.complete-registration') }}" method="POST" class="ff-onboarding-form">
            @csrf
            <div class="ff-verified-mobile"><i class="bi bi-patch-check-fill"></i><span>Mobile verified</span><strong>+91 {{ Auth::guard('buyers')->user()->phone ?? '' }}</strong></div>
            <div class="ff-onboarding-section-title"><span>1</span><div><strong>Your details</strong><small>Personalise your account</small></div></div>
            <div class="mb-3"><label class="form-label fw-semibold" for="signup-name">Full name</label><input id="signup-name" class="form-control" name="name" value="{{ old('name', Auth::guard('buyers')->user()->name ?? '') }}" autocomplete="name" placeholder="Enter your full name" required></div>
            <div class="mb-3"><label class="form-label fw-semibold" for="signup-email">Email address</label><input id="signup-email" class="form-control" name="email" type="email" value="{{ old('email', Auth::guard('buyers')->user()->email ?? '') }}" autocomplete="email" placeholder="you@example.com" required></div>
            <div class="mb-4"><label class="form-label fw-semibold" for="signup-gender">Gender <span class="text-muted fw-normal">(optional)</span></label><select id="signup-gender" name="gender" class="form-select"><option value="">Prefer not to say</option><option value="Male">Male</option><option value="Female">Female</option></select></div>
            <div class="ff-onboarding-section-title"><span>2</span><div><strong>Delivery address</strong><small>Where should we deliver?</small></div></div>
            <div class="mb-3"><label class="form-label fw-semibold" for="signup-address-name">Address contact name</label><input id="signup-address-name" class="form-control" name="address_name" value="{{ old('address_name', Auth::guard('buyers')->user()->name ?? '') }}" autocomplete="name" required></div>
            <div class="mb-3"><label class="form-label fw-semibold" for="signup-locality">Area / locality</label><input id="signup-locality" class="form-control" name="locality" value="{{ old('locality') }}" autocomplete="address-level3" placeholder="Area or neighbourhood" required></div>
            <div class="mb-3"><label class="form-label fw-semibold" for="signup-street">Flat, house, street</label><textarea id="signup-street" class="form-control" name="street_name" rows="2" autocomplete="street-address" placeholder="House / flat number, street and building" required>{{ old('street_name') }}</textarea></div>
            <div class="mb-3"><label class="form-label fw-semibold" for="signup-landmark">Landmark <span class="text-muted fw-normal">(optional)</span></label><input id="signup-landmark" class="form-control" name="landmark" value="{{ old('landmark') }}" placeholder="Nearby landmark"></div>
            <div class="row g-3 mb-4"><div class="col-8"><label class="form-label fw-semibold" for="signup-city">City</label><input id="signup-city" class="form-control" name="city" value="{{ old('city', request()->cookie('city')) }}" autocomplete="address-level2" required></div><div class="col-4"><label class="form-label fw-semibold" for="signup-type">Save as</label><select id="signup-type" name="type" class="form-select" required><option value="Home">Home</option><option value="Work">Work</option><option value="Other">Other</option></select></div></div>
            <button type="submit" class="btn btn-primary rounded-pill w-100 py-2">Save details &amp; continue <i class="bi bi-arrow-right ms-1"></i></button>
            <p class="small text-muted text-center mt-3 mb-0"><i class="bi bi-shield-check me-1"></i>Your information is used to manage your Freshful orders.</p>
          </form>
        @else
          <div class="ff-login-progress" aria-label="Sign-in steps"><span class="active"></span><span></span></div>
          <form action="{{ route('user.login') }}" method="POST" id="ffOtpLoginForm">
            @csrf
            <div id="ff-phone-step">
              <label class="form-label fw-semibold" for="phoneLogin">Mobile number</label>
              <div class="input-group mb-3"><span class="input-group-text"><i class="bi bi-telephone"></i></span><span class="input-group-text">+91</span><input type="tel" class="form-control" id="phoneLogin" name="phone_display" pattern="[0-9]{10}" maxlength="10" inputmode="numeric" autocomplete="tel-national" value="{{ old('phone', session('phone')) }}" placeholder="10-digit mobile number" required></div>
              <button type="button" class="btn btn-primary rounded-pill w-100 py-2" id="sendOtpBtn"><span>Send one-time password</span><i class="bi bi-arrow-right ms-1"></i></button>
            </div>
            <div id="ff-otp-step" hidden>
              <div class="ff-otp-sent-note"><i class="bi bi-chat-dots"></i><span>Enter the 6-digit code sent to <strong id="ffOtpPhone"></strong></span><button type="button" id="ffChangePhone" class="btn btn-link p-0">Change</button></div>
              <label class="form-label fw-semibold" for="ffOtpInput">One-time password</label>
              <input type="hidden" name="phone" id="ffOtpPhoneInput">
              <div class="input-group mb-2"><span class="input-group-text"><i class="bi bi-shield-lock"></i></span><input type="text" class="form-control ff-otp-input" id="ffOtpInput" name="otp" pattern="[0-9]{6}" maxlength="6" inputmode="numeric" autocomplete="one-time-code" placeholder="• • • • • •" required></div>
              <div class="d-flex justify-content-between align-items-center small mb-3"><span id="ffOtpMessage" class="text-muted" role="status"></span><button type="button" id="ffResendOtp" class="btn btn-link p-0" disabled>Resend code <span id="ffResendCountdown">30s</span></button></div>
              <button type="submit" class="btn btn-primary rounded-pill w-100 py-2">Verify &amp; continue <i class="bi bi-arrow-right ms-1"></i></button>
            </div>
          </form>
        @endif
        <div class="mt-3 text-center">
          <p class="fs-7 mb-0">By continuing you agree to our</p>
          <a href="{{ url('/terms') }}" target="_blank" class="text-primary-alt fs-6 fw-bold">Terms &amp; Conditions</a>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const ready = (fn) => document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', fn) : fn();
  ready(() => {
    const modalEl = document.getElementById('loginoffcanvas');
    @if(session('complete_profile'))
    if (modalEl && window.bootstrap) bootstrap.Modal.getOrCreateInstance(modalEl, {backdrop: 'static', keyboard: false}).show();
    @elseif(session('openLoginform'))
    if (modalEl && window.bootstrap) bootstrap.Modal.getOrCreateInstance(modalEl).show();
    @endif

    const form = document.getElementById('ffOtpLoginForm');
    if (!form) return;
    const phone = document.getElementById('phoneLogin'), otpPhone = document.getElementById('ffOtpPhoneInput');
    const phoneStep = document.getElementById('ff-phone-step'), otpStep = document.getElementById('ff-otp-step');
    const send = document.getElementById('sendOtpBtn'), resend = document.getElementById('ffResendOtp'), code = document.getElementById('ffOtpInput');
    let timer;
    const setOtpStep = (value) => {
      phoneStep.hidden = value; otpStep.hidden = !value;
      document.querySelectorAll('.ff-login-progress span').forEach((step, i) => step.classList.toggle('active', value ? i < 2 : i === 0));
      if (value) code.focus();
    };
    const startCountdown = () => {
      let seconds = 30; resend.disabled = true;
      document.getElementById('ffResendCountdown').textContent = seconds + 's';
      clearInterval(timer);
      timer = setInterval(() => {
        seconds -= 1; document.getElementById('ffResendCountdown').textContent = seconds > 0 ? seconds + 's' : '';
        if (seconds <= 0) { clearInterval(timer); resend.disabled = false; }
      }, 1000);
    };
    const requestOtp = async () => {
      const digits = phone.value.replace(/\D/g, '');
      if (!/^\d{10}$/.test(digits)) { phone.setCustomValidity('Enter a valid 10-digit mobile number.'); phone.reportValidity(); phone.setCustomValidity(''); return; }
      send.disabled = true; send.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending code…';
      const message = document.getElementById('ffOtpMessage');
      message.classList.remove('text-danger');
      try {
        const response = await fetch(@json(route('send-otp')), {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value}, body: JSON.stringify({phone: digits})});
        const result = await response.json();
        if (!response.ok || result.status !== 'success') throw new Error(result.message || 'We could not send the code. Please try again.');
        otpPhone.value = digits; document.getElementById('ffOtpPhone').textContent = '+91 ' + digits;
        message.textContent = result.message || 'Code sent. It expires in 5 minutes.';
        setOtpStep(true); startCountdown();
      } catch (error) { message.textContent = error.message || 'Connection issue. Please try again.'; message.classList.add('text-danger'); }
      finally { send.disabled = false; send.innerHTML = '<span>Send one-time password</span><i class="bi bi-arrow-right ms-1"></i>'; }
    };
    send.addEventListener('click', requestOtp);
    resend.addEventListener('click', () => { if (!resend.disabled) requestOtp(); });
    document.getElementById('ffChangePhone').addEventListener('click', () => { setOtpStep(false); code.value = ''; phone.focus(); });
    phone.addEventListener('input', () => { phone.value = phone.value.replace(/\D/g, '').slice(0, 10); });
    code.addEventListener('input', () => { code.value = code.value.replace(/\D/g, '').slice(0, 6); });
    if ({{ session('otp_step') || $errors->any() && old('otp') ? 'true' : 'false' }}) {
      otpPhone.value = @json(old('phone', '')); document.getElementById('ffOtpPhone').textContent = '+91 ' + otpPhone.value; setOtpStep(true);
    }
  });
})();
</script>

<!-- Sticky cart bar and cart drawer -->
<div id="ff-cart-float" class="ff-cart-float" hidden>
    <div class="ff-cart-float-inner">
        <span class="ff-cart-float-icon"><i class="bi bi-basket2-fill"></i></span>
        <strong><span id="ff-cart-float-count">0 items</span><span class="ff-cart-float-sep">|</span><span id="ff-cart-float-total">₹0.00</span></strong>
        <button type="button" data-bs-toggle="offcanvas" data-bs-target="#cardoffcanvas" aria-controls="cardoffcanvas">View Cart <i class="bi bi-arrow-right ms-1"></i></button>
        <button type="button" class="ff-cart-float-close" onclick="document.getElementById('ff-cart-float').hidden=true" aria-label="Hide cart bar"><i class="bi bi-x-lg"></i></button>
    </div>
</div>

<div class="offcanvas offcanvas-end ff-cart-drawer" tabindex="-1" id="cardoffcanvas" aria-labelledby="cardoffcanvasLabel">
    <div class="offcanvas-header ff-cart-drawer-header">
        <div><h2 class="offcanvas-title" id="cardoffcanvasLabel">My Cart</h2><span id="ff-cart-drawer-count" class="text-muted small">0 items</span></div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body ff-cart-drawer-body">
        <div class="ff-cart-drawer-grid">
            <section class="ff-cart-list" aria-label="Cart items">
                <div id="cart-items"></div>
                <a href="{{ url('/view_product/all') }}" class="ff-continue-shopping"><i class="bi bi-arrow-left me-1"></i>Continue shopping</a>
            </section>
            <aside class="ff-cart-summary">
                <h3>Order summary</h3>
                <button class="ff-cart-promo-button" type="button" onclick="openFreshfulPromo()">Find promo code <i class="bi bi-tag ms-1"></i></button>
                <div class="ff-cart-summary-line"><span>Subtotal</span><span id="subtotal">₹0</span></div>
                <div class="ff-cart-summary-line"><span>Delivery charge</span><span id="delivery">₹0</span></div>
                <div class="ff-cart-summary-line"><span>Discount</span><span id="discount">₹0</span></div>
                <div class="ff-cart-summary-line ff-cart-summary-total"><strong>Total</strong><strong id="total">₹0</strong></div>
                <span id="footer-total" hidden>₹0</span>
                @auth
                <a href="{{ url('/checkout') }}" class="ff-cart-checkout">Proceed to checkout <i class="bi bi-arrow-right ms-1"></i></a>
                @else
                <button type="button" class="ff-cart-checkout" onclick="openLoginFromCart()">Login to checkout <i class="bi bi-arrow-right ms-1"></i></button>
                @endauth
                <p class="ff-cart-secure"><i class="bi bi-shield-check me-1"></i>Fresh, secure checkout</p>
            </aside>
        </div>
    </div>
</div>

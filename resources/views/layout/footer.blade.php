<footer id="footer" class="footer ff-footer">
    <div class="ff-foot-main">
        <div class="container-fluid container-xl">
            <div class="ff-foot-grid">

                <div class="ff-foot-brand">
                    <a class="ff-foot-logo" href="{{ url('/') }}" aria-label="Freshful home">
                        <img src="{{ asset('assets/image/logo.png') }}" alt="Freshful" class="logo-footer" loading="lazy">
                    </a>
                    <h6 class="ff-foot-legal">FRESHFUL DIGITAL FOODS (OPC) PRIVATE LIMITED</h6>
                    <p class="ff-foot-about">Freshful means &lsquo;freshness&rsquo;. We measure ourselves in terms of the customer delight delivered, staying true to our values of quality, compassion, and innovation.</p>
                    <ul class="ff-foot-contact">
                        <li><i class="bi bi-telephone-fill" aria-hidden="true"></i><a href="tel:+918124200242">+91 8124200242</a></li>
                        <li><i class="bi bi-envelope-fill" aria-hidden="true"></i><a href="mailto:talktous@freshful.in">talktous@freshful.in</a></li>
                    </ul>
                    <div class="ff-social" aria-label="Follow Freshful on social media">
                        <a class="is-facebook" href="https://www.facebook.com/freshfulfood/" target="_blank" rel="noopener noreferrer me" aria-label="Freshful on Facebook" title="Facebook"><i class="bi bi-facebook" aria-hidden="true"></i></a>
                        <a class="is-instagram" href="https://www.instagram.com/freshfulfood" target="_blank" rel="noopener noreferrer me" aria-label="Freshful on Instagram" title="Instagram"><i class="bi bi-instagram" aria-hidden="true"></i></a>
                        <a class="is-x" href="https://x.com/Freshfullfoods" target="_blank" rel="noopener noreferrer me" aria-label="Freshful on X" title="X"><i class="bi bi-twitter-x" aria-hidden="true"></i></a>
                        <a class="is-youtube" href="https://www.youtube.com/@Freshfulfoods" target="_blank" rel="noopener noreferrer me" aria-label="Freshful on YouTube" title="YouTube"><i class="bi bi-youtube" aria-hidden="true"></i></a>
                        <a class="is-linkedin" href="https://www.linkedin.com/company/freshfulfoods/home/" target="_blank" rel="noopener noreferrer me" aria-label="Freshful on LinkedIn" title="LinkedIn"><i class="bi bi-linkedin" aria-hidden="true"></i></a>
                    </div>
                </div>

                <nav class="ff-foot-col" aria-label="Navigation">
                    <h6 class="ff-foot-title">Navigation</h6>
                    <ul class="ff-foot-list">
                        <li><a href="{{ url('/') }}">Home</a></li>
                        <li><a href="{{ url('/why-freshful') }}">Why us?</a></li>
                        <li><a href="{{ route('certificate') }}">Certificate</a></li>
                        <li><a href="{{ url('/franchisee') }}">Franchise</a></li>
                        <li><a href="{{ url('/about') }}">About us</a></li>
                    </ul>
                </nav>

                <nav class="ff-foot-col" aria-label="Policies">
                    <h6 class="ff-foot-title">Policies</h6>
                    <ul class="ff-foot-list">
                        <li><a href="{{ url('/privacypolicy') }}">Privacy policy</a></li>
                        <li><a href="{{ url('/shipping-policy') }}">Shipping policy</a></li>
                        <li><a href="{{ url('/terms') }}">Terms &amp; Condition</a></li>
                        <li><a href="{{ url('/refund_policy') }}">Refund Policy</a></li>
                        <li><a href="{{ url('/faq') }}">FAQs</a></li>
                    </ul>
                </nav>

                <nav class="ff-foot-col" aria-label="Shop and help">
                    <h6 class="ff-foot-title">Shop &amp; help</h6>
                    <ul class="ff-foot-list">
                        <li><a href="{{ url('/view_product/all') }}">Shop all</a></li>
                        <li><a href="{{ route('categories') }}">Categories</a></li>
                        <li><a href="{{ route('maps') }}">Our stores</a></li>
                        <li><a href="{{ url('/refer_earn') }}">Refer &amp; Earn</a></li>
                        <li><a href="{{ url('/contact_us') }}">Contact us</a></li>
                    </ul>
                </nav>

                <aside class="ff-foot-care" aria-label="Customer care">
                    <div>
                        <h3>We are here to help</h3>
                        <p>Service center helpline time</p>
                        <p class="ff-foot-hours"><i class="bi bi-clock-fill" aria-hidden="true"></i><span>Sat &ndash; Friday<br>10:00 AM &ndash; 9:00 PM</span></p>
                    </div>
                    <a class="ff-foot-call" href="tel:+918124200242"><i class="bi bi-telephone-fill" aria-hidden="true"></i> Call +91 8124200242</a>
                </aside>
            </div>
        </div>
    </div>

    <div class="ff-foot-bottom">
        <div class="container-fluid container-xl">
            <p>&copy; {{ date('Y') }} Freshful Digital Foods (OPC) Private Limited, Copyright, All rights reserved.</p>
            <div class="ff-foot-badges"><span><i class="bi bi-patch-check-fill" aria-hidden="true"></i> FSSAI Lic. No. 12422999001815</span></div>
        </div>
    </div>
</footer>

<!-- Scroll Top -->
<a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

<!-- Toast notification container -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:99999;">
    <div id="app-toast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body fw-semibold" id="app-toast-msg"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- Main JS File -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<!-- <script src="{{ url('assets/js/main.js')}}"></script> -->
<script src="{{ url('assets/js/slider.js')}}"></script>
<script>
    if (typeof toastr !== 'undefined') {
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "4000",
            "extendedTimeOut": "1000"
        };
    }

    function notify(message, type) {
        const toastEl = document.getElementById('app-toast');
        const toastMsg = document.getElementById('app-toast-msg');
        if (!toastEl || !toastMsg) return;

        const colors = { success: '#198754', error: '#dc3545', warning: '#ffc107', info: '#0dcaf0' };
        toastEl.style.background = colors[type] || colors.info;
        toastEl.style.color = (type === 'warning') ? '#000' : '#fff';
        toastMsg.textContent = message;

        const t = bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 3500 });
        t.show();
    }
</script>
@if(session('success'))
    <script>
        toastr.success("{{ session('success') }}");
    </script>
@endif

@if(session('error'))
    <script>
        toastr.error("{{ session('error') }}");
    </script>
@endif

@if(session('warning'))
    <script>
        toastr.warning("{{ session('warning') }}");
    </script>
@endif

@if(session('info'))
    <script>
        toastr.info("{{ session('info') }}");
    </script>
@endif
<script>
    // Optional: Add sticky header effect with smooth animation
    window.addEventListener('scroll', function() {
        const header = document.getElementById('myHeader');
        if (header) {
            if (window.scrollY > 100) {
                header.classList.add('sticky');
            } else {
                header.classList.remove('sticky');
            }
        }
    });
</script>
<script>
    const buyer_id = {{ Auth::user()->id ?? 0 }};
    const getcartUrl = "{{ route('get-cart') }}";
    window.cartQuantities = window.cartQuantities || {};
    function loadCart() {
        fetch(getcartUrl, { credentials: 'same-origin' })
            .then(res => res.json())
            .then(data => {
                if (data.result && data.products && data.products.length > 0) {
                    renderCart(data.products);
                } else {
                    window.cartQuantities = {};
                    document.getElementById('cart-items').innerHTML = `
                        <div class="text-center py-4">
                            <img src="{{ asset('uploads/empty-cart.png') }}" alt="Empty cart" style="max-width:180px;opacity:0.8;">
                            <h3 class="mt-2 fs-5 fw-bold">Your cart is empty</h3>
                            <p class="text-muted fs-7">Add something fresh and it will appear here.</p>
                        </div>`;
                    updateCartBadge(0);
                    updateBill(0);
                    const floatBar = document.getElementById('ff-cart-float');
                    const drawerCount = document.getElementById('ff-cart-drawer-count');
                    if (floatBar) floatBar.hidden = true;
                    if (drawerCount) drawerCount.textContent = '0 items';
                    syncProductQuantityControls();
                }
            })
            .catch(() => {
                document.getElementById('cart-items').innerHTML = '<p class="text-muted text-center py-3">Unable to load cart</p>';
            });
    }
</script>
<script>
    function renderCart(products) {
        let html = '';
        let subtotal = 0;
        let itemCount = 0;
        window.cartQuantities = {};

        const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));

        products.forEach((item, index) => {
            let pid = item.cart_pid;
            let price = parseFloat(item.main_price) || 0;
            let qty = parseInt(item.quantity);
            itemCount += qty;
            window.cartQuantities[pid] = qty;
            let total = price * qty;
            let unit = ((item.unit_quantity ?? '') + ' ' + (item.product_unit ?? '')).trim();
            let productImage = item.product_image ? ('/uploads/images/products/' + encodeURIComponent(item.product_image)) : item.image;

            subtotal += total;

            html += `
            <article class="ff-cart-row" id="cart_item_${pid}">
                <img class="ff-cart-row-image" src="${escapeHtml(productImage)}" alt="${escapeHtml(item.title)}" loading="lazy" onerror="this.src='{{ asset('uploads/empty-cart.png') }}'">
                <div class="ff-cart-row-info">
                    <strong class="ff-cart-row-title">${escapeHtml(item.title)}</strong>
                    <span class="ff-cart-row-meta">${escapeHtml(unit)}</span>
                    <span class="ff-cart-row-price">₹${price.toFixed(2)} each</span>
                </div>
                <div class="ff-cart-row-tools">
                    <div class="ff-cart-stepper" aria-label="Change quantity">
                        <button type="button" onclick="updateQty(${pid}, ${qty - 1})" aria-label="Decrease quantity">−</button>
                        <span>${qty}</span>
                        <button type="button" onclick="updateQty(${pid}, ${qty + 1})" aria-label="Increase quantity">+</button>
                    </div>
                    <strong class="ff-cart-row-total">₹${total.toFixed(2)}</strong>
                    <button type="button" class="ff-cart-remove" onclick="updateQty(${pid}, 0)" aria-label="Remove ${escapeHtml(item.title)}" title="Remove item"><i class="bi bi-trash3"></i></button>
                </div>
            </article>
            `;
        });

        if (products.length === 0) {
            html = '<div class="text-center py-4"><h3 class="fs-5 fw-bold">Your cart is empty</h3><p class="text-muted mb-0">Add something fresh and it will appear here.</p></div>';
        }

        document.getElementById('cart-items').innerHTML = html;
        updateCartBadge(itemCount);
        updateBill(subtotal);
        const countText = itemCount + (itemCount === 1 ? ' item' : ' items');
        const drawerCount = document.getElementById('ff-cart-drawer-count');
        if (drawerCount) drawerCount.textContent = countText;
        const floatBar = document.getElementById('ff-cart-float');
        const floatCount = document.getElementById('ff-cart-float-count');
        const floatTotal = document.getElementById('ff-cart-float-total');
        if (floatBar) floatBar.hidden = itemCount < 1;
        if (floatCount) floatCount.textContent = countText;
        if (floatTotal) floatTotal.textContent = '₹' + subtotal.toFixed(2);
        syncProductQuantityControls();
    }

    function updateCartBadge(count) {
        const badge = document.getElementById('cart-badge-count');
        if (badge) {
            badge.textContent = count > 0 ? count : '';
            badge.style.display = count > 0 ? 'inline-flex' : 'none';
        }
    }
</script>
<script>
    function updateBill(subtotal) {
        let delivery = 0;
        let discount = 0;
        let total = subtotal + delivery - discount;

        const values = {subtotal, delivery, discount, total};
        Object.entries(values).forEach(([id, value]) => {
            const node = document.getElementById(id);
            if (node) node.innerText = `₹${Number(value).toFixed(2)}`;
        });
        const footerTotal = document.getElementById('footer-total');
        if (footerTotal) footerTotal.innerText = `₹${Number(total).toFixed(2)}`;
    }
</script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        loadCart();
    });
</script>
<script>
const csrfToken = '{{ csrf_token() }}';
const addCartUrl = "{{ route('add-cart') }}";
function updateCartHttp(payload) {
    return fetch(addCartUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify(payload)
    })
    .then(res => res.json());
}

function updateQty(product_id, quantity) {
    if (quantity < 0) return;
    updateCartHttp({ product_id, buyer_id, quantity })
        .then(data => {
            if (data.result) {
                updateCartBadge(data.cart_count);
                loadCart();
            } else {
                notify(data.message || 'Unable to update quantity', 'error');
            }
        })
        .catch(() => notify('Network error while updating cart', 'error'));
}

function openLoginFromCart() {
    const cartEl = document.getElementById('cardoffcanvas');
    const loginEl = document.getElementById('loginoffcanvas');
    if (!cartEl || !loginEl) return;

    const cartOffcanvas = bootstrap.Offcanvas.getInstance(cartEl);
    if (cartOffcanvas) {
        cartEl.addEventListener('hidden.bs.offcanvas', function handler() {
            cartEl.removeEventListener('hidden.bs.offcanvas', handler);
            bootstrap.Modal.getOrCreateInstance(loginEl).show();
        }, { once: true });
        cartOffcanvas.hide();
    } else {
        bootstrap.Modal.getOrCreateInstance(loginEl).show();
    }
}

function openFreshfulPromo() {
    const promoEl = document.getElementById('promocode');
    if (!promoEl) {
        window.location.href = "{{ url('/checkout') }}";
        return;
    }
    const cartEl = document.getElementById('cardoffcanvas');
    const cartOffcanvas = cartEl ? bootstrap.Offcanvas.getInstance(cartEl) : null;
    const showPromo = () => bootstrap.Modal.getOrCreateInstance(promoEl).show();
    if (cartOffcanvas) {
        cartEl.addEventListener('hidden.bs.offcanvas', showPromo, { once: true });
        cartOffcanvas.hide();
    } else {
        showPromo();
    }
}

function addToCart(product_id, quantity = null) {
    const nextQuantity = (quantity === null || quantity === undefined)
        ? (Number(window.cartQuantities[product_id] || 0) + 1)
        : Number(quantity);
    updateCartHttp({ product_id, buyer_id, quantity: nextQuantity })
        .then(data => {
            if (data.result) {
                window.cartQuantities[product_id] = nextQuantity;
                syncProductQuantityControls();
                updateCartBadge(data.cart_count);
                loadCart();
                notify(data.message || 'Added to cart', 'success');
            } else {
                notify(data.message || 'Failed to add to cart', 'error');
            }
        })
        .catch(() => notify('Network error while adding to cart', 'error'));
}

function syncProductQuantityControls() {
    document.querySelectorAll('[data-product-quantity]').forEach(control => {
        const productId = control.dataset.productQuantity;
        const quantity = Number(window.cartQuantities[productId] || 0);
        const addButton = control.querySelector('.product-add-button');
        const stepper = control.querySelector('.catalog-quantity, .ff-quantity-stepper');
        const value = control.querySelector('.ff-quantity-value');
        if (addButton) addButton.hidden = quantity > 0;
        if (stepper) stepper.hidden = quantity < 1;
        if (value) value.textContent = quantity;
    });
}

function changeProductQuantity(product_id, amount) {
    const quantity = Math.max(0, Number(window.cartQuantities[product_id] || 0) + Number(amount));
    addToCart(product_id, quantity);
}

function addSelectedProductQuantity(productId) {
    const selected = Math.max(1, Number(document.getElementById('detailQuantity')?.value) || 1);
    const current = Number(window.cartQuantities[productId] || 0);
    addToCart(productId, current + selected);
}

function addProductToCompare() {
    /* The compare feature was removed from Freshful. Kept as an empty function so older pages do not raise an error. */
}
</script>
<script>
// Location search — Google Places Autocomplete + hub verification (v2 21 Oct 2026: pick freely, confirm to save, auto-detect)
function freshfulLocationPinIcon() {
    var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="72" viewBox="0 0 48 72"><path d="M22 39 L19 65 Q24 72 29 65 L26 39 Z" fill="#8a9196" stroke="#555d62" stroke-width="1"/><circle cx="24" cy="23" r="21" fill="#f20d18" stroke="#fff" stroke-width="3"/><circle cx="24" cy="23" r="7" fill="#fff"/></svg>';
    return { url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg), scaledSize: new google.maps.Size(40, 60), anchor: new google.maps.Point(20, 58) };
}
var FF_LOC = { map: null, marker: null, geocoder: null, ac: null, pending: null, seq: 0 };
function ffLocCsrf() {
    var m = document.querySelector('meta[name="csrf-token"]'); if (m && m.content) return m.content;
    var i = document.querySelector('input[name="_token"]'); return i ? i.value : '';
}
function ffLocCookie(n) { var m = document.cookie.match('(?:^|; )' + n + '=([^;]*)'); try { return m ? decodeURIComponent(m[1]) : ''; } catch (e) { return m ? m[1] : ''; } }
function ffLocEsc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
function ffLocStatus(html) { var s = document.getElementById('location-status'); if (s) s.innerHTML = html || ''; }
function ffLocPost(body) {
    return fetch('/ajax/check-hub', { method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': ffLocCsrf() },
        body: JSON.stringify(body) }).then(function (r) { return r.json(); });
}
function ffLocBox() {
    var b = document.getElementById('ff-loc-confirm'); if (b) return b;
    var st = document.getElementById('location-status'); if (!st) return null;
    b = document.createElement('div'); b.id = 'ff-loc-confirm'; b.style.cssText = 'display:none;margin-top:12px;padding:14px;border:1px solid #e6eae4;border-radius:14px;background:#fafbf9';
    b.innerHTML = '<div style="font-size:12px;color:#68736b;letter-spacing:.04em;text-transform:uppercase">Deliver to</div>' +
        '<div id="ff-loc-addr" style="font-weight:600;margin-top:2px"></div><div id="ff-loc-avail" style="font-size:13px;margin-top:4px"></div>' +
        '<div style="font-size:12px;color:#68736b;margin-top:6px">Not exact? Drag the pin or tap the map to move it.</div>' +
        '<button type="button" id="ff-loc-go" disabled style="width:100%;margin-top:12px;min-height:46px;border:0;border-radius:12px;background:#f53b13;color:#fff;font-weight:700;opacity:.5">Confirm this location</button>';
    st.parentNode.insertBefore(b, st);
    b.querySelector('#ff-loc-go').addEventListener('click', ffLocConfirm);
    return b;
}
function initLocationSearch() {
    if (!window.google || !google.maps) return;
    if (!FF_LOC.geocoder) FF_LOC.geocoder = new google.maps.Geocoder();
    var modal = document.getElementById('location-dropdown');
    if (modal && !modal.dataset.ffBound) { modal.dataset.ffBound = '1'; modal.addEventListener('shown.bs.modal', ffLocSetupMap); }
    if (modal && modal.classList.contains('show')) ffLocSetupMap();
    if (!ffLocCookie('lat') && !ffLocCookie('hub_id')) ffLocAutoDetect();
}
function ffLocSetupMap() {
    var input = document.getElementById('locationSearchInput');
    var el = document.getElementById('freshful-header-map');
    if (!el || !window.google) return;
    ffLocBox();
    if (!FF_LOC.map) {
        var lat = parseFloat(ffLocCookie('lat')), lng = parseFloat(ffLocCookie('lng'));
        var has = !isNaN(lat) && !isNaN(lng);
        FF_LOC.map = new google.maps.Map(el, { center: has ? { lat: lat, lng: lng } : { lat: 20.5937, lng: 78.9629 }, zoom: has ? 16 : 5,
            mapTypeControl: false, streetViewControl: false, fullscreenControl: true, gestureHandling: 'greedy', clickableIcons: false });
        FF_LOC.marker = new google.maps.Marker({ map: FF_LOC.map, position: has ? { lat: lat, lng: lng } : null, draggable: true, icon: freshfulLocationPinIcon(), title: 'Drag to your exact location' });
        FF_LOC.map.addListener('click', function (e) { ffLocMove(e.latLng, false); });
        FF_LOC.marker.addListener('dragend', function (e) { ffLocMove(e.latLng, false); });
    }
    google.maps.event.trigger(FF_LOC.map, 'resize');
    if (input && !input.dataset.autocompleteReady && google.maps.places) {
        FF_LOC.ac = new google.maps.places.Autocomplete(input, { types: ['geocode'], componentRestrictions: { country: 'in' }, fields: ['geometry', 'formatted_address', 'address_components', 'name'] });
        FF_LOC.ac.addListener('place_changed', function () {
            var place = FF_LOC.ac.getPlace();
            if (place && place.geometry) { ffLocMove(place.geometry.location, true); return; }
            var typed = (place && place.name) || input.value;
            if (!typed) return;
            ffLocStatus('<span class="text-muted">Finding ' + ffLocEsc(typed) + '…</span>');
            FF_LOC.geocoder.geocode({ address: typed, componentRestrictions: { country: 'IN' } }, function (r, s) {
                if (s !== 'OK' || !r || !r[0]) { ffLocStatus('<span class="text-danger">We could not find that place. Pick a suggestion or tap the map.</span>'); return; }
                ffLocStatus(''); ffLocMove(r[0].geometry.location, true);
            });
        });
        input.addEventListener('keydown', function (e) { if (e.key === 'Enter') e.preventDefault(); });
        input.dataset.autocompleteReady = '1';
    }
    if (FF_LOC.pending) ffLocShow(FF_LOC.pending);
}
function ffLocMove(latLng, zoomIn) {
    if (FF_LOC.map) {
        FF_LOC.map.panTo(latLng);
        if (zoomIn || FF_LOC.map.getZoom() < 15) FF_LOC.map.setZoom(17);
        FF_LOC.marker.setPosition(latLng);
    }
    ffLocPreview(typeof latLng.lat === 'function' ? latLng.lat() : latLng.lat, typeof latLng.lng === 'function' ? latLng.lng() : latLng.lng, null);
}
/* Look up the address and whether a hub delivers there - nothing is saved yet. */
function ffLocPreview(lat, lng, done) {
    var my = ++FF_LOC.seq;
    FF_LOC.pending = { lat: lat, lng: lng, address: '', city: '', ok: false, checking: true };
    ffLocShow(FF_LOC.pending);
    FF_LOC.geocoder.geocode({ location: { lat: lat, lng: lng } }, function (results, status) {
        if (my !== FF_LOC.seq) return;
        var p = FF_LOC.pending;
        if (status === 'OK' && results && results[0]) {
            p.address = results[0].formatted_address || '';
            (results[0].address_components || []).forEach(function (c) {
                if (!p.city && (c.types.indexOf('locality') >= 0 || c.types.indexOf('postal_town') >= 0)) p.city = c.long_name;
            });
            if (!p.city) (results[0].address_components || []).forEach(function (c) { if (!p.city && c.types.indexOf('administrative_area_level_2') >= 0) p.city = c.long_name; });
            var input = document.getElementById('locationSearchInput'); if (input) input.value = p.address;
        }
        ffLocPost({ lat: lat, lng: lng, address: p.address, city: p.city, preview: 1 }).then(function (res) {
            if (my !== FF_LOC.seq) return;
            p.checking = false; p.ok = !!res.result; p.message = res.message || ''; p.hub = res.hub || '';
            ffLocShow(p); if (done) done(p);
        }).catch(function () { if (my !== FF_LOC.seq) return; p.checking = false; p.ok = false; p.message = 'Network error. Please try again.'; ffLocShow(p); if (done) done(p); });
    });
}
function ffLocShow(p) {
    var b = ffLocBox(); if (!b) return;
    b.style.display = 'block';
    document.getElementById('ff-loc-addr').textContent = p.address || (p.checking ? 'Finding address…' : 'Pinned location');
    var av = document.getElementById('ff-loc-avail'), go = document.getElementById('ff-loc-go');
    if (p.checking) { av.innerHTML = '<span class="text-muted">Checking delivery…</span>'; }
    else if (p.ok) { av.innerHTML = '<span class="text-success fw-semibold">✓ We deliver here</span>'; }
    else { av.innerHTML = '<span class="text-danger">' + ffLocEsc(p.message || "Sorry, we don't deliver here yet.") + '</span>'; }
    go.disabled = !p.ok || p.checking; go.style.opacity = go.disabled ? '.5' : '1';
}
function ffLocConfirm() {
    var p = FF_LOC.pending; if (!p || !p.ok) return;
    var go = document.getElementById('ff-loc-go'); if (go) { go.disabled = true; go.textContent = 'Saving…'; }
    ffLocPost({ lat: p.lat, lng: p.lng, address: p.address, city: p.city }).then(function (res) {
        if (res.result) { ffLocStatus('<span class="text-success fw-semibold">✓ ' + ffLocEsc(res.message) + '</span>'); setTimeout(function () { location.reload(); }, 400); }
        else { ffLocStatus('<span class="text-danger">' + ffLocEsc(res.message) + '</span>'); if (go) { go.textContent = 'Confirm this location'; go.disabled = false; } }
    }).catch(function () { ffLocStatus('<span class="text-danger">Network error. Please try again.</span>'); if (go) { go.textContent = 'Confirm this location'; go.disabled = false; } });
}
/* First visit: find the customer automatically; save it straight away when we deliver there. */
function ffLocAutoDetect() {
    try { if (sessionStorage.getItem('ff-loc-auto')) return; sessionStorage.setItem('ff-loc-auto', '1'); } catch (e) {}
    if (!navigator.geolocation) return;
    navigator.geolocation.getCurrentPosition(function (pos) {
        ffLocPreview(pos.coords.latitude, pos.coords.longitude, function (p) {
            if (p.ok) { ffLocConfirm(); return; }
            var m = document.getElementById('location-dropdown');
            if (m && window.bootstrap) bootstrap.Modal.getOrCreateInstance(m).show();
        });
    }, function () {}, { enableHighAccuracy: true, timeout: 12000, maximumAge: 300000 });
}
function useMyLocation() {
    if (!navigator.geolocation) { ffLocStatus('Location access is not available in this browser.'); return; }
    ffLocStatus('<span class="text-muted">Getting your current location…</span>');
    navigator.geolocation.getCurrentPosition(function (pos) {
        ffLocStatus('');
        ffLocMove(new google.maps.LatLng(pos.coords.latitude, pos.coords.longitude), true);
    }, function () { ffLocStatus('<span class="text-danger">Location access was blocked. Search for an address or tap the map instead.</span>'); },
    { enableHighAccuracy: true, timeout: 12000 });
}
/* Kept for older pages that call it directly: shows the place for confirmation. */
function checkHubLocation(lat, lng, address, city) { ffLocPreview(lat, lng, null); }
function freshfulResolveHeaderPin(position) { ffLocMove(position, false); }
</script>
<script>
/* One Google Maps loader for the whole site (v3). The API is never loaded twice:
   - if a page already loads it with its own script tag, we wait for that one;
   - otherwise we load it once, with the site key and the places library.
   Page callbacks: initLocationSearch (delivery area) always runs; names in window.__ffMapsCb run too;
   any page can also call ffMapsReady(function () { ... }). */
(function () {
    if (window.ffMaps) return;
    var KEY = {!! json_encode((string) config('services.google_maps.key')) !!};
    var S = window.ffMaps = { done: false, queue: [] };
    function ok() { return !!(window.google && google.maps && google.maps.Map); }
    function call(name) {
        if (typeof window[name] !== 'function') return;
        try { window[name](); } catch (e) { if (window.console) console.error('Google Maps start-up "' + name + '" failed', e); }
    }
    function finish(own) {
        if (S.done) return; S.done = true;
        var extra = window.__ffMapsCb || [];
        call('initLocationSearch');
        extra.forEach(call);
        if (own) ['initMap', 'initStoresMap', 'initStoreMap', 'initStores'].forEach(function (n) { if (extra.indexOf(n) < 0) call(n); });
        S.queue.splice(0).forEach(function (fn) { try { fn(); } catch (e) { if (window.console) console.error(e); } });
    }
    window.ffMapsBoot = function () { finish(true); };
    window.ffMapsReady = function (fn) { if (S.done) { try { fn(); } catch (e) { if (window.console) console.error(e); } } else { S.queue.push(fn); } };
    if (!window.gm_authFailure) window.gm_authFailure = function () {
        if (window.console) console.error('Google Maps refused the site key (check billing, allowed websites and enabled APIs).');
        var s = document.getElementById('location-status');
        if (s) s.textContent = 'The map is temporarily unavailable. You can still type your address.';
    };
    function foreign() {
        var all = document.querySelectorAll('script[src*="maps.googleapis.com/maps/api/js"]');
        for (var i = 0; i < all.length; i++) { if (all[i].id !== 'ff-gmaps') return all[i]; }
        return null;
    }
    function load() {
        if (document.getElementById('ff-gmaps')) return;
        var s = document.createElement('script');
        s.id = 'ff-gmaps'; s.async = true; s.defer = true;
        s.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(KEY) + '&libraries=places&callback=ffMapsBoot';
        document.head.appendChild(s);
    }
    function wait(left) {
        if (ok()) { finish(false); return; }
        if (left > 0) { setTimeout(function () { wait(left - 1); }, 150); } else { load(); }
    }
    function start() {
        if (ok()) { finish(false); }
        else if (foreign()) { wait(135); }
        else { load(); }
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', start); } else { start(); }
})();
</script>

<script>
(function () {
    const searchInput = document.getElementById('search_product');
    const dropdown    = document.getElementById('search-results-dropdown');
    if (!searchInput || !dropdown) return;

    let debounceTimer;

    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        const q = this.value.trim();
        if (q.length < 2) { dropdown.style.display = 'none'; dropdown.innerHTML = ''; return; }

        debounceTimer = setTimeout(function () {
            fetch('{{ route("search-products") }}?query=' + encodeURIComponent(q), { credentials: 'same-origin' })
                .then(r => r.json())
                .then(items => {
                    if (!items.length) {
                        dropdown.innerHTML = '<div class="px-3 py-2 text-muted small">No results found</div>';
                        dropdown.style.display = 'block';
                        return;
                    }
                    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[character]));
                    const productImageBase = "{{ asset('uploads/images/products') }}/";
                    dropdown.innerHTML = items.map(item => {
                        const imageName = String(item.product_image || '').replace(/^\/+/, '');
                        const imageUrl = imageName
                            ? productImageBase + imageName.split('/').map(encodeURIComponent).join('/')
                            : String(item.image_url || '');
                        const title = escapeHtml(item.product_name || item.item_name || 'Product');
                        const price = escapeHtml(item.main_price ?? '');
                        const href = escapeHtml(item.detail_url || '#');
                        const image = escapeHtml(imageUrl);
                        const thumbnail = image
                            ? `<img src="${image}" alt="${title}" loading="lazy" onerror="this.hidden=true;this.nextElementSibling.hidden=false">`
                            : '';
                        return `<a href="${href}" class="d-flex align-items-center gap-3 px-3 py-2 text-decoration-none text-dark freshful-search-result">
                            ${thumbnail}<span class="freshful-search-placeholder" ${image ? 'hidden' : ''}><i class="bi bi-basket2"></i></span>
                            <div class="flex-grow-1 min-w-0"><div class="fw-semibold fs-7 text-truncate">${title}</div><div class="text-danger small">₹${price}</div></div>
                        </a>`;
                    }).join('');
                    dropdown.style.display = 'block';
                })
                .catch(() => { dropdown.style.display = 'none'; });
        }, 300);
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function (e) {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });

    // Keyboard: Enter navigates to first result
    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            const first = dropdown.querySelector('a');
            if (first) { e.preventDefault(); window.location.href = first.href; }
        }
    });
})();
</script>

<script>
(function () {
    document.addEventListener('click', function (event) {
        const button = event.target.closest('[data-scroll-target][data-scroll-direction]');
        if (!button) return;
        const track = document.getElementById(button.dataset.scrollTarget);
        if (!track) return;
        const direction = Number(button.dataset.scrollDirection) || 1;
        const item = track.firstElementChild;
        const style = getComputedStyle(track);
        const gap = parseFloat(style.columnGap || style.gap) || 12;
        const step = item ? item.getBoundingClientRect().width + gap : track.clientWidth * .8;
        track.scrollBy({ left: direction * Math.max(step * 2, track.clientWidth * .72), behavior: 'smooth' });
    });

    let addressMap, addressMarker, addressGeocoder, addressAutocomplete;
    let activeAddressForm;
    const parts = (result) => {
        const all = {};
        (result.address_components || []).forEach(function (item) {
            item.types.forEach(type => { if (!all[type]) all[type] = item.long_name; });
        });
        const road = [all.street_number, all.route].filter(Boolean).join(' ');
        const locality = all.sublocality_level_1 || all.sublocality || all.neighborhood || all.locality || all.administrative_area_level_3 || '';
        const city = all.locality || all.administrative_area_level_2 || all.administrative_area_level_1 || '';
        return { road: road || result.formatted_address || '', locality: locality || city || result.formatted_address || '', city: city || locality || '' };
    };
    function fillFromPin(position) {
        if (!activeAddressForm || !addressGeocoder) return;
        const status = document.getElementById('freshful-address-map-status');
        if (status) status.textContent = 'Finding this address…';
        addressGeocoder.geocode({ location: position }, function (results, resultStatus) {
            if (resultStatus !== 'OK' || !results || !results[0]) {
                if (status) status.textContent = 'Pin saved. Please complete the address fields manually.';
                return;
            }
            const result = results[0], data = parts(result);
            const set = (name, value) => { const input = activeAddressForm.querySelector('[name="' + name + '"]'); if (input && value) input.value = value; };
            set('street_name', data.road);
            set('locality', data.locality);
            set('city', data.city);
            set('latitude', typeof position.lat === 'function' ? position.lat() : position.lat);
            set('longitude', typeof position.lng === 'function' ? position.lng() : position.lng);
            if (status) status.textContent = 'Pinned: ' + result.formatted_address;
        });
    }
    function initAddressMap() {
        const mapElement = document.getElementById('freshful-address-map');
        const form = document.querySelector('[data-freshful-address-form]');
        if (!mapElement || !form || !form.closest('.modal')?.classList.contains('show')) return;
        activeAddressForm = form;
        if (!window.google || !google.maps) { setTimeout(initAddressMap, 450); return; }
        addressGeocoder = addressGeocoder || new google.maps.Geocoder();
        const search = document.getElementById('freshful-address-map-search');
        if (!addressMap) {
            addressMap = new google.maps.Map(mapElement, { center: { lat: 20.5937, lng: 78.9629 }, zoom: 5, mapTypeControl: false, streetViewControl: false, fullscreenControl: true });
            addressMarker = new google.maps.Marker({ map: addressMap, draggable: true, icon: freshfulLocationPinIcon(), title: 'Delivery address', zIndex: 999999 });
            addressMap.addListener('click', e => { addressMap.setCenter(e.latLng); addressMap.setZoom(Math.max(addressMap.getZoom(), 15)); addressMarker.setPosition(e.latLng); fillFromPin(e.latLng); });
            addressMarker.addListener('dragend', e => fillFromPin(e.latLng));
        }
        google.maps.event.trigger(addressMap, 'resize');
        if (search && !search.dataset.autocompleteReady) {
            addressAutocomplete = new google.maps.places.Autocomplete(search, { types: ['geocode'], componentRestrictions: { country: 'in' } });
            addressAutocomplete.addListener('place_changed', function () {
                const place = addressAutocomplete.getPlace();
                if (!place.geometry) return;
                addressMap.setCenter(place.geometry.location); addressMap.setZoom(16); addressMarker.setPosition(place.geometry.location); fillFromPin(place.geometry.location);
            });
            search.dataset.autocompleteReady = '1';
        }
        const lat = Number(form.querySelector('[name="latitude"]')?.value);
        const lng = Number(form.querySelector('[name="longitude"]')?.value);
        if (Number.isFinite(lat) && Number.isFinite(lng) && (lat || lng)) {
            const position = { lat, lng }; addressMap.setCenter(position); addressMap.setZoom(16); addressMarker.setPosition(position);
        } else {
            const placeText = [form.querySelector('[name="street_name"]')?.value, form.querySelector('[name="locality"]')?.value, form.querySelector('[name="city"]')?.value].filter(Boolean).join(', ');
            if (placeText) addressGeocoder.geocode({ address: placeText }, (results, status) => { if (status === 'OK' && results[0]) { addressMap.setCenter(results[0].geometry.location); addressMap.setZoom(15); addressMarker.setPosition(results[0].geometry.location); } });
        }
    }
    function resetAddressForm() {
        const form = document.querySelector('[data-freshful-address-form]'); if (!form) return;
        form.reset(); form.querySelector('[name="address_id"]').value = '';
        const modal = form.closest('.modal'), title = modal.querySelector('.modal-title'), save = modal.querySelector('[data-address-save]');
        if (title) title.textContent = 'Add delivery address'; if (save) save.textContent = 'Save Address';
        const status = document.getElementById('freshful-address-map-status'); if (status) status.textContent = 'Click the map to place the pin.';
        if (addressMarker) addressMarker.setPosition(null);
    }
    document.addEventListener('click', function (event) {
        const add = event.target.closest('[data-freshful-add-address]');
        if (add) { resetAddressForm(); return; }
        const edit = event.target.closest('.freshful-edit-address');
        if (edit) {
            event.preventDefault(); resetAddressForm();
            const form = document.querySelector('[data-freshful-address-form]'), modalEl = form?.closest('.modal');
            if (!form || !modalEl) return;
            const data = {
                id: edit.dataset.addressId || '', name: edit.dataset.addressName || '', phone: edit.dataset.addressPhone || '',
                locality: edit.dataset.addressLocality || '', street_name: edit.dataset.addressStreetName || '', landmark: edit.dataset.addressLandmark || '',
                city: edit.dataset.addressCity || '', type: edit.dataset.addressType || '', latitude: edit.dataset.addressLatitude || '', longitude: edit.dataset.addressLongitude || ''
            };
            Object.keys(data).forEach(key => { const input = form.querySelector('[name="' + key + '"]'); if (input && data[key] !== null && data[key] !== undefined) input.value = data[key]; });
            modalEl.querySelector('.modal-title').textContent = 'Edit delivery address'; modalEl.querySelector('[data-address-save]').textContent = 'Update Address';
            bootstrap.Modal.getOrCreateInstance(modalEl).show(); setTimeout(initAddressMap, 200);
        }
        if (event.target.closest('[data-freshful-use-location]')) {
            if (!navigator.geolocation) { const status = document.getElementById('freshful-address-map-status'); if (status) status.textContent = 'Location access is not available in this browser.'; return; }
            const status = document.getElementById('freshful-address-map-status'); if (status) status.textContent = 'Getting your current location…';
            navigator.geolocation.getCurrentPosition(pos => { if (!addressMap) return; const p = { lat: pos.coords.latitude, lng: pos.coords.longitude }; addressMap.setCenter(p); addressMap.setZoom(17); addressMarker.setPosition(p); fillFromPin(p); }, () => { if (status) status.textContent = 'Location access was blocked. Search or pin the map instead.'; });
        }
    });
    document.addEventListener('shown.bs.modal', function (event) { if (event.target.querySelector('#freshful-address-map')) setTimeout(initAddressMap, 150); });
})();
</script>

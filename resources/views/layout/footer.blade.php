<footer id="footer" class="footer mt-5">
    <div class="container-fluid container-xl">
        <div class="row row-cols-lg-4 row-cols-md-4 row-cols-1">

            <div class="col-lg-4 col-md-4 col-sm-12 col-12">
                <div class="mb-2">
                    <img src="{{ asset('assets/image/logo.png') }}" alt="Logo" class="img-fluid logo-footer">
                </div>
                <div style="max-width: 80%;">
                    <h6 class="fw-bolder mb-2 fs-6">FRESHFUL DIGITAL FOODS (OPC) PRIVATE LIMITED</h6>
                    <p class="text-left fs-7 lh-1">Freshful means ‘freshness’. We measure ourselves in terms of the customer delight delivered, staying true to our values of quality, compassion, and innovation .</p>
                </div>
            </div>
            <div class="col-lg-8 col-md-8 col-sm-12 col-12 mt-5">
                <div class="row row-cols-lg-3 row-cols-md-3 row-cols-1">
                    <div class="col ps-lg-4">
                        <div class="mb-2">
                            <h6 class="fw-bolder fs-6 mb-2">Navigation</h6>
                            <ul class="list-unstyled">
                                <li class="mb-1"><a class="fs-7" href="{{ url('/') }}">Home</a></li>
                                <li class="mb-1"><a class="fs-7" href="{{ url('/why-freshful') }}">Why us?</a></li>
                                <li class="mb-1"><a class="fs-7" href="{{ route('certificate') }}">Certificate</a></li>
                                <li class="mb-1"><a class="fs-7" href="{{ url('/franchisee') }}">Franchise</a></li>
                                <li class="mb-1"><a class="fs-7" href="{{ url('/about') }}">About us</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col">
                        <h6 class="fw-bolder fs-6 mb-2">Policies</h6>
                        <ul class="list-unstyled">
                            <li class="mb-1"><a class="fs-7" href="{{ url('/privacypolicy') }}">Privacy policy</a></li>
                            <li class="mb-1"><a class="fs-7" href="{{ url('/shipping-policy') }}">Shipping policy</a></li>
                            <li class="mb-1"><a class="fs-7" href="{{ url('/terms') }}">Terms & Condition</a></li>
                            <li class="mb-1"><a class="fs-7" href="{{ url('/refund_policy') }}">Refund Policy</a></li>
                            <li class="mb-1"><a class="fs-7" href="{{ url('/faq') }}">FAQs</a></li>
                        </ul>
                    </div>
                    <div class="col">
                        <h6 class="fw-bolder fs-6 mb-3">Follow us on social media</h6>
                        <div class="d-flex gap-lg-2 gap-1">
                            <a href="{{ url('/') }}" class="btn btn-outline-primary rounded-1"><i class="bi bi-facebook fs-5"></i></a>
                            <a href="{{ url('/') }}" class="btn btn-outline-primary rounded-1"><i class="bi bi-twitter fs-5"></i></a>
                            <a href="{{ url('/') }}" class="btn btn-outline-primary rounded-1"><i class="bi bi-instagram fs-5"></i></a>
                            <a href="{{ url('/') }}" class="btn btn-outline-primary rounded-1"><i class="bi bi-youtube fs-5"></i></a>
                        </div>
                        <h6 class="fw-bolder fs-6 mt-3 mb-1">Service center helpline time</h6>
                        <p class="fs-7">Sat-Friday, 10:00 AM-9:00 PM</p>
                    </div>

                </div>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-12">
                <p class="text-end fs-8 text-muted mb-0">© {{ date('Y') }} Freshful Digital Foods (OPC) Private Limited, Copyright, All rights reserved.</p>
            </div>
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
    document.addEventListener("DOMContentLoaded", function() {
        const button = document.getElementById("open-location");
        const collapseTarget = document.getElementById("location-dropdown");

        const bsCollapse = new bootstrap.Collapse(collapseTarget, {
            toggle: false // prevent auto toggle on init
        });

        button.addEventListener("click", function() {
            bsCollapse.toggle();
        });
    });
</script>
<script>
    document.getElementById("sendOtpBtn").addEventListener("click", function() {
        console.log("Send OTP button clicked");
        const phone = document.getElementById("phoneLogin").value;
            fetch("{{ route('send-otp') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    phone: phone
                })
            })
            .then(response => response.json())
            .then(data => {         
                if(data.status === "success"){
                    toastr.success(data.message);
                }else{
                    toastr.error(data.message);
                }
            })
            .catch(error=>{
                toastr.error("Something went wrong");
            });

    });
</script>
<script>
    const buyer_id = {{ Auth::user()->id ?? 0 }};
    const getcartUrl = "{{ route('get-cart') }}";
    function loadCart() {
        fetch(getcartUrl, { credentials: 'same-origin' })
            .then(res => res.json())
            .then(data => {
                if (data.result && data.products && data.products.length > 0) {
                    renderCart(data.products);
                } else {
                    document.getElementById('cart-items').innerHTML = `
                        <div class="text-center py-4">
                            <img src="{{ asset('uploads/empty-cart.png') }}" alt="Empty cart" style="max-width:180px;opacity:0.8;">
                            <p class="text-muted mt-2 fs-7">Your cart is empty</p>
                        </div>`;
                    updateCartBadge(0);
                    updateBill(0);
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

        products.forEach((item, index) => {
            let pid = item.cart_pid;
            let price = parseFloat(item.main_price) || 0;
            let qty = parseInt(item.quantity);
            let total = price * qty;
            let unit = ((item.unit_quantity ?? '') + ' ' + (item.product_unit ?? '')).trim();

            subtotal += total;

            html += `
            <div class="d-flex align-items-center justify-content-between border-bottom mb-3 pb-3" id="cart_item_${pid}">
                <div class="flex-grow-1 me-2">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="bg-accent p-1 d-flex align-items-center justify-content-center"
                            style="border-radius: 5px; width: 25px;height: 25px;">
                            ${index + 1}
                        </span>
                        <h6 class="mb-0 fs-7">${item.title}</h6>
                        <button class="btn btn-sm p-0 ms-auto text-muted" onclick="updateQty(${pid}, 0)" title="Remove">&times;</button>
                    </div>
                    <span class="badge bg-light text-dark border">${unit}</span>
                    <span class="text-danger fw-bold ms-2">₹${price}</span>
                </div>

                <div class="d-flex align-items-center gap-1">
                    <button class="btn btn-sm btn-outline-secondary qty-btn" onclick="updateQty(${pid}, ${qty - 1})">−</button>
                    <span class="px-2">${qty}</span>
                    <button class="btn btn-sm btn-outline-secondary qty-btn" onclick="updateQty(${pid}, ${qty + 1})">+</button>
                </div>
            </div>
            `;
        });

        if (products.length === 0) {
            html = '<div class="text-center py-4"><p class="text-muted">Your cart is empty</p></div>';
        }

        document.getElementById('cart-items').innerHTML = html;
        updateCartBadge(products.length);
        updateBill(subtotal);
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

        document.getElementById('subtotal').innerText = `₹${subtotal}`;
        document.getElementById('delivery').innerText = `₹${delivery}`;
        document.getElementById('discount').innerText = `₹${discount}`;
        document.getElementById('total').innerText = `₹${total}`;
        document.getElementById('footer-total').innerText = `₹${total}`;
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
            bootstrap.Offcanvas.getOrCreateInstance(loginEl).show();
        }, { once: true });
        cartOffcanvas.hide();
    } else {
        bootstrap.Offcanvas.getOrCreateInstance(loginEl).show();
    }
}

function addToCart(product_id, quantity = 1) {
    updateCartHttp({ product_id, buyer_id, quantity })
        .then(data => {
            if (data.result) {
                updateCartBadge(data.cart_count);
                loadCart();
                notify(data.message || 'Added to cart', 'success');
            } else {
                notify(data.message || 'Failed to add to cart', 'error');
            }
        })
        .catch(() => notify('Network error while adding to cart', 'error'));
}
</script>
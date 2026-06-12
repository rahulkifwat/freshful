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
                                <li class="mb-1"><a class="fs-7" href="{{ url('/') }}">Certificate</a></li>
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

<!-- Main JS File -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<!-- <script src="{{ url('assets/js/main.js')}}"></script> -->
<script src="{{ url('assets/js/slider.js')}}"></script>
<script>
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "4000",
        "extendedTimeOut": "1000"
    };
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
    const buyer_id = {{ Auth::user()->id  ?? 0}};

    function loadCart() {
        fetch(`/get-cart?buyer_id=${buyer_id}`)
            .then(res => res.json())
            .then(data => {
                if (data.result) {
                    renderCart(data.products);
                } else {
                    document.getElementById('cart-items').innerHTML = "<p>No items in cart</p>";
                }
            });
    }
</script>
<script>
    function renderCart(products) {
        let html = '';
        let subtotal = 0;

        products.forEach((item, index) => {
            let price = item.main_price;
            let qty = parseInt(item.quantity);
            let total = price * qty;

            subtotal += total;

            html += `
            <div class="d-flex align-items-center justify-content-between border-bottom mb-3 pb-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="bg-accent p-1 d-flex align-items-center justify-content-center"
                            style="border-radius: 5px; width: 25px;height: 25px;">
                            ${index + 1}
                        </span>
                        <h6 class="mb-0">${item.title}</h6>
                    </div>
                    <span class="badge bg-light text-dark border">${item.unit ?? ''}</span>
                    <span class="text-danger fw-bold ms-2">₹${price}</span>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-red qty-btn" onclick="updateQty('${item.id}', ${qty - 1})">−</button>
                    <span>${qty}</span>
                    <button class="btn btn-red qty-btn" onclick="updateQty('${item.id}', ${qty + 1})">+</button>
                </div>
            </div>
            `;
        });

        document.getElementById('cart-items').innerHTML = html;

        updateBill(subtotal);
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

function updateCartHttp(payload) {
    return fetch('/add-cart', {
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

    if (!buyer_id) {
        toastr.warning('Please login to update cart items');
        return;
    }

    updateCartHttp({ product_id, buyer_id, quantity })
        .then(data => {
            if (data.result) {
                loadCart();
            } else {
                toastr.error(data.message || 'Unable to update quantity');
            }
        })
        .catch(err => {
            console.error('Update quantity error:', err);
            toastr.error('Network error while updating cart');
        });
}

function addToCart(product_id, quantity = 1) {
    if (!buyer_id) {
        var loginCanvas = document.getElementById('loginoffcanvas');
        if (loginCanvas) {
            new bootstrap.Offcanvas(loginCanvas).show();
        } else {
            toastr.warning('Please login to add items to cart');
        }
        return;
    }

    updateCartHttp({ product_id, buyer_id, quantity })
        .then(data => {
            if (data.result) {
                console.log('Cart Updated:', data);
                if (typeof updateCartBadge === 'function') {
                    updateCartBadge(data.cart_count);
                }
                loadCart();
                toastr.success(data.message || 'Added to cart');
            } else {
                toastr.error(data.message || 'Failed to add cart item');
            }
        })
        .catch(err => {
            console.error('Add to cart error:', err);
            toastr.error('Network error while adding to cart');
        });
}
</script>
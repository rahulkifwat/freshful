@extends('layout.master')
@section('content')
@php
    $orderCollection = collect($orders ?? []);
    $orderCount = $orderCollection->count();
    $deliveredCount = $orderCollection->filter(fn ($order) => str_contains(strtolower((string) $order->order_status), 'delivered'))->count();
    $activeCount = $orderCollection->filter(fn ($order) => !str_contains(strtolower((string) $order->order_status), 'delivered') && !str_contains(strtolower((string) $order->order_status), 'cancel'))->count();
    $lifetimeSpend = $orderCollection->sum(fn ($order) => (float) ($order->total_amount ?? 0));
    $accountName = trim((string) ($buyer->name ?? 'Freshful customer'));
    $initials = collect(explode(' ', $accountName))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    $referralShareUrl = !empty($referralCode) ? url('/refer_earn?code=' . urlencode($referralCode)) : url('/refer_earn');
    $statusClass = fn ($status) => str_contains(strtolower((string) $status), 'deliver') ? 'success' : (str_contains(strtolower((string) $status), 'cancel') ? 'danger' : (str_contains(strtolower((string) $status), 'pending') ? 'warning' : 'primary'));
@endphp
<main class="ff-account py-4 py-lg-5">
    <div class="container">
        <header class="ff-account-hero mb-4">
            <div class="ff-account-avatar">{{ $initials ?: 'F' }}</div>
            <div class="flex-grow-1">
                <div class="small text-white-50">YOUR FRESHFUL ACCOUNT</div>
                <h1 class="h3 mb-1">Hello, {{ $accountName }}</h1>
                <p class="mb-0 text-white-75">Manage orders, wallet, addresses and your profile in one place.</p>
            </div>
            <a href="{{ route('view_product', ['category' => 'all']) }}" class="btn btn-light rounded-pill px-4 fw-semibold d-none d-md-inline-flex">Continue shopping <i class="bi bi-arrow-right ms-2"></i></a>
        </header>

        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger">Please check the highlighted information and try again.</div>@endif

        <div class="row g-4 align-items-start">
            <aside class="col-lg-3">
                <div class="ff-account-sidebar">
                    <div class="ff-account-side-profile mb-3">
                        <div class="ff-account-avatar ff-account-avatar-sm">{{ $initials ?: 'F' }}</div>
                        <div class="min-w-0"><div class="fw-bold text-truncate">{{ $accountName }}</div><div class="small text-muted text-truncate">{{ $buyer->phone ?? $buyer->email ?? '' }}</div></div>
                    </div>
                    <nav class="nav nav-pills flex-column gap-1" id="ff-account-tabs" role="tablist" aria-label="Account sections">
                        <button class="nav-link active" id="ff-tab-overview" data-bs-toggle="pill" data-bs-target="#ff-pane-overview" type="button" role="tab"><i class="bi bi-grid-1x2"></i> Overview</button>
                        <button class="nav-link" id="ff-tab-orders" data-bs-toggle="pill" data-bs-target="#ff-pane-orders" type="button" role="tab"><i class="bi bi-bag-check"></i> Order history <span class="ff-nav-count">{{ $orderCount }}</span></button>
                        <button class="nav-link" id="ff-tab-wallet" data-bs-toggle="pill" data-bs-target="#ff-pane-wallet" type="button" role="tab"><i class="bi bi-wallet2"></i> My wallet</button>
                        <button class="nav-link" id="ff-tab-addresses" data-bs-toggle="pill" data-bs-target="#ff-pane-addresses" type="button" role="tab"><i class="bi bi-geo-alt"></i> My addresses <span class="ff-nav-count">{{ count($addresses ?? []) }}</span></button>
                        <button class="nav-link" id="ff-tab-profile" data-bs-toggle="pill" data-bs-target="#ff-pane-profile" type="button" role="tab"><i class="bi bi-person"></i> Profile details</button>
                        <a class="nav-link" href="{{ url('/refer_earn') }}"><i class="bi bi-gift"></i> Refer &amp; earn</a>
                        <a class="nav-link ff-logout-link" href="{{ route('user.logout') }}"><i class="bi bi-box-arrow-right"></i> Sign out</a>
                    </nav>
                </div>
            </aside>

            <section class="col-lg-9">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="ff-pane-overview" role="tabpanel" aria-labelledby="ff-tab-overview" tabindex="0">
                        <div class="ff-account-section-heading mb-3"><div><span class="ff-eyebrow">ACCOUNT OVERVIEW</span><h2 class="h4 mb-0">Your dashboard</h2></div><span class="ff-total-pill">Lifetime spend ₹{{ number_format((float) $lifetimeSpend, 2) }}</span></div>
                        <div class="row g-3 mb-4">
                            <div class="col-6 col-xl-3"><div class="ff-stat-card"><span class="ff-stat-icon orange"><i class="bi bi-bag"></i></span><span class="ff-stat-label">Total orders</span><strong>{{ $orderCount }}</strong><a href="#ff-pane-orders" data-account-open="ff-tab-orders">View orders <i class="bi bi-arrow-right"></i></a></div></div>
                            <div class="col-6 col-xl-3"><div class="ff-stat-card"><span class="ff-stat-icon blue"><i class="bi bi-truck"></i></span><span class="ff-stat-label">In progress</span><strong>{{ $activeCount }}</strong><span class="ff-stat-foot">Orders on their way</span></div></div>
                            <div class="col-6 col-xl-3"><div class="ff-stat-card"><span class="ff-stat-icon green"><i class="bi bi-check2-circle"></i></span><span class="ff-stat-label">Delivered</span><strong>{{ $deliveredCount }}</strong><span class="ff-stat-foot">Completed orders</span></div></div>
                            <div class="col-6 col-xl-3"><div class="ff-stat-card"><span class="ff-stat-icon purple"><i class="bi bi-wallet2"></i></span><span class="ff-stat-label">Wallet balance</span><strong>₹{{ number_format((float) ($buyer->wallet_amount ?? 0), 2) }}</strong><a href="#ff-pane-wallet" data-account-open="ff-tab-wallet">Wallet details <i class="bi bi-arrow-right"></i></a></div></div>
                        </div>

                        <div class="ff-panel mb-4">
                            <div class="ff-panel-head"><div><span class="ff-eyebrow">LATEST ACTIVITY</span><h3 class="h5 mb-0">Recent orders</h3></div><button class="btn btn-sm btn-outline-success rounded-pill px-3" type="button" data-account-open="ff-tab-orders">See all</button></div>
                            @forelse($orderCollection->take(4) as $order)
                                <article class="ff-order-row">
                                    <div class="ff-order-icon"><i class="bi bi-bag-check"></i></div>
                                    <div class="flex-grow-1 min-w-0"><div class="fw-bold">Order #{{ $order->order_id }}</div><div class="small text-muted">{{ $order->date_added ? \Illuminate\Support\Carbon::parse($order->date_added)->format('d M Y, h:i A') : 'Date unavailable' }} · {{ $order->order_count }} item(s)</div></div>
                                    <span class="badge rounded-pill text-bg-{{ $statusClass($order->order_status) }}">{{ $order->order_status }}</span>
                                    <strong class="ms-2">₹{{ number_format((float) ($order->total_amount ?? 0), 2) }}</strong>
                                </article>
                            @empty
                                <div class="ff-empty-state"><span><i class="bi bi-basket"></i></span><h4>No orders yet</h4><p>Your order updates will appear here after your first purchase.</p><a class="btn btn-success rounded-pill px-4" href="{{ route('view_product', ['category' => 'all']) }}">Explore products</a></div>
                            @endforelse
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6"><div class="ff-shortcut-card"><span class="ff-shortcut-icon"><i class="bi bi-geo-alt"></i></span><div><h3 class="h6 mb-1">Delivery addresses</h3><p class="small text-muted mb-2">{{ count($addresses ?? []) }} saved address(es)</p><button type="button" class="btn btn-link p-0 text-success fw-semibold" data-account-open="ff-tab-addresses">Manage addresses <i class="bi bi-arrow-right"></i></button></div></div></div>
                            <div class="col-md-6"><div class="ff-shortcut-card"><span class="ff-shortcut-icon gift"><i class="bi bi-gift"></i></span><div><h3 class="h6 mb-1">Refer a friend</h3><p class="small text-muted mb-2">Share Freshful with friends and explore available rewards.</p><a href="{{ url('/refer_earn') }}" class="text-success fw-semibold text-decoration-none">View referral details <i class="bi bi-arrow-right"></i></a></div></div></div>
                        </div>
                        <div class="ff-referral-card"><div><span class="ff-eyebrow">YOUR INVITE CODE</span><h3 class="h6 fw-bold mb-1">Share Freshful with a friend</h3><p class="small text-muted mb-0">@if(!empty($referralCode))Share your existing referral code with friends.@elseYour personal invite code is being prepared. Refresh this page in a moment.@endif</p></div>@if(!empty($referralCode))<div class="ff-referral-code"><code id="ff-referral-code">{{ $referralCode }}</code><button type="button" class="btn btn-sm btn-success rounded-pill" data-copy-referral>Copy</button><a class="btn btn-sm btn-outline-success rounded-pill" target="_blank" rel="noopener" href="https://wa.me/?text={{ rawurlencode('Join Freshful with my invite code ' . $referralCode . ' and get fresh groceries: ' . $referralShareUrl) }}">Share</a></div>@else<a href="{{ url('/refer_earn') }}" class="btn btn-outline-success rounded-pill px-3">Referral details</a>@endif</div>
                    </div>

                    <div class="tab-pane fade" id="ff-pane-orders" role="tabpanel" aria-labelledby="ff-tab-orders" tabindex="0">
                        <div class="ff-account-section-heading mb-3"><div><span class="ff-eyebrow">PURCHASES</span><h2 class="h4 mb-0">Order history</h2></div><span class="ff-total-pill">{{ $orderCount }} order(s)</span></div>
                        <div class="ff-panel">
                            @forelse($orderCollection as $order)
                                <article class="ff-order-card">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3"><div><span class="small text-muted">ORDER NUMBER</span><h3 class="h6 fw-bold mb-0">#{{ $order->order_id }}</h3></div><span class="badge rounded-pill text-bg-{{ $statusClass($order->order_status) }}">{{ $order->order_status }}</span></div>
                                    <div class="row g-3 small text-muted"><div class="col-6 col-md-3"><span class="d-block text-uppercase ff-mini-label">Placed</span>{{ $order->date_added ? \Illuminate\Support\Carbon::parse($order->date_added)->format('d M Y') : '—' }}</div><div class="col-6 col-md-3"><span class="d-block text-uppercase ff-mini-label">Items</span>{{ $order->order_count }} item(s)</div><div class="col-6 col-md-3"><span class="d-block text-uppercase ff-mini-label">Delivery</span>{{ $order->delivery_type === 'Express' ? 'Express delivery' : ($order->schedule_time ?: 'Scheduled') }}</div><div class="col-6 col-md-3"><span class="d-block text-uppercase ff-mini-label">Order total</span><strong class="text-dark">₹{{ number_format((float) ($order->total_amount ?? 0), 2) }}</strong></div></div>
                                    <div class="ff-order-actions"><a href="{{ route('track-order', $order->order_id) }}" class="btn btn-success btn-sm rounded-pill px-3">View details</a>@if(!str_contains(strtolower((string) $order->order_status), 'cancel') && !str_contains(strtolower((string) $order->order_status), 'deliver'))<a href="{{ route('cancel-order', $order->order_id) }}" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="return confirm('Do you want to cancel this order?')">Cancel order</a>@endif</div>
                                </article>
                            @empty
                                <div class="ff-empty-state"><span><i class="bi bi-bag"></i></span><h4>No orders found</h4><p>When you place an order, it will appear in your history.</p><a class="btn btn-success rounded-pill px-4" href="{{ route('view_product', ['category' => 'all']) }}">Start shopping</a></div>
                            @endforelse
                        </div>
                    </div>

                    <div class="tab-pane fade" id="ff-pane-wallet" role="tabpanel" aria-labelledby="ff-tab-wallet" tabindex="0">
                        <div class="ff-account-section-heading mb-3"><div><span class="ff-eyebrow">FRESHFUL WALLET</span><h2 class="h4 mb-0">Wallet &amp; transactions</h2></div></div>
                        <div class="ff-wallet-card mb-4"><div><span class="small text-white-75">AVAILABLE BALANCE</span><div class="ff-wallet-balance" data-wallet-balance>₹{{ number_format((float) ($buyer->wallet_amount ?? 0), 2) }}</div><p class="mb-0 text-white-75">Use your balance on your next Freshful order.</p><button type="button" class="btn btn-light text-success fw-bold rounded-pill mt-3 px-4" data-bs-toggle="modal" data-bs-target="#ffWalletTopupModal"><i class="bi bi-plus-lg me-1"></i>Add money</button></div><span class="ff-wallet-emblem"><i class="bi bi-wallet2"></i></span></div>
                        <div class="ff-panel"><div class="ff-panel-head"><div><span class="ff-eyebrow">ACCOUNT ACTIVITY</span><h3 class="h5 mb-0">Recent wallet transactions</h3></div></div>
                            @forelse(($walletTransactions ?? collect()) as $transaction)
                                @php $transactionType = (string) data_get($transaction, 'event_type', data_get($transaction, 'transaction_type', data_get($transaction, 'type', 'Transaction'))); $isCredit = in_array(strtolower($transactionType), ['topup', 'referral_reward'], true) || str_contains(strtolower($transactionType), 'credit') || str_contains(strtolower($transactionType), 'add') || str_contains(strtolower($transactionType), 'refund'); @endphp
                                <article class="ff-wallet-row"><span class="ff-wallet-row-icon {{ $isCredit ? 'is-credit' : 'is-debit' }}"><i class="bi {{ $isCredit ? 'bi-arrow-down-left' : 'bi-arrow-up-right' }}"></i></span><div class="flex-grow-1"><div class="fw-semibold">{{ data_get($transaction, 'description', data_get($transaction, 'note', data_get($transaction, 'remarks', data_get($transaction, 'details', ucfirst($transactionType))))) }}</div><div class="small text-muted">{{ (data_get($transaction, 'created_at') ?? data_get($transaction, 'date_added') ?? data_get($transaction, 'transaction_date')) ? \Illuminate\Support\Carbon::parse(data_get($transaction, 'created_at') ?? data_get($transaction, 'date_added') ?? data_get($transaction, 'transaction_date'))->format('d M Y, h:i A') : 'Transaction date unavailable' }}</div></div><strong class="{{ $isCredit ? 'text-success' : 'text-dark' }}">{{ $isCredit ? '+' : '−' }}₹{{ number_format(abs((float) data_get($transaction, 'amount', 0)), 2) }}</strong></article>
                            @empty
                                <div class="ff-empty-state ff-empty-compact"><span><i class="bi bi-receipt"></i></span><h4>No transaction details available</h4><p>Your wallet balance is retained from your existing account. Transaction history will appear here when available.</p></div>
                            @endforelse
                        </div>
                    </div>

                    <div class="tab-pane fade" id="ff-pane-addresses" role="tabpanel" aria-labelledby="ff-tab-addresses" tabindex="0">
                        <div class="ff-account-section-heading mb-3"><div><span class="ff-eyebrow">DELIVERY DETAILS</span><h2 class="h4 mb-0">My addresses</h2></div><button type="button" class="btn btn-success rounded-pill px-3" data-freshful-add-address data-bs-toggle="modal" data-bs-target="#addAddressModal"><i class="bi bi-plus-lg me-1"></i>Add address</button></div>
                        <div class="row g-3">
                            @forelse($addresses as $address)
                                <div class="col-md-6"><article class="ff-address-card h-100"><div class="d-flex justify-content-between align-items-start gap-2"><span class="badge rounded-pill text-bg-light border text-success">{{ ucfirst($address->type) }}</span><div class="d-flex gap-3"><button type="button" class="btn btn-link btn-sm p-0 text-success freshful-edit-address" data-address-id="{{ $address->id }}" data-address-name="{{ $address->name }}" data-address-phone="{{ $address->phone }}" data-address-locality="{{ $address->locality ?? '' }}" data-address-street-name="{{ $address->street_name }}" data-address-landmark="{{ $address->landmark ?? '' }}" data-address-city="{{ $address->city }}" data-address-type="{{ $address->type }}" data-address-latitude="{{ $address->latitude ?? '' }}" data-address-longitude="{{ $address->longitude ?? '' }}">Edit</button><a href="{{ route('deleteAddress', $address->id) }}" class="btn btn-link btn-sm p-0 text-danger" onclick="return confirm('Delete this saved address?')">Delete</a></div></div><h3 class="h6 fw-bold mt-3 mb-1">{{ $address->name }}</h3><p class="small text-muted mb-2">{{ $address->street_name }}, {{ $address->locality ?? '' }}@if($address->landmark), {{ $address->landmark }}@endif, {{ $address->city }}</p><div class="small text-muted"><i class="bi bi-telephone me-1"></i>{{ $address->phone }}</div></article></div>
                            @empty
                                <div class="col-12"><div class="ff-empty-state"><span><i class="bi bi-geo-alt"></i></span><h4>No saved addresses</h4><p>Add a delivery address so checkout is quicker next time.</p><button type="button" class="btn btn-success rounded-pill px-4" data-freshful-add-address data-bs-toggle="modal" data-bs-target="#addAddressModal">Add your first address</button></div></div>
                            @endforelse
                        </div>
                    </div>

                    <div class="tab-pane fade" id="ff-pane-profile" role="tabpanel" aria-labelledby="ff-tab-profile" tabindex="0">
                        <div class="ff-account-section-heading mb-3"><div><span class="ff-eyebrow">PERSONAL INFORMATION</span><h2 class="h4 mb-0">Profile details</h2></div></div>
                        <div class="ff-panel"><form action="{{ route('updateAccount') }}" method="POST">@csrf<div class="row g-3"><div class="col-md-6"><label class="form-label fw-semibold">Full name</label><input name="name" type="text" class="form-control" value="{{ old('name', $buyer->name ?? '') }}" required></div><div class="col-md-6"><label class="form-label fw-semibold">Email address</label><input name="email" type="email" class="form-control" value="{{ old('email', $buyer->email ?? '') }}" required></div><div class="col-md-6"><label class="form-label fw-semibold">Phone number</label><input name="phone" type="text" class="form-control" value="{{ $buyer->phone ?? '' }}" readonly><div class="form-text">Contact support to change your registered phone number.</div></div><div class="col-md-6"><label class="form-label fw-semibold">Gender</label><select name="gender" class="form-select" required><option value="">Select</option><option value="Male" @selected(old('gender', $buyer->gender ?? '') === 'Male')>Male</option><option value="Female" @selected(old('gender', $buyer->gender ?? '') === 'Female')>Female</option></select></div><div class="col-12"><button class="btn btn-success rounded-pill px-4">Save changes</button></div></div></form></div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</main>

<div class="modal fade" id="addAddressModal" tabindex="-1" aria-labelledby="accountAddressLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl"><div class="modal-content ff-address-modal">
        <div class="modal-header"><div><h5 class="modal-title fw-bold" id="accountAddressLabel">Add delivery address</h5><p class="small text-muted mb-0">Search or pin your location, then complete the address details.</p></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <form action="{{ route('storeAddress') }}" method="POST" data-freshful-address-form>@csrf
            <input type="hidden" name="address_id" value=""><input type="hidden" name="latitude" value=""><input type="hidden" name="longitude" value="">
            <div class="modal-body"><div class="row g-4">
                <div class="col-lg-6"><label class="form-label fw-semibold" for="freshful-address-map-search">Find your location</label><div class="input-group mb-2"><span class="input-group-text bg-white"><i class="bi bi-search"></i></span><input id="freshful-address-map-search" class="form-control" placeholder="Search area, street or landmark" autocomplete="off"></div><div id="freshful-address-map" class="freshful-address-map" role="application" aria-label="Choose delivery location on map"></div><div class="d-flex justify-content-between align-items-center gap-2 mt-2"><small id="freshful-address-map-status" class="text-muted">Click the map to place the pin.</small><button type="button" class="btn btn-sm btn-outline-success" data-freshful-use-location><i class="bi bi-crosshair me-1"></i>Use my location</button></div></div>
                <div class="col-lg-6"><div class="row g-3"><div class="col-12"><label class="form-label fw-semibold">Full Name *</label><input type="text" name="name" class="form-control" required></div><div class="col-md-6"><label class="form-label fw-semibold">Phone *</label><input type="tel" name="phone" class="form-control" required></div><div class="col-md-6"><label class="form-label fw-semibold">Address Type *</label><select name="type" class="form-select" required><option value="Home">Home</option><option value="Work">Work</option><option value="Other">Other</option></select></div><div class="col-12"><label class="form-label fw-semibold">Area / Locality *</label><input type="text" name="locality" class="form-control" required></div><div class="col-12"><label class="form-label fw-semibold">Flat / Building / Street *</label><input type="text" name="street_name" class="form-control" required></div><div class="col-md-6"><label class="form-label fw-semibold">Landmark</label><input type="text" name="landmark" class="form-control"></div><div class="col-md-6"><label class="form-label fw-semibold">City *</label><input type="text" name="city" class="form-control" required></div></div></div>
            </div></div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success fw-bold" data-address-save>Save address</button></div>
        </form>
    </div></div>
</div>


<div class="modal fade" id="ffWalletTopupModal" tabindex="-1" aria-labelledby="ffWalletTopupTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 rounded-4 shadow">
    <div class="modal-header border-0 pb-0"><div><span class="ff-eyebrow">FRESHFUL WALLET</span><h5 class="modal-title fw-bold" id="ffWalletTopupTitle">Add money</h5></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body pt-3"><p class="small text-muted">Choose an amount to add securely. Minimum ₹100, maximum ₹10,000 per top-up.</p>
      <div class="d-flex flex-wrap gap-2 mb-3">@foreach([100, 250, 500, 1000] as $preset)<button type="button" class="btn btn-outline-success rounded-pill" data-wallet-preset="{{ $preset }}">₹{{ $preset }}</button>@endforeach</div>
      <label class="form-label fw-semibold" for="ff-wallet-amount">Amount (₹)</label><input class="form-control form-control-lg" id="ff-wallet-amount" type="number" min="100" max="10000" step="1" value="500" inputmode="numeric">
      <div class="small text-danger mt-2" data-wallet-error role="alert"></div>
      <button type="button" class="btn btn-success w-100 rounded-pill fw-bold mt-3 py-2" data-wallet-pay>Continue to secure payment</button>
      <div class="text-center text-muted small mt-3"><i class="bi bi-shield-check me-1"></i>Payment handled securely by Razorpay</div>
    </div>
  </div></div>
</div>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
(function(){
 const token=document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
 const error=document.querySelector('[data-wallet-error]'), amount=document.getElementById('ff-wallet-amount'), pay=document.querySelector('[data-wallet-pay]');
 document.querySelectorAll('[data-wallet-preset]').forEach(b=>b.addEventListener('click',()=>amount.value=b.dataset.walletPreset));
 if(!pay)return;
 pay.addEventListener('click',async()=>{
  error.textContent=''; const value=Number(amount.value);
  if(!Number.isInteger(value)||value<100||value>10000){error.textContent='Enter an amount from ₹100 to ₹10,000.';return;}
  pay.disabled=true;pay.textContent='Preparing secure payment…';
  try{
   const response=await fetch(@json(route('user.wallet.topup.order')),{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':token},body:JSON.stringify({amount:value})});
   const order=await response.json();if(!response.ok)throw new Error(order.message||'Could not start payment.');
   if(!window.Razorpay)throw new Error('Secure payment window could not load. Please try again.');
   const checkout=new Razorpay({key:order.key,amount:order.amount,currency:order.currency,name:order.name,description:'Wallet top-up',order_id:order.order_id,
    handler:async function(result){
     error.textContent='Verifying payment…';
     try{const verified=await fetch(@json(route('user.wallet.topup.verify')),{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':token},body:JSON.stringify(result)});const data=await verified.json();if(!verified.ok)throw new Error(data.message||'Payment verification is pending. Refresh your wallet shortly.');error.classList.remove('text-danger');error.classList.add('text-success');error.textContent=data.message||'Wallet updated.';setTimeout(()=>location.reload(),800);}
     catch(e){error.classList.add('text-danger');error.textContent=e.message;}
    },modal:{ondismiss:function(){pay.disabled=false;pay.textContent='Continue to secure payment';}},theme:{color:'#21874e'}});
   checkout.on('payment.failed',e=>{error.textContent=e.error?.description||'Payment failed. No wallet credit was made.';});checkout.open();
  }catch(e){error.textContent=e.message;pay.disabled=false;pay.textContent='Continue to secure payment';}
 });
})();
</script>

<style>
.ff-account{background:#f6f8f6;min-height:70vh;color:#27332d}.ff-account-hero{display:flex;align-items:center;gap:18px;padding:24px 28px;border-radius:20px;background:linear-gradient(115deg,#e93018,#fa621e);color:#fff;box-shadow:0 12px 32px rgba(218,58,23,.16)}.ff-account-avatar{width:66px;height:66px;flex:0 0 66px;display:grid;place-items:center;border-radius:50%;background:#fff;color:#e94321;font-size:23px;font-weight:800;border:3px solid rgba(255,255,255,.65)}.ff-account-avatar-sm{width:44px;height:44px;flex:0 0 44px;font-size:15px;border:0;background:#fff1eb}.ff-account-sidebar,.ff-panel,.ff-stat-card,.ff-shortcut-card{border:1px solid #e9eee9;border-radius:16px;background:#fff;box-shadow:0 4px 18px rgba(27,45,34,.045)}.ff-account-sidebar{padding:16px;position:sticky;top:20px}.ff-account-side-profile{display:flex;align-items:center;gap:12px;padding:4px 4px 15px;border-bottom:1px solid #edf0ed}.ff-account .nav-pills .nav-link{display:flex;align-items:center;gap:11px;text-align:left;color:#526057;padding:11px 12px;border-radius:11px;font-weight:600;background:transparent;border:0}.ff-account .nav-pills .nav-link i{font-size:18px;width:22px;text-align:center}.ff-account .nav-pills .nav-link:hover{background:#f6f8f6;color:#e94321}.ff-account .nav-pills .nav-link.active{background:#fff1eb;color:#e94321}.ff-account .ff-logout-link{color:#bd3420!important;margin-top:8px;border-top:1px solid #edf0ed!important;border-radius:0!important;padding-top:16px!important}.ff-nav-count{margin-left:auto;border-radius:20px;background:#f2f4f2;padding:2px 9px;font-size:12px}.ff-account-section-heading,.ff-panel-head{display:flex;align-items:center;justify-content:space-between;gap:12px}.ff-eyebrow{display:block;margin-bottom:4px;color:#87948b;font-size:10px;font-weight:800;letter-spacing:.12em}.ff-stat-card{height:100%;padding:16px;display:flex;flex-direction:column;align-items:flex-start}.ff-stat-icon{width:36px;height:36px;display:grid;place-items:center;border-radius:11px;margin-bottom:12px}.ff-stat-icon.orange{background:#fff0e9;color:#ec5729}.ff-stat-icon.blue{background:#edf6ff;color:#3184cb}.ff-stat-icon.green{background:#eaf8ef;color:#29945a}.ff-stat-icon.purple{background:#f2efff;color:#7554c7}.ff-stat-label{font-size:12px;color:#718078}.ff-stat-card strong{font-size:22px;line-height:1.25;margin:3px 0}.ff-stat-card a,.ff-stat-foot{font-size:11px;color:#718078;text-decoration:none}.ff-stat-card a{color:#24874f;font-weight:600}.ff-panel{padding:20px}.ff-panel-head{padding-bottom:15px;border-bottom:1px solid #edf0ed;margin-bottom:4px}.ff-order-row{display:flex;align-items:center;gap:12px;padding:14px 0;border-bottom:1px solid #f0f2f0}.ff-order-row:last-child,.ff-wallet-row:last-child{border-bottom:0}.ff-order-icon,.ff-shortcut-icon,.ff-wallet-row-icon{width:40px;height:40px;flex:0 0 40px;border-radius:12px;display:grid;place-items:center;background:#fff1eb;color:#eb512a;font-size:18px}.ff-order-row>strong{white-space:nowrap;font-size:13px}.ff-order-row .badge{white-space:nowrap}.ff-shortcut-card{height:100%;padding:16px;display:flex;gap:13px;align-items:flex-start}.ff-shortcut-icon{background:#eef8f1;color:#298853}.ff-shortcut-icon.gift{background:#fff3e9;color:#eb7227}.ff-order-card{padding:17px 0;border-bottom:1px solid #edf0ed}.ff-order-card:last-child{border-bottom:0}.ff-order-actions{display:flex;gap:9px;margin-top:15px}.ff-mini-label{font-size:9px;letter-spacing:.08em;color:#89958d;margin-bottom:3px}.ff-total-pill{padding:7px 12px;background:#eff7f1;color:#27834b;border-radius:25px;font-size:12px;font-weight:700}.ff-wallet-card{display:flex;justify-content:space-between;align-items:center;min-height:170px;padding:24px 28px;color:#fff;border-radius:18px;background:linear-gradient(115deg,#167546,#2aa568);box-shadow:0 12px 32px rgba(28,123,75,.17)}.ff-wallet-balance{font-size:34px;font-weight:800;letter-spacing:-.03em;margin:3px 0}.ff-wallet-emblem{font-size:48px;opacity:.55}.ff-wallet-row{display:flex;align-items:center;gap:12px;padding:14px 0;border-bottom:1px solid #f0f2f0}.ff-wallet-row-icon.is-credit{background:#eaf8ef;color:#258d50}.ff-wallet-row-icon.is-debit{background:#f2f4f2;color:#53645a}.ff-empty-state{text-align:center;padding:35px 12px}.ff-empty-state>span{display:grid;place-items:center;margin:0 auto 12px;width:52px;height:52px;border-radius:50%;background:#f2f7f3;color:#38945d;font-size:23px}.ff-empty-state h4{font-size:16px;font-weight:700}.ff-empty-state p{color:#7a8880;font-size:13px;margin-bottom:15px}.ff-empty-compact{padding:24px 12px}.ff-referral-card{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-top:16px;padding:18px 20px;border:1px solid #f4ddd3;border-radius:15px;background:linear-gradient(110deg,#fff8f4,#fff)}.ff-referral-code{display:flex;align-items:center;gap:10px;padding:7px 8px 7px 14px;border:1px dashed #e5b8a9;border-radius:12px;background:#fff}.ff-referral-code code{color:#d74725;font-weight:800;letter-spacing:.08em}.ff-address-card{padding:17px;border:1px solid #e9eee9;border-radius:15px;background:#fff}.ff-address-modal{overflow:hidden;border:0;border-radius:20px;box-shadow:0 24px 70px rgba(20,30,25,.2)}.freshful-address-map,.ff-address-map{display:block;width:100%;height:330px;min-height:300px;overflow:hidden;border:1px solid #e7ebe6;border-radius:14px;background:#f2f5f0}@media(max-width:991px){.ff-account-sidebar{position:static}.ff-account-sidebar .nav-pills{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr))}.ff-account-sidebar .nav-pills .nav-link{font-size:13px}.ff-account-sidebar .ff-logout-link{margin-top:0;border:0!important;padding-top:11px!important;border-radius:11px!important}}@media(max-width:575px){.ff-referral-card{align-items:flex-start;flex-direction:column}.ff-account-hero{align-items:flex-start;padding:19px;gap:13px}.ff-account-avatar{width:50px;height:50px;flex-basis:50px;font-size:18px}.ff-account-hero h1{font-size:21px}.ff-account-hero p{font-size:12px}.ff-panel{padding:15px}.ff-order-row{flex-wrap:wrap}.ff-order-row>strong{margin-left:auto!important}.ff-account-sidebar .nav-pills{grid-template-columns:1fr 1fr}.ff-wallet-card{padding:20px;min-height:145px}.ff-wallet-balance{font-size:28px}.freshful-address-map,.ff-address-map{height:250px;min-height:250px}.ff-address-modal .modal-body{max-height:75vh;overflow:auto}}
</style>
<script>
document.addEventListener('click', function (event) {
    const copyButton = event.target.closest('[data-copy-referral]');
    if (copyButton) {
        const code = document.getElementById('ff-referral-code')?.textContent?.trim();
        if (!code) return;
        const done = () => { copyButton.textContent = 'Copied'; setTimeout(() => copyButton.textContent = 'Copy', 1600); };
        if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(code).then(done);
        else { const input = document.createElement('textarea'); input.value = code; document.body.appendChild(input); input.select(); document.execCommand('copy'); input.remove(); done(); }
        return;
    }
    const trigger = event.target.closest('[data-account-open]');
    if (!trigger) return;
    event.preventDefault();
    const target = document.getElementById(trigger.dataset.accountOpen);
    if (target && window.bootstrap) bootstrap.Tab.getOrCreateInstance(target).show();
});
</script>
@endsection

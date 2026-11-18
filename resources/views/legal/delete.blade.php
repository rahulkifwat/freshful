@extends('legal.layout')
@section('title','Delete your account')
@section('body')
<h1>Delete your Freshful account</h1>
<p class="muted">Freshful app (Android), by Freshful Digital Foods (OPC) Private Limited.</p>

<h2>Option 1: delete inside the app (instant)</h2>
<ol>
<li>Open the Freshful app and log in.</li>
<li>Go to the Profile tab.</li>
<li>Tap <b>Delete account</b>, read the notice and confirm.</li>
</ol>

<h2>Option 2: request deletion by message</h2>
<p>If you cannot open the app, email <a href="mailto:talktous@freshful.in?subject=Delete%20my%20Freshful%20account">talktous@freshful.in</a> or WhatsApp +91 81242 00242 from your registered mobile number, with the subject "Delete my Freshful account". We verify the request and delete the account within 15 days.</p>

<h2>What is deleted</h2>
<ul>
<li>Your name, email address, password, cart, favourites and saved preferences.</li>
<li>Your push notification tokens and "notify me" requests.</li>
<li>You are logged out on every device, and your mobile number is released so you can register again later.</li>
</ul>

<h2>What we keep, and for how long</h2>
<ul>
<li>Past orders, invoices and the delivery address on each order, for the period Indian tax and accounting law requires (up to 8 years). They are used only for legal, tax and dispute purposes.</li>
<li>Technical logs for up to 90 days.</li>
</ul>

<h2>Before you delete</h2>
<p>An account cannot be deleted while an order is on its way, or while money remains in your Freshful wallet. Complete the order or use the wallet balance first, or contact us and we will help.</p>
<p>Read our <a href="{{ url('/privacy-policy') }}">Privacy Policy</a>.</p>
@endsection

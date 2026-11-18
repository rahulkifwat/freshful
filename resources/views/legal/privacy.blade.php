@extends('legal.layout')
@section('title','Privacy Policy')
@section('body')
<h1>Privacy Policy</h1>
<p class="muted">Effective 6 November 2026. Applies to the Freshful app (Android) and freshful.in.</p>
<p>Freshful Digital Foods (OPC) Private Limited ("Freshful", "we", "us") sells fresh meat, seafood, vegetables, fruit and grocery and delivers them to your door. This policy explains what personal information we collect when you use our app and website, why we collect it, who we share it with, how long we keep it, and the choices you have.</p>

<h2>1. Information we collect</h2>
<ul>
<li><b>Account details:</b> your mobile number (used to log in with a one-time password), and, if you add them, your name and email address.</li>
<li><b>Delivery addresses and location:</b> addresses you save, and the map location you pick for delivery. If you tap "Locate me" or allow location access, the app reads your phone's approximate or precise location only to show whether we deliver to you and to place the pin on the map. We do not track your location in the background.</li>
<li><b>Orders:</b> items bought, delivery time, amounts, coupons used, order status and delivery tracking.</li>
<li><b>Payment information:</b> online payments are handled by our payment partner (Razorpay). We receive the payment status and a payment reference. We do not see or store your full card number, UPI PIN or net-banking password.</li>
<li><b>Notifications:</b> a push notification token for your device, so we can send order updates and "back in stock" alerts you asked for.</li>
<li><b>Support messages:</b> what you send us through Contact us, WhatsApp or email.</li>
<li><b>Device and app information:</b> app version, device type and error reports, used to keep the app working and fix problems.</li>
</ul>

<h2>2. Why we use it</h2>
<ul>
<li>To create your account, verify you by one-time password and keep you logged in.</li>
<li>To take, prepare, deliver and invoice your orders, and to give refunds.</li>
<li>To tell you about your order and to send alerts you requested (for example, when a sold-out item is back).</li>
<li>To answer your questions and resolve complaints.</li>
<li>To prevent fraud and misuse, and to meet legal, tax and accounting obligations.</li>
<li>To improve the app, our range and our delivery service.</li>
</ul>
<p>We do not sell your personal information.</p>

<h2>3. Who we share it with</h2>
<ul>
<li><b>Delivery partners:</b> your name, delivery address and phone number, only for the order they deliver.</li>
<li><b>Service providers</b> working for us: SMS provider (one-time passwords), payment gateway, push notification and app hosting services, and cloud hosting. They may use your information only to provide their service to us.</li>
<li><b>Authorities:</b> when the law requires it, or to protect our rights and the safety of others.</li>
</ul>

<h2>4. How long we keep it</h2>
<ul>
<li><b>Account details, cart and favourites:</b> until you delete your account. When you delete your account they are erased.</li>
<li><b>Orders, invoices and the delivery address on each order:</b> kept for the period Indian tax and accounting law requires (up to 8 years), even after account deletion, and used only for those purposes.</li>
<li><b>Support messages:</b> up to 24 months after the matter is closed.</li>
<li><b>Notification tokens:</b> until you log out, delete the account or the token stops working.</li>
<li><b>Technical logs:</b> up to 90 days.</li>
</ul>

<h2>5. Your choices and rights</h2>
<ul>
<li>See and edit your name, email and addresses in the app under Profile.</li>
<li><b>Delete your account:</b> in the app, go to Profile and choose Delete account. You can also read the steps at <a href="{{ url('/delete-account') }}">{{ url('/delete-account') }}</a>.</li>
<li>Switch off notifications in your phone's settings, and location access at any time.</li>
<li>Ask us to correct or give you a copy of your information by writing to talktous@freshful.in.</li>
</ul>

<h2>6. Security</h2>
<p>We protect data with encrypted connections, access controls and one-time-password login. No system is perfectly secure, so please keep your phone and OTP private.</p>

<h2>7. Children</h2>
<p>Freshful is for adults. We do not knowingly collect information from anyone under 18.</p>

<h2>8. Changes</h2>
<p>If we change this policy, we will update the date above and, for important changes, tell you in the app.</p>

<h2>9. Contact and grievances</h2>
<div class="card">
<p><b>Grievance Officer</b>, Freshful Digital Foods (OPC) Private Limited<br>
Email: <a href="mailto:talktous@freshful.in">talktous@freshful.in</a><br>
Phone / WhatsApp: +91 81242 00242<br>
We reply to privacy requests within 15 days.</p>
</div>
@endsection

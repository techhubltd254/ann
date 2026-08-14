@extends('layouts.app')
@section('title', 'Admin Manual')
@section('content')
<div class="pt-24 max-w-4xl mx-auto px-5 py-10 prose prose-sm max-w-none">
    <h1 class="text-2xl font-black text-gray-900">Administrator Manual</h1>
    <p class="text-gray-500">KICC Digital Economy Platform — Admin Guide v1.0</p>
    <hr class="my-6">
    <h2>1. Accessing the Admin Panel</h2>
    <p>Login at <a href="https://kicctest.org/login">kicctest.org/login</a> with KICC admin credentials. After login, you are redirected to the admin portal.</p>
    <h2>2. KICC Admin Dashboard</h2>
    <p>Navigate to <code>/kicc-admin</code> for the full admin dashboard with tabs: Overview, Sub-Portals, Counties, Exhibitors, National Govt, Orders, Providers, Escrow, Users, Trade Enquiries, Agents, Reviews, Commissions, Coupons.</p>
    <h2>3. County Admin</h2>
    <p>Each county has its own admin at <code>/county-admin/{slug}/pro</code> with tabs for content, images, prices, sectors, marketplace, 4D videos, ads, packages, reports.</p>
    <h2>4. Agent Onboarding</h2>
    <p>Agents register at <code>/agents/register</code>. Admin reviews at <code>/kicc-admin/agents</code> — approve/reject, verify documents, set commission rate.</p>
    <h2>5. Moderation</h2>
    <p><strong>Reviews:</strong> <code>/kicc-admin/reviews</code> — approve/reject user reviews. <strong>Trade Enquiries:</strong> <code>/kicc-admin/trade/enquiries</code> — update status, add admin notes.</p>
    <h2>6. Commissions & Coupons</h2>
    <p><strong>Commissions:</strong> auto-tracked per order at <code>/kicc-admin/commissions</code>. <strong>Coupons:</strong> create at <code>/kicc-admin/coupons</code>.</p>
    <h2>7. Mobile Apps</h2>
    <p>Public app: <code>kicc-mobile/public/</code> — Flutter/Dart. Admin app: <code>kicc-mobile/admin/</code> — Flutter/Dart. Build with <code>flutter build apk</code> or <code>flutter build ios</code>.</p>
    <h2>8. Deployment</h2>
    <p>Git push to <code>main</code> → Laravel Cloud auto-deploys. Droplet at <code>root@167.172.62.234</code> — deploy via <code>rsync</code> + <code>php artisan config:cache</code> + <code>systemctl restart php8.4-fpm</code>.</p>
</div>
@endsection
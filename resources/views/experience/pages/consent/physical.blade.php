@extends('layouts.blank')
@section('title', 'Media Consent Form — KICC')
@section('content')
<style>
@page { margin: 15mm; }
body { font-family: 'Inter', 'Arial', sans-serif; font-size: 11pt; line-height: 1.5; color: #0B0B0B; max-width: 210mm; margin: 0 auto; padding: 20px; }
h1 { font-size: 18pt; font-weight: 800; margin-bottom: 2px; }
h2 { font-size: 13pt; font-weight: 700; margin-top: 20px; margin-bottom: 6px; border-bottom: 1px solid #FFFFFF; padding-bottom: 3px; }
.subtitle { color: #0B0B0B; font-size: 10pt; margin-bottom: 15px; }
p, li { font-size: 10pt; }
ul { padding-left: 18px; }
li { margin-bottom: 2px; }
.section { margin-bottom: 12px; }
.check-item { display: flex; align-items: flex-start; gap: 8px; margin-bottom: 8px; }
.check-box { width: 16px; height: 16px; border: 1.5px solid #0B0B0B; margin-top: 3px; flex-shrink: 0; }
.signature-area { margin-top: 30px; padding-top: 20px; border-top: 1px solid #0B0B0B; }
.signature-row { display: flex; gap: 20px; margin-bottom: 15px; }
.signature-field { flex: 1; }
.signature-field label { display: block; font-size: 9pt; font-weight: 700; margin-bottom: 3px; }
.signature-line { border-bottom: 1px solid #0B0B0B; height: 28px; margin-top: 5px; }
.qr-code { text-align: center; margin: 20px 0; }
.qr-code img { width: 120px; height: 120px; }
.qr-label { font-size: 8pt; color: #0B0B0B; margin-top: 4px; }
.footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #FFFFFF; font-size: 8pt; color: #FFFFFF; text-align: center; }
.page-break { page-break-before: always; }
@media print { .no-print { display: none; } }
</style>
</head>
<body>

<div class="no-print" style="background:#FFCD05;padding:10px;text-align:center;font-weight:bold;margin-bottom:20px;border-radius:8px;">
    ⚠️ Print or sign digitally — then file with KICC records.
    <a href="{{ route('consent.show', 'kicc-media-consent') }}" style="text-decoration:underline;margin-left:10px;">Sign Digitally →</a>
</div>

<h1>CONSENT FORM — PHOTOGRAPHY & VIDEOGRAPHY</h1>
<p class="subtitle">KICC National Exhibition Platform · Kenyatta International Convention Centre</p>

<div class="section">
    <h2>1. Purpose of Data Collection</h2>
    <p>Your image, likeness, and voice may be captured through photographs, video recordings, and other media during your participation in KICC events and activities. This data will be used for:</p>
    <ul>
        <li>Exhibition and promotional purposes on the KICC National Exhibition Platform (kicctest.org)</li>
        <li>Social media posts across KICC official channels</li>
        <li>Marketing and advertising materials (brochures, billboards, digital ads)</li>
        <li>Archival records of KICC events and exhibitions</li>
    </ul>
</div>

<div class="section">
    <h2>2. Legal Basis</h2>
    <p>This consent is obtained under the <strong>Kenya Data Protection Act 2019</strong> (Section 30(a) — Consent; Section 44 — Sensitive personal data including biometric data) and the <strong>EU General Data Protection Regulation (GDPR)</strong> (Article 6(1)(a) — Consent; Article 9(2)(a) — Explicit consent for biometric data).</p>
</div>

<div class="section">
    <h2>3. Data Types Collected</h2>
    <ul>
        <li>Photographs (still images)</li>
        <li>Video recordings (moving images with audio)</li>
        <li>Name and identification details</li>
    </ul>
</div>

<div class="section">
    <h2>4. Data Retention</h2>
    <p>Your data will be retained for a period of <strong>10 (ten) years</strong> from the date of consent for archival purposes. After this period, all data will be securely deleted or anonymized.</p>
</div>

<div class="section">
    <h2>5. Your Rights</h2>
    <p>Under the Kenya Data Protection Act 2019, you have the right to: (1) Access, (2) Rectify, (3) Erasure (right to be forgotten), (4) Restrict processing, (5) Data portability, (6) Object to processing, (7) Withdraw consent at any time.</p>
</div>

<div class="section">
    <h2>6. Withdrawal of Consent</h2>
    <p><strong>Email:</strong> dpo@kicc.go.ke &nbsp;|&nbsp; <strong>Phone:</strong> (+254) 20 3261000<br>
    <strong>Address:</strong> KICC, P.O. Box 30746-00100, Nairobi</p>
</div>

<div class="section">
    <h2>7. Data Controller</h2>
    <p><strong>Kenyatta International Convention Centre</strong><br>
    P.O. Box 30746-00100, Nairobi, Kenya &nbsp;|&nbsp; dpo@kicc.go.ke</p>
</div>

<div class="section">
    <h2>8. Complaints</h2>
    <p>Office of the Data Protection Commissioner (ODPC)<br>
    <strong>Email:</strong> complaints@odpc.go.ke &nbsp;|&nbsp; <strong>Website:</strong> www.odpc.go.ke</p>
</div>

{{-- QR Code — links to digital consent form --}}
<div class="qr-code">
    <p style="font-weight:700;margin-bottom:6px;">📱 Scan to sign digitally</p>
    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode(route('consent.show', 'kicc-media-consent')) }}" 
         alt="QR Code for digital consent form"
         style="width:120px;height:120px;border:1px solid #FFFFFF;padding:5px;">
    <p class="qr-label">{{ route('consent.show', 'kicc-media-consent') }}</p>
</div>

{{-- Signature Area --}}
<div class="signature-area">
    <h2>9. Consent Declaration</h2>
    <p style="font-size:10pt;margin-bottom:15px;">By signing below, I confirm that I have read, understood, and freely consent to the processing of my personal data (including biometric data) as described in this form.</p>

    <div class="check-item">
        <span class="check-box"></span>
        <span>I consent to my <strong>photograph</strong> being taken during KICC events.</span>
    </div>
    <div class="check-item">
        <span class="check-box"></span>
        <span>I consent to <strong>video recording</strong> of my participation in KICC events.</span>
    </div>
    <div class="check-item">
        <span class="check-box"></span>
        <span>I consent to my image and video being <strong>published</strong> on the KICC platform, social media, and promotional materials.</span>
    </div>
    <div class="check-item">
        <span class="check-box"></span>
        <span>I understand that my data will be <strong>retained for 10 years</strong> for archival purposes.</span>
    </div>
    <div class="check-item">
        <span class="check-box"></span>
        <span>I have read and understood the terms. I can withdraw consent at any time.</span>
    </div>
    <div class="check-item">
        <span class="check-box"></span>
        <span>(Optional) I consent to my image being <strong>shared with partner organizations</strong> for trade promotion.</span>
    </div>

    <div class="signature-row">
        <div class="signature-field">
            <label>Full Name (print)</label>
            <div class="signature-line"></div>
        </div>
        <div class="signature-field">
            <label>ID / Passport Number</label>
            <div class="signature-line"></div>
        </div>
    </div>
    <div class="signature-row">
        <div class="signature-field">
            <label>Phone Number</label>
            <div class="signature-line"></div>
        </div>
        <div class="signature-field">
            <label>Email Address</label>
            <div class="signature-line"></div>
        </div>
    </div>
    <div class="signature-row">
        <div class="signature-field">
            <label>Signature</label>
            <div class="signature-line"></div>
        </div>
        <div class="signature-field">
            <label>Date</label>
            <div class="signature-line"></div>
        </div>
    </div>
</div>

<div class="footer">
    <p>KICC National Exhibition Platform — Kenya Data Protection Act 2019 Compliant</p>
    <p>KICC, P.O. Box 30746-00100, Nairobi · dpo@kicc.go.ke · (+254) 20 3261000</p>
</div>

</body></html>
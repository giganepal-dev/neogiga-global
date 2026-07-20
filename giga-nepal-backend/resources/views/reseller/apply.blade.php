<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reseller Application · NeoGiga</title>
    <style>
        body{margin:0;font:15px/1.55 ui-sans-serif,system-ui;background:#eef2f7;color:#0f172a}
        .wrap{max-width:680px;margin:0 auto;padding:32px 16px}
        .card{background:#fff;border:1px solid rgba(148,163,184,.18);border-radius:14px;padding:24px}
        .field{display:grid;gap:6px;margin-bottom:14px}.field label{font-weight:600;font-size:.84rem;color:#64748B}
        .control{width:100%;padding:10px 12px;border:1px solid rgba(148,163,184,.18);border-radius:10px;font:inherit}
        .btn{width:100%;padding:11px;border:0;border-radius:10px;background:#f9bd2c;font:inherit;font-weight:700;cursor:pointer}
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Become a NeoGiga Reseller</h1>
        <p style="color:#64748B">Apply for your home regional marketplace. Upload company registration, reseller certificate, and tax documents for manual approval.</p>
        @if ($errors->any())<ul style="color:#991b1b">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>@endif
        <form method="post" action="/reseller/apply" enctype="multipart/form-data">@csrf
            <div class="field"><label>Company name</label><input class="control" name="company_name" required></div>
            <div class="field"><label>Contact person</label><input class="control" name="contact_person" required></div>
            <div class="field"><label>Email</label><input class="control" type="email" name="email" required></div>
            <div class="field"><label>Phone</label><input class="control" name="phone"></div>
            <div class="field"><label>Home marketplace</label>
                <select class="control" name="marketplace_id">
                    <option value="">Auto-detect from current site</option>
                    @foreach($marketplaces as $mp)<option value="{{ $mp->id }}">{{ $mp->name }}</option>@endforeach
                </select>
            </div>
            <div class="field"><label>Company registration</label><input class="control" type="file" name="document_company_reg" required accept=".pdf,.jpg,.jpeg,.png"></div>
            <div class="field"><label>Reseller / distributor certificate</label><input class="control" type="file" name="document_reseller_certificate" required accept=".pdf,.jpg,.jpeg,.png"></div>
            <div class="field"><label>Tax / GST clearance</label><input class="control" type="file" name="document_tax_certificate" required accept=".pdf,.jpg,.jpeg,.png"></div>
            <div class="field"><label>GST info (optional)</label><input class="control" type="file" name="document_gst_info" accept=".pdf,.jpg,.jpeg,.png"></div>
            <div class="field"><label>Territory notes</label><textarea class="control" name="territory_notes" rows="3"></textarea></div>
            <button class="btn" type="submit">Submit application</button>
        </form>
        <p style="margin-top:16px;text-align:center;color:#64748B"><a href="/reseller/login">Already approved? Sign in</a></p>
    </div>
</div>
</body>
</html>

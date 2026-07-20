<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Institutional Application · NeoGiga</title>
    <x-icon-styles/>
    <style>
        :root{--line:rgba(148,163,184,.18);--muted:#64748B;--accent:#f9bd2c}
        *{box-sizing:border-box}body{margin:0;font:15px/1.55 ui-sans-serif,system-ui;background:#eef2f7;color:#0f172a}
        .wrap{max-width:640px;margin:0 auto;padding:32px 16px 48px}
        .card{background:#fff;border:1px solid var(--line);border-radius:14px;padding:24px}
        h1{margin:0 0 6px;font-size:1.35rem}.sub{color:var(--muted);margin:0 0 20px}
        .field{display:grid;gap:6px;margin-bottom:14px}.field label{font-weight:600;font-size:.84rem;color:var(--muted)}
        .control{width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:10px;font:inherit;background:#fff}
        .btn{display:inline-flex;justify-content:center;width:100%;padding:11px;border:0;border-radius:10px;background:var(--accent);font:inherit;font-weight:700;cursor:pointer;color:#231a00}
        .err{background:#fef2f2;color:#991b1b;border:1px solid #fecaca;border-radius:9px;padding:10px 12px;font-size:.86rem;margin-bottom:12px}
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Institutional account application</h1>
        <p class="sub">Apply for government, school, or corporate procurement with official quotations and regional institutional discounts.</p>
        @if ($errors->any())
            <div class="err" role="alert">
                <ul style="margin:0;padding-left:18px">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        @if (session('status'))<div class="err" style="background:#ecfdf5;color:#166534;border-color:#bbf7d0">{{ session('status') }}</div>@endif
        <form method="post" action="/b2b/apply" enctype="multipart/form-data">
            @csrf
            <div class="field"><label for="name">Organization name</label><input id="name" class="control" name="name" value="{{ old('name') }}" required></div>
            <div class="field">
                <label for="type">Institution type</label>
                <select id="type" class="control" name="type" required>
                    @foreach($accountTypes as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', 'corporate') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><label for="email">Official email</label><input id="email" class="control" type="email" name="email" value="{{ old('email') }}" required></div>
            <div class="field"><label for="phone">Phone</label><input id="phone" class="control" name="phone" value="{{ old('phone') }}"></div>
            <div class="field"><label for="pan_vat_number">PAN / VAT / Tax ID</label><input id="pan_vat_number" class="control" name="pan_vat_number" value="{{ old('pan_vat_number') }}"></div>
            <div class="field"><label for="document_company_reg">Company registration (PDF/image)</label><input id="document_company_reg" class="control" type="file" name="document_company_reg" accept=".pdf,.jpg,.jpeg,.png"></div>
            <div class="field"><label for="document_tax_certificate">Tax / GST certificate</label><input id="document_tax_certificate" class="control" type="file" name="document_tax_certificate" accept=".pdf,.jpg,.jpeg,.png"></div>
            <div class="field"><label for="document_institutional_id">Institutional ID (gov letter, school license)</label><input id="document_institutional_id" class="control" type="file" name="document_institutional_id" accept=".pdf,.jpg,.jpeg,.png"></div>
            <button type="submit" class="btn">Submit application</button>
        </form>
        <p class="sub" style="margin-top:16px;text-align:center">Already approved? <a href="/b2b/login">Sign in to the business portal</a></p>
    </div>
</div>
</body>
</html>

@extends('frontend.account.layout')
@section('title','Partner roles — NeoGiga')
@section('account-content')
<header class="account-topbar"><div><h1>Partner roles</h1><p>One login can hold multiple roles after NeoGiga compliance approval.</p></div></header>
<section class="account-panel">
    <div class="account-panel-head"><div><h2>Available account roles</h2><p>Approved roles appear in the “Working as” switcher.</p></div></div>
    <div class="account-role-catalog">@foreach($catalog as $key=>$role)<article class="account-role-option"><strong>{{ $role['label'] }}</strong><span>{{ $role['description'] }}</span></article>@endforeach</div>
</section>
<section class="account-panel">
    <div class="account-panel-head"><div><h2>Your applications</h2><p>Status and compliance review history for this login.</p></div></div>
    @if($applications->isEmpty())
        <div class="account-empty">No partner applications submitted.</div>
    @else
        <div class="account-table-wrap"><table class="account-table">
            <thead><tr><th>Application</th><th>Role</th><th>Company</th><th>Status</th><th>Submitted</th></tr></thead>
            <tbody>
            @foreach($applications as $application)
                <tr>
                    <td class="mono">{{ $application->application_number }}</td>
                    <td>{{ ucwords(str_replace('_',' ',$application->role_key)) }}</td>
                    <td>{{ $application->company_name }}</td>
                    <td><span class="account-badge {{ $application->status }}">{{ str_replace('_',' ',$application->status) }}</span></td>
                    <td>{{ $application->submitted_at ? \Carbon\Carbon::parse($application->submitted_at)->format('d M Y') : 'Draft' }}</td>
                </tr>
                @if($application->status === 'needs_information')
                    <tr><td colspan="5">
                        <form class="account-form" method="post" action="/account/applications/{{ $application->id }}/resubmit" enctype="multipart/form-data">
                            @csrf
                            <div class="account-form-grid">
                                <div class="account-field full"><label>NeoGiga review note</label><p>{{ $application->review_notes ?: 'Additional information is required.' }}</p></div>
                                <div class="account-field full"><label for="applicant_notes_{{ $application->id }}">Your response</label><textarea id="applicant_notes_{{ $application->id }}" name="applicant_notes" required></textarea></div>
                                <div class="account-field full"><label for="documents_{{ $application->id }}">Additional documents</label><input id="documents_{{ $application->id }}" type="file" name="documents[]" accept=".pdf,.png,.jpg,.jpeg,.webp" multiple></div>
                            </div>
                            <button class="account-button" type="submit">Resubmit for review</button>
                        </form>
                    </td></tr>
                @endif
            @endforeach
            </tbody>
        </table></div>
    @endif
</section>
<section class="account-panel">
    <div class="account-panel-head"><div><h2>Apply for a partner role</h2><p>NeoGiga reviews company identity, territory, authorization and supporting documents before activation.</p></div></div>
    <form class="account-form" method="post" action="/account/applications" enctype="multipart/form-data">@csrf<div class="account-form-grid">
        <div class="account-field"><label for="role_key">Requested role</label><select id="role_key" name="role_key" required><option value="">Select role</option>@foreach($catalog as $key=>$role)<option value="{{ $key }}" @selected(old('role_key')===$key)>{{ $role['label'] }}</option>@endforeach</select></div>
        <div class="account-field"><label for="company_name">Company / institution</label><input id="company_name" name="company_name" value="{{ old('company_name') }}" required></div>
        <div class="account-field"><label for="legal_name">Legal name</label><input id="legal_name" name="legal_name" value="{{ old('legal_name') }}"></div>
        <div class="account-field"><label for="contact_phone">Contact phone</label><input id="contact_phone" name="contact_phone" value="{{ old('contact_phone') }}" required></div>
        <div class="account-field"><label for="registration_number">Registration number</label><input id="registration_number" name="registration_number" value="{{ old('registration_number') }}"></div>
        <div class="account-field"><label for="tax_number">Tax / VAT number</label><input id="tax_number" name="tax_number" value="{{ old('tax_number') }}"></div>
        <div class="account-field"><label for="website">Website</label><input id="website" type="url" name="website" value="{{ old('website') }}"></div>
        <div class="account-field"><label for="territory">Requested territory</label><input id="territory" name="territory" value="{{ old('territory') }}"></div>
        <div class="account-field full"><label for="business_description">Business capability and intended use</label><textarea id="business_description" name="business_description" required>{{ old('business_description') }}</textarea></div>
        <div class="account-field full"><label for="documents">Supporting documents (PDF or image, 10 MB each)</label><input id="documents" type="file" name="documents[]" accept=".pdf,.png,.jpg,.jpeg,.webp" multiple></div>
    </div><div><button class="account-button gold" type="submit">Submit for review</button></div></form>
    <p class="account-footnote">Submitting an application does not grant access. NeoGiga activates partner functions only after document and compliance approval.</p>
</section>
@endsection

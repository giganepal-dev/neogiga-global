@extends('pcb.layout')

@section('title', 'Create PCB Project — NeoGiga PCB')
@section('robots', 'noindex,nofollow,noarchive')

@section('content')
<section style="padding:28px 0 64px">
    <div class="wrap">
        <nav class="crumbs"><a href="/en/projects">Projects</a><span>/</span><span>New project</span></nav>
        @if($errors->any())<div class="errors"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <header style="margin-bottom:24px"><div class="eyebrow">Project requirements</div><h1 class="page-title" style="margin:5px 0 6px">Create PCB project</h1><p class="muted" style="max-width:72ch">Define the commercial and engineering context before adding private design files.</p></header>

        <form class="card" method="post" action="/en/projects">
            @csrf
            <div class="card-body">
                <div class="form-grid">
                    <div class="field full"><label for="name">Project name</label><input class="control" id="name" name="name" value="{{ old('name') }}" placeholder="Industrial sensor controller rev A" required></div>
                    <div class="field full"><label for="description">Project brief <span class="hint">— Board purpose, environment, interfaces, constraints</span></label><textarea class="control" id="description" name="description" placeholder="Describe the board purpose, operating environment, interfaces, and expected lifecycle.">{{ old('description') }}</textarea></div>
                    <div class="field"><label for="application_type">Application</label><select class="control" id="application_type" name="application_type"><option value="">Select application</option>@foreach(['IoT','Industrial automation','Robotics','Automotive','Energy storage','Consumer electronics','Medical','Research / education','Other'] as $option)<option @selected(old('application_type')===$option)>{{ $option }}</option>@endforeach</select></div>
                    <div class="field"><label for="project_type">Project type</label><select class="control" id="project_type" name="project_type" required><option value="prototype" @selected(old('project_type','prototype')==='prototype')>Prototype</option><option value="production" @selected(old('project_type')==='production')>Production</option></select></div>
                    <div class="field"><label for="confidentiality">Confidentiality</label><select class="control" id="confidentiality" name="confidentiality" required><option value="internal">Private workspace</option><option value="confidential">Confidential</option><option value="nda_required">NDA required for collaborators</option></select></div>
                    <div class="field"><label for="target_quantity">Target quantity</label><input class="control" id="target_quantity" type="number" name="target_quantity" min="1" max="1000000" value="{{ old('target_quantity',5) }}" required></div>
                    <div class="field"><label for="target_budget">Target budget <span class="hint">— Optional</span></label><input class="control" id="target_budget" type="number" step="0.01" min="0" name="target_budget" value="{{ old('target_budget') }}"></div>
                    <div class="field"><label for="currency">Currency</label><select class="control" id="currency" name="currency" required>@foreach(['USD','NPR','INR','BDT','AUD','GBP','EUR'] as $currency)<option @selected(old('currency','USD')===$currency)>{{ $currency }}</option>@endforeach</select></div>
                    <div class="field"><label for="required_date">Required date <span class="hint">— Optional</span></label><input class="control" id="required_date" type="date" name="required_date" min="{{ now()->addDay()->toDateString() }}" value="{{ old('required_date') }}"></div>
                    <div class="field"><label for="destination_country">Destination country</label><input class="control" id="destination_country" name="destination_country" value="{{ old('destination_country') }}" placeholder="Nepal" required></div>
                    <div class="field"><label for="shipping_postal_code">Postal code <span class="hint">— Optional</span></label><input class="control" id="shipping_postal_code" name="shipping_postal_code" value="{{ old('shipping_postal_code') }}"></div>
                </div>
                <div class="form-actions"><button class="btn btn-primary" type="submit">Create project</button><a class="btn btn-ghost" href="/en/projects">Cancel</a></div>
            </div>
        </form>
    </div>
</section>
@endsection

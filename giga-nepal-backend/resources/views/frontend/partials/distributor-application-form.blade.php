<h2>Join Distributor Network</h2>
<p class="muted">Applications are reviewed manually. Approval is not automatic.</p>
<form class="ng-json-form" data-endpoint="/partner-applications/distributor">
    @csrf
    <input type="hidden" name="source" value="public_distributor_network">
    <div class="form-grid">
        <div><label>Business name</label><input name="business_name" required></div>
        <div><label>Contact person</label><input name="contact_person" required></div>
        <div><label>Email</label><input name="email" type="email" required></div>
        <div><label>Phone</label><input name="phone" required></div>
        <div><label>WhatsApp</label><input name="whatsapp"></div>
        <div><label>Operating scope</label><select name="operating_scope" required><option value="country">Single country / regional</option><option value="global">Global distributor</option></select></div>
        <div><label>Registration country</label><select name="country_id" data-partner-country required><option value="">Detecting your country…</option></select><small class="muted" data-country-note></small></div>
        <div><label>Distributor type</label><select name="distributor_type" required><option value="">Choose</option><option value="country_distributor">Country distributor</option><option value="regional_distributor">Regional distributor</option><option value="city_distributor">City distributor</option><option value="reseller">Reseller</option><option value="service_partner">Service partner</option></select></div>
        <div class="full"><label>Territory interest</label><input name="territory_interest" placeholder="Nepal, Kathmandu, Bihar, Delhi NCR"></div>
        <div class="full"><label>Current business categories</label><input name="current_business_categories_text" placeholder="Electronics, solar, automation, EV parts"></div>
        <div><label>Dealer network?</label><select name="existing_dealer_network"><option value="0">No</option><option value="1">Yes</option></select></div>
        <div><label>Warehouse available?</label><select name="warehouse_available"><option value="0">No</option><option value="1">Yes</option></select></div>
        <div class="full"><label>Monthly capacity</label><input name="monthly_capacity" placeholder="Example: 500 orders or 20 dealers"></div>
        <div class="full"><label>Message</label><textarea name="message"></textarea></div>
    </div>
    <button class="btn btn-primary" type="submit" style="margin-top:14px">Join Distributor Network</button>
    <p class="notice" data-form-notice></p>
</form>
@once
@push('foot')
<script>
async function loadPartnerCountries(form){const select=form.querySelector('[data-partner-country]');if(!select)return;const note=form.querySelector('[data-country-note]');const button=form.querySelector('button[type="submit"]');button.disabled=true;try{const r=await fetch('/partner-country-options',{headers:{Accept:'application/json'}}),j=await r.json();if(!r.ok)throw new Error(j.message||'Country list unavailable');const d=j.data;select.innerHTML='';const countries=d.country_locked&&d.detected_country?[d.detected_country]:d.countries;select.append(new Option(d.country_locked?'Detected country':'Choose an active country',''));countries.forEach(c=>select.append(new Option(c.name+' ('+c.iso_code_2+')',c.id)));if(d.detected_country_id){select.value=String(d.detected_country_id);note.textContent='Detected from your location. This active country is used for registration.'}else{note.textContent='Your location is not an active NeoGiga country. Please choose one.'}button.disabled=false}catch(err){select.innerHTML='<option value="">Country options unavailable</option>';note.textContent=err.message}}
document.querySelectorAll('.ng-json-form').forEach(form=>{loadPartnerCountries(form);form.addEventListener('submit',async e=>{e.preventDefault();const notice=form.querySelector('[data-form-notice]');notice.className='notice';notice.textContent='Submitting...';const fd=new FormData(form), data={};fd.forEach((v,k)=>{data[k]=v});['product_categories','brands_carried','current_business_categories'].forEach(k=>{const raw=data[k+'_text'];if(raw){data[k]=raw.split(',').map(x=>x.trim()).filter(Boolean);delete data[k+'_text']}});['has_physical_store','has_existing_inventory','existing_dealer_network','warehouse_available'].forEach(k=>{if(k in data)data[k]=data[k]==='1'});try{const r=await fetch(form.dataset.endpoint,{method:'POST',headers:{'Content-Type':'application/json',Accept:'application/json'},body:JSON.stringify(data)});const j=await r.json();if(!r.ok)throw new Error(j.message||Object.values(j.errors||{})[0]?.[0]||'Submission failed');notice.className='notice ok';notice.textContent='Application submitted. Status: '+j.data.status+'. NeoGiga team will review it.';form.reset();loadPartnerCountries(form)}catch(err){notice.className='notice err';notice.textContent=err.message}})})
</script>
@endpush
@endonce

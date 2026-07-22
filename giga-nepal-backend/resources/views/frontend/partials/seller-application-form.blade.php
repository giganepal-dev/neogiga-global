<h2>Apply for Seller Early Access</h2>
<p class="muted">Coming Soon / Early Access. This application creates a pending review record only.</p>
<form class="ng-json-form" data-endpoint="/api/seller-applications">
    <input type="hidden" name="source" value="public_sell_on_neogiga">
    <div class="form-grid">
        <div><label>Business name</label><input name="business_name" required></div>
        <div><label>Contact person</label><input name="contact_person" required></div>
        <div><label>Email</label><input name="email" type="email" required></div>
        <div><label>Phone</label><input name="phone" required></div>
        <div><label>WhatsApp</label><input name="whatsapp"></div>
        <div><label>Operating scope</label><select name="operating_scope" required><option value="country">Single country</option><option value="global">Global seller</option></select></div>
        <div><label>Registration country</label><select name="country_id" data-partner-country required><option value="">Detecting your country…</option></select><small class="muted" data-country-note></small></div>
        <div><label>Seller type</label><select name="seller_type" required><option value="">Choose</option><option>manufacturer</option><option>authorized_distributor</option><option>importer</option><option>regional_reseller</option><option>local_electronics_shop</option><option>service_center</option><option>school_lab_supplier</option><option>industrial_supplier</option></select></div>
        <div><label>Business type</label><input name="business_type" placeholder="Company, shop, partnership" required></div>
        <div><label>Monthly order capacity</label><input name="monthly_order_capacity" placeholder="Example: 100 orders"></div>
        <div class="full"><label>Website</label><input name="website" type="url" placeholder="https://example.com"></div>
        <div class="full"><label>Product categories</label><input name="product_categories_text" placeholder="Robotics, IoT, solar, tools"></div>
        <div class="full"><label>Brands carried</label><input name="brands_carried_text" placeholder="Arduino, Espressif, Mean Well"></div>
        <div><label>Physical store?</label><select name="has_physical_store"><option value="0">No</option><option value="1">Yes</option></select></div>
        <div><label>Existing inventory?</label><select name="has_existing_inventory"><option value="0">No</option><option value="1">Yes</option></select></div>
        <div class="full"><label>Message</label><textarea name="message"></textarea></div>
    </div>
    <button class="btn btn-primary" type="submit" style="margin-top:14px">Apply for Seller Early Access</button>
    <a class="btn btn-ghost" href="/distributors" style="margin-top:14px">Join Distributor Network</a>
    <p class="notice" data-form-notice></p>
</form>
@once
@push('foot')
<script>
async function loadPartnerCountries(form){const select=form.querySelector('[data-partner-country]');if(!select)return;const note=form.querySelector('[data-country-note]');const button=form.querySelector('button[type="submit"]');button.disabled=true;try{const r=await fetch('/api/partner-country-options',{headers:{Accept:'application/json'}}),j=await r.json();if(!r.ok)throw new Error(j.message||'Country list unavailable');const d=j.data;select.innerHTML='';const countries=d.country_locked&&d.detected_country?[d.detected_country]:d.countries;select.append(new Option(d.country_locked?'Detected country':'Choose an active country',''));countries.forEach(c=>select.append(new Option(c.name+' ('+c.iso_code_2+')',c.id)));if(d.detected_country_id){select.value=String(d.detected_country_id);note.textContent='Detected from your location. This active country is used for registration.'}else{note.textContent='Your location is not an active NeoGiga country. Please choose one.'}button.disabled=false}catch(err){select.innerHTML='<option value="">Country options unavailable</option>';note.textContent=err.message}}
document.querySelectorAll('.ng-json-form').forEach(form=>{loadPartnerCountries(form);form.addEventListener('submit',async e=>{e.preventDefault();const notice=form.querySelector('[data-form-notice]');notice.className='notice';notice.textContent='Submitting...';const fd=new FormData(form), data={};fd.forEach((v,k)=>{data[k]=v});['product_categories','brands_carried','current_business_categories'].forEach(k=>{const raw=data[k+'_text'];if(raw){data[k]=raw.split(',').map(x=>x.trim()).filter(Boolean);delete data[k+'_text']}});['has_physical_store','has_existing_inventory','existing_dealer_network','warehouse_available'].forEach(k=>{if(k in data)data[k]=data[k]==='1'});try{const r=await fetch(form.dataset.endpoint,{method:'POST',headers:{'Content-Type':'application/json',Accept:'application/json'},body:JSON.stringify(data)});const j=await r.json();if(!r.ok)throw new Error(j.message||Object.values(j.errors||{})[0]?.[0]||'Submission failed');notice.className='notice ok';notice.textContent='Application submitted. Status: '+j.data.status+'. NeoGiga team will review it.';form.reset();loadPartnerCountries(form)}catch(err){notice.className='notice err';notice.textContent=err.message}})})
</script>
@endpush
@endonce

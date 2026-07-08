<h2>Join Distributor Network</h2>
<p class="muted">Applications are reviewed manually. Approval is not automatic.</p>
<form class="ng-json-form" data-endpoint="/api/distributor-applications">
    <input type="hidden" name="source" value="public_distributor_network">
    <div class="form-grid">
        <div><label>Business name</label><input name="business_name" required></div>
        <div><label>Contact person</label><input name="contact_person" required></div>
        <div><label>Email</label><input name="email" type="email" required></div>
        <div><label>Phone</label><input name="phone" required></div>
        <div><label>WhatsApp</label><input name="whatsapp"></div>
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
document.querySelectorAll('.ng-json-form').forEach(form=>{form.addEventListener('submit',async e=>{e.preventDefault();const notice=form.querySelector('[data-form-notice]');notice.className='notice';notice.textContent='Submitting...';const fd=new FormData(form), data={};fd.forEach((v,k)=>{data[k]=v});['product_categories','brands_carried','current_business_categories'].forEach(k=>{const raw=data[k+'_text'];if(raw){data[k]=raw.split(',').map(x=>x.trim()).filter(Boolean);delete data[k+'_text']}});['has_physical_store','has_existing_inventory','existing_dealer_network','warehouse_available'].forEach(k=>{if(k in data)data[k]=data[k]==='1'});try{const r=await fetch(form.dataset.endpoint,{method:'POST',headers:{'Content-Type':'application/json',Accept:'application/json'},body:JSON.stringify(data)});const j=await r.json();if(!r.ok)throw new Error(j.message||'Submission failed');notice.className='notice ok';notice.textContent='Application submitted. Status: '+j.data.status+'. NeoGiga team will review it.';form.reset()}catch(err){notice.className='notice err';notice.textContent=err.message}})})</script>
@endpush
@endonce

<?php
namespace App\Services\B2B;
use App\Models\B2B\B2BQuotation;
class B2BQuotationService {
    public function number(): string { return 'BQT-'.now()->format('YmdHis').'-'.random_int(100,999); }
    public function create(array $data, ?int $userId=null): B2BQuotation {
        $items=$data['items']; unset($data['items']);
        $subtotal=0; $tax=0;
        foreach($items as &$item){ $item['line_total']=round((float)$item['quantity']*(float)$item['unit_price'],4); $subtotal+=$item['line_total']; $tax+=(float)($item['tax_amount']??0); }
        $shipping=(float)($data['shipping_total']??0);
        $quote=B2BQuotation::create([...$data,'quotation_number'=>$this->number(),'status'=>'draft','subtotal'=>$subtotal,'tax_total'=>$tax,'grand_total'=>$subtotal+$tax+$shipping,'created_by'=>$userId,'price_snapshot'=>['items'=>$items,'generated_at'=>now()->toIso8601String()]]);
        foreach($items as $item){ $quote->items()->create($item); }
        return $quote->load('items');
    }
}

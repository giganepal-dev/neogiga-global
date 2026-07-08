<?php
namespace App\Services\B2B;
use App\Models\B2B\B2BQuoteRequest;
class B2BQuoteService {
    public function number(): string { return 'BRFQ-'.now()->format('YmdHis').'-'.random_int(100,999); }
    public function create(array $data, ?int $accountId): B2BQuoteRequest {
        $items=$data['items'];
        unset($data['items']);
        $rfq=B2BQuoteRequest::create([...$data,'b2b_account_id'=>$accountId,'rfq_number'=>$this->number(),'status'=>'open']);
        foreach($items as $item){ $rfq->items()->create($item); }
        return $rfq->load('items');
    }
}

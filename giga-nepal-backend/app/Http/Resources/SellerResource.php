<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'country_id' => $this->country_id,
            'status' => $this->status,
            'type' => $this->type,
            'commerce_status' => $this->commerce_status ?? null,
            'public_profile_enabled' => (bool) ($this->public_profile_enabled ?? false),
            'is_verified' => (bool) ($this->is_verified ?? false),
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosCustomerReward extends Model
{
    protected $table = 'pos_customer_rewards';
    protected $guarded = [];

    public function customer() { return $this->belongsTo(User::class, 'customer_id'); }
    public function rewardSystem() { return $this->belongsTo(PosRewardSystem::class, 'reward_system_id'); }
}

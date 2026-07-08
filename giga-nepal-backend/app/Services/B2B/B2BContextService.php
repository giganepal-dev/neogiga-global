<?php
namespace App\Services\B2B;
use App\Models\B2B\B2BAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
class B2BContextService {
    public function accountFor(User $user): ?B2BAccount {
        if (!Schema::hasTable('b2b_account_users')) return null;
        $row=DB::table('b2b_account_users')->where('user_id',$user->id)->where('is_active',true)->first();
        return $row ? B2BAccount::find($row->b2b_account_id) : null;
    }
    public function abortUnlessAccount(User $user): B2BAccount {
        $account=$this->accountFor($user);
        abort_if(!$account,403,'No B2B account is linked to this user.');
        return $account;
    }
}

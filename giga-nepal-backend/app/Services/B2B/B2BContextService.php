<?php
namespace App\Services\B2B;
use App\Models\B2B\B2BAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class B2BContextService
{
    public function accountFor(User $user): ?B2BAccount
    {
        $link = DB::table('b2b_account_users')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();
        return $link ? B2BAccount::find($link->b2b_account_id) : null;
    }
}

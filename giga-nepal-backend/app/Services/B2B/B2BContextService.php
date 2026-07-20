<?php

namespace App\Services\B2B;

use App\Models\B2B\B2BAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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

    public function abortUnlessAccount(User $user): B2BAccount
    {
        $account = $this->accountFor($user);
        if (! $account) {
            throw new AccessDeniedHttpException('No business account linked.');
        }

        return $account;
    }
}

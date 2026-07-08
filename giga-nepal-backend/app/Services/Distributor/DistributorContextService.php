<?php

namespace App\Services\Distributor;

use App\Models\Distributor\Distributor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DistributorContextService
{
    public function distributorFor(User $user): ?Distributor
    {
        if (! Schema::hasTable('distributors')) {
            return null;
        }

        $direct = Distributor::query()->where('user_id', $user->id)->first();
        if ($direct) {
            return $direct;
        }

        if (! Schema::hasTable('distributor_staff')) {
            return null;
        }

        $staff = DB::table('distributor_staff')->where('user_id', $user->id)->where('is_active', true)->first();

        return $staff ? Distributor::find($staff->distributor_id) : null;
    }

    public function abortUnlessDistributor(User $user): Distributor
    {
        $distributor = $this->distributorFor($user);
        abort_if(! $distributor, 403, 'No distributor account is linked to this user.');

        return $distributor;
    }
}

<?php
namespace App\Models\B2B;
use Illuminate\Database\Eloquent\Model;
class B2BAccountUser extends Model {
    protected $table='b2b_account_users';
    protected $fillable=['b2b_account_id','user_id','name','email','role','permissions','is_active'];
    protected $casts=['permissions'=>'array','is_active'=>'boolean'];
}

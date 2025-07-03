<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static updateOrCreate(array $array, array $array1)
 * @method static where(string $string, $id)
 */
class UserAddress extends Model
{
    protected $table = 'user_addresses';
    protected $fillable = ['user_id', 'monthly_income', 'annual_income_for_company', 'company_telephone_number', 'business_name'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static where(string $string, $id)
 * @method static updateOrCreate(array $array, array $only)
 */
class UserPersonalDetail extends Model
{
    protected $table = 'user_personal_details';
    protected $fillable = ['user_id', 'date_of_birth', 'cpf', 'gender', 'account_type'];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

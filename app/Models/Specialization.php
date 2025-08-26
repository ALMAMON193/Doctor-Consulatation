<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static firstOrCreate(string[] $array)
 * @method static select(string $string, string $string1)
 */
class Specialization extends Model
{
    protected $fillable = [
        'name',
        'price'
    ];
    public function doctors(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            DoctorProfile::class,        // related model
            'doctor_specializations',    // pivot table
            'specialization_id',         // foreign key on pivot
            'doctor_id'                  // related key on pivot
        );
    }
}

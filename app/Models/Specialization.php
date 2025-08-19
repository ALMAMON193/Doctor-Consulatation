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
}

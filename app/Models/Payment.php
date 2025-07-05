<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed $currency
 */
class Payment extends Model
{
    protected $fillable = [
        'consultation_id', 'payment_intent_id', 'amount', 'currency',
        'status','payment_method', 'failure_reason', 'paid_at'
    ];

    public function consultation(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }
}

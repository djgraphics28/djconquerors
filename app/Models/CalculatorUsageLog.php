<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalculatorUsageLog extends Model
{
    protected $fillable = [
        'user_id',
        'calculator_type',
        'invested_amount',
        'first_reward',
        'signals_per_day',
        'number_of_days',
        'is_first_time',
        'final_amount',
        'calculation_data',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'invested_amount' => 'decimal:2',
        'first_reward' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'is_first_time' => 'boolean',
        'calculation_data' => 'array',
    ];

    /**
     * Get the user that owns the calculator usage log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

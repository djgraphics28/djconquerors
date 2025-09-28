<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdrawal extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'amount',
        'status',
        'paid_date',
        'transaction_id',
    ];

    /**
     * Get the user that owns the Withdrawal
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['amount', 'status', 'paid_date', 'transaction_id']);
        // Chain fluent methods for configuration options
    }

}

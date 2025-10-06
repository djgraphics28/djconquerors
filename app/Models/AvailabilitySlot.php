<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AvailabilitySlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'start_time',
        'end_time',
        'is_available',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Get available slots for a specific date
     */
    public static function getAvailableSlots($date)
    {
        return self::where('date', $date)
                  ->where('is_available', true)
                  ->get();
    }

    /**
     * Check if a specific time slot is available
     */
    public static function isSlotAvailable($date, $startTime, $endTime)
    {
        return self::where('date', $date)
                  ->where('start_time', '<=', $startTime)
                  ->where('end_time', '>=', $endTime)
                  ->where('is_available', true)
                  ->exists();
    }
}

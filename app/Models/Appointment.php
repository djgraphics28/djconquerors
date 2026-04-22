<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Appointment extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('evidence');
    }

    protected $fillable = [
        'user_id',
        'host_user_id',
        'start_time',
        'end_time',
        'status',
        'is_sure_investor',
        'notes',
        'venue'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Relationship with User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function host()
    {
        return $this->belongsTo(User::class, 'host_user_id');
    }

    /**
     * Check if appointment time is available
     */
    public static function isTimeAvailable($startTime, $endTime)
    {
        return !self::where(function ($query) use ($startTime, $endTime) {
            $query->where(function ($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                ->where('end_time', '>', $startTime);
            });
        })
        ->whereIn('status', ['pending', 'confirmed'])
        ->exists();
    }

    /**
     * Scope for available time slots
     */
    public function scopeAvailable($query, $startTime, $endTime)
    {
        return $query->where('start_time', '>=', $startTime)
                    ->where('end_time', '<=', $endTime)
                    ->whereIn('status', ['pending', 'confirmed']);
    }
}

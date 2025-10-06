<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmailReceiver extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'receive_appointment_notifications',
        'receive_system_notifications',
        'receive_user_registrations',
        'is_active',
        'custom_settings',
    ];

    protected $casts = [
        'receive_appointment_notifications' => 'boolean',
        'receive_system_notifications' => 'boolean',
        'receive_user_registrations' => 'boolean',
        'is_active' => 'boolean',
        'custom_settings' => 'array',
    ];

    /**
     * Relationship with User model
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get active admin receivers for appointment notifications
     */
    public static function getAppointmentReceivers()
    {
        return static::with('user')
                    ->where('is_active', true)
                    ->where('receive_appointment_notifications', true)
                    ->get()
                    ->map(function ($receiver) {
                        return [
                            'id' => $receiver->id,
                            'name' => $receiver->user->name,
                            'email' => $receiver->user->email,
                            'user_id' => $receiver->user_id,
                        ];
                    });
    }

    /**
     * Get active receivers for system notifications
     */
    public static function getSystemReceivers()
    {
        return static::with('user')
                    ->where('is_active', true)
                    ->where('receive_system_notifications', true)
                    ->get()
                    ->map(function ($receiver) {
                        return [
                            'name' => $receiver->user->name,
                            'email' => $receiver->user->email,
                        ];
                    });
    }

    /**
     * Get active receivers for user registration notifications
     */
    public static function getUserRegistrationReceivers()
    {
        return static::with('user')
                    ->where('is_active', true)
                    ->where('receive_user_registrations', true)
                    ->get()
                    ->map(function ($receiver) {
                        return [
                            'name' => $receiver->user->name,
                            'email' => $receiver->user->email,
                        ];
                    });
    }

    /**
     * Check if a user is an email receiver
     */
    public static function isUserReceiver($userId)
    {
        return static::where('user_id', $userId)
                    ->where('is_active', true)
                    ->exists();
    }

    /**
     * Get receiver by user ID
     */
    public static function getByUserId($userId)
    {
        return static::with('user')
                    ->where('user_id', $userId)
                    ->where('is_active', true)
                    ->first();
    }

    /**
     * Scope for active receivers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for appointment notifications
     */
    public function scopeAppointmentNotifications($query)
    {
        return $query->where('receive_appointment_notifications', true);
    }

    /**
     * Scope for system notifications
     */
    public function scopeSystemNotifications($query)
    {
        return $query->where('receive_system_notifications', true);
    }

    /**
     * Scope for user registration notifications
     */
    public function scopeUserRegistrationNotifications($query)
    {
        return $query->where('receive_user_registrations', true);
    }

    /**
     * Get formatted name with role
     */
    public function getFormattedNameAttribute()
    {
        return "{$this->user->name}";
    }

    /**
     * Get email address from linked user
     */
    public function getEmailAttribute()
    {
        return $this->user->email;
    }

    /**
     * Get name from linked user
     */
    public function getNameAttribute()
    {
        return $this->user->name;
    }
}

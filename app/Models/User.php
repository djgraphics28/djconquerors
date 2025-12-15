<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Spatie\Image\Enums\Fit;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Activitylog\LogOptions;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements MustVerifyEmail , HasMedia {
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles;
    use LogsActivity;
    use InteractsWithMedia;
    use HasApiTokens;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'riscoin_id',
        'inviters_code',
        'invested_amount',
        'is_active',
        'date_joined',
        'birth_date',
        'phone_number',
        'is_birthday_mention',
        'is_monthly_milestone_mention',
        'last_login_at',
        'last_login_ip',
        'gender',
        'occupation',
        'support_team',
        'assistant_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_joined' => 'date',
            'birth_date' => 'date',
            'invested_amount' => 'decimal:2',
            'last_login_at' => 'datetime',
        ];
    }

    protected $appends = ['age'];

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get users who were invited by this user (where their inviters_code matches this user's riscoin_id)
     * and their invites recursively
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function invites()
    {
        return $this->hasMany(User::class, 'inviters_code', 'riscoin_id')->with('invites');
    }

    /**
     * Get all descendants (entire team) - you might want to use this for statistics
     */
    public function getDescendantsAttribute()
    {
        $descendants = collect();

        $currentLevel = $this->invites;
        while ($currentLevel->isNotEmpty()) {
            $descendants = $descendants->merge($currentLevel);
            $currentLevel = $currentLevel->flatMap->invites;
        }

        return $descendants;
    }

    // Scope for active users
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Get total team investment
    public function getTeamInvestmentAttribute()
    {
        return $this->descendants->sum('invested_amount') + $this->invested_amount;
    }

    // Get total team members count
    public function getTotalTeamMembersAttribute()
    {
        return $this->descendants->count();
    }

    // Get direct team count
    public function getDirectTeamCountAttribute()
    {
        return $this->invites()->count();
    }

    /**
     * Get all of the withdrawals for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class, 'user_id', 'id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['name', 'email', 'riscoin_id', 'inviters_code', 'invested_amount', 'is_active', 'date_joined', 'birth_date', 'phone_number']);
        // Chain fluent methods for configuration options
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'inviters_code', 'riscoin_id');
    }

    public function getAgeAttribute()
    {
        if (!$this->birth_date) {
            return null;
        }

        $age = $this->birth_date->age;
        return "{$age} years old";
    }

    public function getMonthsAndDaysSinceJoinedAttribute()
    {
        if (!$this->date_joined) {
            return null;
        }

        $joinedDate = \Illuminate\Support\Carbon::parse($this->date_joined);
        $now = now();

        // Calculate total difference in months and days
        $diff = $joinedDate->diff($now);

        $months = $diff->y * 12 + $diff->m;
        $days = $diff->d;

        return number_format($months) . " months and " . number_format($days) . " days";
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this
            ->addMediaConversion('preview')
            ->fit(Fit::Contain, 300, 300)
            ->nonQueued();
    }

    public function getNameAttribute($value)
    {
        return Str::title($value);
    }

    public function superior()
    {
        return $this->belongsTo(User::class, 'inviters_code', 'riscoin_id');
    }

    /**
     * Relationship with appointments
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Check if user has appointment at specific time
     */
    public function hasAppointmentAt($dateTime)
    {
        return $this->appointments()
                   ->where('start_time', '<=', $dateTime)
                   ->where('end_time', '>=', $dateTime)
                   ->whereIn('status', ['pending', 'confirmed'])
                   ->exists();
    }

    /**
     * Relationship with EmailReceiver
     */
    public function emailReceiver(): HasOne
    {
        return $this->hasOne(EmailReceiver::class);
    }

    /**
     * Check if user is an email receiver
     */
    public function isEmailReceiver(): bool
    {
        return $this->emailReceiver()->where('is_active', true)->exists();
    }

    /**
     * Get email receiver settings
     */
    public function getEmailReceiverSettings()
    {
        return $this->emailReceiver()->where('is_active', true)->first();
    }

    /**
     * Scope for users who are email receivers
     */
    public function scopeEmailReceivers($query)
    {
        return $query->whereHas('emailReceiver', function ($q) {
            $q->where('is_active', true);
        });
    }

    /**
     * Scope for users who receive appointment notifications
     */
    public function scopeAppointmentNotificationReceivers($query)
    {
        return $query->whereHas('emailReceiver', function ($q) {
            $q->where('is_active', true)
              ->where('receive_appointment_notifications', true);
        });
    }

    /**
     * Get the last login with fallback to updated_at
     */
    public function getLastLoginAttribute()
    {
        $loginTime = $this->last_login_at ?? $this->updated_at;

        return $loginTime ? $loginTime->diffForHumans() : 'Never logged in';
    }

    /**
     * Get the actual last login timestamp (for internal use)
     */
    public function getLastLoginTimestampAttribute()
    {
        return $this->last_login_at ?? $this->updated_at;
    }

    /**
     * Get the assistant that owns the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function assistant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assistant_id', 'id');
    }

    /**
     * Get the managerLevel associated with the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function managerLevel(): HasOne
    {
        // include user_id so the relation can be properly hydrated when selecting specific columns
        return $this->hasOne(Manager::class, 'user_id', 'id')->select('user_id', 'level');
    }
}

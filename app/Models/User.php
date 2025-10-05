<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Activitylog\LogOptions;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\InteractsWithMedia;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements MustVerifyEmail , HasMedia {
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles;
    use LogsActivity;
    use InteractsWithMedia;

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

        $months = $joinedDate->diffInMonths($now);
        $days = $joinedDate->addMonths($months)->diffInDays($now);

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
}

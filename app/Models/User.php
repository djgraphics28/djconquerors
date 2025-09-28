<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Traits\LogsActivity;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements MustVerifyEmail {
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles;
    use LogsActivity;

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
        ];
    }

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
        return $this->hasMany(Comment::class, 'user_id', 'id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['name', 'email', 'riscoin_id', 'inviters_code', 'invested_amount', 'is_active', 'date_joined', 'birth_date', 'phone_number']);
        // Chain fluent methods for configuration options
    }
}

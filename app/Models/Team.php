<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Team extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'name',
        'description',
        'domain',
    ];

    /**
     * Get all of the members for the Team
     */
    public function members(): HasMany
    {
        return $this->hasMany(User::class, 'team_id', 'id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('team_logo')
            ->singleFile();

        $this->addMediaCollection('favicon')
            ->singleFile();
    }
}

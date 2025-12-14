<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guide extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'classification',
        'is_published',
        'order',
    ];

    /**
     * Get all of the items for the Guide
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(GuideItem::class, 'guide_id', 'id');
    }
}

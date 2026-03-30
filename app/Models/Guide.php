<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guide extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'guide_option_id',
        'is_published',
        'order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'order' => 'integer',
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

    public function option(): BelongsTo
    {
        return $this->belongsTo(GuideOption::class, 'guide_option_id');
    }
}

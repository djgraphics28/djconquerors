<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuideItem extends Model
{
    protected $fillable = [
        'guide_id',
        'title',
        'content',
        'order',
    ];

    /**
     * Get the guide that owns the GuideItem
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function guide(): BelongsTo
    {
        return $this->belongsTo(Guide::class, 'guide_id', 'id');
    }
}

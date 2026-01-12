<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReplyTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get all of the items for the ReplyTemplate
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(ReplyTemplateItem::class, 'reply_template_id', 'id');
    }

    /**
     * Scope a query to only include active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to order templates by their order column.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    /**
     * Get the full rendered content of all items for this template.
     *
     * @param User $user
     * @param array $defaults
     * @return string
     */
    public function renderAllItems(User $user, array $defaults = []): string
    {
        $renderedItems = [];

        // Only render active items
        foreach ($this->items()->active()->orderBy('order')->get() as $item) {
            $renderedItems[] = $item->render($user, $defaults);
        }

        return implode("\n\n", $renderedItems);
    }
}


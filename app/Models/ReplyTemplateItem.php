<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReplyTemplateItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'reply_template_id',
        'title',
        'content',
        'order',
        'is_active',
    ];

    protected $casts = [
        'order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the template that owns the item
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function replyTemplate(): BelongsTo
    {
        return $this->belongsTo(ReplyTemplate::class, 'reply_template_id', 'id');
    }

    /**
     * Scope a query to only include active items.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Render the template item with user data.
     *
     * @param User $user
     * @param array $defaults
     * @return string
     */
    public function render(User $user, array $defaults = []): string
    {
        $content = $this->content;

        // Find all template variables in the format {variable_name}
        preg_match_all('/\{([a-zA-Z0-9_\.]+)\}/', $content, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $variable) {
                $value = $this->getVariableValue($user, $variable, $defaults);
                $content = str_replace('{' . $variable . '}', $value, $content);
            }
        }

        return $content;
    }

    /**
     * Get the value for a template variable from the user or defaults.
     *
     * @param User $user
     * @param string $variable
     * @param array $defaults
     * @return string
     */
    protected function getVariableValue(User $user, string $variable, array $defaults): string
    {
        // First check if there's a custom default provided
        if (isset($defaults[$variable])) {
            return $defaults[$variable];
        }

        // Try to get the value from the user model (supports dot notation)
        try {
            $value = data_get($user, $variable);

            if ($value !== null) {
                // Format numbers with commas if it's invested_amount
                if ($variable === 'invested_amount' && is_numeric($value)) {
                    return number_format($value, 2);
                }
                return (string) $value;
            }
        } catch (\Exception $e) {
            // Variable not found in user model
        }

        // Return a fallback default value
        return $this->getDefaultValue($variable);
    }

    /**
     * Get default fallback values for common variables.
     *
     * @param string $variable
     * @return string
     */
    protected function getDefaultValue(string $variable): string
    {
        $defaults = [
            'name' => 'User',
            'first_name' => 'User',
            'last_name' => '',
            'email' => 'email@example.com',
            'username' => 'user',
            'riscoin_id' => 'N/A',
            'invested_amount' => '0.00',
            'age' => 'Not specified',
            'gender' => 'Not specified',
            'inviters_code' => 'N/A',
            'assistant.riscoin_id' => 'N/A',
        ];

        return $defaults[$variable] ?? '{' . $variable . '}';
    }
}

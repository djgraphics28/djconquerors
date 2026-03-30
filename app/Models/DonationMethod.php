<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DonationMethod extends Model
{
    protected $fillable = [
        'name',
        'type',
        'account_name',
        'account_number',
        'instructions',
        'qr_code_path',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order'     => 'integer',
    ];

    // ── Scopes ──────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('id');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────────

    public function getQrCodeUrlAttribute(): ?string
    {
        if (!$this->qr_code_path) {
            return null;
        }

        return Storage::disk('public')->url($this->qr_code_path);
    }

    public function deleteQrCode(): void
    {
        if ($this->qr_code_path) {
            Storage::disk('public')->delete($this->qr_code_path);
            $this->update(['qr_code_path' => null]);
        }
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'gcash'  => '💙',
            'maya'   => '💚',
            'bank'   => '🏦',
            default  => '💳',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'gcash'  => 'GCash',
            'maya'   => 'Maya',
            'bank'   => 'Bank Transfer',
            default  => 'Other',
        };
    }
}

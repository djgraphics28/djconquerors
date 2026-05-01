<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class BinanceApkDownload extends Model implements HasMedia
{
    use HasFactory, LogsActivity, InteractsWithMedia;

    protected $fillable = [
        'app_type',
        'title',
        'description',
        'download_url',
        'version',
        'is_active',
    ];

    public static array $appTypes = [
        'binance' => 'Binance',
        'okx'     => 'OKX',
        'bitget'  => 'Bitget',
        'other'   => 'Other',
    ];

    public function getAppLabelAttribute(): string
    {
        return self::$appTypes[$this->app_type] ?? ucfirst($this->app_type);
    }

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile();

        $this->addMediaCollection('apk')
            ->singleFile();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['app_type', 'title', 'description', 'download_url', 'version', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class SiteContent extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public static function getValue(string $key, string $fallback = ''): string
    {
        if (!Schema::hasTable('site_contents')) {
            return $fallback;
        }

        return self::query()->where('key', $key)->value('value') ?? $fallback;
    }
}

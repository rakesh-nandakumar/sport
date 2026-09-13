<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ActivityType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'icon', 'color', 'unit_label', 'description',
        'requires_game', 'default_slot_minutes', 'is_featured', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'requires_game' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $type) {
            $type->slug = $type->slug ?: Str::slug($type->name);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class)->orderBy('name');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }
}

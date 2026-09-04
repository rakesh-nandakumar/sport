<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = ['slug', 'name', 'category', 'icon', 'default_pricing_unit', 'is_active', 'sort_order'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    public function hasFields(): bool
    {
        return $this->fields() !== [];
    }

    /** Type-specific field schema declared in config/activities.php. */
    public function fields(): array
    {
        return config("activities.types.{$this->slug}.fields", []);
    }

    public function defaultCapacity(): ?int
    {
        return config("activities.types.{$this->slug}.default_capacity");
    }
}

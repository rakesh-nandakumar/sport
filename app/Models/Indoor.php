<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Indoor extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'tags', 'location', 'email', 'website', 'description','contact_number', 'price','user_id','photo', 'gallery',
    'monday_opening', 'monday_closing',
    'tuesday_opening', 'tuesday_closing',
    'wednesday_opening', 'wednesday_closing',
    'thursday_opening', 'thursday_closing',
    'friday_opening', 'friday_closing',
    'saturday_opening', 'saturday_closing',
    'sunday_opening', 'sunday_closing'];


    public function scopeFilters($query, array $filter){
        if($filter['activity'] ?? false){
            $query->whereHas('resources.activity', fn ($q) => $q->where('slug', $filter['activity']));
        };

        if($filter['search'] ?? false){
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . request('search') . '%')
                ->orWhere('tags', 'like', '%' . request('search') . '%')
                ->orWhere('location', 'like', '%' . request('search') . '%');
            });
        };
    }

    /** Venue default opening hours for that weekday. Null = closed. */
    public function hoursFor(CarbonInterface $date): ?array
    {
        $day = strtolower($date->format('l'));
        $open = $this->{$day . '_opening'};
        $close = $this->{$day . '_closing'};

        if (! $open || ! $close) {
            return null;
        }

        return ['open' => $open, 'close' => $close];
    }

    // relationship
    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public  function comments(){
        return $this->hasMany(Comment::class, 'indoor_id', 'id');
    }

    public function bookings(){
        return $this->hasMany(Booking::class, 'indoor_id', 'id');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    /** Distinct activity types offered by this venue's resources. */
    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class, 'resources', 'indoor_id', 'activity_id')->distinct();
    }
}

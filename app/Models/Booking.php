<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = ['start_time', 'finish_time', 'comments', 'user_id', 'indoor_id', 'resource_id', 'status', 'total_price', 'unit_quantity', 'selected_options', 'phoneNumber', 'custName'];

    protected $casts = [
        'start_time' => 'datetime',
        'finish_time' => 'datetime',
        'selected_options' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function indoor(): BelongsTo
    {
        return $this->belongsTo(Indoor::class);
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }
}

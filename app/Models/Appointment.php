<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;
    protected $fillable = ['start_time', 'finish_time', 'comments', 'user_id', 'indoor_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function indoor(): BelongsTo
    {
        return $this->belongsTo(Indoor::class);
    }
}

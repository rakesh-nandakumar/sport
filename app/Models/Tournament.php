<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'tournamentDate', 'noOFplayers', 'contact_number', 'entry_fee', 'description', 'photo', 'user_id', 'location'];

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }


}

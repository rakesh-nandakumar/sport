<?php

namespace App\Models;

use App\Http\Controllers\IndoorController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Livewire\PostComments;


class Comment extends Model
{
    use HasFactory;

    protected $table = 'comments';

    protected $fillable = ['comment', 'user_id', 'indoor_id'];

    public function user(){
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function indoor(){
        return $this->belongsTo(\App\Models\Indoor::class, 'indoor_id', 'id');
    }
}

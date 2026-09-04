<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Enums\Role;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role_id' => Role::class,
        'role' => Role::class,
    ];

    //RElationship
    public function indoors(){
        return $this->hasMany(Indoor::class, 'user_id');
    }

    public function comments(){
        return $this->hasMany(Comment::class, 'user_id');
    }

    public function tournaments(){
        return $this->hasMany(Tournament::class, 'user_id');
    }

    public function bookings(){
        return $this->hasMany(Booking::class, 'user_id');
    }
}


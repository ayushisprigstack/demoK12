<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;

class K12User extends Model {

    use HasApiTokens,
        HasFactory,
        Notifiable;

    protected $table = "k12_users";

    public function location() {
        return $this->belongsTo(Location::class, 'location', 'ID');
    }

    public function user() {
        return $this->belongsTo(User::class, 'email', 'email');
    }

}

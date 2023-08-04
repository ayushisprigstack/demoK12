<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;

class School extends Model {

    use HasApiTokens,
        HasFactory,
        Notifiable;

    protected $table = "schools";

    public function location() {
        return $this->belongsTo(Location::class, 'location');
    }

    public function tickets() {
        return $this->hasMany(Ticket::class, 'school_id');
    }

}

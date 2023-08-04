<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
   use SoftDeletes, HasApiTokens, HasFactory, Notifiable;
    protected $fillable = [
        'name',
        'email',
        'password',
        'remember_token'
    ];
    protected $hidden = [
        'password'
    ];
    
    function avtardata()
    {
         return $this->belongsTo(Avtar::class, 'avtar', 'id');
    }
    
    function school(){
        return $this->belongsTo(School::class, 'school_id', 'ID');
    }
    
    }

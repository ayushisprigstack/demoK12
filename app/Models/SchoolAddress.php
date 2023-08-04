<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;

class SchoolAddress extends Model {

    use HasApiTokens,
        HasFactory,
        Notifiable;

    protected $table = "school_address";
    protected $fillable = [
        'SchoolID','PhoneNum','StreetLine', 'City', 'StateOrProvinceCode', 'PostalCode', 'CountryCode','Location'
    ];
   
    function school(){
        return $this->belongsTo(School::class, 'SchoolID', 'ID');
    }
    
    function location(){
        return $this->belongsTo(Location::class, 'Location', 'ID');
    }

}


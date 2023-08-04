<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TechnicianLocation extends Model
{
    use HasApiTokens, HasFactory, Notifiable,SoftDeletes;    
    protected $table="technicianLocation"; 
protected $fillable = [
    'Technician',
    'Location',
];    
}
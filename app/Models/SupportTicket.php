<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table="support_tickets";
    
     public function user() {
        return $this->belongsTo(User::class, 'AssignedTo', 'id');
    }
    
    public function building(){
        return $this->belongsTo(Building::class, 'Building', 'ID');
    }
    
    public function technology(){
        return $this->belongsTo(Technology::class, 'TypeChildId', 'ID');
    }
    
    public function maintenance(){
        return $this->belongsTo(Maintenance::class, 'TypeChildId', 'ID');
    }
}
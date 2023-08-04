<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;

class SupportTicketAssignment extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table="support_ticket_assignments";
    
       public function building() {
        return $this->belongsTo(Building::class, 'building_id', 'ID');
      }
      
       public function assignTo() {
        return $this->belongsTo(User::class, 'staffmember_id', 'id');
    }

}


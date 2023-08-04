<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;

class TicketRepairLog extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table="ticket_repair_log";
    
    public function damageTypes() {
        return $this->belongsToMany(DamageType::class, 'DamageType', 'ID', 'ID');
    }

    public function ticket() {
        return $this->belongsTo(Ticket::class, 'Ticket_Id', 'ID');
    }

}

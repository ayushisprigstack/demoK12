<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;

class SupportTicketComments extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table="supportTickets_comments";
    
    public function user() {
    return $this->belongsTo(User::class,'UserID', 'id');
    }
     
    public function supportTicket() {
    return $this->belongsTo(SupportTicket::class,'SupportTicketID', 'ID');
    }
}
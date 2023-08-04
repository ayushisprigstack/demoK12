<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;

class CloseTicketBatchLog extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table="closeTicket_batch_log";
    
    public function ticket() {
        return $this->belongsTo(Ticket::class, 'Ticket_Id', 'ID')->with('ticketAttachments');
    }
    
}

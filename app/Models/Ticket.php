<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use App\Models\InventoryManagement;
use App\Models\TicketStatus;
use App\Models\TicketsAttachment;

class Ticket extends Model {

    use HasApiTokens,
        HasFactory,
        Notifiable;

    protected $table = "tickets";

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    
    public function school()
    {
        return $this->belongsTo(School::class, 'school_id', 'ID'); 
    }

    public function inventoryManagement() {
        return $this->belongsTo(InventoryManagement::class, 'inventory_id', 'ID');
    }

    public function statusname() {
        return $this->belongsTo(TicketStatus::class, 'ticket_status');
    }

    public function ticketIssues() {
        return $this->hasMany(TicketIssue::class, 'ticket_Id', 'ID');       
    }

    public function ticketHistory() {
        return $this->hasMany(TicketStatusLog::class, 'Ticket_id', 'ID');   
    }

    public function ticketAttachments() {
        return $this->hasMany(TicketsAttachment::class, 'Ticket_ID', 'ID');
    }

    public function ticketRepairLog() {
        return $this->hasMany(TicketRepairLog::class, 'Ticket_Id', 'ID');
    }

    public function ticketImg() {
        return $this->hasMany(TicketImage::class, 'Ticket_ID', 'ID');
    }
    
    public function batchLog() {
        return $this->hasMany(CloseTicketBatchLog::class, 'Ticket_Id', 'ID');
    }
    
}

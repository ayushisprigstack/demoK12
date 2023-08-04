<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;

class SchoolBatchLog extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table="school_batch_log";

     public function ticket() {
        return $this->belongsTo(Ticket::class, 'TicketID', 'ID')->with('ticketAttachments','inventoryManagement');
    }

}

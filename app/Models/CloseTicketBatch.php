<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;

class CloseTicketBatch extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table="closeTicket_batch";
     
     public function invoice() {
        return $this->hasMany(InvoiceLog::class, 'Batch_ID', 'ID');
    }
    
     public function batchLog() {
        return $this->hasMany(CloseTicketBatchLog::class, 'Batch_Id', 'ID')->with('ticket');
    }
    
    
    
}

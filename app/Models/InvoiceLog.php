<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;

class InvoiceLog extends Model
{
    use HasApiTokens,
        HasFactory,
        Notifiable;

    protected $table = "invoice_log";

    public function school() {
        return $this->belongsTo(School::class, 'School_Id', 'ID');
    }
    
    public function batch() {
        return $this->belongsTo(CloseTicketBatch::class, 'Batch_ID', 'ID')->with('batchLog');
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use App\Models\InventoryManagement;
use App\Models\Ticket;

class TicketIssue extends Model {

    use HasApiTokens,
        HasFactory,
        Notifiable;

    protected $table = "ticket_issues";
    protected $fillable = [
        'ticket_Id', // Add this line to the fillable array
        'issue_Id',
        'user_id',
        'inventory_id',
    ];

    public function deviceIssue() {
        return $this->belongsTo(DeviceIssue::class, 'issue_Id');
    }

    public function ticket() {
        return $this->belongsTo(Ticket::class, 'ticket_Id', 'ID');
    }

}

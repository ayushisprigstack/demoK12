<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketsAttachment extends Model {

    use SoftDeletes,
        HasApiTokens,
        HasFactory,
        Notifiable;

    protected $table = "Tickets_attachment";

    public function part() {
        return $this->belongsTo(PartSKUs::class, 'Parts_ID', 'ID');
    }

    public function ticket() {
        return $this->belongsTo(Ticket::class, 'Ticket_ID', 'ID');
    }

    public function user() {
        return $this->belongsTo(User::class, 'User_ID', 'id');
    }

}

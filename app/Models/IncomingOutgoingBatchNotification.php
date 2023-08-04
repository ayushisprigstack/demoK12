<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;

class IncomingOutgoingBatchNotification extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table="incoming_outgoing_batches_notifications";
    
 
    
}

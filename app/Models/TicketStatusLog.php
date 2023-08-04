<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use App\Models\InventoryManagement;

class TicketStatusLog extends Model
{
    use HasApiTokens, HasFactory, Notifiable;    
    protected $table="tickets_status_log";   
    
  
}
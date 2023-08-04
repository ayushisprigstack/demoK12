<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use App\Models\InventoryManagement;

class TicketImage extends Model
{
    use HasApiTokens, HasFactory, Notifiable;    
    protected $table="ticket_images";   
       
}
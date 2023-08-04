<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartSKUs extends Model
{
    use SoftDeletes, HasApiTokens, HasFactory, Notifiable;
    protected $table="PartSKU";
    
         public function TicketsAttachment() {
        return $this->belongsTo(TicketsAttachment::class, 'Parts_ID', 'ID');
   }
   
    }

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use App\Models\InventoryManagement;

class ErrorLog extends Model
{
    use HasApiTokens, HasFactory, Notifiable;    
    protected $table="error_log";   
    
   function ErrorLog($errorfrom,$errormsg,$uid){
        $error = new ErrorLog();
        $error->user_id = $uid;
        $error->error_from = $errorfrom;
        $error->error_msg = $errormsg;
        $error->save();
        
    }
}
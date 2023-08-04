<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table="Menu";
    
      public function menuAccesses()
    {
        return $this->hasMany(MenuAccess::class, 'Menu', 'ID');
    }
    
     public function submenu() {
      return $this->hasMany(Menu::class,'ParentId', 'ID');
    }
    
}
<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;

class DeviceType extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table="devicetypes";
    
    public function inventoryManagement()
{
    return $this->hasOne(InventoryManagement::class, 'Device_type'); // Assuming 'device_id' is the foreign key column in the InventoryManagement table
}
    
}



<?php

namespace App\Models;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasApiTokens, HasFactory, Notifiable,SoftDeletes;
    protected $table="students";
    
    protected $fillable = [
    'Device_user_first_name',
    'Device_user_last_name',
    'Grade',
    'Parent_guardian_name',
    'Parent_phone_number',
    'Parent_Guardian_Email',
    'Parental_coverage',
    'Student_num',
    'stripeCustomerID',
     'School_ID'      
    ];
    
    public function inventoryManagement() {
    return $this->belongsTo(InventoryManagement::class,'Inventory_ID', 'ID');
    }
    
    public function studentInventories() {
        return $this->hasMany(StudentInventory::class, 'ID', 'Student_ID');
    }
}
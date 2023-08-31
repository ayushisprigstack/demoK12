<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryManagement extends Model
{
    use HasFactory;
   protected $table="inventory_management"; 
   
  public function studentInventory() {
        return $this->hasOne(StudentInventory::class, 'Inventory_ID', 'ID')->with('student');
   }
 
    
    public function ticket(){
        return $this->hasMany(Ticket::class, 'inventory_id', 'ID')->with('statusname','ticketIssues','ticketHistory');
    }

    public function building(){
        return $this->belongsTo(Building::class, 'Building', 'ID');
    }
    }

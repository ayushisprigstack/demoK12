<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentInventory extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = "student_inventories";

    public function student()
    {
        return $this->belongsTo(Student::class, 'Student_ID', 'ID');
    }

    public function inventory()
    {
        return $this->belongsTo(InventoryManagement::class, 'Inventory_ID', 'ID');
    }
}

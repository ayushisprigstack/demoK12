<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;

class ContactUs extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table="contact_us_log";
        

    public function school() {
        return $this->belongsTo(School::class, 'SchoolID', 'ID');
    }

    public function plan()
    {
        return $this->belongsTo(InsurancePlan::class, 'PlanNum', 'PlanNum'); 
    }
    
     public function student()
    {
        return $this->belongsTo(Student::class, 'StudentNum', 'Student_num');
    }
}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;

class InsurancePlanEnrollment extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table="insurance_plan_enrollments";
    
      public function school()
    {
        return $this->belongsTo(School::class, 'SchoolID', 'ID'); 
    }
    
      public function student()
    {
        return $this->belongsTo(Student::class, 'StudentID', 'ID'); 
    }
    
      public function plan()
    {
        return $this->belongsTo(InsurancePlan::class, 'PlanID', 'ID'); 
    }
     
}


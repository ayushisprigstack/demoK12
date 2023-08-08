<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;

class InsurancePlan extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table="insurance_plans";
     
     public function coverdDeviceModels() {
        return $this->hasMany(CoverdDeviceModelLog::class, 'PlanID', 'ID');   
    }
    
     public function coverdServices() {
        return $this->hasMany(CoverdServiceLog::class, 'PlanID', 'ID');   
    }
    
      public function school() {
        return $this->belongsTo(School::class, 'SchoolID', 'ID')->with('logo:Logo_Path');
    }
    public function coverdServicesNames() {
        return $this->hasMany(CoverdServiceLog::class, 'PlanID')->with('services:id,Name');
    }

}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;

class CoverdServiceLog extends Model {

    use HasApiTokens,
        HasFactory,
        Notifiable;

    protected $table = "coverd_services_log";

    public function services() {
        return $this->hasMany(ProductsForInsurancePlan::class, 'ID', 'ServiceID');
    }

    public function plan() {
        return $this->belongsTo(InsurancePlan::class, 'PlanID', 'ID');
    }

}

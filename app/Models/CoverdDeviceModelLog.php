<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;

class CoverdDeviceModelLog extends Model {

    use HasApiTokens,
        HasFactory,
        Notifiable;

    protected $table = "covered_device_models_log";

    public function plan() {
        return $this->belongsTo(InsurancePlan::class, 'PlanID', 'ID');
    }

}

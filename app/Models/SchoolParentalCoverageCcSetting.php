<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;

class SchoolParentalCoverageCcSetting extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table="school_Parental_coverage_cc_settings";
     
}

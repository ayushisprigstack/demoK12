<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;

class NotificationEventsLog extends Model {

    use HasApiTokens,
        HasFactory,
        Notifiable;

    protected $table = "notification_events_log";

    public function user() {
        return $this->belongsTo(User::class, 'UserID', 'id');
    }

    public function school() {
        return $this->belongsTo(School::class, 'SchoolID', 'ID');
    }

    public function event() {
        return $this->belongsTo(NotificationEvents::class, 'EventID', 'ID');
    }

}

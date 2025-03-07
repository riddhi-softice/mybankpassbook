<?php

namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;

class NotificationSent extends Authenticatable
{
    protected $table = "notifications_sent";
    protected $guarded = [];
    protected $hidden = ['updated_at'];
}

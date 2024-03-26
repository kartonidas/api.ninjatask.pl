<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsHistory extends Model
{
    public $table = "sms_history";
    
    public const STATUS_OK = "OK";
    public const STATUS_ERR = "ERROR";
}
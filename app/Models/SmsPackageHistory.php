<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsPackageHistory extends Model
{
    public $table = "sms_package_history";
    
    public const OPERATION_ADD = "add";
    public const OPERATION_SEND = "send";
}
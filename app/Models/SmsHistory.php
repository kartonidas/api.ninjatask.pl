<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\DbTimestamp;

class SmsHistory extends Model
{
    use DbTimestamp;
    use \App\Traits\UuidTrait {
        boot as traitBoot;
    }
    
    public $table = "sms_history";
    
    public const STATUS_OK = "OK";
    public const STATUS_ERR = "ERROR";
    
    public static $sortable = ["created_at"];
    public static $defaultSortable = ["created_at", "desc"];
    
    protected $hidden = ["uuid"];
}
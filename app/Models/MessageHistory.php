<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MessageHistory extends Model
{
    public $table = "message_history";
    
    public const TYPE_EMAIL = "email";
    public const TYPE_SMS = "sms";
    
    public const OBJECT_TASK = "task";
}
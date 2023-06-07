<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use App\Mail\Task\AssignedMessage;
use App\Models\Invoice;
use App\Models\Task;
use App\Models\User;
use App\Traits\DbTimestamp;

class Notification extends Model
{
    use DbTimestamp;
    
    public function scopeApiFields(Builder $query): void
    {
        $query->select("id", "object_id", "type", "object_name", "read", "read_time", "created_at");
    }
    
    public static function notify($user_id, $object_id, $type)
    {
        $row = new self;
        $row->user_id = $user_id;
        $row->object_id = $object_id;
        $row->type = $type;
        $row->object_name = $row->getObjectName();
        $row->save();
        
        $user = User::find($row->user_id);
        if($user)
        {
            $settings = $user->getAccountSettings();
            switch($type)
            {
                case "task:assign":
                    if(in_array($type, $settings->notifications))
                    {
                        $task = Task::find($row->object_id);
                        if($task)
                        {
                            $url = env("FRONTEND_URL") . "task/" . $row->object_id;
                            Mail::to($user->email)->locale($settings->locale)->queue(new AssignedMessage($url, $task));
                        }
                    }
                break;
            }
        }
    }
    
    public function getObjectName()
    {
        $out = [];
        switch($this->type)
        {
            case "task:assign":
                $task = Task::withoutGlobalScopes()->find($this->object_id);
                if($task)
                    return $task->name;
            break;
            case "invoice:generated":
                $invoice = Invoice::withoutGlobalScopes()->find($this->object_id);
                if($invoice)
                    return $invoice->full_number;
            break;
        }
        return "";
    }
}
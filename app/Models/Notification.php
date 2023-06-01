<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use App\Mail\Task\AssignedMessage;
use App\Models\User;

class Notification extends Model
{
    public static function notify($user_id, $object_id, $type)
    {
        $row = new self;
        $row->user_id = $user_id;
        $row->object_id = $object_id;
        $row->type = $type;
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
                        $url = env("FRONTEND_URL") . "task/" . $row->object_id;
                        Mail::to($user->email)->send(new AssignedMessage($url, $settings->locale));
                    }
                break;
            }
        }
        
    }
}
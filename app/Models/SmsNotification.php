<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Jobs\SmsSend;
use App\Libraries\Helper;
use App\Models\Task;
use App\Models\User;

class SmsNotification extends Model
{
    use \App\Traits\UuidTrait {
        boot as traitBoot;
    }
    
    public const TYPE_TASK_ATTACH = "task_attach";
    public const TYPE_TASK_REMINDER = "task_reminder";
    
    public static function getAllowedNotifications()
    {
        return [
            self::TYPE_TASK_ATTACH => __("Task attach"),
            self::TYPE_TASK_REMINDER => __("Task reminder"),
        ];
    }
    
    public static function getNotifications(string $uuid = null)
    {
        $notifications = [];
        
        foreach(self::getAllowedNotifications() as $type => $notification)
            $notifications[$type] = self::getDefaultNotifications($type);
        
        $rows = SmsNotification::whereRaw("1=1");
        if($uuid !== null)
            $rows->withoutGlobalScope("uuid")->where("uuid", $uuid);
        $rows = $rows->whereIn("type", array_keys(self::getAllowedNotifications()))->get();
        
        foreach($rows as $row)
        {
            $notifications[$row->type] = [
                "send" => $row->send,
                "message" => $row->message,
                "days" => $row->days,
            ];
        }
        
        return $notifications;
    }
    
    private static function getDefaultNotifications($type)
    {
        switch($type)
        {
            case self::TYPE_TASK_ATTACH:
                return [
                    "send" => false,
                    "message" => __("New task: [NAZWA] (see: [LINK])"),
                ];
            break;
        
            case self::TYPE_TASK_REMINDER:
                return [
                    "send" => false,
                    "message" => __("Reminder: [NAZWA], date: [DATA] (see: [LINK])"),
                    "days" => 15,
                ];
            break;
        }
        
        return [];
    }
    
    public static function taskMessage($type, Task $task, User $user)
    {
        $smsConfig = $user->getFirm()->getSmsConfig($user->getUuid());
        if(!empty($smsConfig["allowed"]) && $smsConfig["allowed"] > 0 && !empty($smsConfig[$type]["send"]))
        {
            $message = self::prepareTaskMessage(trim($smsConfig[$type]["message"]), $task);
            if(!empty($message) && !empty($user->phone))
                SmsSend::dispatch($task->uuid, $user->phone, $message);
        }
    }
    
    private static function prepareTaskMessage($message, Task $task)
    {
        $place = $task->getProject();
        $message = str_ireplace(["[NAZWA]", "[NAME]"], mb_substr($task->name, 0, 30), $message);
        $message = str_ireplace(["[MIEJSCE]", "[PLACE]"], mb_substr($place ? $place->name : "", 0, 30), $message);
        $message = str_ireplace(["[DATA]", "[DATE]"], $task->start_date, $message);
        $message = str_ireplace(["[LINK]"], $task->getShortLink(), $message);
        $message = Helper::__no_pl($message, false);
        
        return trim($message);
    }
}

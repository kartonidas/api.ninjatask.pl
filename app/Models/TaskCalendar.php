<?php

namespace App\Models;

use DateTime;
use DateInterval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TaskCalendar extends Model
{
    use \App\Traits\UuidTrait {
        boot as traitBoot;
    }
    
    public $table = "task_calendar";
    public $timestamps = false;
    
    public const DEFAULT_START_TIME = "08:00";
    public const DEFAULT_END_TIME = "20:00";
    
    public static function getDefaultStartTime()
    {
        return self::DEFAULT_START_TIME;
    }
    
    public static function getDefaultEndTime()
    {
        return self::DEFAULT_END_TIME;
    }
    
    public static function generateDates(Task $task)
    {
        $dates = [];
        if($task->start_date)
        {
            $startDate = $task->getStartDateTime();
            $endDate = $task->getEndDateTime();
            
            $startDate = new DateTime($startDate);
            $endDate = new DateTime($endDate);

            do
            {
                $dates[] = $startDate->format("Y-m-d");
                $startDate->add(new DateInterval("P1D"));
            }
            while($startDate < $endDate);
        }
        
        if(empty($dates))
            self::withoutGlobalScope("uuid")->where("uuid", $task->uuid)->where("task_id", $task->id)->delete();
        else
        {
            self
                ::withoutGlobalScope("uuid")
                ->where("uuid", $task->uuid)
                ->where("task_id", $task->id)
                ->whereNotIn("date", $dates)
                ->delete();
                
            $currentDates = self
                ::withoutGlobalScope("uuid")
                ->where("uuid", $task->uuid)
                ->where("task_id", $task->id)
                ->pluck("date")
                ->all();
                
            $dates = array_diff($dates, $currentDates);
            if(!empty($dates))
            {
                foreach($dates as $d)
                {
                    $row = new self;
                    $row->uuid = $task->uuid;
                    $row->task_id = $task->id;
                    $row->date = $d;
                    $row->save();
                }
            }
        }
    }
    
    public static function deleteDates(Task $task)
    {
        self::withoutGlobalScope("uuid")->where("uuid", $task->uuid)->where("task_id", $task->id)->delete();
    }
}
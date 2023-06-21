<?php

namespace App\Models;

use DateInterval;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Libraries\Helper;
use App\Models\Task;
use App\Models\TaskTimeDay;
use App\Traits\User;

class TaskTime extends Model
{
    use User;
    
    const ACTIVE = "active";
    const PAUSED = "paused";
    const FINISHED = "finished";
    
    public function canDelete() {
        return Auth::user()->owner || (Auth::user()->id == $this->user_id);
    }
    
    public function canEdit() {
        return Auth::user()->owner || (Auth::user()->id == $this->user_id);
    }
    
    public function scopeApiFields(Builder $query): void
    {
        $query->select("id", "status", "task_id", "user_id", "started", "finished", "timer_started", "total", "comment", "billable");
    }
    
    public function splitTimeIntoDays()
    {
        if(TaskTimeDay::where("task_time_id", $this->id)->count())
            TaskTimeDay::where("task_time_id", $this->id)->forceDelete();
        
        $task = Task::withoutGlobalScopes()->withTrashed()->find($this->task_id);
        if(!$task || $task->trashed())
        {
            if(!$task)
                TaskTimeDay::where("task_time_id", $this->id)->forceDelete();
            else
                TaskTimeDay::where("task_time_id", $this->id)->delete();
        }
        
        if($this->status == self::FINISHED && $task && !$task->trashed())
        {
            $started = DateTime::createFromFormat("U", $this->started);
            $finished = DateTime::createFromFormat("U", $this->finished);
            
            $days = [];
            if($started->format("Y-m-d") == $finished->format("Y-m-d"))
                $days[] = $started->format("Y-m-d");
            else
            {
                $days[] = $started->format("Y-m-d");
                while($started->format("Y-m-d") != $finished->format("Y-m-d"))
                {
                    $started->add(new DateInterval("P1D"));
                    $days[] = $started->format("Y-m-d");
                }
            }
            $days = array_values(array_unique($days));
            sort($days);
            
            if(count($days) == 1)
                self::createTaskTimeDay(
                    $task->uuid,
                    $this->id,
                    $task->project_id,
                    $this->task_id,
                    $this->user_id,
                    $days[0],
                    $this->total
                );
            else
            {
                $total = 0;
                foreach($days as $i => $day)
                {
                    $timeToAdd = 0;
                    // Pierwszy i ostatni dzień
                    if($i == 0 || $i+1 == count($days))
                    {
                        if($i == 0)
                        {
                            $tmp = DateTime::createFromFormat("U", $this->started);
                            $loggedTimeFirstDay = strtotime($tmp->format("Y-m-d 23:59:59")) - $this->started;
                            
                            // Pierwszego dnia zalogowaliśmy mniej jak 60 sekund:
                            // - jeśli jest to więcej jak 30 to logujemy jedną minute,
                            // - jeśli mniej to nie logujemy czasu wcale
                            if($loggedTimeFirstDay < 60 && $loggedTimeFirstDay >= 30)
                                $timeToAdd = 60;
                            elseif($loggedTimeFirstDay >= 60)
                                $timeToAdd = Helper::roundTime($loggedTimeFirstDay);
                        }
                        else
                        {
                            $timeToAdd = $this->total - $total;
                        }
                    }
                    else
                    {
                        $timeToAdd = 60 * 60 * 24; // Praca trwala przez całą dobę
                    }
                    
                    if($timeToAdd > 0)
                    {
                        self::createTaskTimeDay(
                            $task->uuid,
                            $this->id,
                            $task->project_id,
                            $this->task_id,
                            $this->user_id,
                            $day,
                            $timeToAdd
                        );
                        $total += $timeToAdd;
                    }
                }
            }
        }
    }
    
    private static function createTaskTimeDay($uuid, $id, $pid, $tid, $uid, $day, $total)
    {
        $row = new TaskTimeDay;
        $row->uuid = $uuid;
        $row->task_time_id = $id;
        $row->project_id = $pid;
        $row->task_id = $tid;
        $row->user_id = $uid;
        $row->date = $day;
        $row->total = $total;
        $row->save();
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Models\TaskAssignedUser;
use App\Models\TaskTime;
use App\Traits\File;
use App\Traits\DbTimestamp;

class Task extends Model
{
    use DbTimestamp, File, SoftDeletes;
    use \App\Traits\UuidTrait {
        boot as traitBoot;
    }
    
    public function scopeApiFields(Builder $query): void
    {
        $query->select("id", "name", "description", "project_id", "priority", "completed", "created_at");
    }
    
    public function calculateTotalTime()
    {
        $totalTime = $totalBillableTime = 0;
        $taskTimes = TaskTime::where("task_id", $this->id)->where("status", TaskTime::FINISHED)->get();
        foreach($taskTimes as $taskTime)
        {
            $totalTime += $taskTime->total;
            if($taskTime->billable)
                $totalBillableTime += $taskTime->total;
        }
        
        $this->total = $totalTime;
        $this->total_billable = $totalBillableTime;
        $this->saveQuietly();
    }
    
    public function getAssignedUserIds()
    {
        $out = [];
        $rows = TaskAssignedUser::where("task_id", $this->id)->get();
        foreach($rows as $row)
            $out[] = $row->user_id;
        return $out;
    }
    
    public function assignUsers($users)
    {
        $users = array_filter($users);
        foreach($users as $user)
        {
            if(!TaskAssignedUser::where("task_id", $this->id)->where("user_id", $user)->count())
            {
                $assign = new TaskAssignedUser;
                $assign->task_id = $this->id;
                $assign->user_id = $user;
                $assign->save();
            }
        }
        TaskAssignedUser::where("task_id", $this->id)->whereNotIn("user_id", $users)->delete();
    }
    
    public function getActiveTaskTime() {
        $out = [
            "state" => "stop",
            "total" => 0,
        ];
        
        $activeTotalTime = 0;
        $taskTime = TaskTime::where("task_id", $this->id)->where("user_id", Auth::user()->id)->whereIn("status", [TaskTime::ACTIVE, TaskTime::PAUSED])->first();
        if($taskTime)
        {
            if($taskTime->status == TaskTime::ACTIVE)
            {
                $out["state"] = "active";
                $out["total"] = $taskTime->total + (time() - $taskTime->timer_started);
                
                $activeTotalTime = $out["total"];
            }
            else
            {
                $out["state"] = "paused";
                $out["total"] = $taskTime->total;    
            }
        }
        
        $out["total_logged"] = TaskTime::where("task_id", $this->id)->where("user_id", Auth::user()->id)->where("status", "!=", TaskTime::ACTIVE)->sum("total") + $activeTotalTime;
        
        return $out;
    }
}
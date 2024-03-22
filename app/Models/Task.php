<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\InvalidStatus;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Status;
use App\Models\TaskAssignedUser;
use App\Models\TaskCalendar;
use App\Models\TaskTime;
use App\Models\User;
use App\Traits\File;
use App\Traits\DbTimestamp;

class Task extends Model
{
    use DbTimestamp, File, SoftDeletes;
    use \App\Traits\UuidTrait {
        boot as traitBoot;
    }
    
    public const STATE_OPEN = "open";
    public const STATE_IN_PROGRESS = "in_progress";
    public const STATE_SUSPENDED = "suspended";
    public const STATE_CLOSED = "closed";
    
    public static $sortable = ["name", "created_at", "start_date", "end_date"];
    public static $defaultSortable = null;
    
    public function delete()
    {
        $notifications = Notification::where("object_id", $this->id)->where("type", "LIKE", "task:%")->get();
        if(!$notifications->isEmpty())
        {
            foreach($notifications as $notification)
            {
                $notification->delete();
                $sdo = new SoftDeletedObject;
                $sdo->source = "task";
                $sdo->source_id = $this->id;
                $sdo->object = "notification";
                $sdo->object_id = $notification->id;
                $sdo->save();
            }
        }
        return parent::delete();
    }
    
    public function scopeApiFields(Builder $query): void
    {
        $query->select("id", "name", "description", "project_id", "status_id", "priority", "start_date", "start_date_time", "end_date", "end_date_time", "due_date", "created_at", "state");
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
    
    public function getAssignedUsers()
    {
        $out = [];
        $userIds = TaskAssignedUser::where("task_id", $this->id)->pluck("user_id")->all();
        return User::byFirm()->whereIn("id", $userIds)->withTrashed()->get();
    }
    
    public function assignUsers($users)
    {
        if($users && is_array($users))
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
        else
            TaskAssignedUser::where("task_id", $this->id)->delete();
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
    
    private static $statuses = null;
    public function getStatusName($uuid = null)
    {
        if(static::$statuses == null)
        {
            static::$statuses = [];
            $statuses = $uuid === null ? Status::all() : Status::withoutGlobalScopes()->where("uuid", $uuid)->get();
            if(!$statuses->isEmpty())
            {
                foreach($statuses as $status)
                    static::$statuses[$status->id]= $status->name;
            }
        }
        
        if(!empty(static::$statuses[$this->status_id]))
            return static::$statuses[$this->status_id];
        
        return "-";
    }
    
    public function getCalendarDates()
    {
        return TaskCalendar::where("task_id", $this->id)->orderBy("date", "ASC")->get();
    }
    
    public function getStartDateTime()
    {
        if(!$this->start_date)
            return null;
        
        return $this->start_date . " " . ($this->start_date_time ? $this->start_date_time : TaskCalendar::getDefaultStartTime()) . ":00";
    }
    
    public function getEndDateTime()
    {
        if(!$this->start_date)
            return null;
        
        $endDate = $this->end_date ? $this->end_date : $this->start_date;
        return $endDate . " " . ($this->end_date_time ? $this->end_date_time : TaskCalendar::getDefaultEndTime()) . ":00";
    }
    
    public function getProject()
    {
        return Project::find($this->project_id);
    }
    
    public function canStart()
    {
        if($this->state == self::STATE_IN_PROGRESS)
            return false;
        return true;
    }
    
    public function start()
    {
        if(!$this->canStart())
            throw new InvalidStatus(__("The task is already in progress"));
        
        $status = Status::where("task_state", Status::TASK_STATE_IN_PROGRESS)->orderBy("is_default", "DESC")->first();
        if($status)
        {
            $this->status_id = $status->id;
            $this->save();
        }
    }
    
    public function canStop()
    {
        if($this->state !== self::STATE_IN_PROGRESS)
            return false;
        return true;
    }
    
    public function stop()
    {
        if(!$this->canStop())
            throw new InvalidStatus(__("The task is not in progress"));
        
        $status = Status::where("task_state", Status::TASK_STATE_IN_CLOSED)->orderBy("is_default", "DESC")->first();
        if($status)
        {
            $this->status_id = $status->id;
            $this->save();
        }
    }
    
    public function scopeAssignedList(Builder $query, $force = false): void
    {
        $user = Auth::user();
        if($force || (!$user->owner && $user->show_only_assigned_tasks))
        {
            $assignedTaskIds = TaskAssignedUser::select("task_id")->where("user_id", $user->id)->pluck("task_id")->all();
            $query->whereIn("id", $assignedTaskIds);
        }
    }
    
    public function hasAccess()
    {
        $user = Auth::user();
        if(!$user->owner && $user->show_only_assigned_tasks)
            return in_array($user->id, $this->getAssignedUserIds());
        
        return true;
    }
}
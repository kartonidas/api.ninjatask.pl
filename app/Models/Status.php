<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Models\Task;

class Status extends Model
{
    use \App\Traits\UuidTrait {
        boot as traitBoot;
    }
    
    public const TASK_STATE_OPEN = "open";
    public const TASK_STATE_IN_PROGRESS = "in_progress";
    public const TASK_STATE_IN_SUSPENDED = "suspended";
    public const TASK_STATE_IN_CLOSED = "closed";
    
    public static function getDefaultStatuses()
    {
        return [
            [__("New"), 1, 0, self::TASK_STATE_OPEN],
            [__("In progress"), 0, 0, self::TASK_STATE_IN_PROGRESS],
            [__("Done"), 0, 1, self::TASK_STATE_IN_CLOSED],
        ];
    }
    
    public static function getAllowedTaskStates()
    {
        return [
            self::TASK_STATE_OPEN => __("Open"),
            self::TASK_STATE_IN_PROGRESS => __("In progress"),
            self::TASK_STATE_IN_SUSPENDED => __("Suspended"),
            self::TASK_STATE_IN_CLOSED => __("Closed"),
        ];
    }
    
    public static function createDefaultStatuses($uuid)
    {
        foreach(self::getDefaultStatuses() as $status)
        {
            $row = new self;
            $row->uuid = $uuid;
            $row->name = $status[0];
            $row->is_default = $status[1];
            $row->close_task = $status[2];
            $row->set_when = $status[3];
            $row->saveQuietly();
        }
    }
    
    public function scopeApiFields(Builder $query): void
    {
        $query->select("id", "name", "is_default", "close_task", "task_state");
    }
    
    public function getTaskCount()
    {
        return Task::where("status_id", $this->id)->count();
    }
    
    public function canDelete()
    {
        if($this->is_default)
            return false;
        
        $cnt = Task::where("status_id", $this->id)->count();
        if($cnt > 0)
            return false;
        return true;
    }
    
    public function delete()
    {
        if($this->canDelete())
            return parent::delete();
    }
    
    public function isDefaultFlag()
    {
        if($this->is_default)
        {
            foreach(self::where("id", "!=", $this->id)->where("task_state", $this->task_state)->get() as $row)
            {
                $row->is_default = 0;
                $row->save();
            }
        }
    }
}
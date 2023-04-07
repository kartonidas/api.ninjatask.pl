<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\TaskTime;
use App\Traits\DbTimestamp;

class Task extends Model
{
    use DbTimestamp, SoftDeletes;
    use \App\Traits\UuidTrait {
        boot as traitBoot;
    }
    
    public function scopeApiFields(Builder $query): void
    {
        $query->select("id", "name", "description", "created_at");
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
}
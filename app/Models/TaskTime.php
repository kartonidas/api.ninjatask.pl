<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TaskTime extends Model
{
    const ACTIVE = "active";
    const PAUSED = "paused";
    const FINISHED = "finished";
    
    public function scopeApiFields(Builder $query): void
    {
        $query->select("id", "status", "task_id", "user_id", "started", "finished", "timer_started", "total", "comment", "billable");
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
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
}
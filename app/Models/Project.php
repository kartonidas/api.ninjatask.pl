<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Task;
use App\Traits\DbTimestamp;

class Project extends Model
{
    use DbTimestamp, SoftDeletes;
    use \App\Traits\UuidTrait {
        boot as traitBoot;
    }
    
    public function delete()
    {
        $tasks = Task::all();
        if(!$tasks->isEmpty())
        {
            foreach($tasks as $task)
                $task->delete();
        }
        return parent::delete();
    }
    
    public function scopeApiFields(Builder $query): void
    {
        $query->select("id", "name", "location", "description", "owner", "created_at");
    }
    
    public function getTaskCount()
    {
        $cntTotal = Task::where("project_id", $this->id)->count();
        $cntOpened = Task::where("project_id", $this->id)->where("completed", 0)->count();
        return [
            "total" => $cntTotal,
            "opened" => $cntOpened,
            "me" => 3
        ];
    }
}
<?php

namespace App\Observers;

use Illuminate\Support\Facades\Auth;
use App\Jobs\LimitsCalculate;
use App\Models\Project;
use App\Models\SoftDeletedObject;
use App\Models\Task;

class ProjectObserver
{
    public function created(Project $project): void
    {
        LimitsCalculate::dispatch($project->uuid);
    }
    
    public function deleted(Project $project): void
    {
        LimitsCalculate::dispatch($project->uuid);
    }
    
    function restored(Project $project): void
    {
        LimitsCalculate::dispatch($project->uuid);
        
        $taskToRestored = SoftDeletedObject
            ::where("source", "project")
            ->where("source_id", $project->id)
            ->where("object", "task")
            ->get();
            
        if(!$taskToRestored->isEmpty())
        {
            foreach($taskToRestored as $taskToRestore)
            {
                $task = Task::withoutGlobalScopes()->onlyTrashed()->where("id", $taskToRestore->object_id)->first();
                if($task)
                    $task->restore();
                    
                $taskToRestore->delete();
            }
        }
    }
}

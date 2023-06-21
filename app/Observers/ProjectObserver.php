<?php

namespace App\Observers;

use Illuminate\Support\Facades\Auth;
use App\Jobs\LimitsCalculate;
use App\Models\Project;
use App\Models\ProjectDeletedTask;
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
        
        $taskToRestored = ProjectDeletedTask::where("project_id", $project->id)->get();
        if(!$taskToRestored->isEmpty())
        {
            foreach($taskToRestored as $taskToRestore)
            {
                $task = Task::withoutGlobalScopes()->onlyTrashed()->where("id", $taskToRestore->task_id)->first();
                if($task)
                    $task->restore();
            }
            ProjectDeletedTask::where("project_id", $project->id)->delete();
        }
    }
}

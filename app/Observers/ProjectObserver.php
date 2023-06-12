<?php

namespace App\Observers;

use Illuminate\Support\Facades\Auth;
use App\Jobs\LimitsCalculate;
use App\Models\Project;

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
}

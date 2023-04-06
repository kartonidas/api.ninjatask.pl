<?php

namespace App\Observers;

use Illuminate\Support\Facades\Mail;
use App\Models\Task;
use App\Models\TaskTime;

class TaskTimeObserver
{
    public function created(TaskTime $taskTime): void
    {
        $this->calculateTotalTime($taskTime);
    }
    
    public function updated(TaskTime $taskTime): void
    {
        $this->calculateTotalTime($taskTime);
    }
    
    public function deleted(TaskTime $taskTime): void
    {
        $this->calculateTotalTime($taskTime);
    }
    
    private function calculateTotalTime(TaskTime $taskTime)
    {
        $task = Task::find($taskTime->task_id);
        if($task)
            $task->calculateTotalTime();
    }
}

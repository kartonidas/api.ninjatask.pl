<?php

namespace App\Observers;

use Illuminate\Support\Facades\Mail;
use App\Models\Task;
use App\Models\TaskTime;
use App\Models\TaskTimeDay;

class TaskTimeObserver
{
    public function created(TaskTime $taskTime): void
    {
        $this->calculateTotalTime($taskTime);
        $taskTime->splitTimeIntoDays();
    }
    
    public function updated(TaskTime $taskTime): void
    {
        $this->calculateTotalTime($taskTime);
        $taskTime->splitTimeIntoDays();
    }
    
    public function deleted(TaskTime $taskTime): void
    {
        $this->calculateTotalTime($taskTime);
        
        TaskTimeDay::where("task_time_id", $taskTime->id)->forceDelete();
    }
    
    private function calculateTotalTime(TaskTime $taskTime)
    {
        $task = Task::find($taskTime->task_id);
        if($task)
            $task->calculateTotalTime();
    }
}

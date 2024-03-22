<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

use App\Models\Status;
use App\Models\Task;

class PatchUpdateTaskState extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'patch:update-task-state';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set when on statuses';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $tasks = Task::withoutGlobalScope("uuid")->get();
        foreach($tasks as $task)
        {
            $status = Status::withoutGlobalScope("uuid")->find($task->status_id);
            if(!$status)
                throw new Exception("Brak statusu ID: " . $task->status_id);
            
            $task->state = $status->task_state;
            $task->saveQuietly();
        }
    }
}

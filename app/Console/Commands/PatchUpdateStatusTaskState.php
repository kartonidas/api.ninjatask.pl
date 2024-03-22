<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

use App\Models\Status;

class PatchUpdateStatusTaskState extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'patch:update-status-task-state';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update status task state column';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $statuses = Status::withoutGlobalScope("uuid")->get();
        foreach($statuses as $status)
        {
            if($status->name == "Nowy")
                $status->task_state = Status::TASK_STATE_OPEN;
                
            if($status->name == "W trakcie")
                $status->task_state = Status::TASK_STATE_IN_PROGRESS;
                
            if($status->name == "Zrobione")
                $status->task_state = Status::TASK_STATE_IN_CLOSED;
                
            $status->is_default = 1;
            $status->saveQuietly();
        }
    }
}

<?php

namespace App\Console\Commands;

use DateTime;
use Illuminate\Console\Command;
use App\Models\Task;

class RemoveDeletedTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:remove-deleted-tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pernamently removed deleted tasks';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $removeOlderThanDays = 14;
        
        $date = new DateTime();
        $date->modify("-$removeOlderThanDays day");
        $tasks = Task::withoutGlobalscopes()->where("deleted_at", "<=", $date->format("Y-m-d 00:00:00"))->onlyTrashed()->get();
        
        if(!$tasks->isEmpty())
        {
            foreach($tasks as $task)
                $task->forceDelete();
        }
    }
}

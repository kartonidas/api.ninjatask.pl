<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use App\Models\Task;

class RestoreTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:restore-task {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore soft deleted task';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $id = intval($this->argument("id"));
        $task = Task::withoutGlobalScopes()->withTrashed()->find($id);
        
        if(!$task)
            $this->output->writeln("<error>Task does not exists</error>");
        else
        {
            if(!$task->trashed())
                $this->output->writeln("<error>Currently task is not deleted</error>");
            else
            {
                $task->restore();
                $this->info("Task successfull restored!");        
            }
        }
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Project;

class RestoreProject extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:restore-project {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore soft deleted project';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $id = intval($this->argument("id"));
        $project = Project::withoutGlobalScopes()->withTrashed()->find($id);
        
        if(!$project)
            $this->output->writeln("<error>Project does not exists</error>");
        else
        {
            if(!$project->trashed())
                $this->output->writeln("<error>Currently project is not deleted</error>");
            else
            {
                $project->restore();
                $this->info("Project successfull restored!");        
            }
        }
    }
}

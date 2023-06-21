<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TaskTime;

class GenerateTaskTimeStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-task-time-stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $time = TaskTime::find(4);
        
        $time->splitTimeIntoDays();
    }
}

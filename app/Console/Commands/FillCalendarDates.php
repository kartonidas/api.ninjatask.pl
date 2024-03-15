<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Libraries\Templates\Rental as TemplateRental;
use App\Models\Task;
use App\Models\TaskCalendar;

class FillCalendarDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fill-calendar-dates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fill calendar task dates';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $tasks = Task::withoutGlobalScope("uuid")->get();
        foreach($tasks as $task)
        {
            echo $task->id;
            TaskCalendar::generateDates($task);
            echo "\n";
        }
    }
}
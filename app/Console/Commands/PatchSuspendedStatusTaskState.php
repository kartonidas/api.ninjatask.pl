<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

use App\Models\Status;

class PatchSuspendedStatusTaskState extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'patch:suspended-status-task-state';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make suspended task status';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $uuids = Status::withoutGlobalScope("uuid")->pluck("uuid")->all();
        $uuids = array_unique($uuids);
        foreach($uuids as $uuid)
        {
            $cnt = Status::withoutGlobalScope("uuid")->where("uuid", $uuid)->where("task_state", Status::TASK_STATE_IN_SUSPENDED)->count();
            if(!$cnt)
            {
                $s = new Status;
                $s->uuid = $uuid;
                $s->name = "Wstrzymane";
                $s->is_default = 1;
                $s->task_state = Status::TASK_STATE_IN_SUSPENDED;
                $s->saveQuietly();
            }
        }
    }
}

<?php

namespace App\Observers;

use Illuminate\Support\Facades\Mail;
use App\Models\Notification;
use App\Models\TaskAssignedUser;

class TaskAssignedUserObserver
{
    public function created(TaskAssignedUser $row): void
    {
        Notification::notify($row->user_id, $row->task_id, "task:assign");
    }
}

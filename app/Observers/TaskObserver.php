<?php

namespace App\Observers;

use Illuminate\Support\Facades\Mail;
use App\Models\Notification;
use App\Models\Task;
use App\Models\TaskAssignedUser;
use App\Models\TaskComment;
use App\Models\TaskTime;

class TaskObserver
{
    public function forceDeleting(Task $task): void
    {
        // Remove task attachments
        $attachments = $task->getAttachments(null, true);
        foreach($attachments as $attachment)
            $attachment->delete();
            
        // Remove comments assigned to task
        $comments = TaskComment::where("task_id", $task->id)->get();
        if(!$comments->isEmpty())
        {
            foreach($comments as $comment)
                $comment->delete(true);
        }
        
        // Remove logged times
        $times = TaskTime::where("task_id", $task->id)->get();
        if(!$times->isEmpty())
        {
            foreach($times as $time)
                $time->deleteQuietly();
        }
        
        // Remove assigned users
        $assignedUsers = TaskAssignedUser::where("task_id", $task->id)->get();
        if(!$assignedUsers->isEmpty())
        {
            foreach($assignedUsers as $assignedUser)
                $assignedUser->delete();
        }
    }
}

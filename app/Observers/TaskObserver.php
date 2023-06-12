<?php

namespace App\Observers;

use Illuminate\Support\Facades\Auth;
use App\Jobs\LimitsCalculate;
use App\Models\Notification;
use App\Models\Status;
use App\Models\Task;
use App\Models\TaskAssignedUser;
use App\Models\TaskComment;
use App\Models\TaskTime;

class TaskObserver
{
    public function created(Task $task): void
    {
        LimitsCalculate::dispatch($task->uuid);
    }
    
    public function deleted(Task $task): void
    {
        LimitsCalculate::dispatch($task->uuid);
    }
    
    public function updated(Task $task): void
    {
        if($task->isDirty("status_id") && !$task->completed)
        {
            $status = Status::find($task->status_id);
            if($status && $status->close_task)
            {
                $task->completed = 1;
                $task->completed_at = date("Y-m-d H:i:s");
                $task->saveQuietly();
            }
        }
        
        if($task->isDirty("status_id"))
        {
            if($task->created_user_id != Auth::user()->id)
                Notification::notify($task->created_user_id, Auth::user()->id, $task->id, "task:change_status_owner");
            
            $userIds = $task->getAssignedUserIds();
            foreach($userIds as $id)
            {
                if(Auth::user()->id != $id)
                    Notification::notify($id, Auth::user()->id, $task->id, "task:change_status_assigned");
            }
        }
    }
    
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
        
        $notifications = Notification::where("object_id", $this->id)->whereIn("type", ["task:assign"])->get();
        if(!$notifications->isEmpty())
        {
            foreach($notifications as $notification)
                $notification->delete();
        }
    }
}

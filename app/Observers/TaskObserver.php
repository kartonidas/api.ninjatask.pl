<?php

namespace App\Observers;

use Illuminate\Support\Facades\Auth;
use App\Jobs\LimitsCalculate;
use App\Models\Notification;
use App\Models\SoftDeletedObject;
use App\Models\Status;
use App\Models\Task;
use App\Models\TaskAssignedUser;
use App\Models\TaskCalendar;
use App\Models\TaskComment;
use App\Models\TaskTime;
use App\Models\TaskTimeDay;

class TaskObserver
{
    public function created(Task $task): void
    {
        LimitsCalculate::dispatch($task->uuid);
        TaskCalendar::generateDates($task);
    }
    
    public function deleted(Task $task): void
    {
        LimitsCalculate::dispatch($task->uuid);
        TaskTimeDay::where("task_id", $task->id)->delete();
        TaskCalendar::deleteDates($task);
    }
    
    public function updated(Task $task): void
    {
        if($task->isDirty("status_id"))
        {
            $status = Status::find($task->status_id);
            if(!$task->completed)
            {
                if($status && $status->close_task)
                    $task->completed = 1;
            }
            else
            {
                if($status && !$status->close_task)
                    $task->completed = 0;
            }
        }
        
        if($task->isDirty("completed"))
        {
            $task->completed_at = $task->completed ? date("Y-m-d H:i:s") : null;
            $task->saveQuietly();
        }
        
        if($task->isDirty("status_id"))
        {
            $notifyTaskAuthor = null;
            if($task->created_user_id != Auth::user()->id)
            {
                Notification::notify($task->created_user_id, Auth::user()->id, $task->id, "task:change_status_owner");
                $notifyTaskAuthor = $task->created_user_id;
            }
            
            $userIds = $task->getAssignedUserIds();
            foreach($userIds as $id)
            {
                if($notifyTaskAuthor && $notifyTaskAuthor == $id)
                    continue;
                
                if(Auth::user()->id != $id)
                    Notification::notify($id, Auth::user()->id, $task->id, "task:change_status_assigned");
            }
        }
        
        if($task->isDirty("start_date") || $task->isDirty("end_date"))
            TaskCalendar::generateDates($task);
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
        
        // Remove logged splitted times
        $times = TaskTimeDay::where("task_id", $task->id)->get();
        if(!$times->isEmpty())
        {
            foreach($times as $time)
                $time->forceDelete();
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
    
    function restored(Task $task): void
    {
        LimitsCalculate::dispatch($task->uuid);
        
        $notificationToRestored = SoftDeletedObject
            ::where("source", "task")
            ->where("source_id", $task->id)
            ->where("object", "notification")
            ->get();
            
        if(!$notificationToRestored->isEmpty())
        {
            foreach($notificationToRestored as $notificationToRestore)
            {
                $notification = Notification::withoutGlobalScopes()->onlyTrashed()->where("id", $notificationToRestore->object_id)->first();
                if($notification)
                    $notification->restore();
                    
                $notificationToRestore->delete();
            }
        }
        
        TaskTimeDay::where("task_id", $task->id)->restore();
    }
}

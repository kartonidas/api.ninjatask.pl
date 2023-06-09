<?php

namespace App\Observers;

use Illuminate\Support\Facades\Auth;
use App\Models\Notification;
use App\Models\Task;
use App\Models\TaskComment;

class TaskCommentObserver
{
    public function created(TaskComment $row): void
    {
        $task = Task::find($row->task_id);
        if($task)
        {
            if($task->created_user_id != Auth::user()->id)
                Notification::notify($task->created_user_id, Auth::user()->id, $row->id, "task:new_comment_owner");
            
            $userIds = $task->getAssignedUserIds();
            foreach($userIds as $id)
            {
                if(Auth::user()->id != $id)
                    Notification::notify($id, Auth::user()->id, $row->id, "task:new_comment_assigned");
            }
        }
    }
}

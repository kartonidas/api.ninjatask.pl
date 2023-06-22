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
            $notifyTaskAuthor = null;
            if($task->created_user_id != Auth::user()->id)
            {
                Notification::notify($task->created_user_id, Auth::user()->id, $task->id, "task:new_comment_owner", $row->id);
                $notifyTaskAuthor = $task->created_user_id;
            }
            
            $userIds = $task->getAssignedUserIds();
            foreach($userIds as $id)
            {
                if($notifyTaskAuthor && $notifyTaskAuthor == $id)
                    continue;
                
                if(Auth::user()->id != $id)
                    Notification::notify($id, Auth::user()->id, $task->id, "task:new_comment_assigned", $row->id);
            }
        }
    }
}

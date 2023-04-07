<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Exceptions\InvalidStatus;
use App\Exceptions\ObjectExist;
use App\Exceptions\ObjectNotExist;
use App\Models\User;
use App\Models\Task;
use App\Models\TaskComment;

class TaskCommentController extends Controller
{
    /**
    * Get task comments list
    *
    * Return task comments list.
    * @urlParam id integer required Task identifier.
    * @queryParam size integer Number of rows. Default: 50
    * @queryParam page integer Number of page (pagination). Default: 1
    * @response 200 {"total_rows": 100, "total_pages": "4", "current_page": 1, "has_more": true, "data": [{"id": 1, "comment": "Example comment", "user_id": 1, "created_at": "2020-01-01 10:00:00"}]}
    * @header Authorization: Bearer {TOKEN}
    * @group Task comments
    */
    public function list(Request $request, $id)
    {
        User::checkAccess("task:list");
        
        $task = Task::find($id);
        if(!$task)
            throw new ObjectNotExist(__("Task not exist"));
        
        $request->validate([
            "size" => "nullable|integer|gt:0",
            "page" => "nullable|integer|gt:0",
        ]);
        
        $size = $request->input("size", config("api.list.size"));
        $page = $request->input("page", 1);
        
        $users = TaskComment
            ::apiFields()
            ->where("task_id", $id)
            ->take($size)
            ->skip(($page-1)*$size)
            ->get();
            
        $total = TaskComment::where("task_id", $id)->count();
        $out = [
            "total_rows" => $total,
            "total_pages" => ceil($total / $size),
            "current_page" => $page,
            "has_more" => ceil($total / $size) > $page,
            "data" => $users,
        ];
            
        return $out;
    }
    
    /**
    * Get task comment details
    *
    * Return task comment details.
    * @urlParam id integer required Task identifier.
    * @urlParam cid integer required Comment identifier.
    * @response 200 {"id": 1, "comment": "Example comment", "user_id": "1", "created_at": "2020-01-01 10:00:00"}
    * @response 404 {"error":true,"message":"Comment does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group Task comments
    */
    public function get(Request $request, $taskId, $id)
    {
        User::checkAccess("task:list");
        
        $task = Task::find($taskId);
        if(!$task)
            throw new ObjectNotExist(__("Task not exist"));
        
        $comment = TaskComment::apiFields()->find($id);
        if(!$comment)
            throw new ObjectNotExist(__("Comment does not exist"));
        
        return $comment;
    }
    
    /**
    * Create new comment
    *
    * Create new comment.
    * @bodyParam id integer required Task identifier.
    * @bodyParam comment string required Comment.
    * @responseField id integer The id of the newly created comment
    * @header Authorization: Bearer {TOKEN}
    * @group Task comments
    */
    public function create(Request $request, $taskId)
    {
        User::checkAccess("task:list");
        
        $request->validate([
            "comment" => "required|max:10000",
        ]);
        
        $task = Task::find($taskId);
        if(!$task)
            throw new ObjectNotExist(__("Task not exist"));
        
        $comment = new TaskComment;
        $comment->task_id = $task->id;
        $comment->user_id = Auth::user()->id;
        $comment->comment = $request->input("comment");
        $comment->save();
        
        return $comment->id;
    }
    
    /**
    * Update comment
    *
    * Update comment.
    * @bodyParam id integer required Task identifier.
    * @urlParam cid integer required Comment identifier.
    * @bodyParam comment string comment.
    * @responseField status boolean Update status
    * @header Authorization: Bearer {TOKEN}
    * @group Task comments
    */
    public function update(Request $request, $taskId, $id)
    {
        User::checkAccess("task:list");
        
        $task = Task::find($taskId);
        if(!$task)
            throw new ObjectNotExist(__("Task does not exist"));
        
        $comment = TaskComment::apiFields()->find($id);
        if(!$comment)
            throw new ObjectNotExist(__("Comment does not exist"));
        
        $rules = [
            "comment" => "required|max:10000",
        ];
        
        $validate = [];
        $updateFields = ["comment"];
        foreach($updateFields as $field)
        {
            if($request->has($field))
            {
                if(!empty($rules[$field]))
                    $validate[$field] = $rules[$field];
            }
        }
        
        if(!empty($validate))
            $request->validate($validate);
        
        foreach($updateFields as $field)
        {
            if($request->has($field))
                $comment->{$field} = $request->input($field);
        }
        $comment->save();
        
        return true;
    }
    
    /**
    * Delete comment
    *
    * Delete comment.
    * @bodyParam id integer required Task identifier.
    * @urlParam cid integer required Comment identifier.
    * @responseField status boolean Delete status
    * @response 404 {"error":true,"message":"Task does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group Task comments
    */
    public function delete(Request $request, $taskId, $id)
    {
        User::checkAccess("task:list");
        
        $task = Task::find($taskId);
        if(!$task)
            throw new ObjectNotExist(__("Task does not exist"));
        
        $comment = TaskComment::apiFields()->find($id);
        if(!$comment)
            throw new ObjectNotExist(__("Comment does not exist"));
        
        $comment->delete();
        return true;
    }
}
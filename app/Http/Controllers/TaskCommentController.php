<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\File as RuleFile;
use App\Exceptions\AccessDenied;
use App\Exceptions\InvalidStatus;
use App\Exceptions\ObjectExist;
use App\Exceptions\ObjectNotExist;
use App\Models\File;
use App\Models\User;
use App\Models\Task;
use App\Models\TaskComment;
use App\Rules\Attachment;

class TaskCommentController extends Controller
{
    /**
    * Get task comments list
    *
    * Return task comments list.
    * @urlParam id integer required Task identifier.
    * @queryParam size integer Number of rows. Default: 50
    * @queryParam page integer Number of page (pagination). Default: 1
    * @response 200 {"total_rows": 100, "total_pages": "4", "current_page": 1, "has_more": true, "data": [{"id": 1, "comment": "Example comment", "user_id": 1, "created_at": "2020-01-01 10:00:00", "attachments": [{"id": 1, "user_id": 1, "type": "task_comments", "filename": "filename.ext", "orig_name": "filename.ext", "extension": "ext", "size": 100, "description": "Example description", "created_at": "2020-01-01 10:00:00", "base64": "Base64 encode file content"}], "can_delete": true, "user": "John Doe", "_me": true}]}
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
        
        $comments = TaskComment
            ::apiFields()
            ->where("task_id", $id)
            ->take($size)
            ->skip(($page-1)*$size)
            ->orderBy("created_at", "DESC")
            ->get();
            
        foreach($comments as $k => $comment)
        {
            $comments[$k]->attachments = $comment->getAttachments();
            $comments[$k]->can_delete = $comment->canDelete();
            $comments[$k]->user = $comment->getUserName();
            $comments[$k]->_me = $comment->user_id == Auth::user()->id;
        }
            
        $total = TaskComment::where("task_id", $id)->count();
        $out = [
            "total_rows" => $total,
            "total_pages" => ceil($total / $size),
            "current_page" => $page,
            "has_more" => ceil($total / $size) > $page,
            "data" => $comments,
        ];
            
        return $out;
    }
    
    /**
    * Load more comments
    *
    * Load more comments.
    * @urlParam id integer required Task identifier.
    * @urlParam lid integer required Last displayed comment ID.
    * @queryParam size integer Number of rows. Default: 50
    * @response 200 {"total_rows": 100, "total_pages": "4", "current_page": 1, "has_more": true, "data": [{"id": 1, "comment": "Example comment", "user_id": 1, "created_at": "2020-01-01 10:00:00", "attachments": [{"id": 1, "user_id": 1, "type": "task_comments", "filename": "filename.ext", "orig_name": "filename.ext", "extension": "ext", "size": 100, "description": "Example description", "created_at": "2020-01-01 10:00:00", "base64": "Base64 encode file content"}], "can_delete": true, "user": "John Doe", "_me": true}]}
    * @header Authorization: Bearer {TOKEN}
    * @group Task comments
    */
    public function loadMore(Request $request, $id, $lid)
    {
        User::checkAccess("task:list");
        
        $task = Task::find($id);
        if(!$task)
            throw new ObjectNotExist(__("Task not exist"));
        
        $request->validate([
            "size" => "nullable|integer|gt:0",
        ]);
        
        $size = $request->input("size", config("api.list.size")) + 1;
        $page = $request->input("page", 1);
        
        $comments = TaskComment
            ::apiFields()
            ->where("task_id", $id)
            ->where("id", "<", $lid)
            ->take($size)
            ->skip(($page-1)*$size)
            ->orderBy("created_at", "DESC")
            ->get();
            
        foreach($comments as $k => $comment)
        {
            $comments[$k]->attachments = $comment->getAttachments();
            $comments[$k]->can_delete = $comment->canDelete();
            $comments[$k]->user = $comment->getUserName();
            $comments[$k]->_me = $comment->user_id == Auth::user()->id;
        }
        
        $hasMore = false;
        if(count($comments) == $size)
        {
            $comments->pop();
            $hasMore = true;
        }
        $total = TaskComment::where("task_id", $id)->count();
        $out = [
            "total_rows" => $total,
            "total_pages" => ceil($total / $size),
            "current_page" => $page,
            "has_more" => $hasMore,
            "data" => $comments,
        ];
            
        return $out;
    }
    
    /**
    * Get task comment details
    *
    * Return task comment details.
    * @urlParam id integer required Task identifier.
    * @urlParam cid integer required Comment identifier.
    * @response 200 {"id": 1, "comment": "Example comment", "user_id": "1", "created_at": "2020-01-01 10:00:00", "attachments": [{"id": 1, "user_id": 1, "type": "task_comments", "filename": "filename.ext", "orig_name": "filename.ext", "extension": "ext", "size": 100, "description": "Example description", "created_at": "2020-01-01 10:00:00", "base64": "Base64 encode file content"}], "can_delete": true, "user" : "John Doe", "_me": true}
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
        
        $comment = TaskComment::where("task_id", $taskId)->apiFields()->find($id);
        if(!$comment)
            throw new ObjectNotExist(__("Comment does not exist"));
        
        $comment->attachments = $comment->getAttachments();
        $comment->can_delete = $comment->canDelete();
        $comment->user = $comment->getUserName();
        $comment->_me = $comment->user_id == Auth::user()->id;
        
        return $comment;
    }
    
    /**
    * Create new comment
    *
    * Create new comment.
    * @bodyParam id integer required Task identifier.
    * @bodyParam comment string required Comment.
    * @bodyParam attachments Array of files attach to task ([{"name": "File name", "base64": Base64 encoded file, "description": "Optional file description"}])
    * @responseField id integer The id of the newly created comment
    * @header Authorization: Bearer {TOKEN}
    * @group Task comments
    */
    public function create(Request $request, $taskId)
    {
        User::checkAccess("task:list");
        
        $request->validate([
            "comment" => "required|max:10000",
            "attachments" => ["nullable", "array", new Attachment],
        ]);
        
        $task = Task::find($taskId);
        if(!$task)
            throw new ObjectNotExist(__("Task not exist"));
        
        $comment = new TaskComment;
        $comment->task_id = $task->id;
        $comment->user_id = Auth::user()->id;
        $comment->comment = $request->input("comment");
        $comment->save();
        
        if(!empty($request->input("attachments", [])))
            $comment->upload($request->input("attachments"));
        
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
        
        $comment = TaskComment::where("task_id", $taskId)->apiFields()->find($id);
        if(!$comment)
            throw new ObjectNotExist(__("Comment does not exist"));
        
        if($comment->user_id != Auth::user()->id)
            throw new AccessDenied(__("Access denied"));
        
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
        
        $comment = TaskComment::where("task_id", $taskId)->apiFields()->find($id);
        if(!$comment)
            throw new ObjectNotExist(__("Comment does not exist"));
        
        if(!$comment->canDelete())
            throw new AccessDenied(__("Access denied"));
        
        $comment->delete();
        return true;
    }
    
    /**
    * Get attachment from comment
    *
    * Get attachment from comment.
    * @urlParam id integer required Task identifier.
    * @urlParam cid integer required Comment identifier.
    * @urlParam aid integer required Attachment identifier.
    * @response 200 {"id": 1, "user_id": 1, "type": "task_comments", "filename": "filename.ext", "orig_name": "filename.ext", "extension": "ext", "size": 100, "description": "Example description", "created_at": "2020-01-01 10:00:00", "base64": "Base64 encode file content"}
    * @response 404 {"error":true,"message":"Task does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group Task comments
    */
    public function getAttachment(Request $request, $taskId, $commentId, $id)
    {
        User::checkAccess("task:list");
        
        $task = Task::find($taskId);
        if(!$task)
            throw new ObjectNotExist(__("Task does not exist"));
        
        $comment = TaskComment::where("task_id", $taskId)->apiFields()->find($commentId);
        if(!$comment)
            throw new ObjectNotExist(__("Comment does not exist"));
        
        $file = File::where("type", $comment->getTable())->where("object_id", $comment->id)->apiFields()->find($id);
        if(!$file)
            throw new ObjectNotExist(__("Attachment does not exist"));
        
        if(!$file->fileExists())
            throw new ObjectNotExist(__("File does not exist"));
        
        $file->base64 = $file->getBase64();
        return $file;
    }
    
    /**
    * Add attachment to comment
    *
    * Add attachment to comment.
    * @urlParam id integer required Task identifier.
    * @urlParam cid integer required Comment identifier.
    * @responseField status boolean Add attachment status
    * @bodyParam name string required File name.
    * @bodyParam file string required Base64 encode file content".
    * @bodyParam description string Description".
    * @response 404 {"error":true,"message":"Task does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group Task comments
    */
    public function addAttachment(Request $request, $taskId, $id)
    {
        User::checkAccess("task:update");
        
        $task = Task::find($taskId);
        if(!$task)
            throw new ObjectNotExist(__("Task does not exist"));
        
        $comment = TaskComment::where("task_id", $taskId)->apiFields()->find($id);
        if(!$comment)
            throw new ObjectNotExist(__("Comment does not exist"));
        
        $allowedMimeTypes = config("api.upload.allowed_mime_types");
        $request->validate([
            "file" => [
                "required",
                RuleFile::types($allowedMimeTypes)
            ],
            "description" => "nullable|max:2000",
        ]);
        
        $toUpload = [
            "file" => $request->file("file"),
            "description" => $request->input("description", "")
        ];
        $comment->upload([$toUpload]);
        return true;
    }
    
    /**
    * Remove attachment from comment
    *
    * Remove attachment from comment.
    * @urlParam id integer required Task identifier.
    * @urlParam cid integer required Comment identifier.
    * @urlParam aid integer required Attachment identifier.
    * @responseField status boolean Remove attachment status
    * @response 404 {"error":true,"message":"Task does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group Task comments
    */
    public function removeAttachment(Request $request, $taskId, $commentId, $id)
    {
        User::checkAccess("task:update");
        
        $task = Task::find($taskId);
        if(!$task)
            throw new ObjectNotExist(__("Task does not exist"));
        
        $comment = TaskComment::where("task_id", $taskId)->apiFields()->find($commentId);
        if(!$comment)
            throw new ObjectNotExist(__("Comment does not exist"));
        
        $file = File::where("type", $comment->getTable())->where("object_id", $comment->id)->apiFields()->find($id);
        if(!$file)
            throw new ObjectNotExist(__("Attachment does not exist"));
        
        $file->delete();
        
        return true;
    }
}
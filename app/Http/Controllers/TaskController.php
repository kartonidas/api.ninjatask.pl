<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Exceptions\InvalidStatus;
use App\Exceptions\ObjectExist;
use App\Exceptions\ObjectNotExist;
use App\Models\Project;
use App\Models\User;
use App\Models\Task;
use App\Models\TaskAssignedUser;

class TaskController extends Controller
{
    /**
    * Get tasks list
    *
    * Return tasks list assigned to project.
    * @urlParam id integer required Project identifier.
    * @queryParam size integer Number of rows. Default: 50
    * @queryParam page integer Number of page (pagination). Default: 1
    * @response 200 {"total_rows": 100, "total_pages": "4", "current_page": 1, "has_more": true, "data": [{"id": 1, "name": "Example task", "description": "Example description", "created_at" => "2020-01-01 10:00:00", "assigned_to" => [1,2]}]}
    * @header Authorization: Bearer {TOKEN}
    * @group Tasks
    */
    public function list(Request $request, $id)
    {
        User::checkAccess("task:list");
        
        $project = Project::find($id);
        if(!$project)
            throw new ObjectNotExist(__("Project not exist"));
        
        $request->validate([
            "size" => "nullable|integer|gt:0",
            "page" => "nullable|integer|gt:0",
        ]);
        
        $size = $request->input("size", config("api.list.size"));
        $page = $request->input("page", 1);
        
        $tasks = Task
            ::apiFields()
            ->where("project_id", $id)
            ->take($size)
            ->skip(($page-1)*$size)
            ->get();
        
        foreach($tasks as $k => $task)
            $tasks[$k]->assigned_to = $task->getAssignedUserIds();
        
        $total = Task::where("project_id", $id)->count();
        $out = [
            "total_rows" => $total,
            "total_pages" => ceil($total / $size),
            "current_page" => $page,
            "has_more" => ceil($total / $size) > $page,
            "data" => $tasks,
        ];
            
        return $out;
    }
    
    /**
    * Get task details
    *
    * Return task details.
    * @urlParam id integer required Task identifier.
    * @response 200 {"id": 1, "name": "Example task", "description": "Example description", "created_at" => "2020-01-01 10:00:00", "assigned_to" => [1,2]}
    * @response 404 {"error":true,"message":"Task does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group Tasks
    */
    public function get(Request $request, $id)
    {
        User::checkAccess("task:list");
        
        $task = Task::apiFields()->find($id);
        if(!$task)
            throw new ObjectNotExist(__("Task does not exist"));
        
        $task->assigned_to = $task->getAssignedUserIds();
        
        return $task;
    }
    
    /**
    * Create new task
    *
    * Create new task.
    * @bodyParam project_id integer required Project identifier.
    * @bodyParam name string required Task name.
    * @bodyParam description string Task description.
    * @bodyParam users integer Array of users identifier assigned to task.
    * @responseField id integer The id of the newly created task
    * @header Authorization: Bearer {TOKEN}
    * @group Tasks
    */
    public function create(Request $request)
    {
        User::checkAccess("task:create");
        
        $request->validate([
            "project_id" => "required|integer",
            "name" => "required|max:250",
            "description" => "nullable|max:5000",
            "users" => ["nullable", "array", Rule::in($this->getAllowedUserIds())],
        ]);
        
        $project = Project::find($request->input("project_id"));
        if(!$project)
            throw new ObjectNotExist(__("Project not exist"));
        
        $task = new Task;
        $task->project_id = $project->id;
        $task->name = $request->input("name");
        $task->description = $request->input("description", "");
        $task->created_user_id = Auth::user()->id;
        $task->save();
        
        if($request->input("users"))
            $task->assignUsers($request->input("users"));
        
        return $task->id;
    }
    
    /**
    * Update task
    *
    * Update task.
    * @urlParam id integer required Task identifier.
    * @bodyParam name string Task name.
    * @bodyParam description string Task description.
    * @bodyParam users integer Array of users identifier assigned to task.
    * @responseField status boolean Update status
    * @header Authorization: Bearer {TOKEN}
    * @group Tasks
    */
    public function update(Request $request, $id)
    {
        User::checkAccess("task:update");
        
        $task = Task::find($id);
        if(!$task)
            throw new ObjectNotExist(__("Task does not exist"));
        
        $rules = [
            "name" => "required|max:250",
            "description" => "nullable|max:5000",
            "users" => ["nullable", "array", Rule::in($this->getAllowedUserIds())],
        ];
        
        $validate = [];
        $updateFields = ["name", "description"];
        foreach($updateFields as $field)
        {
            if($request->has($field))
            {
                if(!empty($rules[$field]))
                    $validate[$field] = $rules[$field];
            }
        }
        
        if($request->has("users"))
            $validate["users"] = $rules["users"];
        
        if(!empty($validate))
            $request->validate($validate);
        
        foreach($updateFields as $field)
        {
            if($request->has($field))
                $task->{$field} = $request->input($field);
        }
        $task->save();
        
        if($request->has("users"))
            $task->assignUsers($request->input("users", []));
        
        return true;
    }
    
    /**
    * Delete task
    *
    * Delete task.
    * @urlParam id integer required Task identifier.
    * @responseField status boolean Delete status
    * @response 404 {"error":true,"message":"Task does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group Tasks
    */
    public function delete(Request $request, $id)
    {
        User::checkAccess("task:delete");
        
        $task = Task::find($id);
        if(!$task)
            throw new ObjectNotExist(__("Task does not exist"));
        
        $task->delete();
        return true;
    }
    
    /**
    * Assign user to task
    *
    * Assign user to task.
    * @urlParam id integer required Task identifier.
    * @bodyParam user_id integer required User identifier.
    * @responseField status boolean Assign status
    * @response 404 {"error":true,"message":"Task does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group Tasks
    */
    public function assignUser(Request $request, $id)
    {
        User::checkAccess("task:update");
        
        $task = Task::find($id);
        if(!$task)
            throw new ObjectNotExist(__("Task does not exist"));
        
        $request->validate([
            "user_id" => "required|integer",
        ]);
        
        $user = User::byFirm()->find($request->input("user_id"));
        if(!$user)
            throw new ObjectNotExist(__("User does not exist"));
        
        if(TaskAssignedUser::where("task_id", $task->id)->where("user_id", $user->id)->count())
            throw new ObjectExist(__("User is currently assigned to task"));
        
        $assign = new TaskAssignedUser;
        $assign->task_id = $task->id;
        $assign->user_id = $user->id;
        $assign->save();
        
        return true;
    }
    
    /**
    * Deassign user from task
    *
    * Deassign user from task.
    * @urlParam id integer required Task identifier.
    * @bodyParam user_id integer required User identifier.
    * @responseField status boolean Deassign status
    * @response 404 {"error":true,"message":"Task does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group Tasks
    */
    public function deAssignUser(Request $request, $id)
    {
        User::checkAccess("task:update");
        
        $task = Task::find($id);
        if(!$task)
            throw new ObjectNotExist(__("Task does not exist"));
        
        $request->validate([
            "user_id" => "required|integer",
        ]);
        
        $user = User::byFirm()->find($request->input("user_id"));
        if(!$user)
            throw new ObjectNotExist(__("User does not exist"));
        
        if(!TaskAssignedUser::where("task_id", $task->id)->where("user_id", $user->id)->count())
            throw new ObjectNotExist(__("User is not currently assigned to task"));
        
        TaskAssignedUser::where("task_id", $task->id)->where("user_id", $user->id)->delete();
        return true;
    }
    
    private function getAllowedUserIds()
    {
        $userIds = [];
        $users = User::byFirm()->get();
        foreach($users as $user)
            $userIds[] = $user->id;
        return $userIds;
    }
}
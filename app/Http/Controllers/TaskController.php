<?php

namespace App\Http\Controllers;

use App\Exceptions\ObjectNotExist;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\Project;
use App\Models\User;
use App\Models\Task;

class TaskController extends Controller
{
    /**
    * Get tasks list
    *
    * Return tasks list assigned to project.
    * @urlParam id integer required Project identifier.
    * @queryParam size integer Number of rows. Default: 50
    * @queryParam page integer Number of page (pagination). Default: 1
    * @response 200 {"total_rows": 100, "total_pages": "4", "current_page": 1, "has_more": true, "data": [{"id": 1, "name": "Test project", "location": "Warsaw", "description": "", "owner": "john@doe.com"}]}
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
        
        $users = Task
            ::apiFields()
            ->where("project_id", $id)
            ->take($size)
            ->skip(($page-1)*$size)
            ->get();
            
        $total = Task::where("project_id", $id)->count();
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
    * Get task details
    *
    * Return task details.
    * @urlParam id integer required Project identifier.
    * @response 200 {"id": 1, "name": "Example task"}
    * @response 404 {"error":true,"message":"Project does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group Tasks
    */
    public function get(Request $request, $id)
    {
        User::checkAccess("task:list");
        
        $task = Task::apiFields()->find($id);
        if(!$task)
            throw new ObjectNotExist(__("Task does not exist"));
        
        return $task;
    }
    
    /**
    * Create new task
    *
    * Create new task.
    * @bodyParam project_id integer required Project identifier.
    * @bodyParam name string required Task name.
    * @bodyParam description string Task description.
    * @responseField id integer The id of the newly created project
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
        ]);
        
        $project = Project::find($request->input("project_id"));
        if(!$project)
            throw new ObjectNotExist(__("Project not exist"));
        
        $task = new Task;
        $task->project_id = $project->id;
        $task->name = $request->input("name");
        $task->description = $request->input("description", "");
        $task->save();
        
        return $task->id;
    }
    
    /**
    * Update task
    *
    * Update task.
    * @urlParam id integer required Task identifier.
    * @bodyParam name string Task name.
    * @bodyParam description string Project description.
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
        
        if(!empty($validate))
            $request->validate($validate);
        
        foreach($updateFields as $field)
        {
            if($request->has($field))
                $task->{$field} = $request->input($field);
        }
        $task->save();
        
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
    * Start task timer
    *
    * Start task timer (log time).
    * @urlParam id integer required Task identifier.
    * @responseField status boolean Status
    * @response 404 {"error":true,"message":"Task does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group Tasks
    */
    public function start(Request $request, $id)
    {
    }
    
    /**
    * Stop task timer
    *
    * Stop task timer (log time).
    * @urlParam id integer required Task identifier.
    * @responseField status boolean Status
    * @response 404 {"error":true,"message":"Task does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group Tasks
    */
    public function stop(Request $request, $id)
    {
    }
    
    /**
    * Log task spend time
    *
    * Log task spend time.
    * @urlParam id integer required Task identifier.
    * @bodyParam start integer required Start time.
    * @bodyParam end integer required Start end.
    * @responseField status boolean Status
    * @response 404 {"error":true,"message":"Task does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group Tasks
    */
    public function logTime(Request $request, $id)
    {
    }
}
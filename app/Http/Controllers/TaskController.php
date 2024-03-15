<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File as RuleFile;
use Illuminate\Validation\ValidationException;
use App\Exceptions\Exception;
use App\Exceptions\AccessDenied;
use App\Exceptions\InvalidStatus;
use App\Exceptions\ObjectExist;
use App\Exceptions\ObjectNotExist;
use App\Http\Requests\CalendarRequest;
use App\Libraries\Data;
use App\Models\Customer;
use App\Models\File;
use App\Models\Project;
use App\Models\Status;
use App\Models\Subscription;
use App\Models\Task;
use App\Models\TaskAssignedUser;
use App\Models\TaskCalendar;
use App\Models\User;
use App\Rules\Attachment;
use App\Traits\Sortable;

class TaskController extends Controller
{
    use Sortable;
    
    /**
    * Get tasks list
    *
    * Return tasks list assigned to project.
    * @urlParam id integer required Project identifier.
    * @queryParam size integer Number of rows. Default: 50
    * @queryParam page integer Number of page (pagination). Default: 1
    * @queryParam query string Search task by name Default: "task name"
    * @queryParam users integer Search task by assigned usrs. Default: [2]
    * @queryParam status integer Search task by task status identifier. Default: 1
    * @queryParam priority integer Search task by task priority. Default: 1
    * @queryParam state string Search task by task state (one of: opened, closed). Default: opened
    * @response 200 {"total_rows": 100, "total_pages": "4", "current_page": 1, "has_more": true, "data": [{"id": "1", "name": "Example task", "description": "Example description", "project_id": 1, "priority" : 2, "status_id": 2, "status": "To do", "start_date" : "Y-m-d", "due_date" : "Y-m-d", "created_at": "2020-01-01 10:00:00", "assigned_to": [1,2], "attachments": [{"id": 1, "user_id": 1, "type": "tasks", "filename": "filename.ext", "orig_name": "filename.ext", "extension": "ext", "size": 100, "description": "Example description", "created_at": "2020-01-01 10:00:00", "base64": "Base64 encode file content"}], "timer": {"state": "active", "total": 250, "total_logged": 1000}, "completed": 1, "status": "Done"}], "project_name": "Project name"}
    * @header Authorization: Bearer {TOKEN}
    * @group Tasks
    */
    public function list(Request $request, $id)
    {
        User::checkAccess("task:list");
        return $this->_getTask($request, "project", $id);
    }
    
    public function listCustomer(Request $request, $id)
    {
        User::checkAccess("task:list");
        return $this->_getTask($request, "customer", $id);
    }
    
    private function _getTask(Request $request, $source = "project", $id)
    {
        if(!in_array($source, ["project", "customer"]))
            throw new Exception(__("Invalid type"));
        
        $request->validate([
            "size" => "nullable|integer|gt:0",
            "page" => "nullable|integer|gt:0",
            "query" => "nullable|max:200",
            "users" => ["nullable", "array"],
            "status" => ["nullable", "integer", Rule::in($this->getAllowedStatuses())],
            "priority" => ["nullable", "integer", Rule::in([1,2,3])],
            "state" => ["nullable", Rule::in(["opened", "closed"])],
            "name" => "nullable|max:200",
            "created_from" => "nullable|date_format:Y-m-d",
            "created_to" => "nullable|date_format:Y-m-d",
        ]);
        
        $size = $request->input("size", config("api.list.size"));
        $page = $request->input("page", 1);
        
        $searchQuery = $request->input("query", null);
        $searchUsers = $request->input("users", null);
        $searchStatus = $request->input("status", null);
        $searchPriority = $request->input("priority", null);
        $searchState = $request->input("state", null);
        $searchName = $request->input("name", null);
        $searchCreatedAtFrom = $request->input("created_from", null);
        $searchCreatedAtTo = $request->input("created_to", null);

        if($source == "project")
        {
            $project = Project::find($id);
            if(!$project)
                throw new ObjectNotExist(__("Project not exist"));
            
            $tasks = Task::apiFields()->where("project_id", $id);
        }
        else
        {
            $customer = Customer::find($id);
            if(!$customer)
                throw new ObjectNotExist(__("Customer not exist"));
            
            $projectIds = Project::where("customer_id", $customer->id)->pluck("id")->all();
            $tasks = Task::apiFields()->whereIn("project_id", $projectIds);
        }
            
        if($searchStatus)
            $tasks->where("status_id", $searchStatus);
        if($searchPriority)
            $tasks->where("priority", $searchPriority);
        if($searchState)
            $tasks->where("completed", $searchState == "opened" ? 0 : 1);
        if($searchUsers)
        {
            $taskAddignedIds = [-1];
            $assigned = TaskAssignedUser::select("task_id")->whereIn("user_id", $searchUsers)->get();
            if(!$assigned->isEmpty())
            {
                foreach($assigned as $a)
                    $taskAddignedIds[] = $a->task_id;
            }
            $taskAddignedIds = array_unique($taskAddignedIds);
            $tasks->whereIn("id", $taskAddignedIds);
        }
        if($searchQuery)
        {
            $tasks->where(function($q) use($searchQuery) {
                $q
                    ->where("name", "LIKE", "%" . $searchQuery . "%")
                    ->orWhere("description", "LIKE", "%" . $searchQuery . "%");
            });
        }
        if($searchName)
            $tasks->where("name", "LIKE", "%" . $searchName . "%");
        if($searchCreatedAtFrom)
            $tasks->whereDate("created_at", ">=", $searchCreatedAtFrom);
        if($searchCreatedAtTo)
            $tasks->whereDate("created_at", "<=", $searchCreatedAtTo);
        
        $total = $tasks->count();
        
        $tasks = $tasks->take($size)->skip(($page-1)*$size);
            
        $orderBy = $this->getOrderBy($request, Task::class, null);
        if(!empty($orderBy[0]))
            $tasks->orderBy($orderBy[0], $orderBy[1]);
        else
        {
            $tasks
                ->orderBy("completed", "ASC")
                ->orderBy("priority", "DESC")
                ->orderBy("updated_at", "DESC");
        }
        $tasks = $tasks->get();
        
        foreach($tasks as $k => $task)
        {
            $tasks[$k]->assigned_to = $task->getAssignedUserIds();
            $tasks[$k]->attachments = $task->getAttachments();
            $tasks[$k]->timer = $task->getActiveTaskTime();
            $tasks[$k]->completed = $task->completed == 1;
            $tasks[$k]->status = $task->getStatusName();
        }
        
        $out = [
            "total_rows" => $total,
            "total_pages" => ceil($total / $size),
            "current_page" => $page,
            "has_more" => ceil($total / $size) > $page,
            "data" => $tasks,
            "project_name" => isset($project) ? $project->name : null,
        ];
            
        return $out;
    }
    
    /**
    * Get task details
    *
    * Return task details.
    * @urlParam id integer required Task identifier.
    * @response 200 {"id": 1, "name": "Example task", "description": "Example description", "project_id": 1, "priority" : 2, "status_id": 2, "status": "To do", "created_at": "2020-01-01 10:00:00", "assigned_to": [1,2], "attachments": [{"id": 1, "user_id": 1, "type": "tasks", "filename": "filename.ext", "orig_name": "filename.ext", "extension": "ext", "size": 100, "description": "Example description", "created_at": "2020-01-01 10:00:00", "base64": "Base64 encode file content"}], "timer": {"state": "active", "total": 250, "total_logged": 1000}, "completed": 1, "status": "Done", "project_name": "Project name", "start_date" : "Y-m-d", "due_date" : "Y-m-d"}
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
        $task->attachments = $task->getAttachments();
        $task->timer = $task->getActiveTaskTime();
        $task->completed = $task->completed == 1;
        $task->status = $task->getStatusName();
        
        $project = Project::find($task->project_id);
        $task->project_name = $project ? $project->name : "";
        
        return $task;
    }
    
    /**
    * Create new task
    *
    * Create new task.
    * @bodyParam project_id integer required Project identifier.
    * @bodyParam status_id integer Status identifier.
    * @bodyParam name string required Task name.
    * @bodyParam description string Task description.
    * @bodyParam users array Array of users identifier assigned to task.
    * @bodyParam attachments array Array of files attach to task ([{"name": "File name", "base64": Base64 encoded file, "description": "Optional file description"}])
    * @bodyParam priority integer Task priority (one of: 1, 2, 3).
    * @bodyParam start_date date Task start date (format: Y-m-d)
    * @bodyParam start_date_time string Task start time (format: HH:MM)
    * @bodyParam end_date date Task end date (format: Y-m-d)
    * @bodyParam end_date_time string Task end time (format: HH:MM)
    * @bodyParam due_date date Task due date (format: Y-m-d)
    * @responseField id integer The id of the newly created task
    * @header Authorization: Bearer {TOKEN}
    * @group Tasks
    */
    public function create(Request $request)
    {
        User::checkAccess("task:create");
        Subscription::checkPackage("task");
        
        self::prepareSelfAssignedId($request);
        
        $request->validate([
            "project_id" => "required|integer",
            "name" => "required|max:250",
            "description" => "nullable|max:5000",
            "users" => ["nullable", "array", Rule::in($this->getAllowedUserIds())],
            "attachments" => ["nullable", "array", new Attachment],
            "priority" => ["nullable", Rule::in(array_keys(config("api.tasks.priority")))],
            "status_id" => ["nullable", "numeric", Rule::in($this->getAllowedStatuses())],
            "start_date" => ["nullable", "date_format:Y-m-d"],
            "start_date_time" => ["nullable", Rule::in(Data::getAllowedTimes())],
            "end_date" => ["nullable", "date_format:Y-m-d"],
            "end_date_time" => ["nullable", Rule::in(Data::getAllowedTimes())],
            "due_date" => ["nullable", "date_format:Y-m-d"],
        ]);
        
        $project = Project::find($request->input("project_id"));
        if(!$project)
            throw new ObjectNotExist(__("Project not exist"));
        
        $task = new Task;
        $task->project_id = $project->id;
        $task->name = $request->input("name");
        $task->description = $request->input("description", "");
        $task->created_user_id = Auth::user()->id;
        $task->priority = intval($request->input("priority", 2));
        $task->status_id = intval($request->input("status_id"));
        $task->start_date = $request->input("start_date");
        $task->start_date_time = $request->input("start_date_time");
        $task->end_date = $request->input("end_date");
        $task->end_date_time = $request->input("end_date_time");
        $task->due_date = $request->input("due_date");
        $task->save();
        
        if(!empty($request->input("attachments", [])))
            $task->upload($request->input("attachments"));
        
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
    * @bodyParam status_id integer Status identifier.
    * @bodyParam description string Task description.
    * @bodyParam users integer Array of users identifier assigned to task.
    * @bodyParam priority integer Task priority (one of: 1, 2, 3).
    * @bodyParam start_date date Task start date (format: Y-m-d)
    * @bodyParam start_date_time string Task start time (format: HH:MM)
    * @bodyParam end_date date Task end date (format: Y-m-d)
    * @bodyParam end_date_time string Task end time (format: HH:MM)
    * @bodyParam due_date date Task due date (format: Y-m-d)
    * @responseField status boolean Update status
    * @header Authorization: Bearer {TOKEN}
    * @group Tasks
    */
    public function update(Request $request, $id)
    {
        $task = Task::find($id);
        
        try {
            User::checkAccess("task:update");
        } catch(AccessDenied $e) {
            if($task && !in_array(Auth::user()->id, $task->getAssignedUserIds()))
                throw $e;
        }
        
        self::prepareSelfAssignedId($request);
        
        if(!$task)
            throw new ObjectNotExist(__("Task does not exist"));
        
        $rules = [
            "name" => "required|max:250",
            "description" => "nullable|max:5000",
            "users" => ["nullable", "array", Rule::in($this->getAllowedUserIds($id))],
            "priority" => ["nullable", Rule::in(array_keys(config("api.tasks.priority")))],
            "status_id" => ["nullable", "numeric", Rule::in($this->getAllowedStatuses())],
            "start_date" => ["nullable", "date_format:Y-m-d"],
            "start_date_time" => ["nullable", Rule::in(Data::getAllowedTimes())],
            "end_date" => ["nullable", "date_format:Y-m-d"],
            "end_date_time" => ["nullable", Rule::in(Data::getAllowedTimes())],
            "due_date" => ["nullable", "date_format:Y-m-d"],
        ];
        
        $validate = [];
        $updateFields = ["name", "description", "priority", "status_id", "start_date", "start_date_time", "end_date", "end_date_time", "due_date"];
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
    
    /**
    * Get attachment from task
    *
    * Get attachment from task.
    * @urlParam id integer required Task identifier.
    * @urlParam aid integer required Attachment identifier.
    * @response 200 {"id": 1, "user_id": 1, "type": "tasks", "filename": "filename.ext", "orig_name": "filename.ext", "extension": "ext", "size": 100, "description": "Example description", "created_at": "2020-01-01 10:00:00", "base64": "Base64 encode file content"}
    * @response 404 {"error":true,"message":"Task does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group Tasks
    */
    public function getAttachment(Request $request, $taskId, $id)
    {
        User::checkAccess("task:list");
        
        $task = Task::find($taskId);
        if(!$task)
            throw new ObjectNotExist(__("Task does not exist"));
        
        $file = File::where("type", $task->getTable())->where("object_id", $task->id)->apiFields()->find($id);
        if(!$file)
            throw new ObjectNotExist(__("Attachment does not exist"));
        
        if(!$file->fileExists())
            throw new ObjectNotExist(__("File does not exist"));
        
        $file->base64 = $file->getBase64();
        return $file;
    }
    
    /**
    * Add attachment to task
    *
    * Add attachment to task.
    * @urlParam id integer required Task identifier.
    * @responseField status boolean Add attachment status
    * @bodyParam name string required File name.
    * @bodyParam base64 string required Base64 encode file content".
    * @bodyParam description string Description".
    * @response 404 {"error":true,"message":"Task does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group Tasks
    */
    public function addAttachment(Request $request, $id)
    {
        User::checkAccess("task:update");
        
        $task = Task::find($id);
        if(!$task)
            throw new ObjectNotExist(__("Task does not exist"));
        
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
        
        $id = $task->uploadSingle($toUpload);
        return $id;
    }
    
    /**
    * Remove attachment from task
    *
    * Remove attachment from task.
    * @urlParam id integer required Task identifier.
    * @urlParam aid integer required Attachment identifier.
    * @responseField status boolean Remove attachment status
    * @response 404 {"error":true,"message":"Task does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group Tasks
    */
    public function removeAttachment(Request $request, $taskId, $id)
    {
        User::checkAccess("task:update");
        
        $task = Task::find($taskId);
        if(!$task)
            throw new ObjectNotExist(__("Task does not exist"));
        
        $file = File::where("type", $task->getTable())->where("object_id", $task->id)->find($id);
        if(!$file)
            throw new ObjectNotExist(__("Attachment does not exist"));
        
        $file->delete();
        
        return true;
    }
    
    /**
    * Get task allowed users
    *
    * Get task allowed users ready to assigned.
    * @urlParam id integer optional Task identifier.
    * @response 200 [{"id":2,"firstname":"John","lastname":"Doe","email":"john.doe@gmail.com","_me":true,"_allowed":true,"_check":false}]
    * @header Authorization: Bearer {TOKEN}
    * @group Tasks
    */
    public function getAllowedUsers(Request $request, $taskId = 0)
    {
        User::checkAccess("task:list");
        
        return $this->getAllowedUsersList($taskId, false);
        
        $currentAssignedUsers = [];
        if($taskId)
        {
            $task = Task::find($taskId);
            if($task)
                $currentAssignedUsers = $task->getAssignedUserIds();
        }
        
        $out = [];
        $users = User::withTrashed()->byFirm()->where("activated", 1)->orderBy("lastname", "ASC")->orderBy("firstname", "ASC")->get();
        foreach($users as $user)
        {
            $out[] = [
                "id" => $user->id,
                "firstname" => $user->firstname,
                "lastname" => $user->lastname,
                "email" => $user->email,
                "_me" => $user->id == Auth::user()->id,
                "_allowed" => !$user->trashed(),
                "_check" => in_array($user->id, $currentAssignedUsers),
            ];
        }
        
        return $out;
    }
    
    /**
    * Close task
    *
    * Set task as closed.
    * @urlParam id integer optional Task identifier.
    * @responseField status boolean Update status
    * @header Authorization: Bearer {TOKEN}
    * @group Tasks
    */
    public function close(Request $request, $id = 0)
    {
        $task = Task::find($id);
        try {
            User::checkAccess("task:update");
        } catch(AccessDenied $e) {
            if($task && !in_array(Auth::user()->id, $task->getAssignedUserIds()))
                throw $e;
        }
        
        if(!$task)
            throw new ObjectNotExist(__("Task does not exist"));
        
        if($task->completed)
            throw new InvalidStatus(__("The task is currently closed"));
        
        $task->completed = 1;
        $task->completed_at = date("Y-m-d H:i:s");
        $task->save();
        
        return true;
    }
    
    /**
    * Open task
    *
    * Set task as opened.
    * @urlParam id integer optional Task identifier.
    * @responseField status boolean Update status
    * @header Authorization: Bearer {TOKEN}
    * @group Tasks
    */
    public function open(Request $request, $id = 0)
    {
        $task = Task::find($id);
        try {
            User::checkAccess("task:update");
        } catch(AccessDenied $e) {
            if($task && !in_array(Auth::user()->id, $task->getAssignedUserIds()))
                throw $e;
        }
        
        if(!$task)
            throw new ObjectNotExist(__("Task does not exist"));
        
        if(!$task->completed)
            throw new InvalidStatus(__("The task is currently opened"));
        
        $task->completed = 0;
        $task->save();
        
        return true;
    }
    
    /**
    * My work
    *
    * Get logged user opened and assigned tasks.
    * @response 200 {"total_rows": 100, "total_pages": "4", "current_page": 1, "has_more": true, "data": [{"id": "1", "name": "Example task", "description": "Example description", "project_id": 1, "priority" : 2, "created_at": "2020-01-01 10:00:00", "assigned_to": [1,2], "attachments": [{"id": 1, "user_id": 1, "type": "tasks", "filename": "filename.ext", "orig_name": "filename.ext", "extension": "ext", "size": 100, "description": "Example description", "created_at": "2020-01-01 10:00:00", "base64": "Base64 encode file content"}], "timer": {"state": "active", "total": 250, "total_logged": 1000}, "completed": 1, "status": "Done"}]}
    * @header Authorization: Bearer {TOKEN}
    * @group Tasks
    */
    public function myWork(Request $request)
    {
        $request->validate([
            "size" => "nullable|integer|gt:0",
            "page" => "nullable|integer|gt:0",
        ]);
        
        $size = $request->input("size", config("api.list.size"));
        $page = $request->input("page", 1);
        
        $taskIds = [-1];
        $assignedTasks = TaskAssignedUser::select("task_id")->where("user_id", Auth::user()->id)->get();
        if(!$assignedTasks->isEmpty())
        {
            foreach($assignedTasks as $row)
                $taskIds[] = $row->task_id;
        }

        $tasks = Task
            ::apiFields()
            ->whereIn("id", $taskIds)
            ->where("completed", 0);
            
        $total = $tasks->count();
        
        $tasks = $tasks->take($size)
            ->skip(($page-1)*$size)
            ->orderBy("priority", "DESC")
            ->orderBy("updated_at", "desc")
            ->get();
        
        foreach($tasks as $k => $task)
        {
            $tasks[$k]->assigned_to = $task->getAssignedUserIds();
            $tasks[$k]->attachments = $task->getAttachments();
            $tasks[$k]->timer = $task->getActiveTaskTime();
            $tasks[$k]->completed = $task->completed == 1;
            $tasks[$k]->status = $task->getStatusName();
        }
        
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
    * Return total logged time
    *
    * Return total logged time
    * @responseField status int Total logged time in seconds
    * @response 404 {"error":true,"message":"Task does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group Tasks
    */
    public function time(Request $request, $id)
    {
        User::checkAccess("task:list");
        
        $task = Task::select("total")->find($id);
        if(!$task)
            throw new ObjectNotExist(__("Task does not exist"));
        
        return $task->total;
    }
    
    private function getAllowedUserIds($taskId = null)
    {
        $users = self::getAllowedUsersList($taskId);
        foreach($users as $user)
            $userIds[] = $user["id"];
        return $userIds;
    }
    
    private function getAllowedUsersList($taskId = null, $skipDeleted = true)
    {
        $currentAssignedUsers = [];
        if($taskId)
        {
            $task = Task::find($taskId);
            if($task)
                $currentAssignedUsers = $task->getAssignedUserIds();
        }
        
        $out = [];
        $users = User::withTrashed()->byFirm()->where("activated", 1)->orderBy("lastname", "ASC")->orderBy("firstname", "ASC")->get();
        foreach($users as $user)
        {
            if($skipDeleted && !in_array($user->id, $currentAssignedUsers) && $user->trashed())
                continue;
            
            $out[] = [
                "id" => $user->id,
                "firstname" => $user->firstname,
                "lastname" => $user->lastname,
                "email" => $user->email,
                "_me" => $user->id == Auth::user()->id,
                "_allowed" => !$user->trashed(),
                "_check" => in_array($user->id, $currentAssignedUsers),
            ];
        }
        
        return $out;
    }
    
    private function getAllowedStatuses()
    {
        $ids = [];
        $statuses = Status::all();
        if(!$statuses->isEmpty())
        {
            foreach($statuses as $status)
                $ids[] = $status->id;
        }
        return $ids;
    }
    
    // user_id=-1 oznacza przypisanie zadania na siebie
    private static function prepareSelfAssignedId(Request $request) {
        if($request->has("users") && is_array($request->input("users"))) {
            $users = $request->input("users");
            foreach($users as $i => $user) {
                if($user == -1)
                    $users[$i] = Auth::user()->id;
            }
            
            $users = array_unique($users);
            $request->merge(["users" => $users]);
        }
    }
    
    public function calendar(CalendarRequest $request)
    {
        User::checkAccess("task:list");
        
        $validated = $request->validated();
        
        $taskIds = TaskCalendar::whereBetween("date", [$validated["date_from"], $validated["date_to"]]);
        if(!User::checkAccess("user:list", false))
            $taskIds->whereIn("task_id", Auth::user()->getAssignedTaskIds());
        else
        {
            if(!empty($validated["user_id"]))
            {
                $user = User::byFirm()->apiFields()->find($validated["user_id"]);
                if(!$user)
                    throw new Exception("User does not exists");
                
                $taskIds->whereIn("task_id", $user->getAssignedTaskIds());
            }
        }
        
        $taskIds = $taskIds->pluck("task_id")->all();
        $taskIds = array_unique($taskIds);
        
        $tasks = Task::whereIn("id", $taskIds)->get();
        
        $result = [];
        if(!$tasks->isEmpty())
        {
            foreach($tasks as $task)
            {
                $placeInfo = [];
                $place = $task->project_id ? Project::find($task->project_id) : null;
                if($place)
                {
                    $placeInfo = [
                        "id" => $place->id,
                        "name" => $place->name,
                    ];
                    
                    $customer = $place->customer()->first();
                    if($customer)
                    {
                        $placeInfo["customer"] = [
                            "id" => $customer->id,
                            "name" => $customer->name,
                        ];
                    }
                }
                
                $result[] = [
                    "id" => $task->id,
                    "start" => $task->getStartDateTime(),
                    "end" => $task->getEndDateTime(),
                    "title" => $task->name,
                    "description" => $task->description,
                    "place" => $placeInfo,
                    "assigned" => self::getAllowedUsersList($task->id)
                ];
            }
        }
        
        return $result;
    }
}
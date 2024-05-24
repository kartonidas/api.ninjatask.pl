<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File as RuleFile;
use Illuminate\Validation\ValidationException;

use Stevebauman\Purify\Facades\Purify;

use App\Exceptions\Exception;
use App\Exceptions\AccessDenied;
use App\Exceptions\InvalidStatus;
use App\Exceptions\ObjectExist;
use App\Exceptions\ObjectNotExist;
use App\Http\Requests\CalendarRequest;
use App\Http\Requests\TaskStoreRequest;
use App\Http\Requests\TaskUpdateRequest;
use App\Libraries\Data;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\CustomerInvoiceItem;
use App\Models\File;
use App\Models\Project;
use App\Models\Status;
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
    * @response 200 {"total_rows": 100, "total_pages": "4", "current_page": 1, "has_more": true, "data": [{"id": "1", "name": "Example task", "description": "Example description", "project_id": 1, "priority" : 2, "status_id": 2, "status": "To do", "start_date" : "Y-m-d", "due_date" : "Y-m-d", "created_at": "2020-01-01 10:00:00", "assigned_to": [1,2], "attachments": [{"id": 1, "user_id": 1, "type": "tasks", "filename": "filename.ext", "orig_name": "filename.ext", "extension": "ext", "size": 100, "description": "Example description", "created_at": "2020-01-01 10:00:00", "base64": "Base64 encode file content"}], "timer": {"state": "active", "total": 250, "total_logged": 1000}, "status": "Done"}], "project_name": "Project name"}
    * @header Authorization: Bearer {TOKEN}
    * @group Tasks
    */
    public function list(Request $request, $id)
    {
        return $this->_getTask($request, "project", $id);
    }
    
    public function listCustomer(Request $request, $id)
    {
        return $this->_getTask($request, "customer", $id);
    }
    
    public function listAll(Request $request)
    {
        return $this->_getTask($request, "all", null);
    }
    
    private function _getTask(Request $request, $source = "project", $id)
    {
        User::checkAccess("task:list");
        
        if(!in_array($source, ["project", "customer", "all"]))
            throw new Exception(__("Invalid type"));
        
        $request->validate([
            "size" => "nullable|integer|gt:0",
            "page" => "nullable|integer|gt:0",
            "query" => "nullable|max:200",
            "users" => ["nullable", "array"],
            "status" => ["nullable"],
            "priority" => ["nullable", "integer", Rule::in([1,2,3])],
            "name" => "nullable|max:200",
            "project_id" => "nullable|integer",
            "customer_id" => "nullable|integer",
            "created_from" => "nullable|date_format:Y-m-d",
            "created_to" => "nullable|date_format:Y-m-d",
            "start_date_from" => "nullable|date_format:Y-m-d",
            "start_date_to" => "nullable|date_format:Y-m-d",
            "settled" => "nullable|integer"
        ]);
        
        $size = $request->input("size", config("api.list.size"));
        $page = $request->input("page", 1);
        
        $searchQuery = $request->input("query", null);
        $searchUsers = $request->input("users", null);
        $searchStatus = $request->input("status", null);
        $searchPriority = $request->input("priority", null);
        $searchState = $request->input("state", null);
        $searchName = $request->input("name", null);
        $searchProject = $request->input("project_id", null);
        $searchCustomer = $request->input("customer_id", null);
        $searchCreatedAtFrom = $request->input("created_from", null);
        $searchCreatedAtTo = $request->input("created_to", null);
        $searchStartDateFrom = $request->input("start_date_from", null);
        $searchStartDateTo = $request->input("start_date_to", null);
        $settled = $request->input("settled", null);

        $tasks = Task::assignedList()->apiFields();
        if($source == "project")
        {
            $project = Project::find($id);
            if(!$project)
                throw new ObjectNotExist(__("Project not exist"));
            
            $tasks->where("project_id", $id);
        }
        elseif($source == "customer")
        {
            $customer = Customer::find($id);
            if(!$customer)
                throw new ObjectNotExist(__("Customer not exist"));
            
            $projectIds = Project::where("customer_id", $customer->id)->pluck("id")->all();
            $tasks->whereIn("project_id", $projectIds);
        }
            
        if($searchStatus)
        {
            if($searchStatus == "opened")
            {
                $openedStatuses = Status::where("task_state", "!=", Status::TASK_STATE_IN_CLOSED)->pluck("id")->all();
                $tasks->whereIn("status_id", $openedStatuses);
            }
            else
                $tasks->where("status_id", $searchStatus);
        }
        if($searchPriority)
            $tasks->where("priority", $searchPriority);
        if($searchUsers)
        {
            if(in_array("_not_assigned", $searchUsers))
            {
                $taskAddignedIds = TaskAssignedUser::pluck("task_id")->all();
                $taskAddignedIds = array_unique($taskAddignedIds);
                $tasks->whereNotIn("id", $taskAddignedIds);
            }
            else
            {
                $taskAddignedIds = TaskAssignedUser::whereIn("user_id", $searchUsers)->pluck("task_id")->all();
                $taskAddignedIds = array_unique($taskAddignedIds);
                $tasks->whereIn("id", $taskAddignedIds);
            }
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
        if($searchStartDateFrom)
            $tasks->whereDate("start_date", ">=", $searchStartDateFrom);
        if($searchStartDateTo)
            $tasks->whereDate("start_date", "<=", $searchStartDateTo);
        if($searchProject)
            $tasks->where("project_id", $searchProject);
        if($searchCustomer)
        {
            $placeIds = Project::where("customer_id", $searchCustomer)->pluck("id")->all();
            $tasks->whereIn("project_id", $placeIds);
        }
        if($settled === "0" || $settled === "1")
        {
            $customerInvoiceIds = CustomerInvoice::pluck("id")->all();
            $invoicedTaskIds = CustomerInvoiceItem::whereIn("customer_invoice_id", $customerInvoiceIds)->where("task_id", ">", 0)->pluck("task_id")->all();
            if($settled === "0")
                $tasks->whereNotIn("id", $invoicedTaskIds);
            else
                $tasks->whereIn("id", $invoicedTaskIds);
        }
        
        $total = $tasks->count();
        
        $tasks = $tasks->take($size)->skip(($page-1)*$size);
            
        $orderBy = $this->getOrderBy($request, Task::class, null);
        if(!empty($orderBy[0]))
            $tasks->orderBy($orderBy[0], $orderBy[1]);
        else
        {
            $tasks
                ->orderByRaw("CASE WHEN state = '" . Task::STATE_CLOSED . "' THEN 0 ELSE 1 END DESC")
                ->orderBy("priority", "DESC")
                ->orderBy("updated_at", "DESC");
        }
        $tasks = $tasks->get();
        
        foreach($tasks as $k => $task)
        {
            $tasks[$k]->assigned_to = $task->getAssignedUserIds();
            $tasks[$k]->assigned_users = $task->getAssignedUsers();
            $tasks[$k]->attachments = $task->getAttachments();
            $tasks[$k]->timer = $task->getActiveTaskTime();
            $tasks[$k]->status = $task->getStatusName();
            $tasks[$k]->place = $task->getProject();
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
    * @response 200 {"id": 1, "name": "Example task", "description": "Example description", "project_id": 1, "priority" : 2, "status_id": 2, "status": "To do", "created_at": "2020-01-01 10:00:00", "assigned_to": [1,2], "attachments": [{"id": 1, "user_id": 1, "type": "tasks", "filename": "filename.ext", "orig_name": "filename.ext", "extension": "ext", "size": 100, "description": "Example description", "created_at": "2020-01-01 10:00:00", "base64": "Base64 encode file content"}], "timer": {"state": "active", "total": 250, "total_logged": 1000}, "status": "Done", "project_name": "Project name", "start_date" : "Y-m-d", "due_date" : "Y-m-d"}
    * @response 404 {"error":true,"message":"Task does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group Tasks
    */
    public function get(Request $request, $id)
    {
        User::checkAccess("task:list");
        
        $task = Task::apiFields()->find($id);
        if(!$task || !$task->hasAccess())
            throw new ObjectNotExist(__("Task does not exist"));
        
        $task->assigned_to = $task->getAssignedUserIds();
        $task->attachments = $task->getAttachments();
        $task->timer = $task->getActiveTaskTime();
        $task->status = $task->getStatusName();
        $task->place = $task->getProject();
        $task->customer = $task->getCustomer();
        $task->customer_id = $task->place ? $task->place->customer_id : null;
        $task->can_start = $task->canStart();
        $task->can_stop = $task->canStop();
        $task->can_suspend = $task->canSuspend();
        $task->can_resume = $task->canResume();
        
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
    public function create(TaskStoreRequest $request)
    {
        User::checkAccess("task:create");
        
        $validated = $request->validated();
        $task = DB::transaction(function () use($validated) {
            $customer = self::getOrCreateCustomer($validated);
            $project = self::getOrCreateProject($validated, $customer ? $customer->id : null);
            if(!$project)
                throw new ObjectNotExist(__("Project does not exist"));
            
            $task = new Task;
            $task->project_id = $project->id;
            $task->customer_id = $customer ? $customer->id : null;
            $task->name = $validated["name"];
            $task->description = Purify::clean($validated["description"] ?? "");
            $task->created_user_id = Auth::user()->id;
            $task->priority = intval($validated["priority"] ?? 2);
            $task->status_id = intval($validated["status_id"] ?? null);
            $task->start_date = $validated["start_date"] ?? null;
            $task->start_date_time = $validated["start_date_time"] ?? null;
            $task->end_date = $validated["end_date"] ?? null;
            $task->end_date_time = $validated["end_date_time"] ?? null;
            $task->due_date = $validated["due_date"] ?? null;
            $task->cost_gross = $validated["cost_gross"] ?? null;
            $this->validateDates($task);
            $task->save();
            
            if(!empty($validated["attachments"]))
                $task->upload($validated["attachments"]);
            
            if(!empty($validated["users"]))
                $task->assignUsers($validated["users"]);
                
            return $task;
        });
        
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
    public function update(TaskUpdateRequest $request, $id)
    {
        User::checkAccess("task:update");
        
        $task = Task::find($id);
        if(!$task || !$task->hasAccess())
            throw new ObjectNotExist(__("Task does not exist"));
        
        if(!$task)
            throw new ObjectNotExist(__("Task does not exist"));
        
        $validated = $request->validated();
        $task = DB::transaction(function () use($task, $validated) {
            $customerId = $task->customer_id;
            if(!empty($validated["customer"]["new_customer"]) || !empty($validated["customer"]["id"]))
            {
                $customer = self::getOrCreateCustomer($validated);
                $customerId = $customer ? $customer->id : null;
            }
                
            $projectId = $task->project_id;
            $project = Project::find($projectId);
            if(!empty($validated["project"]["new_project"]) || !empty($validated["project"]["id"]))
            {
                $project = self::getOrCreateProject($validated, $customerId);
                $projectId = $project ? $project->id : null;
            }
                
            if(!$project)
                throw new ObjectNotExist(__("Project does not exist"));
            
            if($project->customer_id != $customerId)
                throw new ObjectNotExist(__("Invalid customer ID"));
            
            $task->project_id = $projectId;
            $task->customer_id = $customerId;
            
            foreach($validated as $field => $value)
            {
                if(!Schema::hasColumn($task->getTable(), $field))
                    continue;
                
                $value = $field == "description" ? Purify::clean($value) : $value;
                $task->{$field} = $value;
            }
            $this->validateDates($task);
            $task->save();
            
            if(!empty($validated["users"]))
                $task->assignUsers($validated["users"]);
                
            return $task;
        });
        
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
        if(!$task || !$task->hasAccess())
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
        if(!$task || !$task->hasAccess())
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
        if(!$task || !$task->hasAccess())
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
        if(!$task || !$task->hasAccess())
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
        if(!$task || !$task->hasAccess())
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
        if(!$task || !$task->hasAccess())
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
        return Task::getAllowedUsersList($taskId, false);
    }
    
    /**
    * My work
    *
    * Get logged user opened and assigned tasks.
    * @response 200 {"total_rows": 100, "total_pages": "4", "current_page": 1, "has_more": true, "data": [{"id": "1", "name": "Example task", "description": "Example description", "project_id": 1, "priority" : 2, "created_at": "2020-01-01 10:00:00", "assigned_to": [1,2], "attachments": [{"id": 1, "user_id": 1, "type": "tasks", "filename": "filename.ext", "orig_name": "filename.ext", "extension": "ext", "size": 100, "description": "Example description", "created_at": "2020-01-01 10:00:00", "base64": "Base64 encode file content"}], "timer": {"state": "active", "total": 250, "total_logged": 1000}, "status": "Done"}]}
    * @header Authorization: Bearer {TOKEN}
    * @group Tasks
    */
    public function myWork(Request $request)
    {
        User::checkAccess("task:list");
        
        $request->validate([
            "size" => "nullable|integer|gt:0",
            "page" => "nullable|integer|gt:0",
        ]);
        
        $size = $request->input("size", config("api.list.size"));
        $page = $request->input("page", 1);
        
        $tasks = Task
            ::apiFields()
            ->assignedList(true)
            ->where("state", "!=", Task::STATE_CLOSED);
            
        $total = $tasks->count();
        
        $tasks = $tasks->take($size)->skip(($page-1)*$size);
        $orderBy = $this->getOrderBy($request, Task::class, null);
        if(!empty($orderBy[0]))
            $tasks->orderBy($orderBy[0], $orderBy[1]);
        else
        {
            $tasks
                ->orderBy("priority", "DESC")
                ->orderBy("updated_at", "desc");
        }
        $tasks = $tasks->get();
        
        foreach($tasks as $k => $task)
        {
            $tasks[$k]->assigned_to = $task->getAssignedUserIds();
            $tasks[$k]->assigned_users = $task->getAssignedUsers();
            $tasks[$k]->attachments = $task->getAttachments();
            $tasks[$k]->timer = $task->getActiveTaskTime();
            $tasks[$k]->status = $task->getStatusName();
            $tasks[$k]->place = $task->getProject();
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
        if(!$task || !$task->hasAccess())
            throw new ObjectNotExist(__("Task does not exist"));
        
        return $task->total;
    }
    
    public function calendar(CalendarRequest $request)
    {
        User::checkAccess("task:list");
        
        $validated = $request->validated();
        
        $taskIds = TaskCalendar::whereBetween("date", [$validated["date_from"], $validated["date_to"]]);
        if(!User::checkAccess("user:list", false) || Auth::user()->show_only_assigned_tasks)
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
                    "assigned" => Task::getAllowedUsersList($task->id)
                ];
            }
        }
        
        return $result;
    }
    
    public function updatePriority(Request $request, $id)
    {
        User::checkAccess("task:update");
        
        $task = Task::find($id);
        if(!$task || !$task->hasAccess())
            throw new ObjectNotExist(__("Task does not exist"));
        
        $request->validate([
            "priority" => ["required", "numeric", Rule::in(array_keys(Task::getAllowedPriorities()))],
        ]);
        
        $task->priority = $request->input("priority");
        $task->save();
    }
    
    public function updateStatus(Request $request, $id)
    {
        $task = Task::find($id);
        if(!$task || !$task->hasAccess())
            throw new ObjectNotExist(__("Task does not exist"));
        
        $request->validate([
            "status_id" => ["required", "numeric", Rule::in(Status::getAllowedStatuses())],
        ]);
        
        $task->status_id = $request->input("status_id");
        $task->save();
    }
    
    public function start(Request $request, $id)
    {
        $task = Task::find($id);
        if(!$task || !$task->hasAccess())
            throw new ObjectNotExist(__("Task does not exist"));
        
        $task->start();
        return true;
    }
    
    public function stop(Request $request, $id)
    {
        $task = Task::find($id);
        if(!$task || !$task->hasAccess())
            throw new ObjectNotExist(__("Task does not exist"));
        
        $task->stop();
        return true;
    }
    
    public function suspend(Request $request, $id)
    {
        $task = Task::find($id);
        if(!$task || !$task->hasAccess())
            throw new ObjectNotExist(__("Task does not exist"));
        
        $task->suspend();
        return true;
    }
    
    public function resume(Request $request, $id)
    {
        $task = Task::find($id);
        if(!$task || !$task->hasAccess())
            throw new ObjectNotExist(__("Task does not exist"));
        
        $task->resume();
        return true;
    }
    
    private function validateDates(Task $task)
    {
        if($task->start_date && $task->end_date)
        {
            $startDate = $task->start_date;
            $startDate = $startDate . ($task->start_date_time ? (" " . $task->start_date_time . ":00") : " 00:00:00");
            
            $endDate = $task->end_date;
            $endDate = $endDate . ($task->end_date_time ? (" " . $task->end_date_time . ":59") : " 23:59:59");
            
            if(strtotime($startDate) > strtotime($endDate))
                throw new Exception(__("The end date must be greater than or equal to the start date"));
        }
    }
    
    private static function getOrCreateCustomer($data) : Customer|null
    {
        $customerId = null;
        if(empty($data["customer"]["new_customer"]))
        {
            if(!empty($data["customer"]["id"]))
            {
                $customer = Customer::find($data["customer"]["id"]);
                if(!$customer)
                    throw new ObjectNotExist(__("Customer not exist"));
                
                return $customer;
            }
        }
        else
        {
            $customer = new Customer;
            $customer->type = $data["customer"]["type"];
            $customer->name = $data["customer"]["name"];
            $customer->street = $data["customer"]["street"] ?? "";
            $customer->house_no = $data["customer"]["house_no"] ?? "";
            $customer->apartment_no = $data["customer"]["apartment_no"] ?? "";
            $customer->city = $data["customer"]["city"] ?? "";
            $customer->zip = $data["customer"]["zip"] ?? "";
            $customer->country = $data["customer"]["country"] ?? "";
            $customer->nip = $data["customer"]["nip"] ?? "";
            $customer->save();
            
            if(!empty($data["contacts"]))
                $customer->updateContact($data["contacts"]);
            
            return $customer;
        }
        
        return null;
    }
    
    private static function getOrCreateProject($data, $customerId = null) : Project|null
    {
        $projectId = null;
        if(empty($data["project"]["new_project"]))
        {
            $project = null;
            if(!empty($data["project_id"]))
                $project = Project::find($data["project_id"]);
            else
            {
                if(!empty($data["project"]["id"]))
                    $project = Project::find($data["project"]["id"]);
            }
            
            if(!$project || ($project->customer_id && $project->customer_id != $customerId))
                throw new ObjectNotExist(__("Project not exist"));
            
            if(!$project->customer_id && $customerId)
            {
                $project->customer_id = $customerId;
                $project->save();
            }
            
            return $project;
        }
        else
        {
            $project = new Project;
            $project->customer_id = $customerId;
            $project->name = $data["project"]["name"];
            $project->address = $data["project"]["address"] ?? "";
            $project->description = $data["project"]["description"] ?? "";
            $project->location = $data["project"]["location"] ?? "";
            $project->lat = $data["project"]["lat"] ?? null;
            $project->lon = $data["project"]["lon"] ?? null;
            $project->save();
            
            return $project;
        }
        
        return null;
    }
}
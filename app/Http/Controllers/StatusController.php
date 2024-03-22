<?php

namespace App\Http\Controllers;

use App\Exceptions\ObjectNotExist;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Models\Status;
use App\Models\User;

class StatusController extends Controller
{
    /**
    * Get task statuses list
    *
    * Return task statuses list.
    * @queryParam size integer Number of rows. Default: 50
    * @queryParam page integer Number of page (pagination). Default: 1
    * @response 200 {"total_rows": 100, "total_pages": "4", "current_page": 1, "has_more": true, "data": [{"id": 1, "name": "Test project", "is_default": 1, "close_task": 0, "can_delete": 1}]}]}
    * @header Authorization: Bearer {TOKEN}
    * @group Statuses
    */
    public function list(Request $request)
    {
        User::checkAccess("status:list");
        
        $request->validate([
            "size" => "nullable|integer|gt:0",
            "page" => "nullable|integer|gt:0",
        ]);
        
        $size = $request->input("size", config("api.list.size"));
        $page = $request->input("page", 1);
        
        $statuses = Status
            ::apiFields()
            ->take($size)
            ->skip(($page-1)*$size)
            ->orderBy("is_default", "DESC")
            ->orderBy("name", "ASC")
            ->get();
            
        $statusesSortByTaskStateTmp = [];
        foreach($statuses as $k => $status)
        {
            $count = $status->getTaskCount();
            $statuses[$k]->tasks = $count;
            $statuses[$k]->can_delete = $status->canDelete();
            
            $statusesSortByTaskStateTmp[$status->task_state][] = $status->toArray();
        }
        
        $statusesSortByTaskState = [];
        foreach(Status::getAllowedTaskStates() as $state => $tmp)
        {
            if(!empty($statusesSortByTaskStateTmp[$state]))
                $statusesSortByTaskState = array_merge($statusesSortByTaskState, $statusesSortByTaskStateTmp[$state]);
        }
        
        $total = Status::count();
        $out = [
            "total_rows" => $total,
            "total_pages" => ceil($total / $size),
            "current_page" => $page,
            "has_more" => ceil($total / $size) > $page,
            "data" => $statusesSortByTaskState,
        ];
            
        return $out;
    }
    
    /**
    * Get task status details
    *
    * Return task status details.
    * @urlParam id integer required Status identifier.
    * @response 200 {"id": 1, "name": "Test project", "is_default": 1, "close_task": 0, "can_delete": 1}]}
    * @response 404 {"error":true,"message":"Status does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group Statuses
    */
    public function get(Request $request, $id)
    {
        User::checkAccess("status:list");
        
        $status = Status::apiFields()->find($id);
        if(!$status)
            throw new ObjectNotExist(__("Status does not exist"));
        
        $count = $status->getTaskCount();
        $status->tasks = $count;
        $status->can_delete = $status->canDelete();
        
        return $status;
    }
    
    /**
    * Create new status
    *
    * Create new status.
    * @bodyParam name string required Status name.
    * @bodyParam is_default boolean Set default status.
    * @bodyParam close_task boolean Setting the status will close the task.
    * @responseField id integer The id of the newly created status
    * @header Authorization: Bearer {TOKEN}
    * @group Statuses
    */
    public function create(Request $request)
    {
        User::checkAccess("status:create");
        
        $request->validate([
            "name" => "required|max:250",
            "is_default" => "nullable",
            "close_task" => "nullable",
            "task_state" => ["required", Rule::in(array_keys(Status::getAllowedTaskStates()))],
        ]);
        
        $status = new Status;
        $status->name = $request->input("name");
        $status->is_default = $request->input("is_default", "");
        $status->close_task = $request->input("close_task", "");
        $status->task_state = $request->input("task_state");
        $status->save();
        
        $status->isDefaultFlag();
        
        return $status->id;
    }
    
    /**
    * Update task status
    *
    * Update task status.
    * @urlParam id integer required Status identifier.
    * @bodyParam name string Status name.
    * @bodyParam is_default boolean Set default status.
    * @bodyParam close_task boolean Setting the status will close the task.
    * @responseField status boolean Update status
    * @header Authorization: Bearer {TOKEN}
    * @group Statuses
    */
    public function update(Request $request, $id)
    {
        User::checkAccess("status:update");
        
        $status = Status::find($id);
        if(!$status)
            throw new ObjectNotExist(__("Status does not exist"));
        
        $request->validate([
            "name" => "required|max:250",
            "is_default" => "nullable",
            "close_task" => "nullable",
        ]);
        
        $validate = [];
        $updateFields = ["name", "is_default"];
        
        if(!$status->is_default)
            $updateFields[] = "close_task";
        
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
                $status->{$field} = $request->input($field);
        }
        $status->save();
        
        $status->isDefaultFlag();
        
        return true;
    }
    
    /**
    * Delete status
    *
    * Delete status.
    * @urlParam id integer required Status identifier.
    * @responseField status boolean Delete status
    * @response 404 {"error":true,"message":"Status does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group Statuses
    */
    public function delete(Request $request, $id)
    {
        User::checkAccess("status:delete");
        
        $status = Status::find($id);
        if(!$status)
            throw new ObjectNotExist(__("Status does not exist"));
        
        $status->delete();
        return true;
    }
}
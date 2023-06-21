<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Exceptions\AccessDenied;
use App\Exceptions\InvalidStatus;
use App\Exceptions\ObjectExist;
use App\Exceptions\ObjectNotExist;
use App\Libraries\Helper;
use App\Models\Project;
use App\Models\User;
use App\Models\Task;
use App\Models\TaskTime;

class TaskTimeController extends Controller
{
    /**
    * Start task timer
    *
    * Start task timer (log time).
    * @urlParam id integer required Task identifier.
    * @response200 {"state": "active", "total": 250, "total_logged": 1000}
    * @response 404 {"error":true,"message":"Task does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group Task time
    */
    public function start(Request $request, $id)
    {
        User::checkAccess("task:list");
        
        $task = Task::find($id);
        if(!$task)
            throw new ObjectNotExist(__("Task does not exist"));
        
        if(TaskTime::where("task_id", $id)->where("user_id", Auth::user()->id)->where("status", TaskTime::ACTIVE)->count())
            throw new ObjectExist(__("Task has current active timer"));
        
        $time = time();
        $timer = TaskTime::where("task_id", $id)->where("user_id", Auth::user()->id)->where("status", TaskTime::PAUSED)->first();
        if(!$timer)
        {
            $timer = new TaskTime;
            $timer->uuid = Auth::user()->getUuid();
            $timer->task_id = $id;
            $timer->started = $time;
            $timer->user_id = Auth::user()->id;
        }
            
        $timer->status = TaskTime::ACTIVE;
        $timer->timer_started = $time;
        $timer->save();
        
        return $task->getActiveTaskTime();
    }
    
    /**
    * Pause task timer
    *
    * Pause task timer (log time).
    * @urlParam id integer required Task identifier.
    * @response 200 {"state": "active", "total": 250, "total_logged": 1000}
    * @response 404 {"error":true,"message":"Task does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group Task time
    */
    public function pause(Request $request, $id)
    {
        User::checkAccess("task:list");
        
        $task = Task::find($id);
        if(!$task)
            throw new ObjectNotExist(__("Task does not exist"));
        
        $timer = TaskTime::where("task_id", $id)->where("user_id", Auth::user()->id)->where("status", TaskTime::ACTIVE)->first();
        if(!$timer)
            throw new ObjectNotExist(__("Task does not currently have an active timer"));
        
        $total = time() - $timer->timer_started;
        $timer->status = TaskTime::PAUSED;
        $timer->timer_started = null;
        $timer->total += $total;
        $timer->save();
        
        return $task->getActiveTaskTime();
    }
    
    /**
    * Stop task timer
    *
    * Stop task timer (log time).
    * @urlParam id integer required Task identifier.
    * @response 200 {"state": "active", "total": 250, "total_logged": 1000}
    * @response 404 {"error":true,"message":"Task does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group Task time
    */
    public function stop(Request $request, $id)
    {
        User::checkAccess("task:list");
        
        $task = Task::find($id);
        if(!$task)
            throw new ObjectNotExist(__("Task does not exist"));
        
        $timer = TaskTime::where("task_id", $id)->where("user_id", Auth::user()->id)->whereIn("status", [TaskTime::ACTIVE, TaskTime::PAUSED])->first();
        if(!$timer)
            throw new ObjectNotExist(__("Task does not currently have an active timer"));
        
        $time = time();
        $total = $timer->status == TaskTime::ACTIVE ? ($timer->total + ($time - $timer->timer_started)) : $timer->total;
        
        $timer->status = TaskTime::FINISHED;
        $timer->finished = $time;
        $timer->timer_started = null;
        $timer->total = self::roundTime($total);
        $timer->save();
        
        return $task->getActiveTaskTime();
    }
    
    /**
    * Log task spend time
    *
    * Log task spend time.
    * @urlParam id integer required Task identifier.
    * @bodyParam started integer required Start time.
    * @bodyParam total integer required Total time in seconds.
    * @bodyParam comment string Comment.
    * @bodyParam billable boolean Billable.
    * @responseField status boolean Status
    * @response 404 {"error":true,"message":"Task does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group Task time
    */
    public function logTime(Request $request, $id)
    {
        User::checkAccess("task:list");
        
        $task = Task::find($id);
        if(!$task)
            throw new ObjectNotExist(__("Task does not exist"));
        
        $request->validate([
            "started" => "required|integer|gt:0",
            "total" => "required|integer|gt:0",
            "comment" => "nullable|max:10000",
            "billable" => "nullable|boolean",
        ]);
        
        $timer = new TaskTime;
        $timer->uuid = Auth::user()->getUuid();
        $timer->task_id = $id;
        $timer->user_id = Auth::user()->id;
        $timer->status = TaskTime::FINISHED;
        $timer->started = $request->input("started");
        $timer->finished = $request->input("started") + $request->input("total");
        $timer->total = self::roundTime($request->input("total"));
        $timer->billable = $request->input("billable", 0);
        $timer->comment = $request->input("comment");
        $timer->save();
        
        return true;
    }
    
    /**
    * Update log task spend time
    *
    * Update log task spend time.
    * @urlParam id integer required Task identifier.
    * @urlParam tid integer required Task time identifier.
    * @bodyParam started integer Start time.
    * @bodyParam total integer Total time in seconds.
    * @bodyParam comment string Comment.
    * @bodyParam billable boolean Billable.
    * @responseField status boolean Status
    * @response 404 {"error":true,"message":"Task does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group Task time
    */
    public function updateLogTime(Request $request, $taskId, $id)
    {
        User::checkAccess("task:list");
        
        $task = Task::find($taskId);
        if(!$task)
            throw new ObjectNotExist(__("Task does not exist"));
        
        $timer = TaskTime::where("task_id", $taskId)->find($id);
        if(!$timer)
            throw new ObjectNotExist(__("Task time does not exist"));
        
        if($timer->status != TaskTime::FINISHED)
            throw new InvalidStatus(__("Task time invalid status"));
        
        if(!$timer->canEdit())
            throw new AccessDenied(__("Access denied"));
        
        $rules = [
            "started" => "required|integer|gt:0",
            "total" => "required|integer|gt:0",
            "comment" => "nullable|max:10000",
            "billable" => "nullable|boolean",
        ];
        
        $validate = [];
        $updateFields = ["started", "total", "comment", "billable"];
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
            {
                if($field == "total")
                    $timer->{$field} = self::roundTime($request->input($field));
                else
                    $timer->{$field} = $request->input($field);
            }
        }
        
        $timer->finished = $timer->started + $timer->total;
        $timer->save();
        return true;
    }
    
    /**
    * Get task spend time rows
    *
    * Get task spend time rows.
    * @urlParam id integer required Task identifier.
    * @queryParam size integer Number of rows. Default: 50
    * @queryParam page integer Number of page (pagination). Default: 1
    * @response 200 {"total_rows": 100, "total_pages": "4", "current_page": 1, "has_more": true, "data": [{"id": 1, "status": "active", "task_id": "1", "user_id": 1, "started": "1680843163", "finished": 1680843163, "timer_started": 0, "total": 600, "comment": "Example comment", "billable": 0, "_me": true, "can_delete" : false, "can_edit": false, "user": "John Doe"}]}
    * @response 404 {"error":true,"message":"Task does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group Task time
    */
    public function getTimes(Request $request, $id)
    {
        User::checkAccess("task:list");
        
        $task = Task::find($id);
        if(!$task)
            throw new ObjectNotExist(__("Task does not exist"));
        
        $request->validate([
            "size" => "nullable|integer|gt:0",
            "page" => "nullable|integer|gt:0",
        ]);
        
        $size = $request->input("size", config("api.list.size"));
        $page = $request->input("page", 1);
        
        $times = TaskTime
            ::apiFields()
            ->where("task_id", $id)
            ->where("status", TaskTime::FINISHED)
            ->take($size)
            ->skip(($page-1)*$size)
            ->orderBy("finished", "DESC")
            ->get();
            
        $total = TaskTime::where("task_id", $id)->where("status", TaskTime::FINISHED)->count();
        
        foreach($times as $k => $time)
        {
            $times[$k]->user = $time->getUserName();
            $times[$k]->_me = $time->user_id == Auth::user()->id;
            $times[$k]->can_delete = $time->canDelete();
            $times[$k]->can_edit = $time->canEdit();
            $times[$k]->billable = $time->billable == 1;
        }
        
        $out = [
            "total_rows" => $total,
            "total_pages" => ceil($total / $size),
            "current_page" => $page,
            "has_more" => ceil($total / $size) > $page,
            "data" => $times,
            "total" => $task->total,
        ];
            
        return $out;
    }
    
    /**
    * Get task spend time row
    *
    * Get task spend time row.
    * @urlParam id integer required Task identifier.
    * @urlParam tid integer required Task time identifier.
    * @response 200 {"id": 1, "status": "active", "task_id": "1", "user_id": 1, "started": "1680843163", "finished": 1680843163, "timer_started": 0, "total": 600, "comment": "Example comment", "billable": 0, "_me": true, "can_delete": false, "can_edit": false, "user": "John Doe"}
    * @response 404 {"error":true,"message":"Task does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group Task time
    */
    public function getTime(Request $request, $id, $tid)
    {
        User::checkAccess("task:list");
        
        $task = Task::find($id);
        if(!$task)
            throw new ObjectNotExist(__("Task does not exist"));
        
        $time = TaskTime
            ::apiFields()
            ->where("id", $tid)
            ->where("task_id", $id)
            ->where("status", TaskTime::FINISHED)
            ->first();
            
        $time->user = $time->getUserName();
        $time->_me = $time->user_id == Auth::user()->id;
        $time->billable = $time->billable == 1;
        $time->can_delete = $time->canDelete();
        $time->can_edit = $time->canEdit();
        
        return $time;
    }
    
    /**
    * Delete task log time
    *
    * Delete task log time.
    * @urlParam id integer required Task identifier.
    * @urlParam tid integer required Task time identifier.
    * @responseField status boolean Delete status
    * @response 404 {"error":true,"message":"Task does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group Task time
    */
    
    public function deleteTime(Request $request, $taskId, $id)
    {
        User::checkAccess("task:list");
        
        $task = Task::find($taskId);
        if(!$task)
            throw new ObjectNotExist(__("Task does not exist"));
        
        $timer = TaskTime::where("task_id", $taskId)->find($id);
        if(!$timer)
            throw new ObjectNotExist(__("Task time does not exist"));
        
        if(!$timer->canDelete())
            throw new AccessDenied(__("Access denied"));
        
        $timer->delete();
        return true;
    }
    
    private static function roundTime($total)
    {
        return Helper::roundTime($total);
    }
}
<?php

namespace App\Http\Controllers;

use App\Exceptions\ObjectNotExist;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskTimeDay;
use App\Models\User;

class StatsController extends Controller
{
    /**
    * Get daily user stats
    *
    * Return daily user stats.
    * @urlParam id integer required User identifier.
    * @queryParam month string Month in format: "YYYY-MM", Default current month
    * @response 200 {"user": {"id": "1", "firstname": "John", "lastname": "Doe"}, "month": "2022-12", "stats": [{"2023-06-30":{"total":86400,"objects":{"1":{"id":1,"name":"John Doe","total":86400}}}}], "total": 100, "allowed_months": ["2022-12", "2023-01"], "allowed_years" : ["2022", "2023"]}
    * @header Authorization: Bearer {TOKEN}
    * @group Stats
    */
    public function userDaily(Request $request, $id)
    {
        User::checkAccess("stats:list");
        
        $user = User::byFirm()->find($id);
        if(!$user)
            throw new ObjectNotExist(__("User does not exist"));
            
        $allowedMonths = TaskTimeDay::getAllowedMonths("user", $id);
        $month = $this->getMonth($request, $allowedMonths);
        $stats = TaskTimeDay::getUserStats($id, "daily", $month);
        return [
            "user" => [
                "id" => $id,
                "firstname" => $user->firstname,
                "lastname" => $user->lastname,
            ],
            "month" => $month,
            "stats" => $stats["stats"],
            "total" => $stats["total"],
            "allowed_months" => $allowedMonths,
            "allowed_years" => TaskTimeDay::getAllowedYears("user", $id),
        ];
    }
    
    /**
    * Get monthy user stats
    *
    * Return monthy user stats.
    * @urlParam id integer required User identifier.
    * @queryParam year string Year in format: "YYYY", Default current year
    * @response 200 {"user": {"id": "1", "firstname": "John", "lastname": "Doe"}, "year": "2022", "stats": [{"2023-06":{"total":86400,"objects":{"1":{"id":1,"name":"John Doe","total":86400}}}}], "total": 100, "allowed_months": ["2022-12", "2023-01"], "allowed_years" : ["2022", "2023"]}
    * @header Authorization: Bearer {TOKEN}
    * @group Stats
    */
    public function userMonthly(Request $request, $id)
    {
        User::checkAccess("stats:list");
        
        $user = User::byFirm()->find($id);
        if(!$user)
            throw new ObjectNotExist(__("User does not exist"));
        
        $allowedYears = TaskTimeDay::getAllowedYears("user", $id);
        $year = $this->getYear($request, $allowedYears);
        $stats = TaskTimeDay::getUserStats($id, "monthly", $year);
        return [
            "user" => [
                "id" => $id,
                "firstname" => $user->firstname,
                "lastname" => $user->lastname,
            ],
            "year" => $year,
            "stats" => $stats["stats"],
            "total" => $stats["total"],
            "allowed_months" => TaskTimeDay::getAllowedMonths("user", $id),
            "allowed_years" => $allowedYears,
        ];
    }
    
    /**
    * Get daily project stats
    *
    * Return daily project stats.
    * @urlParam id integer required Project identifier.
    * @queryParam month string Month in format: "YYYY-MM", Default current month
    * @response 200 {"project": {"id": "1", "name": "Example project name"}, "month": "2022-12", "stats": [{"2023-06-30":{"total":86400,"objects":{"1":{"id":1,"name":"Task name","total":86400}}}}], "total": 100, "allowed_months": ["2022-12", "2023-01"], "allowed_years" : ["2022", "2023"]}
    * @header Authorization: Bearer {TOKEN}
    * @group Stats
    */
    public function projectDaily(Request $request, $id)
    {
        User::checkAccess("stats:list");
        
        $project = Project::find($id);
        if(!$project)
            throw new ObjectNotExist(__("Project does not exist"));
                
        $allowedMonths = TaskTimeDay::getAllowedMonths("project", $id);
        $month = $this->getMonth($request, $allowedMonths);
        $stats = TaskTimeDay::getProjectStats($id, "daily", $month);
        return [
            "project" => [
                "id" => $id,
                "name" => $project->name,
            ],
            "month" => $month,
            "stats" => $stats["stats"],
            "total" => $stats["total"],
            "allowed_months" => $allowedMonths,
            "allowed_years" => TaskTimeDay::getAllowedYears("project", $id),
        ];
    }
    
    /**
    * Get monthy project stats
    *
    * Return project user stats.
    * @urlParam id integer required Project identifier.
    * @queryParam year string Year in format: "YYYY", Default current year
    * @response 200 {"project": {"id": "1", "name": "Example project name"}, "year": "2022", "stats": [{"2023-06":{"total":86400,"objects":{"1":{"id":1,"name":"Task name","total":86400}}}}], "total": 100, "allowed_months": ["2022-12", "2023-01"], "allowed_years" : ["2022", "2023"]}
    * @header Authorization: Bearer {TOKEN}
    * @group Stats
    */
    public function projectMonthly(Request $request, $id)
    {
        User::checkAccess("stats:list");
        
        $project = Project::find($id);
        if(!$project)
            throw new ObjectNotExist(__("Project does not exist"));
        
        $allowedYears = TaskTimeDay::getAllowedYears("project", $id);
        $year = $this->getYear($request, $allowedYears);
        $stats = TaskTimeDay::getProjectStats($id, "monthly", $year);
        return [
            "project" => [
                "id" => $id,
                "name" => $project->name,
            ],
            "year" => $year,
            "stats" => $stats["stats"],
            "total" => $stats["total"],
            "allowed_months" => TaskTimeDay::getAllowedMonths("project", $id),
            "allowed_years" => $allowedYears,
        ];
    }
    
    /**
    * Get daily task stats
    *
    * Return daily task stats.
    * @urlParam id integer required Task identifier.
    * @queryParam month string Month in format: "YYYY-MM", Default current month
    * @response 200 {"task": {"id": "1", "name": "Example task name"}, "month": "2022-12", "stats": [{"2023-06-30":{"total":86400,"objects":{"1":{"id":1,"name":"John Doe","total":86400}}}}], "total": 100, "allowed_months": ["2022-12", "2023-01"], "allowed_years" : ["2022", "2023"]}
    * @header Authorization: Bearer {TOKEN}
    * @group Stats
    */
    public function taskDaily(Request $request, $id)
    {
        User::checkAccess("stats:list");
        
        $task = Task::find($id);
        if(!$task)
            throw new ObjectNotExist(__("Task does not exist"));

        $allowedMonths = TaskTimeDay::getAllowedMonths("task", $id);
        $month = $this->getMonth($request, $allowedMonths);
        $stats = TaskTimeDay::getTaskStats($id, "daily", $month);
        return [
            "task" => [
                "id" => $id,
                "name" => $task->name,
            ],
            "month" => $month,
            "stats" => $stats["stats"],
            "total" => $stats["total"],
            "allowed_months" => $allowedMonths,
            "allowed_years" => TaskTimeDay::getAllowedYears("task", $id),
        ];
    }
    
    /**
    * Get monthy task stats
    *
    * Return task user stats.
    * @urlParam id integer required Task identifier.
    * @queryParam year string Year in format: "YYYY", Default current year
    * @response 200 {"project": {"id": "1", "name": "Example task name"}, "year": "2022", "stats": [{"2023-06":{"total":86400,"objects":{"1":{"id":1,"name":"John Doe","total":86400}}}}], "total": 100,"allowed_months": ["2022-12", "2023-01"], "allowed_years" : ["2022", "2023"]}
    * @header Authorization: Bearer {TOKEN}
    * @group Stats
    */
    public function taskMonthly(Request $request, $id)
    {
        User::checkAccess("stats:list");
        
        $task = Task::find($id);
        if(!$task)
            throw new ObjectNotExist(__("Task does not exist"));
        
        $allowedYears = TaskTimeDay::getAllowedYears("task", $id);
        $year = $this->getYear($request, $allowedYears);
        $stats = TaskTimeDay::getTaskStats($id, "monthly", $year);
        return [
            "task" => [
                "id" => $id,
                "name" => $task->name,
            ],
            "year" => $year,
            "stats" => $stats["stats"],
            "total" => $stats["total"],
            "allowed_months" => TaskTimeDay::getAllowedMonths("task", $id),
            "allowed_years" => $allowedYears,
        ];
    }
    
    private static $cacheStats = [];
    /**
    * Get total task stats
    *
    * Return total task stats.
    * @queryParam size integer Number of rows. Default: 50
    * @queryParam page integer Number of page (pagination). Default: 1
    * @queryParam user_id integer User identifier
    * @queryParam task_id integer Task identifier
    * @queryParam project_id integer Project identifier
    * @queryParam date_from string Date from
    * @queryParam date_to string Date to
    * @response 200 {}
    * @header Authorization: Bearer {TOKEN}
    * @group Stats
    */
    public function total(Request $request)
    {
        User::checkAccess("stats:list");
        
        $request->validate([
            "size" => "nullable|integer|gt:0",
            "page" => "nullable|integer|gt:0",
            "user_id" => "nullable|integer|gt:0",
            "task_id" => "nullable|integer|gt:0",
            "project_id" => "nullable|integer|gt:0",
            "date_from" => "nullable|date_format:Y-m-d",
            "date_to" => "nullable|date_format:Y-m-d",
        ]);
        
        $size = $request->input("size", config("api.list.size"));
        $page = $request->input("page", 1);
        
        $userId = $request->input("user_id", null);
        $taskId = $request->input("task_id", null);
        $projectId = $request->input("project_id", null);
        $dateFrom = $request->input("date_from", null);
        $dateTo = $request->input("date_to", null);
        
        $loggedTimes = TaskTimeDay::where("uuid", Auth::user()->getUuid())->whereRaw("1=1");
        
        $searchByLabels = [
            "user" => "",
            "project" => "",
            "task" => "",
        ];
        if($userId)
        {
            $user = User::byFirm()->find($userId);
            if($user)
                $searchByLabels["user"] = $user->lastname . " " . $user->firstname;
            $loggedTimes->where("user_id", $userId);
        }
        if($taskId)
        {
            $task = Task::find($taskId);
            if($task)
                $searchByLabels["task"] = $task->name;
            $loggedTimes->where("task_id", $taskId);
        }
        if($projectId)
        {
            $project = Project::find($projectId);
            if($project)
                $searchByLabels["project"] = $project->name;
            $loggedTimes->where("project_id", $projectId);
        }
        if($dateFrom)
            $loggedTimes->whereDate("date", ">=", $dateFrom);
        if($dateTo)
            $loggedTimes->whereDate("date", "<=", $dateTo);
        
        $total = $loggedTimes->count();
            
        $totalOnePage = 0;
        $totalAllPage = $loggedTimes->sum("total");
        
        $loggedTimes = $loggedTimes->take($size)
            ->skip(($page-1)*$size)
            ->orderBy("date", "DESC")
            ->get();
        
        foreach($loggedTimes as $k => $loggedTime)
        {
            if(empty(static::$cacheStats["task"][$loggedTime->task_id]))
            {
                $task = Task::find($loggedTime->task_id);
                static::$cacheStats["task"][$loggedTime->task_id] = $task ? $task->name : "";
            }
            
            if(empty(static::$cacheStats["project"][$loggedTime->project_id]))
            {
                $project = Project::find($loggedTime->project_id);
                static::$cacheStats["project"][$loggedTime->project_id] = $project ? $project->name : "";
            }
            
            if(empty(static::$cacheStats["user"][$loggedTime->user_id]))
            {
                $user = User::find($loggedTime->user_id);
                static::$cacheStats["user"][$loggedTime->user_id] = $user ? ($user->firstname . " " . $user->lastname) : "";
            }
            
            $loggedTimes[$k]->user = static::$cacheStats["user"][$loggedTime->user_id];
            $loggedTimes[$k]->task = static::$cacheStats["task"][$loggedTime->task_id];
            $loggedTimes[$k]->project = static::$cacheStats["project"][$loggedTime->project_id];
            
            $totalOnePage += $loggedTime->total;
        }
        
        $out = [
            "total_rows" => $total,
            "total_pages" => ceil($total / $size),
            "current_page" => $page,
            "has_more" => ceil($total / $size) > $page,
            "data" => $loggedTimes,
            "labels" => $searchByLabels,
            "total_one_page" => $totalOnePage,
            "total_all_page" => $totalAllPage,
        ];
            
        return $out;
    }
    
    private function getMonth(Request $request, $allowed = [])
    {
        $request->validate([
            "month" => "nullable|date_format:Y-m"
        ]);
        $month = $request->input("month", date("Y-m"));
        
        if(!empty($allowed) && !in_array($month, $allowed))
            $month = end($allowed);
        return $month;
    }
    
    private function getYear(Request $request, $allowed = [])
    {
        $request->validate([
            "year" => "nullable|date_format:Y"
        ]);
        $year = $request->input("year", date("Y"));
        
        if(!empty($allowed) && !in_array($year, $allowed))
            $year = end($allowed);
        return $year;
    }
}
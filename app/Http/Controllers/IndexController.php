<?php

namespace App\Http\Controllers;

use DateTime;
use DateInterval;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Exceptions\ObjectNotExist;
use App\Models\Country;
use App\Models\FirmInvoicingData;
use App\Models\Limit;
use App\Models\Project;
use App\Models\Subscription;
use App\Models\Task;
use App\Models\TaskAssignedUser;
use App\Models\TaskComment;
use App\Models\TaskTime;
use App\Models\User;

class IndexController extends Controller
{
    /**
    * Get dashboard stats
    * @group dashboard
    */
    public function dashboard(Request $request)
    {
        $out = [
            "projects" => [
                "total" => Project::count(),
                "data" => array_values(self::getProjectsStats()),
            ],
            "tasks" => [
                "total" => Task::count(),
                "data" => array_values(self::getTaskStats()),
            ],
            "completed_tasks" => [
                "total" => Task::where("completed", 1)->count(),
                "data" => array_values(self::getTaskStats(true)),
            ],
            "tasks_summary" => self::getTaskSummaryStats(14),
            "latest_tasks" => self::getLatestTasks(8),
            "latest_comments" => self::getLatestComments(10),
            "my_work" => self::getMyWork(10),
            "active_timer" => self::getActiveTimer()
        ];
        
        return $out;
    }
    
    private static function getProjectsStats($days = 14)
    {
        $date = new DateTime();
        $date->sub(new DateInterval("P" . ($days-1) . "D"));
        
        $dbStats = [];
        $stats = Project::whereDate("created_at", ">=", $date->format("Y-m-d"))->orderBy("created_at", "ASC")->get();
            
        if(!$stats->isEmpty())
        {
            foreach($stats as $stat)
            {
                $d = substr($stat->created_at, 0, 10);
                if(empty($out[$d]))
                    $dbStats[$d] = 0;
                    
                $dbStats[$d]++;
            }
        }
        
        $outStats = [];
        $today = new DateTime();
        while(true)
        {
            $d = $date->format("Y-m-d");
            $outStats[$d] = isset($dbStats[$d]) ? $dbStats[$d] : 0;
            
            if($today->format("Y-m-d") == $date->format("Y-m-d"))
                break;
            
            $date->add(new DateInterval("P1D"));
        }
        
        return $outStats;
    }
    
    private static function getTaskSummaryStats($days = 30)
    {
        $d1 = self::getTaskStats(false, $days);
        $d2 = self::getTaskStats(true, $days);
        
        $labels = [];
        foreach(array_keys($d2) as $d)
        {
            list($m, $d) = explode("-", substr($d, -5));
            $labels[] = sprintf("%s/%s", $d, $m);
        }
        
        $max = 5;
        foreach(array_values($d1) as $v)
        {
            if($v > $max)
                $max = $v;
        }
        foreach(array_values($d2) as $v)
        {
            if($v > $max)
                $max = $v;
        }
        return [
            array_values($d1),
            array_values($d2),
            $labels,
            $max
        ];
    }
    
    private static function getLatestTasks($limit = 10)
    {
        $out = [];
        $tasks = Task::select("id", "name", "priority")->orderBy("created_at", "DESC")->limit($limit)->get();
        if(!$tasks->isEmpty())
        {
            foreach($tasks as $task)
            {
                $out[] = [
                    "id" => $task->id,
                    "name" => $task->name,
                    "priority" => $task->priority,
                ];
            }
        }
        
        return $out ? $out : null;
    }
    
    private static function getMyWork($limit = 10)
    {
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
        
        $tasks = $tasks->take($limit)
            ->orderBy("priority", "DESC")
            ->orderBy("updated_at", "desc")
            ->get();
        
        foreach($tasks as $k => $task)
            $tasks[$k]->status = $task->getStatusName();
        
        return $tasks;
    }
    
    private static function getActiveTimer()
    {
        $timer = TaskTime::where("user_id", Auth::user()->id)->whereIn("status", [TaskTime::ACTIVE, TaskTime::PAUSED])->orderBy("created_at", "DESC")->get();
        
        $out = [];
        if(!$timer->isEmpty())
        {
            $data = [];
            foreach($timer as $time)
            {
                $task = Task::find($time->task_id);
                if(!$task)
                    continue;
                
                $total = $time->total;
                if($time->status == TaskTime::ACTIVE)
                    $total = $time->total + (time() - $time->timer_started);
                
                $data[] = [
                    "id" => $time->id,
                    "task_id" => $time->task_id,
                    "status" => $time->status,
                    "total" => $total,
                    "task" => $task->name,
                ];
            }
            $out["total"] = count($data);
            $out["data"] = $data;
        }
        return $out ? $out : null;
    }
    
    private static function getTaskStats($completed = false, $days = 14)
    {
        $date = new DateTime();
        $date->sub(new DateInterval("P" . ($days-1) . "D"));
        
        $dbStats = [];
        if($completed)
            $stats = Task::where("completed", 1)->where("completed_at", ">=", $date->format("Y-m-d"))->orderBy("completed_at", "ASC")->get();
        else
            $stats = Task::whereDate("created_at", ">=", $date->format("Y-m-d"))->orderBy("created_at", "ASC")->get();
            
        if(!$stats->isEmpty())
        {
            foreach($stats as $stat)
            {
                $d = substr($completed ? $stat->completed_at : $stat->created_at, 0, 10);
                if(empty($dbStats[$d]))
                    $dbStats[$d] = 0;
                    
                $dbStats[$d]++;
            }
        }
        
        $outStats = [];
        $today = new DateTime();
        while(true)
        {
            $d = $date->format("Y-m-d");
            $outStats[$d] = isset($dbStats[$d]) ? $dbStats[$d] : 0;
            
            if($today->format("Y-m-d") == $date->format("Y-m-d"))
                break;
            
            $date->add(new DateInterval("P1D"));
        }
        
        return $outStats;
    }
    
    public static function getLatestComments($limit = 10)
    {
        $out = [];
        $comments = TaskComment::orderBy("created_at", "DESC")->limit($limit)->get();
        if(!$comments->isEmpty())
        {
            foreach($comments as $comment)
            {
                $task = Task::find($comment->task_id);
                if(!$task)
                    continue;
                
                $user = User::find($comment->user_id);
                
                $out[] = [
                    "task_id" => $comment->task_id,
                    "task" => $task->name,
                    "comment" => $comment->comment,
                    "user" => $user ? ($user->firstname . " " . $user->lastname) : "",
                    "created" => $comment->created_at,
                ];
            }
        }
        return $out;
    }
    
    /**
    * Get active subscription
    * @response 200 {"start": 1686580275, "start_date": "2023-01-02 10:00:12", "end": 1686580275, "end_date": "2023-01-02 11:00:12"}
    *
    * @group Subscription
    */
    public function getActiveSubscription()
    {
        $out = [];
        $subscription = Subscription::where("status", Subscription::STATUS_ACTIVE)->first();
        if($subscription)
        {
            $out = [
                "start" => $subscription->start,
                "start_date" => date("Y-m-d H:i:s", $subscription->start),
                "end" => $subscription->end,
                "end_date" => date("Y-m-d H:i:s", $subscription->end),
            ];
        }
        return $out;
    }
    
    /**
    * Get current stats
    * @response 200 {"tasks": 18, "can_add_task": true, "projects": 2, "can_add_project": false, "space": 512012, "can_add_files": false}
    *
    * @group Subscription
    */
    public function getCurrentStats()
    {
        $limit = Limit::select("tasks", "projects", "space")->first();
        if($limit)
        {
            $free = config("packages.free");
            $out = [
                "tasks" => $limit->tasks,
                "can_add_task" => Subscription::checkPackage("task", false),
                "projects" => $limit->projects,
                "can_add_project" => Subscription::checkPackage("project", false),
                "space" => $limit->space,
                "can_add_files" => Subscription::checkPackage("space", false),
            ];
        }
        else
        {
            $out = [
                "tasks" => 0,
                "can_add_task" => true,
                "projects" => 0,
                "can_add_project" => true,
                "space" => 0,
                "can_add_files" => true,
            ];
        }
        
        return $out;
    }
    
    /**
    * Search task / projects
    *
    * Search in task / projects table by specified query
    * @queryParam size integer Number of rows. Default: 50
    * @queryParam page integer Number of page (pagination). Default: 1
    * @queryParam q string search phrase
    * @response 200 {"total_rows": 100, "total_pages": "4", "current_page": 1, "has_more": true, "data": [{"id": "1", "name": "Example name", "type": "task", "creatd_at" : "2023-01-01 10:00:00"}, {"id": "1", "name": "Example project", "type": "project", "creatd_at" : "2023-01-01 10:00:00"}]}
    * @group Search
    */
    public function search(Request $request)
    {
        $q = $request->input("q");
        if(mb_strlen($q) >= 1)
        {
            $size = $request->input("size", config("api.list.size"));
            $page = $request->input("page", 1);
        
            $p = Project::select("id", "name", "created_at", DB::raw("'project' AS type"))->where("name", "LIKE", "%" . $q . "%");
            $t = Task::select("id", "name", "created_at", DB::raw("'task' AS type"))->where("name", "LIKE", "%" . $q . "%");
            
            $results = $t->union($p);
            $total = $results->count();
            
            $results = $results->take($size)
                ->skip(($page-1)*$size)
                ->orderBy("name", "ASC")
                ->get();
            
            $out = [
                "total_rows" => $total,
                "total_pages" => ceil($total / $size),
                "current_page" => $page,
                "has_more" => ceil($total / $size) > $page,
                "data" => $results,
            ];
        }
        else
        {
            $out = [
                "total_rows" => 0,
                "total_pages" => 0,
                "current_page" => 1,
                "has_more" => false,
                "data" => [],
            ];
        }
        
        return $out;
    }
    
    private static $cacheSearch = [];
    /**
    * Search task / projects / user
    *
    * Search task / projects / user in specified source table
    * @urlParam source string search in source table (one of: tasks, users, projects)
    * @queryParam size integer Number of rows. Default: 50
    * @queryParam page integer Number of page (pagination). Default: 1
    * @queryParam q string search phrase
    * @response 200 {"total_rows": 100, "total_pages": "4", "current_page": 1, "has_more": true, "data": [{"id": "1", "name": "Example task", "project_id": 2, "project": "Project name"}]}
    * @response 200 {"total_rows": 100, "total_pages": "4", "current_page": 1, "has_more": true, "data": [{"id": "1", "name": "Example task"}]}
    * @response 200 {"total_rows": 100, "total_pages": "4", "current_page": 1, "has_more": true, "data": [{"id": "1", "firstname": "John", "lastname": "Doe"}]}
    * @group Search
    */
    public function searchIn(Request $request, $source)
    {
        if(!in_array($source, ["tasks", "projects", "users"]))
            throw new ObjectNotExist(__("Invalid source"));
        
        $q = $request->input("q");
        if(mb_strlen($q) >= 1)
        {
            $size = $request->input("size", config("api.list.size"));
            $page = $request->input("page", 1);
            
            switch($source)
            {
                case "tasks":
                    $rows = Task::select("id", "name", "project_id")->where("name", "LIKE", "%" . $q . "%")->orderBy("name", "ASC");
                break;
            
                case "projects":
                    $rows = Project::select("id", "name")->where("name", "LIKE", "%" . $q . "%")->orderBy("name", "ASC");
                break;
            
                case "users":
                    $rows = User::byFirm()->select("id", "lastname", "firstname")->where("lastname", "LIKE", "%" . $q . "%")->orderBy("lastname", "ASC");
                break;
            }
            
            $total = $rows->count();
            
            $rows = $rows->take($size)
                ->skip(($page-1)*$size)
                ->get();
            
            if(!$rows->isEmpty())
            {
                switch($source)
                {
                    case "tasks":
                        foreach($rows as $k => $row)
                        {
                            if(empty(static::$cacheSearch["project"][$row->project_id]))
                            {
                                $project = Project::find($row->project_id);
                                static::$cacheSearch["project"][$row->project_id] = $project ? $project->name : "";
                            }
                            $rows[$k]->project = static::$cacheSearch["project"][$row->project_id];
                        }
                    break;
                }
            }
            
            $out = [
                "total_rows" => $total,
                "total_pages" => ceil($total / $size),
                "current_page" => $page,
                "has_more" => ceil($total / $size) > $page,
                "data" => $rows,
            ];
        }
        else
        {
            $out = [
                "total_rows" => 0,
                "total_pages" => 0,
                "current_page" => 1,
                "has_more" => false,
                "data" => [],
            ];
        }
        
        return $out;
    }
    
    /**
    * Return package limits
    * @group Subscription
    */
    public function packages()
    {
        if(Auth::check())
        {
            $invoicingData = FirmInvoicingData::first();
            if($invoicingData)
            {
                $foreign = strtolower($invoicingData->country) != "pl";
                $reverseCharge = $foreign && $invoicingData->type == "firm";
                    
                if($reverseCharge)
                {
                    $packages = config("packages");
                    foreach($packages["allowed"] as $k => $p)
                    {
                        $packages["allowed"][$k]["price"] = $p["price"] * ((100 + $p["vat"]) / 100);
                        $packages["allowed"][$k]["price_gross"] = $p["price"] * ((100 + $p["vat"]) / 100);
                    }
                    
                    $packages["reverse"] = true;
                    return $packages;
                }
            }
        }
        
        $packages = config("packages");
        foreach($packages["allowed"] as $k => $p)
            $packages["allowed"][$k]["price_gross"] = $p["price"] * ((100 + $p["vat"]) / 100);
        
        return $packages;
    }
    
    /**
    * Return countries list
    * @queryParam lang string Language Default: pl
    * @group Others
    */
    public function countries(Request $request)
    {
        $lang = $request->input("lang", "pl");
        
        return Country::select("code", ($lang == "pl" ? "name" : "name_en AS name"), "eu")
            ->orderBy("sort", "DESC")
            ->orderBy("eu", "DESC")
            ->orderBy(($lang == "pl" ? "name" : "name_en"), "DESC")
            ->get();
    }
}
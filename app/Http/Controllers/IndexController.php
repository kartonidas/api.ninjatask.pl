<?php

namespace App\Http\Controllers;

use DateTime;
use DateInterval;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;

class IndexController extends Controller
{
    /**
    * Get dashboard stats
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
        
        return $out;
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
}
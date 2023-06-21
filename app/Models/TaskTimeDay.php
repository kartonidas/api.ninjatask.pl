<?php

namespace App\Models;

use DateTime;
use DateInterval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Models\Task;
use App\Models\User;

class TaskTimeDay extends Model
{
    use SoftDeletes;
    
    public static function getUserStats($uid, $period, $date)
    {
        $out = [];
        $rows = self
            ::where("uuid", Auth::user()->getUuid())
            ->where("user_id", $uid)
            ->where("date", "LIKE", $date . "-%")
            ->get();
        
        return self::generateStats($rows, $period, $date);
    }
    
    public static function getProjectStats($pid, $period, $date)
    {
        $out = [];
        $rows = self
            ::where("uuid", Auth::user()->getUuid())
            ->where("project_id", $pid)
            ->where("date", "LIKE", $date . "-%")
            ->get();
            
        return self::generateStats($rows, $period, $date);
    }
    
    public static function getTaskStats($tid, $period, $date)
    {
        $out = [];
        $rows = self
            ::where("uuid", Auth::user()->getUuid())
            ->where("task_id", $tid)
            ->where("date", "LIKE", $date . "-%")
            ->get();
            
        return self::generateStats($rows, $period, $date, "user");
    }
    
    private static function generateStats($rows, $period, $date, $objectType = "task")
    {
        $out = [];
        switch($period)
        {
            case "daily":
                $firstMonthDay = new DateTime($date . "-01");
                
                for($i = 1; $i <= $firstMonthDay->format("t"); $i++)
                {
                    $out[$date . "-" . str_pad($i, 2, "0", STR_PAD_LEFT)] = [
                        "total" => 0,
                        "objects" => [],
                    ];
                }
                
                if(!$rows->isEmpty())
                {
                    foreach($rows as $row)
                    {
                        if(!isset($out[$row->date]))
                        {
                            $out[$row->date] = [
                                "total" => 0,
                                "objects" => [],
                            ];
                        }
                        $out[$row->date]["total"] += $row->total;
                        
                        $objectName = "";
                        $objectId = $objectType == "user" ? $row->user_id : $row->task_id;
                        if(isset($out[$row->date]["objects"][$objectId]))
                            $objectName = $out[$row->date]["objects"][$objectId]["name"];
                        else
                        {
                            switch($objectType)
                            {
                                case "user":
                                    $objectId = $row->user_id;
                                    $user = User::find($objectId);
                                    if($user)
                                        $objectName = $user->firstname . " " . $user->lastname;
                                break;
                            
                                default:
                                    $task = Task::find($objectId);
                                    if($task)
                                        $objectName = $task->name;
                            }
                        }
                        
                        if(!isset($out[$row->date]["objects"][$objectId]))
                        {
                            $out[$row->date]["objects"][$objectId] = [
                                "id" => $objectId,
                                "name" => $objectName,
                                "total" => 0,
                            ];
                        }
                        $out[$row->date]["objects"][$objectId]["total"] += $row->total;
                    }
                }
            break;
        
            case "monthly":
                for($i = 1; $i <= 12; $i++)
                {
                    $out[$date . "-" . str_pad($i, 2, "0", STR_PAD_LEFT)] = [
                        "total" => 0,
                        "objects" => [],
                    ];
                }
                
                if(!$rows->isEmpty())
                {
                    foreach($rows as $row)
                    {
                        $dateKey = substr($row->date, 0, 7);
                        if(!isset($out[$dateKey]))
                        {
                            $out[$dateKey] = [
                                "total" => 0,
                                "objects" => [],
                            ];
                        }
                        $out[$dateKey]["total"] += $row->total;
                        
                        
                        $objectName = "";
                        $objectId = $objectType == "user" ? $row->user_id : $row->task_id;
                        if(isset($out[$row->date]["objects"][$objectId]))
                            $objectName = $out[$row->date]["objects"][$objectId]["name"];
                        else
                        {
                            switch($objectType)
                            {
                                case "user":
                                    $objectId = $row->user_id;
                                    $user = User::find($objectId);
                                    if($user)
                                        $objectName = $user->firstname . " " . $user->lastname;
                                break;
                            
                                default:
                                    $objectId = $row->task_id;
                                    $task = Task::find($objectId);
                                    if($task)
                                        $objectName = $task->name;
                            }
                        }
                        
                        if(!isset($out[$dateKey]["objects"][$objectId]))
                        {
                            $out[$dateKey]["objects"][$objectId] = [
                                "id" => $objectId,
                                "name" => $objectName,
                                "total" => 0,
                            ];
                        }
                        $out[$dateKey]["objects"][$objectId]["total"] += $row->total;
                    }
                }
            break;
        }
        return $out;
    }
    
    public static function getAllowedMonths()
    {
        $out = [];
        $minDate = self::where("uuid", Auth::user()->getUuid())->min("date");
        $maxDate = self::where("uuid", Auth::user()->getUuid())->max("date");
        
        if(!$minDate)
            $minDate = date("Y-m-d");
            
        if(!$maxDate)
            $maxDate = date("Y-m-d");
        
        if(substr($minDate, 0, 7) == substr($maxDate, 0, 7))
            $out[] = substr($minDate, 0, 7);
        else
        {
            $tmp = new DateTime($minDate . "-01");
            $out[] = $tmp->format("Y-m");
            while($tmp->format("Y-m") != substr($maxDate, 0, 7))
            {
                $tmp->add(new DateInterval("P1M"));
                $out[] = $tmp->format("Y-m");
            }
        }
        return $out;
        
    }
    
    public static function getAllowedYears()
    {
        $out = [];
        $minDate = self::where("uuid", Auth::user()->getUuid())->min("date");
        $maxDate = self::where("uuid", Auth::user()->getUuid())->max("date");
        
        if(!$minDate)
            $minDate = date("Y-m-d");
            
        if(!$maxDate)
            $maxDate = date("Y-m-d");
            
        if(substr($minDate, 0, 4) == substr($maxDate, 0, 4))
            $out[] = substr($minDate, 0, 4);
        else
        {
            $tmp = new DateTime($minDate . "-01");
            $out[] = $tmp->format("Y");
            while($tmp->format("Y") != substr($maxDate, 0, 4))
            {
                $tmp->add(new DateInterval("P1Y"));
                $out[] = $tmp->format("Y");
            }
        }
        return $out;
    }
}
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
    * @response 200 {"user": {"id": "1", "firstname": "John", "lastname": "Doe"}, "month": "2022-12", "stats": [{"2023-06-30":{"total":86400,"objects":{"1":{"id":1,"name":"John Doe","total":86400}}}}], "allowed" : ["2022-12", "2023-01"]}
    * @header Authorization: Bearer {TOKEN}
    * @group Stats
    */
    public function userDaily(Request $request, $id)
    {
        $user = User::byFirm()->find($id);
        if(!$user)
            throw new ObjectNotExist(__("User does not exist"));
            
        $month = $this->getMonth($request);
        return [
            "user" => [
                "id" => $id,
                "firstname" => $user->firstname,
                "lastname" => $user->lastname,
            ],
            "month" => $month,
            "stats" => TaskTimeDay::getUserStats($id, "daily", $month),
            "allowed" => TaskTimeDay::getAllowedMonths(),
        ];
    }
    
    /**
    * Get monthy user stats
    *
    * Return monthy user stats.
    * @urlParam id integer required User identifier.
    * @queryParam year string Year in format: "YYYY", Default current year
    * @response 200 {"user": {"id": "1", "firstname": "John", "lastname": "Doe"}, "year": "2022", "stats": [{"2023-06":{"total":86400,"objects":{"1":{"id":1,"name":"John Doe","total":86400}}}}], "allowed" : ["2022", "2023"]}
    * @header Authorization: Bearer {TOKEN}
    * @group Stats
    */
    public function userMonthly(Request $request, $id)
    {
        $user = User::byFirm()->find($id);
        if(!$user)
            throw new ObjectNotExist(__("User does not exist"));
        
        $year = $this->getYear($request);
        return [
            "user" => [
                "id" => $id,
                "firstname" => $user->firstname,
                "lastname" => $user->lastname,
            ],
            "year" => $year,
            "stats" => TaskTimeDay::getUserStats($id, "monthly", $year),
            "allowed" => TaskTimeDay::getAllowedYears(),
        ];
    }
    
    /**
    * Get daily project stats
    *
    * Return daily project stats.
    * @urlParam id integer required Project identifier.
    * @queryParam month string Month in format: "YYYY-MM", Default current month
    * @response 200 {"project": {"id": "1", "name": "Example project name"}, "month": "2022-12", "stats": [{"2023-06-30":{"total":86400,"objects":{"1":{"id":1,"name":"Task name","total":86400}}}}], "allowed" : ["2022-12", "2023-01"]}
    * @header Authorization: Bearer {TOKEN}
    * @group Stats
    */
    public function projectDaily(Request $request, $id)
    {
        $project = Project::find($id);
        if(!$project)
            throw new ObjectNotExist(__("Project does not exist"));
                
        $month = $this->getMonth($request);
        return [
            "project" => [
                "id" => $id,
                "name" => $project->name,
            ],
            "month" => $month,
            "stats" => TaskTimeDay::getProjectStats($id, "daily", $month),
            "allowed" => TaskTimeDay::getAllowedMonths(),
        ];
    }
    
    /**
    * Get monthy project stats
    *
    * Return project user stats.
    * @urlParam id integer required Project identifier.
    * @queryParam year string Year in format: "YYYY", Default current year
    * @response 200 {"project": {"id": "1", "name": "Example project name"}, "year": "2022", "stats": [{"2023-06":{"total":86400,"objects":{"1":{"id":1,"name":"Task name","total":86400}}}}], "allowed" : ["2022", "2023"]}
    * @header Authorization: Bearer {TOKEN}
    * @group Stats
    */
    public function projectMonthly(Request $request, $id)
    {
        $project = Project::find($id);
        if(!$project)
            throw new ObjectNotExist(__("Project does not exist"));
        
        $year = $this->getYear($request);
        return [
            "project" => [
                "id" => $id,
                "name" => $project->name,
            ],
            "year" => $year,
            "stats" => TaskTimeDay::getProjectStats($id, "monthly", $year),
            "allowed" => TaskTimeDay::getAllowedYears(),
        ];
    }
    
    /**
    * Get daily task stats
    *
    * Return daily task stats.
    * @urlParam id integer required Task identifier.
    * @queryParam month string Month in format: "YYYY-MM", Default current month
    * @response 200 {"task": {"id": "1", "name": "Example task name"}, "month": "2022-12", "stats": [{"2023-06-30":{"total":86400,"objects":{"1":{"id":1,"name":"John Doe","total":86400}}}}], "allowed" : ["2022-12", "2023-01"]}
    * @header Authorization: Bearer {TOKEN}
    * @group Stats
    */
    public function taskDaily(Request $request, $id)
    {
        $task = Task::find($id);
        if(!$task)
            throw new ObjectNotExist(__("Task does not exist"));

        $month = $this->getMonth($request);
        return [
            "task" => [
                "id" => $id,
                "name" => $task->name,
            ],
            "month" => $month,
            "stats" => TaskTimeDay::getTaskStats($id, "daily", $month),
            "allowed" => TaskTimeDay::getAllowedMonths(),
        ];
    }
    
    /**
    * Get monthy task stats
    *
    * Return task user stats.
    * @urlParam id integer required Task identifier.
    * @queryParam year string Year in format: "YYYY", Default current year
    * @response 200 {"project": {"id": "1", "name": "Example task name"}, "year": "2022", "stats": [{"2023-06":{"total":86400,"objects":{"1":{"id":1,"name":"John Doe","total":86400}}}}], "allowed" : ["2022", "2023"]}
    * @header Authorization: Bearer {TOKEN}
    * @group Stats
    */
    public function taskMonthly(Request $request, $id)
    {
        $task = Task::find($id);
        if(!$task)
            throw new ObjectNotExist(__("Task does not exist"));
        
        $year = $this->getYear($request);
        return [
            "task" => [
                "id" => $id,
                "name" => $task->name,
            ],
            "year" => $year,
            "stats" => TaskTimeDay::getTaskStats($id, "monthly", $year),
            "allowed" => TaskTimeDay::getAllowedYears(),
        ];
    }
    
    
    private function getMonth(Request $request)
    {
        $request->validate([
            "month" => "nullable|date_format:Y-m"
        ]);
        return $request->input("month", date("Y-m"));
    }
    
    private function getYear(Request $request)
    {
        $request->validate([
            "year" => "nullable|date_format:Y"
        ]);
        return $request->input("year", date("Y"));
    }
}
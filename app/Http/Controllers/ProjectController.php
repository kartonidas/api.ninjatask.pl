<?php

namespace App\Http\Controllers;

use App\Exceptions\ObjectNotExist;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\Project;
use App\Models\User;

class ProjectController extends Controller
{
    /**
    * Get projects list
    *
    * Return projects list.
    * @queryParam size integer Number of rows. Default: 50
    * @queryParam page integer Number of page (pagination). Default: 1
    * @response 200 {"total_rows": 100, "total_pages": "4", "current_page": 1, "has_more": true, "data": [{"id": 1, "name": "Test project", "location": "Warsaw", "description": "", "owner": "john@doe.com"}]}
    * @header Authorization: Bearer {TOKEN}
    * @group Projects
    */
    public function list(Request $request)
    {
        User::checkAccess("project:list");
        
        $request->validate([
            "size" => "nullable|integer|gt:0",
            "page" => "nullable|integer|gt:0",
        ]);
        
        $size = $request->input("size", config("api.list.size"));
        $page = $request->input("page", 1);
        
        $projects = Project
            ::apiFields()
            ->take($size)
            ->skip(($page-1)*$size)
            ->all();
    
        $total = Project->count();
        $out = [
            "total_rows" => $total,
            "total_pages" => ceil($total / $size),
            "current_page" => $page,
            "has_more" => ceil($total / $size) > $page,
            "data" => $projects,
        ];
            
        return $out;
    }
    
    /**
    * Get project details
    *
    * Return project details.
    * @urlParam id integer required Project identifier.
    * @response 200 {"id": 1, "name": "Test project", "location": "Warsaw", "description": "", "owner": "john@doe.com"}
    * @response 404 {"error":true,"message":"Project does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group Projects
    */
    public function get(Request $request, $id)
    {
        User::checkAccess("project:list");
        
        $project = Project::apiFields()->find($id);
        if(!$project)
            throw new ObjectNotExist(__("Project does not exist"));
        
        return $project;
    }
    
    /**
    * Create new project
    *
    * Create new project.
    * @bodyParam name string required Project name.
    * @bodyParam location string Project location.
    * @bodyParam description string Project description.
    * @bodyParam owner string Project owner.
    * @responseField id integer The id of the newly created project
    * @header Authorization: Bearer {TOKEN}
    * @group Projects
    */
    public function create(Request $request)
    {
        User::checkAccess("project:create");
        
        $request->validate([
            "name" => "required|max:250",
            "location" => "nullable|max:5000",
            "description" => "nullable|max:5000",
            "owner" => "nullable|max:5000",
        ]);
        
        $project = new Project;
        $project->name = $request->input("name");
        $project->location = $request->input("location", "");
        $project->description = $request->input("description", "");
        $project->owner = $request->input("owner", "");
        $project->save();
        
        return $project->id;
    }
    
    /**
    * Update project
    *
    * Update project.
    * @urlParam id integer required Project identifier.
    * @bodyParam name string Project name.
    * @bodyParam location string Project location.
    * @bodyParam description string Project description.
    * @bodyParam owner string Project owner.
    * @responseField status boolean Update status
    * @header Authorization: Bearer {TOKEN}
    * @group Projects
    */
    public function update(Request $request, $id)
    {
        User::checkAccess("project:update");
        
        $project = Project::find($id);
        if(!$project)
            throw new ObjectNotExist(__("Project does not exist"));
        
        $rules = [
            "name" => "required|max:250",
            "location" => "nullable|max:5000",
            "description" => "nullable|max:5000",
            "owner" => "nullable|max:5000",
        ];
        
        $validate = [];
        $updateFields = ["name", "location", "description", "owner"];
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
                $project->{$field} = $request->input($field);
        }
        $project->save();
        
        return true;
    }
    
    /**
    * Delete project
    *
    * Delete project.
    * @urlParam id integer required Project identifier.
    * @responseField status boolean Delete status
    * @response 404 {"error":true,"message":"Project does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group Projects
    */
    public function delete(Request $request, $id)
    {
        User::checkAccess("project:delete");
        
        $project = Project::find($id);
        if(!$project)
            throw new ObjectNotExist(__("Project does not exist"));
        
        $project->delete();
        return true;
    }
}
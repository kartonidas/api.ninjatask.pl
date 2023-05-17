<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Exceptions\ObjectNotExist;
use App\Models\User;
use App\Models\UserPermission;
use App\Rules\Permissions;

class PermissionController extends Controller
{
    /**
    * Get permissions group list
    *
    * Return permissions list.
    * @queryParam size integer Number of rows. Default: 50
    * @queryParam page integer Number of page (pagination). Default: 1
    * @response 200 {"total_rows": 100, "total_pages": "4", "current_page": 1, "has_more": true, "data": [{"id":1,"name":"Example group name","permissions":[{"name": "module name", "perm": ["list","create"]}], "is_default": 0}]}
    * @header Authorization: Bearer {TOKEN}
    * @group User permissions
    */
    public function list(Request $request)
    {
        User::checkAccess("permission:list");
        
        $request->validate([
            "size" => "nullable|integer|gt:0",
            "page" => "nullable|integer|gt:0",
        ]);
        
        $size = $request->input("size", config("api.list.size"));
        $page = $request->input("page", 1);
        
        $permissions = UserPermission
            ::apiFields()
            ->take($size)
            ->skip(($page-1)*$size)
            ->orderBy("name", "ASC")
            ->get();
            
        if(!$permissions->isEmpty())
        {
            foreach($permissions as $k => $permission)
            {
                $permArray = [];
                $perm = $permission->getPermission();
                if($perm)
                {
                    foreach($perm as $module => $p)
                        $permArray[] = ["name" => $module, "perm" => $p];
                }
                $permission->permissions = $permArray;
                $permission->is_default = $permission->is_default == 1;
            }
        }
        
        $total = UserPermission::count();
        $out = [
            "total_rows" => $total,
            "total_pages" => ceil($total / $size),
            "current_page" => $page,
            "has_more" => ceil($total / $size) > $page,
            "data" => $permissions,
        ];
            
        return $out;
    }
    
    /**
    * Create new persmission group
    *
    * Create new persmission group. Permissions should look like this:
    * projects:list,create,update,delete;users:list
    * @bodyParam name string required Group permission name.
    * @bodyParam permissions string required Permission.
    * @bodyParam is_default boolean Set permission default.
    * @responseField id integer The id of the newly created permission group
    * @response 422 {"error":true,"message":"'projects:' does not contain a list of items.","errors":{"permissions":["'projects:' does not contain a list of items."]}}
    * @header Authorization: Bearer {TOKEN}
    * @group User permissions
    */
    public function create(Request $request)
    {
        User::checkAccess("permission:create");
        
        $request->validate([
            "name" => "required|max:100",
            "permissions" => ["required", new Permissions],
            "is_default" => "nullable|boolean",
        ]);
        
        $permission = new UserPermission;
        $permission->name = $request->input("name");
        $permission->is_default = $request->input("is_default", 0);
        $permission->permissions = $request->input("permissions");
        $permission->save();
        
        $permission->isDefaultFlag();
        return $permission->id;
    }
    
    /**
    * Get persmission group details
    *
    * Get persmission group details.
    * @urlParam id integer required Permission group identifier.
    * @response 200 {"id":1,"name":"Example group name","permissions":{"module":["list","create"]}, "is_default": 0}
    * @response 404 {"error":true,"message":"Permission does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group User permissions
    */
    public function get(Request $request, $id)
    {
        User::checkAccess("permission:list");
        
        $permission = UserPermission::apiFields()->find($id);
        if(!$permission)
            throw new ObjectNotExist(__("Permission does not exist"));
        
        $permission->permissions = $permission->getPermission();
        return $permission;
    }
    
    /**
    * Update persmission group
    *
    * Update persmission group.
    * @urlParam id integer required Permission group identifier.
    * @bodyParam name string Group permission name.
    * @bodyParam permissions string Permission.
    * @bodyParam is_default boolean Set permission default.
    * @responseField status boolean Update status
    * @header Authorization: Bearer {TOKEN}
    * @group User permissions
    */
    public function update(Request $request, $id)
    {
        User::checkAccess("permission:update");
        
        $permission = UserPermission::apiFields()->find($id);
        if(!$permission)
            throw new ObjectNotExist(__("Permission does not exist"));
        
        $rules = [
            "name" => "required|max:100",
            "permissions" => ["required", new Permissions],
            "is_default" => "nullable|boolean",
        ];
        
        $validate = [];
        $updateFields = ["name", "permissions", "is_default"];
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
                $permission->{$field} = $request->input($field);
        }
        $permission->save();
        $permission->isDefaultFlag();
        
        return true;
    }
    
    /**
    * Delete persmission group
    *
    * Delete persmission group.
    * @urlParam id integer required Permission group identifier.
    * @responseField status boolean Delete status
    * @response 404 {"error":true,"message":"Permission does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group User permissions
    */
    public function delete(Request $request, $id)
    {
        User::checkAccess("permission:delete");
        
        $permission = UserPermission::find($id);
        if(!$permission)
            throw new ObjectNotExist(__("Permission does not exist"));
        
        $permission->delete();
        return true;
    }
    
    /**
    * Add permission to persmission group
    *
    * Add permission to persmission group.
    * @urlParam id integer required Permission group identifier.
    * @bodyParam object string required Object name.
    * @bodyParam action string Action name (if not set, allow all actions).
    * @responseField status boolean Update status
    * @response 404 {"error":true,"message":"Permission does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group User permissions
    */
    public function addPermission(Request $request, $id)
    {
        User::checkAccess("permission:update");
        
        $permission = UserPermission::apiFields()->find($id);
        if(!$permission)
            throw new ObjectNotExist(__("Permission does not exist"));
        
        $request->validate([
            "object" => ["required", Rule::in(array_keys(config("permissions.permission")))],
        ]);
        
        if($request->has("action"))
        {
            $request->validate([
                "action" => ["required", Rule::in(config("permissions.permission")[$request->input("object")]["operation"])],
            ]);
        }
        
        $permission->add($request->input("object"), $request->input("action", "*"));
        return true;
    }
    
    /**
    * Delete permission from persmission group
    *
    * Delete permission from persmission group.
    * @urlParam id integer required Permission group identifier.
    * @bodyParam object string required Object name.
    * @bodyParam action string Action name (if not set, allow all actions).
    * @responseField status boolean Delete status
    * @response 404 {"error":true,"message":"Permission does not exist"}
    * @header Authorization: Bearer {TOKEN}
    * @group User permissions
    */
    public function removePermission(Request $request, $id)
    {
        User::checkAccess("permission:update");
        
        $permission = UserPermission::apiFields()->find($id);
        if(!$permission)
            throw new ObjectNotExist(__("Permission does not exist"));
        
        $request->validate([
            "object" => ["required", Rule::in(array_keys(config("permissions.permission")))],
        ]);
        
        if($request->has("action"))
        {
            $request->validate([
                "action" => ["required", Rule::in(config("permissions.permission")[$request->input("object")]["operation"])],
            ]);
        }
        
        $permission->del($request->input("object"), $request->input("action", "*"));
        return true;
    }
}
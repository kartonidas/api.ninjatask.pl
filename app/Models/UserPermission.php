<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Exceptions\Exception;
use App\Models\Firm;
use App\Models\User;

class UserPermission extends Model
{
    use \App\Traits\UuidTrait {
        boot as traitBoot;
    }
    
    public function canDelete($exception = false)
    {
        if($this->is_default)
        {
            if($exception)
                throw new Exception(__("Cannot deleted default permission."));
            return false;
        }
        
        if(User::where("deleted", 0)->where("user_permission_id", $this->id)->count())
        {
            if($exception)
                throw new Exception(__("Cannot deleted. Permissions are used."));
            return false;
        }
        
        return true;
    }
    
    public function delete()
    {
        $this->canDelete(true);
        return parent::delete();
    }
    
    public function scopeApiFields(Builder $query): void
    {
        $query->select("id", "name", "permissions", "is_default");
    }
    
    public function getPermission()
    {
        $out = [];
        if(trim($this->permissions) != "")
        {
            $permissions = explode(";", $this->permissions);
            if($permissions)
            {
                foreach($permissions as $permission)
                {
                    list($group, $perm) = explode(":", $permission);
                    $out[$group] = explode(",", $perm);
                }
            }
        }
        return $out;
    }
    
    public static function permissionArrayToString($permissions)
    {
        $permissionParts = [];
        foreach($permissions as $object => $actions)
            $permissionParts[] = $object . ":" . implode(",", $actions);
            
        return implode(";", $permissionParts);
    }
    
    public function add($object, $action = "*")
    {
        if($action == "*")
            $action = config("permissions.permission")[$object]["operation"];
            
        if(!is_array($action))
            $action = [$action];
        
        $permissions = $this->getPermission();
        if(!isset($permissions[$object]))
            $permissions[$object] = $action;
        else
            $permissions[$object] = array_unique(array_merge($permissions[$object], $action));
        
        $this->permissions = self::permissionArrayToString($permissions);
        $this->save();
    }
    
    public function del($object, $action = "*")
    {
        $permissions = $this->getPermission();
        
        if($action == "*")
            unset($permissions[$object]);
        else
        {
            if(isset($permissions[$object]))
            {
                $index = array_search($action, $permissions[$object]);
                if($index !== false)
                    unset($permissions[$object][$index]);
            }
        }
        
        $this->permissions = self::permissionArrayToString($permissions);
        $this->save();
    }
    
    public static function getDefault()
    {
        $default = self::select("id")->where("is_default", 1)->first();
        return $default ? $default->id : false;
    }
    
    public static function getIds()
    {
        $ids = [];
        foreach(self::all() as $row)
            $ids[] = $row->id;
        return $ids;
    }
    
    public function isDefaultFlag()
    {
        if($this->is_default)
        {
            foreach(self::where("id", "!=", $this->id)->get() as $row)
            {
                $row->is_default = 0;
                $row->save();
            }
        }
    }
}
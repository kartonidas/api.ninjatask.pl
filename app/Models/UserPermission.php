<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserPermission extends Model
{
    use \App\Traits\UuidTrait {
        boot as traitBoot;
    }
    
    public function canDelete($exception = false)
    {
        return false;
    }
    
    public function delete()
    {
        $this->canDelete(true);
        return parent::delete();
    }
    
    public function scopeApiFields(Builder $query): void
    {
        $query->select("id", "name", "permissions");
    }
    
    public function getPermission()
    {
        $out = [];
        $permissions = explode(";", $this->permissions);
        if($permissions)
        {
            foreach($permissions as $permission)
            {
                list($group, $perm) = explode(":", $permission);
                $out[$group] = explode(",", $perm);
            }
        }
        return $out;
    }
    
    private static function permissionArrayToString($permissions)
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
}
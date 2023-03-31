<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Permissions implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $allowedPermissions = config("permissions.permission");
        
        $permissions = explode(";", $value);
        if($permissions)
        {
            foreach($permissions as $permission)
            {
                if(strpos($permission, ":") === false)
                {
                    $fail(sprintf(__("'%s' does not contain a list of actions."), $permission));
                    continue;
                }
                
                list($group, $perm) = explode(":", $permission);
                
                if(!in_array($group, array_keys($allowedPermissions)))
                {
                    $fail(sprintf(__("Object '%s' not exist."), $group));
                    continue;
                }
                
                if(!trim($perm))
                {
                    $fail(sprintf(__("'%s' does not contain a list of actions."), $group));
                    continue;
                }
                $perm = explode(",", $perm);
                foreach($perm as $p)
                {
                    if(!in_array($p, $allowedPermissions[$group]["operation"]))
                    {
                        $fail(sprintf(__("'%s' contain invalid action '%s'."), $group, $p));
                        continue;
                    }
                }
            }
        }
    }
}

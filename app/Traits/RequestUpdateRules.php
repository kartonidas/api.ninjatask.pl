<?php

namespace App\Traits;

trait RequestUpdateRules
{
    public function addSometimesToRules($rules, $skip = [])
    {
        foreach($rules as $f => $rule)
        {
            if(in_array($f, $skip))
                continue;
            
            $asString = false;
            if(!is_array($rule))
            {
                $rule = explode("|", $rule);
                $asString = true;
            }
            
            if(!in_array("sometimes", $rule))
                array_unshift($rule, "sometimes");
                
            $rules[$f] = $asString ? implode("|", $rule) : $rule;
        }
        return $rules;
    }
}
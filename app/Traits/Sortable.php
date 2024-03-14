<?php

namespace App\Traits;
use Illuminate\Http\Request;

trait Sortable
{
    public function getOrderBy(Request $request, $model, string|null $default)
    {
        $sort = $order = null;
        
        if($default !== null)
            list($sort, $order) = explode(",", $default);
            
        if(property_exists($model, "sortable") && property_exists($model, "defaultSortable"))
        {
            if(!empty($request->input("sort")) && in_array($request->input("sort"), $model::$sortable))
            {
                $sort = $request->input("sort");
                if(!empty($request->input("order")))
                    $order = $request->input("order") == -1 ? "desc" : "asc";
            }
        }
        
        return [$sort, $order];
    }
}
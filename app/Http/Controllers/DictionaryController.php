<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

use App\Exceptions\ObjectNotExist;
use App\Models\Dictionary;
use App\Models\User;

class DictionaryController extends Controller
{
    public function list(Request $request)
    {
        User::checkAccess("dictionary:list");
        
        $request->validate([
            "size" => "nullable|integer|gt:0",
        ]);
        $size = $request->input("size", config("api.list.size"));
        
        $types = Dictionary::getAllowedTypes();
        
        $out = [];
        foreach($types as $type => $tmp)
        {
            $out[$type] = [];
            $dictionaries = Dictionary::where("type", $type)->orderBy("name", "ASC")->take($size)->get();
            foreach($dictionaries as $i => $dictionary)
            {
                $dictionaries[$i]->can_delete = $dictionary->canDelete();
                $out[$type][] = $dictionary;
            }
        }
        
        return $out;
    }
    
    public function listByType(Request $request, string $type)
    {
        User::checkAccess("dictionary:list");
        
        $validated = $request->validate([
            "size" => "nullable|integer|gt:0",
            "page" => "nullable|integer|gt:0",
            "first" => "nullable|integer|gte:0",
        ]);
        
        $size = $validated["size"] ?? config("api.list.size");
        $page = $request->input("page", 1);
        
        $dictionaries = Dictionary
            ::where("type", $type);
            
        $total = $dictionaries->count();
            
        $dictionaries = $dictionaries->take($size)
            ->skip(($page-1)*$size)
            ->orderBy("name", "ASC")
            ->get();
            
        foreach($dictionaries as $i => $dictionary)
        {
            $dictionaries[$i]->can_delete = $dictionary->canDelete();
        }
        
        $out = [
            "total_rows" => $total,
            "total_pages" => ceil($total / $size),
            "current_page" => $page,
            "has_more" => ceil($total / $size) > $page,
            "data" => $dictionaries,
        ];
            
        return $out;
    }
    
    public function create(Request $request)
    {
        User::checkAccess("dictionary:create");
        
        $request->validate([
            "type" => ["required", Rule::in(array_keys(Dictionary::getAllowedTypes()))],
            "name" => ["required", "max:80"]
        ]);
        
        $item = new Dictionary;
        $item->type = $request->input("type");
        $item->name = $request->input("name");
        $item->active = $request->input("active", 0);
        $item->save();
        
        return $item->id;
    }
    
    public function get(Request $request, $dictionaryId)
    {
        User::checkAccess("dictionary:list");
        
        $dictionary = Dictionary::find($dictionaryId);
        if(!$dictionary)
            throw new ObjectNotExist(__("Dictionary does not exist"));
        
        return $dictionary;
    }
    
    public function update(Request $request, $dictionaryId)
    {
        User::checkAccess("dictionary:update");
        
        $dictionary = Dictionary::find($dictionaryId);
        if(!$dictionary)
            throw new ObjectNotExist(__("Dictionary does not exist"));
        
        $rules = [
            "name" => "required|max:80",
        ];
        
        $validate = [];
        $updateFields = ["name", "active"];
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
                $dictionary->{$field} = $request->input($field);
        }
        $dictionary->save();
        
        return true;
    }
    
    public function delete(Request $request, $dictionaryId)
    {
        User::checkAccess("dictionary:delete");
        
        $dictionary = Dictionary::find($dictionaryId);
        if(!$dictionary)
            throw new ObjectNotExist(__("Dictionary does not exist"));
        
        $dictionary->delete();
        return true;
    }
    
    public function types(Request $request)
    {
        return Dictionary::getAllowedTypes();
    }
}
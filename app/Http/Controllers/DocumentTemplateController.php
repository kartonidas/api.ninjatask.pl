<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

use App\Exceptions\ObjectNotExist;
use App\Http\Requests\DocumentTemplateRequest;
use App\Http\Requests\StoreDocumentTemplateRequest;
use App\Http\Requests\UpdateDocumentTemplateRequest;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVariable;
use App\Models\User;
use App\Traits\Sortable;

class DocumentTemplateController extends Controller
{
    use Sortable;
    
    public function list(DocumentTemplateRequest $request)
    {
        $validated = $request->validated();

        $size = $validated["size"] ?? config("api.list.size");
        $page = $request->input("page", 1);
        
        $documentTemplates = DocumentTemplate::whereRaw("1=1");
        
        if(!empty($validated["search"]))
        {
            if(!empty($validated["search"]["title"]))
                $documentTemplates->where("title", "LIKE", "%" . $validated["search"]["title"] . "%");
            if(!empty($validated["search"]["type"]))
                $documentTemplates->where("type", $validated["search"]["type"]);
        }
        
        $total = $documentTemplates->count();
        
        $orderBy = $this->getOrderBy($request, DocumentTemplate::class, "title,asc");
        $documentTemplates = $documentTemplates->take($size)
            ->skip(($page-1)*$size)
            ->orderBy($orderBy[0], $orderBy[1])
            ->get();
            
        $out = [
            "total_rows" => $total,
            "total_pages" => ceil($total / $size),
            "current_page" => $page,
            "has_more" => ceil($total / $size) > $page,
            "data" => $documentTemplates,
        ];
            
        return $out;
    }
    
    public function listGroupByType(DocumentTemplateRequest $request)
    {
        $validated = $request->validated();

        $size = $validated["size"] ?? config("api.list.size");
        $page = $request->input("page", 1);
        
        $documentTemplates = DocumentTemplate::whereRaw("1=1");
        $total = $documentTemplates->count();
        
        $orderBy = $this->getOrderBy($request, DocumentTemplate::class, "title,asc");
        $documentTemplates = $documentTemplates->take($size)
            ->skip(($page-1)*$size)
            ->orderBy($orderBy[0], $orderBy[1])
            ->get();
        
        $out = [];
        
        foreach($documentTemplates as $documentTemplate)
        {
            $out[$documentTemplate->type][] = [
                "id" => $documentTemplate->id,
                "name" => $documentTemplate->title,
            ];
        }
        
        return $out;
    }
    
    public function create(StoreDocumentTemplateRequest $request)
    {
        User::checkAccess("config:update");
        
        $validated = $request->validated();
        DocumentTemplateVariable::checkUniqueVariableNames($validated["template_variables"] ?? []);
        
        $documentTemplate = new DocumentTemplate;
        $documentTemplate->type = $validated["type"];
        $documentTemplate->title = $validated["title"];
        $documentTemplate->content = $validated["content"];
        $documentTemplate->save();
        
        $documentTemplate->updateVariables($validated["template_variables"] ?? []);
        
        return $documentTemplate->id;
    }
    
    public function get(Request $request, int $documentTemplateId)
    {
        User::checkAccess("config:update");
        
        $documentTemplate = DocumentTemplate::find($documentTemplateId);
        if(!$documentTemplate)
            throw new ObjectNotExist(__("Document template does not exist"));
        
        $documentTemplate->template_variables = $documentTemplate->getTemplateVariables();
        
        return $documentTemplate;
    }
    
    public function update(UpdateDocumentTemplateRequest $request, int $documentTemplateId)
    {
        User::checkAccess("config:update");
        
        $documentTemplate = DocumentTemplate::find($documentTemplateId);
        if(!$documentTemplate)
            throw new ObjectNotExist(__("Document template does not exist"));
        
        $validated = $request->validated();
        DocumentTemplateVariable::checkUniqueVariableNames($validated["template_variables"] ?? []);
        
        foreach($validated as $field => $value)
        {
            if($field == "template_variables")
                continue;
            $documentTemplate->{$field} = $value;
        }
        $documentTemplate->save();
        
        $documentTemplate->updateVariables($validated["template_variables"] ?? []);
        
        return true;
    }
    
    public function delete(Request $request, int $documentTemplateId)
    {
        User::checkAccess("config:update");
        
        $documentTemplate = DocumentTemplate::find($documentTemplateId);
        if(!$documentTemplate)
            throw new ObjectNotExist(__("Document template does not exist"));
        
        $documentTemplate->delete();
        return true;
    }
}
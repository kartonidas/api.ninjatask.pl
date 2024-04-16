<?php

namespace App\Http\Controllers;

use PDF;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Exceptions\Exception;
use App\Exceptions\ObjectNotExist;
use App\Http\Requests\DocumentRequest;
use App\Http\Requests\GenerateTemplateDocumentRequest;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Libraries\TemplateManager;
use App\Libraries\Helper;
use App\Models\Customer;
use App\Models\Document;
use App\Models\DocumentTemplate;
use App\Models\Task;
use App\Models\User;
use App\Traits\Sortable;

class DocumentController extends Controller
{
    use Sortable;
    
    public function list(DocumentRequest $request) {
        User::checkAccess("document:list");
        
        $validated = $request->validated();
        
        $size = $validated["size"] ?? config("api.list.size");
        $skip = isset($validated["first"]) ? $validated["first"] : (($validated["page"] ?? 1)-1)*$size;
        
        $documents = Document::whereRaw("1=1");
            
        if(!empty($validated["search"]))
        {
            if(!empty($validated["search"]["title"]))
                $documents->where("title", "LIKE", "%" . $validated["search"]["title"] . "%");
                
            if(!empty($validated["search"]["item_name"]))
            {
                $itemIds = Item::where("name", "LIKE", "%" . $validated["search"]["item_name"] . "%")->pluck("id")->all();
                $documents->whereIn("item_id", $itemIds);
            }
                
            if(!empty($validated["search"]["type"]))
                $documents->where("type", $validated["search"]["type"]);
        }
            
        $total = $documents->count();
        
        $orderBy = $this->getOrderBy($request, Document::class, "created_at,desc");
        $documents = $documents->take($size)
            ->skip($skip)
            ->orderBy($orderBy[0], $orderBy[1])
            ->get();
            
        foreach($documents as $i => $document)
        {
            unset($documents[$i]->content);
        }
        
        $out = [
            "total_rows" => $total,
            "total_pages" => ceil($total / $size),
            "data" => $documents,
        ];
            
        return $out;
    }
    
    public function getDocumentPdf(Request $request, int $documentId)
    {
        User::checkAccess("document:list");
        
        $document = Document::find($documentId);
        if(!$document)
            throw new ObjectNotExist(__("Document does not exist"));
        
        $customer = Customer::find($document->customer_id);
        if(!$customer)
            throw new ObjectNotExist(__("Customer does not exist"));
        
        $manager = TemplateManager::getTemplate($customer);
        $html = $manager->generateHtml($document->content);
        
        $pdf = PDF::loadView("pdf.customer_document", ["content" => $html]);
        $pdf->getMpdf()->SetTitle(Helper::__no_pl($document->title) . ".pdf");
        $pdf->stream(Helper::__no_pl($document->title) . ".pdf");
    }
    
    public function get(Request $request, int $documentd)
    {
        User::checkAccess("document:list");
        
        $document = Document::find($documentd);
        if(!$document)
            throw new ObjectNotExist(__("Document does not exist"));
        
        return $document;
    }
    
    public function generateTemplateDocument(GenerateTemplateDocumentRequest $request)
    {
        User::checkAccess("document:create");
        
        $validated = $request->validated();
        
        $customer = Customer::find($validated["customer_id"]);
        if(!$customer)
            throw new ObjectNotExist(__("Customer does not exist"));
        
        $task = null;
        if(!empty($validated["task_id"]))
        {
            $task = Task::find($validated["task_id"]);
            if(!$task)
                throw new ObjectNotExist(__("Task does not exist"));
        }
        
        $documentTemplate = DocumentTemplate::find($validated["template"]);
        if(!$documentTemplate || $documentTemplate->type != $validated["type"])
            throw new ObjectNotExist(__("Template does not exists"));
        
        $title = DocumentTemplate::getTypes()[$validated["type"]];
        
        return [
            "content" => $documentTemplate->generateDocument($customer, $task),
            "title" => $title
        ];
    }
    
    public function create(StoreDocumentRequest $request)
    {
        User::checkAccess("document:create");
        
        $validated = $request->validated();
        
        $customer = Customer::find($validated["customer_id"]);
        if(!$customer)
            throw new ObjectNotExist(__("Customer does not exist"));
        
        $task = null;
        if(!empty($validated["task_id"]))
        {
            $task = Task::find($validated["task_id"]);
            if(!$task)
                throw new ObjectNotExist(__("Task does not exist"));
        }
        
        $document = new Document;
        $document->customer_id = $validated["customer_id"];
        $document->task_id = $validated["task_id"] ?? null;
        $document->user_id = Auth::user()->id;
        $document->title = $validated["title"];
        $document->content = $validated["content"];
        $document->type = $validated["type"];
        $document->save();
        
        return $document->id;
    }
    
    public function update(UpdateDocumentRequest $request, int $documentId)
    {
        User::checkAccess("document:update");
        
        $document = Document::find($documentId);
        if(!$document)
            throw new ObjectNotExist(__("Document does not exist"));
        
        $validated = $request->validated();
        
        $document->title = $validated["title"];
        $document->content = $validated["content"];
        $document->save();
        
        return true;
    }
    
    public function delete(Request $request, int $documentId)
    {
        User::checkAccess("document:delete");
        $document = Document::find($documentId);

        if(!$document)
            throw new ObjectNotExist(__("Document does not exist"));
        
        $document->delete();
        
        return true;
    }
}
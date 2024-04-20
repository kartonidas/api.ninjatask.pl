<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

use App\Libraries\TemplateManager;
use App\Models\Customer;
use App\Models\DocumentTemplateVariable;
use App\Models\Task;
use App\Models\User;

class DocumentTemplate extends Model
{
    use \App\Traits\UuidTrait {
        boot as traitBoot;
    }
    
    const TYPE_AGREEMENT = "agreement";
    const TYPE_ANNEX = "annex";
    const TYPE_HANDOVER = "handover_protocol";
    const TYPE_OTHER = "other";
    
    public static $sortable = ["title"];
    public static $defaultSortable = ["title", "asc"];
    
    protected $hidden = ["uuid"];
    
    public function delete()
    {
        DocumentTemplateVariable::where("document_template_id", $this->id)->delete();
        return parent::delete();
    }
    
    public static function getTypes()
    {
        return [
            self::TYPE_AGREEMENT => __("Agreement"),
            self::TYPE_ANNEX => __("Annex"),
            self::TYPE_HANDOVER => __("Handover protocol"),
            self::TYPE_OTHER => __("Other")
        ];
    }
    
    public function generateDocument(Customer $customer)
    {
        $manager = TemplateManager::getTemplate($customer);
        return $manager->generateHtml($this->content);
    }
    
    public function getTemplateVariables()
    {
        return DocumentTemplateVariable::where("document_template_id", $this->id)->get();
    }
    
    public function updateVariables($variables = [])
    {
        $usedIds = [];
        foreach($variables as $variable)
        {
            $variableRow = DocumentTemplateVariable::where("id", $variable["id"])->where("document_template_id", $this->id)->first();
            if(!$variableRow)
            {
                $variableRow = new DocumentTemplateVariable;
                $variableRow->document_template_id = $this->id;
            }
            
            $variableRow->type = $variable["type"];
            $variableRow->name = $variable["name"];
            $variableRow->variable = $variable["variable"];
            $variableRow->item_values = $variable["item_values"] ?? null;
            $variableRow->save();
            
            $usedIds[] = $variableRow->id;
        }
        
        DocumentTemplateVariable::whereNotIn("id", $usedIds)->where("document_template_id", $this->id)->delete();
    }
}
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVariable;

class StoreDocumentTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            "type" => ["required", Rule::in(array_keys(DocumentTemplate::getTypes()))],
            "title" => "required|max:200",
            "content" => "required",
            "template_variables" => ["sometimes", "array"]
        ];
        
        if(!empty($this->template_variables) && is_array($this->template_variables))
        {
            $varRules = [];
            foreach($this->template_variables as $i => $variable)
            {
                $varRules = [
                    "template_variables.$i.id" => ["required", "integer"],
                    "template_variables.$i.name" => ["required", "max:200"],
                    "template_variables.$i.type" => ["required", Rule::in(array_keys(DocumentTemplateVariable::getAllowedTypes()))],
                    "template_variables.$i.variable" => ["required", "distinct"],
                ];
                
                if(!empty($variable["type"]) && $variable["type"] == DocumentTemplateVariable::FIELD_SELECT)
                    $varRules["template_variables.$i.item_values"] = ["required"];
                   
                $rules = array_merge($rules, $varRules);
            }
        }
        
        return $rules;
    }
}

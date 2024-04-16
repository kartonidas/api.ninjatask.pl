<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\DocumentTemplate;

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
        ];
            
        return $rules;
    }
}

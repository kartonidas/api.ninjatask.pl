<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\DocumentTemplate;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            "type" => ["required", Rule::in(array_keys(DocumentTemplate::getTypes()))],
            "content" => "required",
            "title" => "required|max:200",
            "task_id" => "nullable|integer",
            "customer_id" => "required|integer",
        ];
        
        return $rules;
    }
}


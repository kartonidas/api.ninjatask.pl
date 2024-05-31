<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\DocumentTemplate;

class DocumentTemplateRequest extends ListRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            "search.type" => ["nullable", Rule::in(array_keys(DocumentTemplate::getTypes()))],
            "search.title" => "nullable|string",
        ]);
    }
}

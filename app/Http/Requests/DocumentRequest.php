<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\DocumentTemplate;

class DocumentRequest extends ListRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            "search.title" => "nullable|string",
            "search.item_name" => "nullable|string",
            "search.type" => ["nullable", Rule::in(array_keys(DocumentTemplate::getTypes()))],
        ]);
    }
}

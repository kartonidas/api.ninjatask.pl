<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\StoreDocumentTemplateRequest;
use App\Traits\RequestUpdateRules;

class UpdateDocumentTemplateRequest extends StoreDocumentTemplateRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules["type"]);
        return $rules;
    }
}

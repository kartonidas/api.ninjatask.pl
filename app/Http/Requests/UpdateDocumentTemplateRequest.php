<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\StoreDocumentTemplateRequest;
use App\Traits\RequestUpdateRules;

class UpdateDocumentTemplateRequest extends StoreDocumentTemplateRequest
{
    use RequestUpdateRules;
    
    public function rules(): array
    {
        return $this->addSometimesToRules(parent::rules());
    }
}

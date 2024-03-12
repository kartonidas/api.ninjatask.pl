<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\StoreCustomerRequest;
use App\Traits\RequestUpdateRules;

class UpdateCustomerRequest extends StoreCustomerRequest
{
    use RequestUpdateRules;
    
    public function rules(): array
    {
        return $this->addSometimesToRules(parent::rules());
    }
}

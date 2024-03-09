<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\StoreSaleRegisterRequest;
use App\Traits\RequestUpdateRules;

class UpdateSaleRegisterRequest extends StoreSaleRegisterRequest
{
    use RequestUpdateRules;
    
    public function rules(): array
    {
        return $this->addSometimesToRules(parent::rules());
    }
}

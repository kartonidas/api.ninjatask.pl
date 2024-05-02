<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentSignatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            "signature" => "required|regex:/^data:image\/png;base64,/i",
        ];
        
        return $rules;
    }
    
    public function messages(): array
    {
        $messages = [
            "signature.required" => __("Signature is required"),
            "signature.regexp" => __("Invalid siganture"),
        ];
        
        return $messages;
    }
}

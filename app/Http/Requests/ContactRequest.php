<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            "name" => "required|max:100",
            "email" => "required|email",
            "message" => "required|max:2000",
        ];
       
        return $rules;
    }
    
    public function messages(): array
    {
        return [
            "name.required" => "Uzupełnij imię",
            "name.max" => "Maksymalna długość w polu imię: :max znaków",
            "email.required" => "Uzupełnij adres e-mail",
            "email.email" => "Nieprawidłowy adres e-mail",
            "message.required" => "Uzupełnij wiadomość",
            "message.max" => "Maksymalna długość w polu wiadomość: :max znaków",
        ];
    }
}

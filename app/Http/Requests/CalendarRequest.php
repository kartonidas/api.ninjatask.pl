<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CalendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "user_id" => "sometimes|integer",
            "date_from" => "required|date_format:Y-m-d",
            "date_to" => "required|date_format:Y-m-d|after_or_equal:date_from",
        ];
    }
}
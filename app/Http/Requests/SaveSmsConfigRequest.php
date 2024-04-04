<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSmsConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            "task_attach.send" => "required|boolean",
            "task_attach.message" => "required_if:task_attach.send,1|max:480",
            "task_reminder.send" => "required|boolean",
            "task_reminder.message" => "required_if:task_reminder.send,1|max:480",
            "task_reminder.days" => "required|integer|gte:1|lte:30",
        ];
        
        return $rules;
    }
}

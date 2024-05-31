<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

use App\Libraries\Data;
use App\Models\Status;
use App\Models\Task;
use App\Rules\Attachment;

class TaskStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    
    protected function prepareForValidation()
    {
        if(!empty($this->users) && is_array($this->users))
        {
            $users = $this->users;
            foreach($users as $i => $user) {
                if($user == -1)
                    $users[$i] = Auth::user()->id;
            }
            $users = array_unique($users);
            
            $this->merge([
                "users" => $users,
            ]);
        }
    }
    
    public function rules(): array
    {
        $rules = [
            "project" => "required_without:project_id|array",
            "project_id" => "required_without:project|integer",
            "customer" => "nullable|array",
            "name" => "required|max:250",
            "description" => "nullable|max:5000",
            "users" => ["nullable", "array", Rule::in(Task::getAllowedUserIds())],
            "priority" => ["nullable", Rule::in(array_keys(Task::getAllowedPriorities()))],
            "status_id" => ["nullable", "numeric", Rule::in(Status::getAllowedStatuses())],
            "start_date" => ["nullable", "date_format:Y-m-d"],
            "start_date_time" => ["nullable", Rule::in(Data::getAllowedTimes())],
            "end_date" => ["nullable", "date_format:Y-m-d"],
            "end_date_time" => ["nullable", Rule::in(Data::getAllowedTimes())],
            "due_date" => ["nullable", "date_format:Y-m-d"],
            "cost_gross" => ["nullable", "numeric", "gte:0"],
            "attachments" => ["nullable", "array", new Attachment],
        ];
        
        if(!empty($this->customer) && is_array($this->customer))
        {
            if(!empty($this->customer["new_customer"]))
            {
                $rules["customer.new_customer"] = ["required"];
                $rules["customer.type"] = ["required"];
                $rules["customer.name"] = ["required"];
                $rules["customer.street"] = ["sometimes"];
                $rules["customer.house_no"] = ["sometimes"];
                $rules["customer.apartment_no"] = ["sometimes"];
                $rules["customer.city"] = ["sometimes"];
                $rules["customer.zip"] = ["sometimes"];
                $rules["customer.country"] = ["sometimes"];
                $rules["customer.nip"] = ["sometimes"];
                $rules["customer.contacts"] = ["sometimes", "array"];
            }
            else
            {
                $rules["customer.id"] = ["required", "integer"];
            }
        }
        if(!empty($this->project) && is_array($this->project))
        {
            if(!empty($this->project["new_project"]))
            {
                $rules["project.new_project"] = ["required"];
                $rules["project.name"] = ["required"];
                $rules["project.address"] = ["sometimes"];
                $rules["project.description"] = ["sometimes"];
                $rules["project.location"] = ["sometimes"];
                $rules["project.lat"] = ["sometimes"];
                $rules["project.lon"] = ["sometimes"];
            }
            else
            {
                $rules["project.id"] = ["required", "integer"];
            }
        }
        return $rules;
    }
    
    public function messages(): array
    {
        return [
            "project.required" => __("Select or add place"),
        ];
    }
}
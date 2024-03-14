<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Country;
use App\Models\Numbering;

class UpdateCustomerInvoiceDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            "use_invoice_firm_data" => "sometimes|boolean",
            "invoicing_type" => ["required", Rule::in("app" ,"infakt", "fakturownia", "wfirma")],
        ];
        
        $invoicingType = "app";
        if(!empty($this->invoicing_type) && in_array($this->invoicing_type, ["infakt", "fakturownia", "wfirma"]))
            $invoicingType = $this->invoicing_type;
        
        switch($invoicingType)
        {
            case "app":
                $rules["invoice_mask_number"] = "required|max:100";
                $rules["proforma_mask_number"] = "required|max:100";
                $rules["invoice_number_continuation"] = ["required", Rule::in(array_keys(Numbering::getNumberingContinuation()))];
                $rules["proforma_number_continuation"] = ["required", Rule::in(array_keys(Numbering::getNumberingContinuation()))];
                
                if(empty($this->use_invoice_firm_data))
                {
                    $rules["type"] = "required|in:firm,person";
                    $rules["street"] = "required|max:80";
                    $rules["house_no"] = "required|max:20";
                    $rules["apartment_no"] = "nullable|max:20";
                    $rules["city"] = "required|max:120";
                    $rules["zip"] = "required|max:10";
                    $rules["country"] = ["required", Rule::in(Country::getAllowedCodes())];
                    
                    if(empty($this->type) || $this->type == "firm")
                    {
                        if(empty($this->country) || strtolower($this->country) == "pl")
                            $rules["nip"] = ["required", new \App\Rules\Nip];
                        else
                            $rules["nip"] = "required";
                            
                        $rules["name"] = "required|max:200";
                    }
                    else
                    {
                        $rules["firstname"] = "required|max:100";
                        $rules["lastname"] = "required|max:100";
                    }
                }
            break;
        
            case "infakt":
                $rules["infakt_api_key"] = "required|max:1000";
            break;
        
            case "fakturownia":
                $rules["fakturownia_token"] = "required|max:1000";
                $rules["fakturownia_department_id"] = "required|max:1000";
                $rules["fakturownia_domain"] = "required|max:1000";
            break;
        
            case "wfirma":
                $rules["wfirma_access_key"] = "required|max:100";
                $rules["wfirma_secret_key"] = "required|max:100";
            break;
        }
        
        return $rules;
    }
}


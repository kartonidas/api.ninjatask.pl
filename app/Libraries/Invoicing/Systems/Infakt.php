<?php

namespace App\Libraries\Invoicing\Systems;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

use App\Exceptions\Exception;
use App\Libraries\Invoicing\InvoicingInterface;
use App\Models\CustomerInvoice;
use App\Models\Config;

class Infakt implements InvoicingInterface
{
    private $apiKey = false;
    private $serviceUrl = "https://api.sandbox-infakt.pl/api/v3/";
    
    public function initialize($config = [])
    {
        if(empty($config))
            $config = Config::getConfig("invoice");
          
        $this->apiKey = $config["infakt_api_key"];
        try
        {
            $this->apiKey = Crypt::decryptString($this->apiKey);
        }
        catch (Exception $e) {}
        
        if(empty($this->apiKey))
            throw new Exception(__("Invalid infakt.pl configuration"));
        
        return $this;
    }
    
    public function newInvoice(CustomerInvoice $invoice)
    {
        $data = $this->prepareData($invoice);
        
        $response = Http
            ::withOptions([
                "headers" => $this->getAuthHeaders()
            ])
            ->withBody(json_encode(["invoice" => $data]), "application/json")
            ->post($this->serviceUrl . "invoices.json");
        
        $response = $this->parseResponse($response);
        
        if(!empty($response["number"]))
        {
            $this->markAsAccounted($response);
            
            $invoice->external_invoicing_system_id = $response["id"];
            $invoice->full_number = $response["number"];
            $invoice->save();
        }
    }
    
    public function updateInvoice(CustomerInvoice $invoice)
    {
        if(!$invoice->external_invoicing_system_id)
            throw new Exception(__("Invoice was not created using inFakt.pl"));
        
        $data = $this->prepareData($invoice);
        $response = Http
            ::withOptions([
                "headers" => $this->getAuthHeaders()
            ])
            ->withBody(json_encode(["invoice" => $data]), "application/json")
            ->put($this->serviceUrl . "invoices/" . $invoice->external_invoicing_system_id . ".json");
        
        $response = $this->parseResponse($response);
        
        $this->markAsAccounted($response);
    }
    
    public function makeFromProforma(CustomerInvoice $invoice)
    {
        //$proforma = CustomerInvoice::find($invoice->proforma_id);
        //if(!$proforma || $proforma->system != "infakt" || empty($proforma->external_invoicing_system_id))
        //    throw new Exception(__("Proforma does not exists"));
        //
        //$data = $this->prepareData($invoice);
        //$data["proforma_number"] = $proforma->full_number;
        //
        //$response = Http
        //    ::withOptions([
        //        "headers" => $this->getAuthHeaders()
        //    ])
        //    ->withBody(json_encode(["invoice" => $data]), "application/json")
        //    ->post($this->serviceUrl . "invoices.json");
        //echo $response->body();
        //$response = $this->parseResponse($response);
        //
        //if(!empty($response["number"]))
        //{
        //    $this->markAsAccounted($response);
        //    
        //    $invoice->external_invoicing_system_id = $response["id"];
        //    $invoice->full_number = $response["number"];
        //    $invoice->save();
        //}
    }
    
    public function downloadInvoice(CustomerInvoice $invoice)
    {
        if(!$invoice->external_invoicing_system_id)
            throw new Exception(__("Invoice was not created using inFakt.pl"));
        
        $data = [];
        $data["document_type"] = "original";
        
        $response = Http
            ::withHeaders($this->getAuthHeaders())
            ->get($this->serviceUrl . "invoices/" . $invoice->external_invoicing_system_id . "/pdf.json", $data);
        
        $response = $this->parseResponse($response, false);
        return $response;
    }
    
    private function markAsAccounted($invoice)
    {
        try {
            Http
                ::withOptions([
                    "headers" => $this->getAuthHeaders()
                ])
                ->put($this->serviceUrl . "invoices/" . $invoice["uuid"] . "/mark_as_accounted.json");
        } catch(Exception $e) {}
    }
    
    private function getAuthHeaders()
    {
        return [
            "Content-Type" => "application/json",
            "X-inFakt-ApiKey" => $this->apiKey
        ];
    }
    
    private function prepareData(CustomerInvoice $invoice)
    {
        $items = $invoice->items()->get();
        
        $data = [];
        $data["kind"] = $this->getInvoiceKind($invoice);
        $data["currency"] = $invoice["currency"];
        $data["gross_price"] = intval($invoice["gross_amount"] * 100);
        $data["client_company_name"] = $invoice["customer_name"];
        $data["client_street"] = $invoice["customer_street"];
        $data["client_street_number"] = $invoice["customer_house_no"] ?? "";
        $data["client_flat_number"] = $invoice["customer_apartment_no"] ?? "";
        $data["client_city"] = $invoice["customer_city"];
        $data["client_post_code"] = $invoice["customer_zip"];
        if(!empty($invoice["customer_nip"]))
            $data["client_tax_code"] = $invoice["customer_nip"];
        
        $data["payment_method"] = $invoice["payment_type"];
        $data["invoice_date"] = $invoice["document_date"];
        $data["payment_date"] = $invoice["payment_date"];
        $data["sale_date"] = $invoice["sell_date"];
        
        $services = [];
        foreach($items as $item)
        {
            $services[] = [
                "name" => $item["name"],
                "quantity" => $item["quantity"],
                "gross_price" => intval($item["total_gross_amount"]*100),
                "tax_symbol" => $item["vat_value"],
                "unit" => $item["unit_type"],
            ];
        }
        $data["services"] = $services;
        
        return $data;
    }
    
    private function getInvoiceKind(CustomerInvoice $invoice)
    {
        switch($invoice->type)
        {
            case "invoice": return "vat";
            case "proforma": return "proforma";
        }
        
        return "vat";
    }
    
    private function parseResponse($response, $json = true)
    {
        $status = $response->status();
        
        if($status == 200 || $status == 201)
            return $json ? $response->json() : $response->body();
        
        if($status >= 400)
        {
            switch($status)
            {
                case 401:
                    throw new Exception(__("Invalid authorization data. Check your integration settings."));
                break;
                case 403:
                    $encoded = $response->json();
                    if(!empty($encoded["limit"]))
                    {
                        self::setLimitWarning("InFakt.pl : " . $encoded["error"]);
                        throw new Exception("InFakt.pl : " . $encoded["error"]);
                    }
                    throw new Exception(__("Unexpected error occurred."));
                break;
            
                case 404:
                    throw new Exception(__("Obiect not exists."));
                break;
            
                case 422:
                    $encoded = $response->json();
                    if(!empty($encoded["error"]))
                    {
                        $errors = [];
                        if(!empty($encoded["errors"]))
                        {
                            foreach($encoded["errors"] as $error)
                            {
                                if(is_array($error))
                                    $errors = array_merge($errors, $error);
                                else
                                    $errors[] = $error;
                            }
                        }
                        throw new Exception(__($encoded["error"]) . "<br>" . implode("<br>", $errors));
                    }
                    else
                        throw new Exception(__("Unexpected error occurred."));
                break;
            
                default:
                    throw new Exception(__("Unexpected error occurred."));
            }
        }
    }
}
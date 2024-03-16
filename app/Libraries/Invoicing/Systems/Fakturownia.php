<?php

namespace App\Libraries\Invoicing\Systems;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

use App\Exceptions\Exception;
use App\Libraries\Invoicing\InvoicingInterface;
use App\Models\CustomerInvoice;
use App\Models\Config;

class Fakturownia implements InvoicingInterface
{
    private $token = null;
    private $departmentId = null;
    private $serviceUrl = null;
    private $endpointMask = "https://[DOMAIN].fakturownia.pl/";
    
    public function initialize($config = [])
    {
        if(empty($config))
            $config = Config::getConfig("invoice");
            
        $this->token = $config["fakturownia_token"];
        try
        {
            $this->token = Crypt::decryptString($this->token);
        }
        catch (Exception $e) {}
        
        $this->departmentId = $config["fakturownia_department_id"];
        $this->serviceUrl = str_replace("[DOMAIN]", $config["fakturownia_domain"], $this->endpointMask);
        
        if(empty($this->token) || empty($this->departmentId) || empty($this->serviceUrl))
            throw new Exception(__("Invalid fakturownia configuration"));
        
        return $this;
    }
    
    public function newInvoice(CustomerInvoice $invoice)
    {
        $data = $this->prepareData($invoice);
        
        $response = Http::withHeaders(["Accept" => "application/json"]);
        $response = $response->asForm()->post($this->serviceUrl . "invoices.json", $data);
        $response = $this->parseResponse($response);
        if(!empty($response["number"]))
        {
            $invoice->external_invoicing_system_id = $response["id"];
            $invoice->full_number = $response["number"];
            $invoice->save();
        }
    }
    
    public function updateInvoice(CustomerInvoice $invoice)
    {
        if(!$invoice->external_invoicing_system_id)
            throw new Exception(__("Invoice was not created using Fakturownia"));
        
        $response = Http::withHeaders(["Accept" => "application/json"]);
        $response = $response->asForm()->get($this->serviceUrl . "invoices/" . $invoice->external_invoicing_system_id . ".json", ["api_token" => $this->token]);
        $fakturowaniaInvoice = $this->parseResponse($response);
        
        $data = $this->prepareData($invoice);
        
        if(!empty($fakturowaniaInvoice["positions"]))
        {
            foreach($fakturowaniaInvoice["positions"] as $pos)
            {
                $data["invoice"]["positions"][] = [
                    "id" => $pos["id"],
                    "_destroy" => true,
                ];
            }
        }
        
        $response = Http::withHeaders(["Accept" => "application/json"]);
        $response = $response->asForm()->put($this->serviceUrl . "invoices/" . $invoice->external_invoicing_system_id . ".json", $data);
        $response = $this->parseResponse($response);
    }
    
    public function makeFromProforma(CustomerInvoice $invoice)
    {
        $proforma = CustomerInvoice::find($invoice->proforma_id);
        if(!$proforma || $proforma->system != CustomerInvoice::SYSTEM_FAKTUROWNIA || empty($proforma->external_invoicing_system_id))
            throw new Exception(__("Proforma does not exists"));
        
        $data = $this->prepareData($invoice);
        $data["invoice"]["from_invoice_id"] = $proforma->external_invoicing_system_id;
        
        $response = Http::withHeaders(["Accept" => "application/json"]);
        $response = $response->asForm()->post($this->serviceUrl . "invoices.json", $data);
        $response = $this->parseResponse($response);
        if(!empty($response["number"]))
        {
            $invoice->external_invoicing_system_id = $response["id"];
            $invoice->full_number = $response["number"];
            $invoice->save();
        }
    }
    
    public function downloadInvoice(CustomerInvoice $invoice)
    {
        if(!$invoice->external_invoicing_system_id)
            throw new Exception(__("Invoice was not created using fakturowania"));
        
        $data = [
            "api_token" => $this->token,
        ];
        
        $response = Http::get($this->serviceUrl . "invoices/" . $invoice->external_invoicing_system_id . ".pdf", $data);
        $response = $this->parseResponse($response, false);
        
        return $response;
    }
    
    private function prepareData(CustomerInvoice $invoice)
    {
        $items = $invoice->items()->get();
        $data = [
            "api_token" => $this->token,
            "invoice" => [
                "kind" => $this->getInvoiceKind($invoice),
                "sell_date" => $invoice->sell_date,
                "issue_date" => $invoice->document_date,
                "payment_to" => $invoice->payment_date,
                "department_id" => $this->departmentId,
                "positions" => [],
                "payment_type" => $invoice->payment_type,
            ]
        ];
        
        foreach($items as $item)
        {
            $data["invoice"]["positions"][] = [
                "name" => $item["name"],
                "tax" => $item["vat_value"],
                "total_price_gross" => $item["total_gross_amount"],
                "quantity" => $item["quantity"],
                "quantity_unit" => $item["unit_type"],
            ];
        }
        
        $data["invoice"]["buyer_name"] = $invoice["customer_name"];
        $data["invoice"]["buyer_tax_no"] = $invoice["customer_nip"];
        $data["invoice"]["buyer_post_code"] = $invoice["customer_zip"];
        $data["invoice"]["buyer_city"] = $invoice["customer_city"];
        $data["invoice"]["buyer_street"] = $invoice["customer_street"] . " " . $invoice["customer_house_no"] . (!empty($invoice["customer_house_no"]) && !empty($invoice["customer_apartment_no"]) ? "/" : "") . ($invoice["customer_apartment_no"] ?? "");
        
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
        else
        {
            if($status == 404)
                throw new Exception(__("Invalid resource"), 404);
            else {
                $response = $response->json();
                $details = [];
                if(!empty($response["details"])) {
                    foreach($response["details"] as $k => $i)
                        $details[] = sprintf("%s: %s", $k, is_array($i) ? print_r($i, true) : $i);
                }
                if(!empty($response["message"])) {
                    $responseMessage = [];
                    foreach($response["message"] as $k => $i)
                    {
                        if(is_array($i)) continue;
                        $responseMessage[] = sprintf("%s: %s", $k, $i);
                    }
                    $response["message"] = implode(", ", $responseMessage);
                }
                throw new Exception($response["message"] . ($details ? (", details: " . implode(", ", $details)) : ""));
            }
        }
    }
}
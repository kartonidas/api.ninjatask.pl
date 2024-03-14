<?php

namespace App\Libraries\Invoicing\Systems;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

use App\Exceptions\Exception;
use App\Libraries\Invoicing\InvoicingInterface;
use App\Models\CustomerInvoice;
use App\Models\Config;

class Wfirma implements InvoicingInterface
{
    private $accessKey = false;
    private $secretKey = false;
    private $serviceUrl = "https://api2.wfirma.pl/";
    
    public function initialize($config = [])
    {
        if(empty($config))
            $config = Config::getConfig("invoice");
          
        $this->accessKey = $config["wfirma_access_key"];
        $this->secretKey = $config["wfirma_secret_key"];
        try
        {
            $this->accessKey = Crypt::decryptString($config["wfirma_access_key"]);
            $this->secretKey = Crypt::decryptString($config["wfirma_secret_key"]);
        }
        catch (Exception $e) {}
        
        if(empty($this->accessKey) || empty($this->secretKey))
            throw new Exception(__("Invalid wfirma.pl configuration"));
        
        return $this;
    }
    
    public function newInvoice(CustomerInvoice $invoice)
    {
        $data = $this->prepareData($invoice);
        $xml = self::prepareXml("invoices/add", $data);
        $response = Http
            ::withOptions([
                "headers" => $this->getAuthHeaders()
            ])
            ->withBody($xml)
            ->put($this->serviceUrl . "invoices/add?outputFormat=json");
        
        $response = $this->parseResponse($response);
        if(!empty($response["invoices"][0]["invoice"]))
        {
            $invoice->external_invoicing_system_id = $response["invoices"][0]["invoice"]["id"];
            $invoice->full_number = $response["invoices"][0]["invoice"]["fullnumber"];
            $invoice->save();
        }
    }
    
    public function updateInvoice(CustomerInvoice $invoice)
    {
        if(!$invoice->external_invoicing_system_id)
            throw new Exception(__("Invoice was not created using wfirma.pl"));
        
        $data = $this->prepareData($invoice);
        $xml = self::prepareXml("invoices/update", $data);
        
        $response = Http
            ::withOptions([
                "headers" => $this->getAuthHeaders()
            ])
            ->withBody($xml)
            ->post($this->serviceUrl . "invoices/edit/" . $invoice->external_invoicing_system_id . "?outputFormat=json");
        
        $response = $this->parseResponse($response);
    }
    
    public function downloadInvoice(CustomerInvoice $invoice)
    {
        if(!$invoice->external_invoicing_system_id)
            throw new Exception(__("Invoice was not created using wfirma.pl"));
        
        $response = Http
            ::withHeaders($this->getAuthHeaders())
            ->get($this->serviceUrl . "invoices/download/" . $invoice->external_invoicing_system_id);
        
        $response = $this->parseResponse($response, false);
        return $response;
    }
    
    public function makeFromProforma(CustomerInvoice $invoice)
    {
        throw new Exception("Not yet implemented");
    }
    
    
    private function getAuthHeaders()
    {
        return [
            "accessKey" => $this->accessKey,
            "secretKey" => $this->secretKey,
            "appKey" => env("WFIRMA_APP_KEY")
        ];
    }
    
    private function prepareData(CustomerInvoice $invoice)
    {
        $payment_type = $invoice->payment_type == "card" ? "payment_card" : $invoice->payment_type;
        
        $data = [
            "invoice" => $invoice,
            "payment_type" => $payment_type,
            "items" => $invoice->items()->get(),
        ];
        
        return $data;
    }
    
    private function parseResponse($response, $json = true)
    {
        $status = $response->status();
        
        if($status == 200)
        {
            $responseJson = $response->json();
            
            if(!empty($responseJson["invoices"][0]["invoice"]["errors"]))
            {
                $errors = [];
                foreach($responseJson["invoices"][0]["invoice"]["errors"] as $error)
                    $errors[] = $error["error"]["message"];
                    
                if(!empty($errors))
                    throw new Exception(implode(", ", $errors));
            }
            
            switch($responseJson["status"]["code"] ?? "")
            {
                case "ERROR":
                    throw new Exception(__("Podczas próby dodania obiektu wystąpiły błędy walidacji."));
                case "NOT FOUND":
                    throw new Exception(__("Podany obiekt nie istnieje."));
            }
            
            return $json ? $responseJson : $response->body();
        }
        else
            throw new Exception(__("Sprawdź ustawienia integracji wfirma.pl."));
    }
    
    private static function prepareXml($xml, $data = [])
    {
        return view("xml/wFirma/" . $xml, $data)->render();
    }
}

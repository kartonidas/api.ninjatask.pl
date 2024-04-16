<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Libraries\Templates\Customer as TemplateCustomer;
use App\Libraries\Data;
use App\Models\CustomerInvoice;
use App\Models\Dictionary;
use App\Models\Numbering;
use App\Models\Status;

class GenAppValues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:gen-values';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate App values (resources/js/data/values.json)';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $languages = ["pl", "en"];
        
        $toJson = [];
        foreach($languages as $lang)
        {
            app()->setLocale($lang);
            
            if(!isset($toJson[$lang]))
                $toJson[$lang] = [];
                
            foreach(config("invoice") as $type => $data)
            {
                foreach($data as $key => $value)
                    $toJson[$lang][$type][$key] = $value;
            }
            
            foreach(Dictionary::getAllowedTypes() as $type => $name)
                $toJson[$lang]["dictionaries"][$type] = $name;
                
            foreach(Numbering::getNumberingContinuation() as $type => $name)
                $toJson[$lang]["continuation"][$type] = $name;
                
            foreach(CustomerInvoice::getAllowedDocumentTypes() as $type => $name)
                $toJson[$lang]["sale_document_types"][$type] = $name;
                
            foreach(CustomerInvoice::getAllowedPaymentTypes() as $type => $name)
                $toJson[$lang]["payment_types"][$type] = $name;
            
            foreach(Status::getAllowedTaskStates() as $type => $name)    
                $toJson[$lang]["task_states"][$type] = $name;
        }
        
        foreach(Data::getAllowedTimes() as $type => $name)
            $toJson["global"]["times"][$type] = $name;
            
        foreach(CustomerInvoice::getAllowedSystems() as $system)
        {
            foreach(CustomerInvoice::getAllowedDocumentTypes() as $type => $name)
            {
                $allowed = true;
                if($type == CustomerInvoice::DOCUMENT_TYPE_PROFORMA && !in_array($system, CustomerInvoice::getProformaAllowedSystems()))
                    $allowed = false;
                
                $toJson["global"]["sale_document_types_by_system"][$system][$type] = $allowed;
            }
        }
        
        foreach(TemplateCustomer::getAvailableVars()["fields"] as $variable => $variableInfo)
            $toJson[$lang]["templates"]["variables"][] = ["var" => "[" . $variable . "]", "label" => $variableInfo[0]];
        
        $fp = fopen(__DIR__ . "/../../../../app.ninjatask.pl/resources/js/data/values.json", "w");
        fwrite($fp, json_encode($toJson, JSON_PRETTY_PRINT));
        fclose($fp);
    }
}

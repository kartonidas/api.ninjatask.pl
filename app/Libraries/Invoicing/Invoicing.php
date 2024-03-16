<?php

namespace App\Libraries\Invoicing;

use App\Exceptions\Exception;
use App\Libraries\Invoicing\Systems\Fakturownia;
use App\Libraries\Invoicing\Systems\Internal;
use App\Libraries\Invoicing\Systems\Infakt;
use App\Libraries\Invoicing\Systems\Wfirma;
use App\Models\CustomerInvoice;

class Invoicing
{
    public static function newInvoice(CustomerInvoice $invoice)
    {
        $system = self::getInvoicingSystem($invoice);
        
        if(!$system)
            throw new Exception(__("Invalid invoicing system"));
        
        $system->newInvoice($invoice);
    }
    
    public static function updateInvoice(CustomerInvoice $invoice)
    {
        $system = self::getInvoicingSystem($invoice);
        
        if(!$system)
            throw new Exception(__("Invalid invoicing system"));
        
        $system->updateInvoice($invoice);
    }
    
    public static function makeFromProforma(CustomerInvoice $invoice)
    {
        $system = self::getInvoicingSystem($invoice);
        
        if(!$system)
            throw new Exception(__("Invalid invoicing system"));
        
        $system->makeFromProforma($invoice);
    }
    
    public static function downloadInvoice(CustomerInvoice $invoice)
    {
        $system = self::getInvoicingSystem($invoice);
        
        if(!$system)
            throw new Exception(__("Invalid invoicing system"));
        
        return $system->downloadInvoice($invoice);
    }
    
    private static function getInvoicingSystem(CustomerInvoice $invoice)
    {
        switch($invoice->system)
        {
            case CustomerInvoice::SYSTEM_APP:
                $obj = new Internal($invoice);
                return $obj;
            break;
        
            case CustomerInvoice::SYSTEM_FAKTUROWNIA:
                return (new Fakturownia($invoice))->initialize();
            break;
        
            case CustomerInvoice::SYSTEM_WFIRMA:
                return (new Wfirma($invoice))->initialize();
            break;
            case CustomerInvoice::SYSTEM_INFAKT:
                return (new Infakt($invoice))->initialize();
            break;
        }
    }
}
<?php

namespace App\Libraries\Invoicing\Systems;

use App\Libraries\CustomerInvoicePrinter;
use App\Libraries\Invoicing\InvoicingInterface;
use App\Models\CustomerInvoice;

class Internal implements InvoicingInterface
{
    public function newInvoice(CustomerInvoice $invoice)
    {
        $invoice->setNumber();
    }
    
    public function updateInvoice(CustomerInvoice $invoice)
    {
    }
    
    public function makeFromProforma(CustomerInvoice $invoice)
    {
        $invoice->setNumber();
    }
    
    public function downloadInvoice(CustomerInvoice $invoice)
    {
        return CustomerInvoicePrinter::generatePDF($invoice);
    }
}
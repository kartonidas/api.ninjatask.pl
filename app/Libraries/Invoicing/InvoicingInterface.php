<?php

namespace App\Libraries\Invoicing;
use App\Models\CustomerInvoice;

interface InvoicingInterface
{
    public function newInvoice(CustomerInvoice $invoice);
    public function downloadInvoice(CustomerInvoice $invoice);
    public function updateInvoice(CustomerInvoice $invoice);
    public function makeFromProforma(CustomerInvoice $invoice);
}
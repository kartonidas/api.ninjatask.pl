<?php

namespace App\Observers;
use App\Models\CustomerInvoice;

class CustomerInvoiceObserver
{
    public function created(CustomerInvoice $invoice)
    {
        $invoice->setNumber();
    }
}

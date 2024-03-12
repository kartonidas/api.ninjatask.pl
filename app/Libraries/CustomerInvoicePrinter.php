<?php

namespace App\Libraries;

use PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Libraries\Helper;
use App\Models\Config;
use App\Models\CustomerInvoice;
use App\Models\Dictionary;
use App\Models\Firm;

class CustomerInvoicePrinter
{
    public static function generatePDF(CustomerInvoice $invoice)
    {
        $invoice->recipient = $invoice->recipient()->first();
        $invoice->payer = $invoice->payer()->first();
        
        $paymentTypesArr = [];
        $paymentTypes = Dictionary::where("type", "payment_types")->get();
        foreach($paymentTypes as $paymentType)
            $paymentTypesArr[$paymentType->id] = $paymentType->name;
            
        $data = [
            "invoice" => $invoice,
            "items" => $invoice->items()->get(),
            "grouped_items" => $invoice->getGroupedItems(),
            "dictionary" => [
                "payment_types" => $paymentTypesArr,
            ],
            "firm_data" => $invoice->getFirmInvoicingData(),
        ];

        if($invoice->type == "correction")
        {
            $correctedInvoice = $invoice->getCorrectionSource();
            $data["grouped_items_after_correction"] = $correctedInvoice->getGroupedItems();
            $data["corrected_invoice"] = $correctedInvoice;
            $data["items_after_correction"] = $correctedInvoice->items()->get();
        }
        
        $title = Helper::__no_pl(CustomerInvoice::getAllowedDocumentTypes()[$invoice->type]);
        $title .= ": " . $invoice->full_number;

        $pdf = PDF::loadView("pdf.customer-invoices.invoice", $data);
        $pdf->getMpdf()->SetTitle($title);
        return $pdf->stream($title . ".pdf");
    }
}

<?xml version="1.0" encoding="UTF-8"?>
<api>
    <invoices>
        <invoice>
            <id>{{ $invoice->external_invoicing_system_id }}</id>
            <contractor_detail>
                <empty>0</empty>
                <contractor>
                    <name>{{ $invoice->customer_name }}</name>
                    <zip>{{ $invoice->customer_zip }}</zip>
                    <city>{{ $invoice->customer_city }}</city>
                    <street>{{ $invoice->customer_street }} {{ $invoice["customer_house_no"] }}@if(!empty($invoice["customer_house_no"]) && !empty($invoice["customer_apartment_no"])){{ "/" }}@endif{{ $invoice["customer_apartment_no"] ?? "" }}</street>
                    @if($invoice->customer_type == "firm" && !empty($invoice->customer_nip ))
                        <nip>{{ $invoice->customer_nip }}</nip>
                        <tax_id_type>nip</tax_id_type>
                    @endif
                </contractor>
            </contractor_detail>
            
            <date>{{ $invoice["document_date"] }}</date>
            <paymentdate>{{ $invoice["payment_date"] }}</paymentdate>
            <disposaldate>{{ $invoice["sell_date"] }}</disposaldate>
            <disposaldate_empty>1</disposaldate_empty>
            <price_type>brutto</price_type>
            <paymentmethod>{{ $payment_type }}</paymentmethod>
            
            <invoicecontents>
                @foreach($items as $item)
                    <invoicecontent>
                        <name>{{ $item->name }}</name>
                        <count>{{ $item->quantity }}</count>
                        <price>{{ $item->total_gross_amount }}</price>
                        <unit>{{ $item->unit_type }}</unit>
                    </invoicecontent>
                @endforeach
            </invoicecontents>
        </invoice>
    </invoices>
</api>
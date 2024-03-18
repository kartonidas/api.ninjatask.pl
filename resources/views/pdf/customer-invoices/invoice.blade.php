@php
    use App\Models\CustomerInvoice;
    use App\Libraries\Helper;
@endphp
<style>
    *, DIV {
        font-family: Arial;
        font-size: 12px;
    }

    TABLE#head,
    TABLE#items,
    TABLE#footer {
        border-collapse: collapse;
        width: 100%;
    }

    TABLE#head TR TD,
    TABLE#items TR TD,
    TABLE#items TR TH {
        border: 1px solid #000;
        padding: 5px;
        font-family: Arial;
        font-size: 12px;
        font-weight: normal;
    }

    TABLE#footer TR TD {
        border: 1px solid #000;
        height: 80px;
        text-align:center;
        vertical-align: bottom;
        color: #898989;
        font-size: 10px;
    }

    .text-center {
        text-align: center;
    }
    .text-sm {
        font-size: 10px;
    }
</style>

<htmlpagefooter name="myFooter1">
    <table id="footer" width="100%">
        <tr>
            <td width="40%" style="">
                imię, nazwisko i podpis osoby<br/>
                upoważnionej do odebrania dokumentu
            </td>
            <td width="20%" style="border: 0"></td>
            <td width="40%" style="border: 1px solid #000; height: 50px;">
                imię, nazwisko i podpis osoby<br/>
                upoważnionej do podpisania dokumentu
            </td>
        </tr>
    </table>
</htmlpagefooter>
<sethtmlpagefooter name="myFooter1" value="on" />

<table id="head" style="border-collapse: separate">
    <tr>
        <td rowspan="2" class="text-center" style="width: 35%; vertical-align: bottom; padding: 2px; font-size: 10px; color: #898989">
            pieczęć firmowa
        </td>
        <td rowspan="2" colspan="2" style="height: 160px; text-align: center; vertical-align: middle;">
            @if($invoice->type == CustomerInvoice::DOCUMENT_TYPE_INVOICE)
                <strong>
                    FAKTURA<br/>
                    {{ $invoice->full_number }}
                </strong>
            @elseif($invoice->type == CustomerInvoice::DOCUMENT_TYPE_PROFORMA)
                <strong>
                    FAKTURA PROFORMA<br/>
                    {{ $invoice->full_number }}
                </strong>
            @endif
        </td>
        <td class="text-center" style="width: 35%; vertical-align: middle;">
            <strong>{{ $invoice->document_date }}</strong>
            <div class="text-sm">Data wystawienia</div>
        </td>
    </tr>
    <tr>
        <td class="text-center" style="vertical-align: middle;">
            <strong>{{ $invoice->sell_date }}</strong>
            <div class="text-sm">Data sprzedaży</div>
        </td>
    </tr>
    

    <tr>
        <td colspan="2" style="vertical-align: top">
            Sprzedawca:
            <strong>
                @if($firm_data->type == "firm")
                    {{ $firm_data->name }}
                @else
                    {{ $firm_data->firstname }} {{ $firm_data->lastname }}
                @endif
            </strong>
            <br/>
            Adres:
            <strong>
                {{ $firm_data->street }} {{ $firm_data->house_no }}@if($firm_data->apartment_no) / {{ $firm_data->apartment_no }}@endif,
                {{ $firm_data->zip }} {{ $firm_data->city }}
            </strong>
            @if($firm_data->type == "firm")
                <br/>
                <strong>NIP: {{ $firm_data->nip }}</strong>
            @endif
        </td>
        <td colspan="2" style="vertical-align: top">
            Nabywca: <strong>{{ $invoice->customer_name }}</strong>
            <br/>
            Adres:
            <strong>
                {{ $invoice->customer_street }} {{ $invoice->customer_house_no }}@if($invoice->customer_apartment_no) / {{ $invoice->customer_apartment_no }}@endif,
                {{ $invoice->customer_zip }} {{ $invoice->customer_city }}
            </strong>
            
            @if($invoice->type == "firm")
                <br/>
                <strong>NIP: {{ $invoice->customer_nip }}</strong>
            @endif
            </div>
        </td>
    </tr>
    <tr>
        <td colspan="2" class="text-center">
            Forma płatności: {{ $dictionary["payment_types"][$invoice->payment_type_id] ?? "" }}
        </td>
        <td colspan="2" class="text-center">
            Termin płatności: {{ $invoice->payment_date }}
        </td>
    </tr>
</table>

<div style="margin-top:50px">
    <table id="items">
        <thead>
            <tr>
                <th style="width: 40px">Lp.</th>
                <th>Nazwa i PKWiU</th>
                <th style="width: 60px">Ilość</th>
                <th style="width: 70px">JM</th>
                <th style="width: 100px">VAT</th>
                <th style="width: 100px">Cena netto</th>
                <th style="width: 100px">Wartość netto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $index => $item)
                <tr>
                    <td>{{ $index+1 }}.</td>
                    <td>{{ $item->name }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-center">{{ $item->unit_type }}</td>
                    <td style="text-align: right">{{ $item->vat_value }}{{ is_numeric($item->vat_value) ? "%" : "" }}</td>
                    <td style="text-align: right">
                        @if($item->discount > 0)
                            {{ Helper::amount($item->net_amount_discount) }}
                        @else
                            {{ Helper::amount($item->net_amount) }}
                        @endif
                        {{ $invoice->currency }}
                    </td>
                    <td style="text-align: right">
                        @if($item->discount > 0)
                            {{ Helper::amount($item->net_amount_discount * $item->quantity) }}
                        @else
                            {{ Helper::amount($item->net_amount * $item->quantity) }}
                        @endif
                        {{ $invoice->currency }}
                    </td>
                </tr>
            @endforeach

            @include("pdf.customer-invoices._summary")
        </tbody>
    </table>
</div>

<div style="margin-top: 20px; border: 1px solid #000; padding: 10px">
    {{ Helper::slownie($invoice->gross_amount, $invoice->currency) }}
</div>

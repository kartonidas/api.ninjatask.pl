<style>
    @page  {
        margin: 10mm 5mm 10mm 5mm;
    }

    div {
        font-family: arial;
        font-size: 12px;
    }
    table#item-table,
    table#summary-table,
    table#header-table {
        border-collapse: collapse;
        width:100%;
        margin-top: 20px;
    }
    table#item-table thead tr th,
    table#item-table tfoot tr td {
        background: #e2e2e2;
        padding:3px;
        border:1px solid black;
        text-align: left;
        font-family: arial;
        font-size:12px;
    }

    table#item-table tbody tr td {
        padding:3px;
        border: 1px solid black;
    }

    table#item-table tbody tr td.no-border,
    table#item-table tfoot tr td.no-border {
        border: 0;
        background: initial;
    }

    table#item-table tr td,
    table#summary-table tr td,
    table#header-table tr td {
        font-family: arial;
        font-size:12px;
    }

    table#header-table tr td{
        width: 33.333%;
        border:1px solid black;
        text-align:center;
        padding: 5px;
    }

    table#summary-table .label {
        text-align:right;
        width: 200px;
    }
    table#summary-table .value {
        font-weight: bold;
        padding-left: 10px;
    }
    table#summary-table .value2 {
        padding-left: 10px;
    }
</style>

<?php
    $invoiceData = $data->getInvoiceData();
?>

<table id="header-table">
    <tr>
        <td rowspan="2">&nbsp;</td>
        <td style="font-size:17px; font-weight: bold" rowspan="2">
            @if($data->type == "firm")
                Invoice
            @else
                Bill
            @endif
            <br />
            {{ $data->full_number }}
        </td>
        <td>
            <div>
                <span style="font-size:14px"><strong>{{ $data->date }}</strong></span>
                <br />
                <span style="font-size: 10px">
                    Sale date
                </span>
            </div>
        </td>
    </tr>
    <tr>
        <td>
            <span style="font-size:14px"><strong>{{ $data->date }}</strong></span>
            <br />
            <span style="font-size: 10px">
                Date of issue
            </span>
        </td>
    </tr>
</table>

<table id="header-table">
    <tr>
        <td style="text-align: left; vertical-align:top">
            <div>Seller: <strong>{{ $invoiceData->name }}</strong></div>
            Address: <strong>{{ $invoiceData->address }} {{ $invoiceData->street_no }} @if($invoiceData->apartment_no)/ {{ $invoiceData->apartment_no }}@endif, {{ $invoiceData->post_code }} {{ $invoiceData->city }}</strong><br />
            Tax ID: <strong>{{ $invoiceData->nip }}</strong>
        </td>
        <td style="text-align: left; vertical-align:top">
            @if($data->type == "firm")
                <div>Recipient: <strong>{{ $data->name }}</strong></div>
                Address: <strong>{{ $data->street }} {{ $data->house_no }} @if($data->apartment_no)/ {{ $data->apartment_no }}@endif, {{ $data->post_code }} {{ $data->city }}</strong><br />
                Tax ID: <strong>{{ $data->nip }}</strong>
            @else
                <div>Recipient: <strong>{{ $data->firstname }} {{ $data->lastname }}</strong></div>
                Address: <strong>{{ $data->street }} {{ $data->house_no }} @if($data->apartment_no)/ {{ $data->apartment_no }}@endif, {{ $data->post_code }} {{ $data->city }}</strong><br />
            @endif
        </td>
    </tr>
</table>

<table id="item-table">
    <thead>
        <tr>
            <th style="width:30px; text-align:center;">No.</th>
            <th>Name</th>
            <th style="width:100px; text-align:center;">Quantity</th>
            <th style="width:100px; text-align:center;">Unit</th>
            <th style="width:100px; text-align:center;">VAT</th>
            <th style="width:100px; text-align:center;">Net price</th>
            <th style="width:100px; text-align:center;">Net value</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data->getItems() as $k => $item)
            <tr>
                <td style="text-align:center">{{ $k+1 }}</td>
                <td>{{ \App\Models\Invoice::getItemName($item["name"], "en") }}</td>
                <td style="text-align:center">{{ $item["qt"] }}</td>
                <td style="text-align:center">piece</td>
                <td style="text-align:center">
                    @if($data->reverse)
                        np.
                    @else
                        {{ $item["vat"] }}%
                    @endif
                </td>
                <td style="text-align:center">{{ \App\Libraries\Helper::amount($item["amount"]) }} {{ $data->currency }}</td>
                <td style="text-align:center">{{ \App\Libraries\Helper::amount($item["qt"]*$item["amount"]) }} {{ $data->currency }}</td>
            </tr>
        @endforeach

        <tr>
            <td colspan="3" class="no-border"></td>
            <td style="text-align:center; font-weight: bold">VAT</td>
            <td style="text-align:center; font-weight: bold">Net value</td>
            <td style="text-align:center; font-weight: bold">VAT value</td>
            <td style="text-align:center; font-weight: bold">Gross value</td>
        </tr>
        <?php
            $sumNetto = 0;
            $sumVat = 0;
            $sumBrutto = 0;
        ?>
        @foreach($data->getGroupedItemsByVat() as $vat => $item)
            <?php
                $sumNetto += $item["netto"];
                $sumVat += $item["vat"];
                $sumBrutto += $item["brutto"];
            ?>
            <tr>
                <td colspan="3" class="no-border"></td>
                <td style="text-align:center">
                    @if($data->reverse)
                        np.
                    @else
                        {{ \App\Libraries\Helper::amount($vat) }}%
                    @endif
                </td>
                <td style="text-align:center">{{ \App\Libraries\Helper::amount($item["netto"]) }} {{ $data->currency }}</td>
                <td style="text-align:center">{{ \App\Libraries\Helper::amount($item["vat"]) }} {{ $data->currency }}</td>
                <td style="text-align:center">{{ \App\Libraries\Helper::amount($item["brutto"]) }} {{ $data->currency }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" class="no-border" style="text-align:right; font-weight: bold">Total</td>
            <td style="text-align:right"></td>
            <td style="text-align:center; font-weight: bold">{{ \App\Libraries\Helper::amount($sumNetto) }} {{ $data->currency }}</td>
            <td style="text-align:center; font-weight: bold">{{ \App\Libraries\Helper::amount($sumVat) }} {{ $data->currency }}</td>
            <td style="text-align:center; font-weight: bold">{{ \App\Libraries\Helper::amount($sumBrutto) }} {{ $data->currency }}</td>
        </tr>
    </tfoot>
</table>

<div style="clear:both"></div>

<table id="summary-table">
    <tr>
        <td class="label">Total to pay:</td>
        <td class="value">{{ \App\Libraries\Helper::amount($data->gross) }} {{ $data->currency }}</td>
    </tr>
    <tr>
        <td class="label">Method of payment:</td>
        <td class="value">Online transfer</td>
    </tr>
    <tr>
        <td class="label">Paid:</td>
        <td class="value">{{ \App\Libraries\Helper::amount($data->gross) }} {{ $data->currency }}</td>
    </tr>
    <tr>
        <td class="label">Left to pay:</td>
        <td class="value">{{ \App\Libraries\Helper::amount(0) }} {{ $data->currency }}</td>
    </tr>
</table>

<div style="margin-top:80px">
    <div style="float:left; width:40%">
        <div style="font-size:11px; text-align:center;">{{ $invoiceData->invoice_person }}</div>
        <div style="font-size:11px; text-align:center; border-top: 1px solid black;">The person authorized to issue an invoice</div>
    </div>

    <div style="float:right; width:40%">
        <div style="font-size:11px; text-align:center;">&nbsp;</div>
        <div style="font-size:11px; text-align:center; border-top: 1px solid black;">Person authorized to collect the invoice</div>
    </div>
    <div style="clear:both"></div>
</div>

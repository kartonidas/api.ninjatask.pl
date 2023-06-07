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
        <td style="font-size:17px; font-weight: bold">
            Faktura
            <br />
            {{ $data->full_number }}
        </td>
        <td rowspan="2">
            <div>
                <span style="font-size:14px"><strong>{{ $data->date }}</strong></span>
                <br />
                Data dostawy / usługi
            </div>
        </td>
    </tr>
    <tr>
        <td>
            <span style="font-size:14px"><strong>{{ $data->date }}</strong></span>
            <br />
            {{ $invoiceData->invoice_city }}
        </td>
    </tr>
</table>

<table id="header-table">
    <tr>
        <td style="text-align: left">
            <div>Sprzedawca: <strong>{{ $invoiceData->name }}</strong></div>
            Adres: <strong>{{ $invoiceData->address }} {{ $invoiceData->street_no }} @if($invoiceData->apartment_no)/ {{ $invoiceData->apartment_no }}@endif, {{ $invoiceData->post_code }} {{ $invoiceData->city }}</strong><br />
            NIP: <strong>{{ $invoiceData->nip }}</strong>
        </td>
        <td style="text-align: left">
            <div>Odbiorca: <strong>{{ $data->name }}</strong></div>
            Adres: <strong>{{ $data->street }} {{ $data->house_no }} @if($data->apartment_no)/ {{ $data->apartment_no }}@endif, {{ $data->post_code }} {{ $data->city }}</strong><br />
            NIP: <strong>{{ $data->nip }}</strong>
        </td>
    </tr>
</table>

<table id="item-table">
    <thead>
        <tr>
            <th style="width:30px; text-align:center;">Lp.</th>
            <th>Nazwa</th>
            <th style="width:100px; text-align:center;">Ilość</th>
            <th style="width:100px; text-align:center;">JM</th>
            <th style="width:100px; text-align:center;">VAT</th>
            <th style="width:100px; text-align:center;">Cena netto</th>
            <th style="width:100px; text-align:center;">Wartość netto</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data->getItems() as $k => $item)
            <tr>
                <td style="text-align:center">{{ $k+1 }}</td>
                <td>{{ $item["name"] }}</td>
                <td style="text-align:center">{{ $item["qt"] }}</td>
                <td>sztuka</td>
                <td style="text-align:right">{{ $item["vat"] }}%</td>
                <td style="text-align:right">{{ \App\Libraries\Helper::amount($item["amount"]) }} {{ $data->currency }}</td>
                <td style="text-align:right">{{ \App\Libraries\Helper::amount($item["qt"]*$item["amount"]) }}</td>
            </tr>
        @endforeach

        <tr>
            <td colspan="3" class="no-border"></td>
            <td style="text-align:center; font-weight: bold">VAT</td>
            <td style="text-align:center; font-weight: bold">Wartość netto</td>
            <td style="text-align:center; font-weight: bold">Wartość VAT</td>
            <td style="text-align:center; font-weight: bold">Wartość brutto</td>
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
                <td style="text-align:right">{{ \App\Libraries\Helper::amount($vat) }}%</td>
                <td style="text-align:right">{{ \App\Libraries\Helper::amount($item["netto"]) }} {{ $data->currency }}</td>
                <td style="text-align:right">{{ \App\Libraries\Helper::amount($item["vat"]) }} {{ $data->currency }}</td>
                <td style="text-align:right">{{ \App\Libraries\Helper::amount($item["brutto"]) }} {{ $data->currency }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" class="no-border" style="text-align:right; font-weight: bold">Razem</td>
            <td style="text-align:right"></td>
            <td style="text-align:right; font-weight: bold">{{ \App\Libraries\Helper::amount($sumNetto) }} {{ $data->currency }}</td>
            <td style="text-align:right; font-weight: bold">{{ \App\Libraries\Helper::amount($sumVat) }} {{ $data->currency }}</td>
            <td style="text-align:right; font-weight: bold">{{ \App\Libraries\Helper::amount($sumBrutto) }} {{ $data->currency }}</td>
        </tr>
    </tfoot>
</table>

<div style="clear:both"></div>

<table id="summary-table">
    <tr>
        <td class="label">Razem do zapłaty:</td>
        <td class="value">{{ \App\Libraries\Helper::amount($data->gross) }} {{ $data->currency }}</td>
    </tr>
    <tr>
        <td class="label">Słownie:</td>
        <td class="value">{{ \App\Libraries\Helper::slownie($data->gross) }}</td>
    </tr>
    <tr>
        <td class="label">Sposób zapłaty:</td>
        <td class="value">Przelew on-line</td>
    </tr>
    <tr>
        <td class="label">Wpłacono:</td>
        <td class="value">{{ \App\Libraries\Helper::amount($data->gross) }} {{ $data->currency }}</td>
    </tr>
    <tr>
        <td class="label">Pozostało do zapłaty:</td>
        <td class="value">{{ \App\Libraries\Helper::amount(0) }} {{ $data->currency }}</td>
    </tr>
</table>

<div style="margin-top:80px">
    <div style="float:left; width:40%">
        <div style="font-size:11px; text-align:center;">{{ $invoiceData->invoice_person }}</div>
        <div style="font-size:11px; text-align:center; border-top: 1px solid black;">Osoba upoważniona do wystawienia faktury</div>
    </div>

    <div style="float:right; width:40%">
        <div style="font-size:11px; text-align:center;">&nbsp;</div>
        <div style="font-size:11px; text-align:center; border-top: 1px solid black;">Osoba upoważniona do odbioru faktury</div>
    </div>
    <div style="clear:both"></div>
</div>

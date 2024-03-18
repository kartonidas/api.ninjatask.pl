@php
    use App\Libraries\Helper;
@endphp

<tr>
    <td colspan="3" style="border: 0"></td>
    <td class="text-center">VAT</td>
    <td class="text-center">Wartość netto</td>
    <td class="text-center">Wartość VAT</td>
    <td class="text-center">Wartość brutto</td>
</tr>
@php
    $totalNetAmount = $totalGrossValue = $totalGrossAmount = 0;
@endphp
@foreach($grouped_items as $item)
    <tr>
        <td colspan="3" style="border: 0"></td>
        <td class="text-center">
            {{ $item["vat_value"] }}@if(is_numeric($item["vat_value"])){{ "%" }}@endif
        </td>
        <td class="text-center">
            {{ Helper::amount($item["net_amount"]) }}
            {{ $invoice->currency }}
        </td>
        <td class="text-center">
            {{ Helper::amount($item["gross_value"]) }}
            {{ $invoice->currency }}
        </td>
        <td class="text-center">
            {{ Helper::amount($item["gross_amount"]) }}
            {{ $invoice->currency }}
        </td>
    </tr>

    @php
        $totalNetAmount += $item["net_amount"];
        $totalGrossValue += $item["gross_value"];
        $totalGrossAmount += $item["gross_amount"];
    @endphp
@endforeach
<tr>
    <td colspan="3" style="border: 0; text-align: right"><strong>RAZEM</strong></td>
    <td class="text-center"></td>
    <td class="text-center">
        <strong>
            {{ Helper::amount($totalNetAmount) }}
            {{ $invoice->currency }}
        </strong>
    </td>
    <td class="text-center">
        <strong>
            {{ Helper::amount($totalGrossValue) }}
            {{ $invoice->currency }}
        </strong>
    </td>
    <td class="text-center">
        <strong>
            {{ Helper::amount($totalGrossAmount) }}
            {{ $invoice->currency }}
        </strong>
    </td>
</tr>
<tr>
    <td colspan="2">
        <strong>
            Łącznie do zapłaty:
            {{ Helper::amount($invoice->gross_amount) }}
            {{ $invoice->currency }}
        </strong>
    </td>
    <td style="border: 0;"></td>
    <td colspan="2" style="border-right: 0; text-align: left">
        Wpłacono:
        <br/>
        Pozostało do zapłaty:
    </td>
    <td colspan="2" style="border-left: 0; text-align: right">
        {{ Helper::amount($invoice->total_payments) }} {{ $invoice->currency }}
        <br/>
        {{ Helper::amount($invoice->balance) }} {{ $invoice->currency }}
    </td>
</tr>

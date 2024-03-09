@php
    use App\Libraries\Helper;
@endphp

<tr>
    <td colspan="4" style="border: 0"></td>
    <td class="text-center">VAT</td>
    <td class="text-center">Wartość netto</td>
    <td class="text-center">Wartość VAT</td>
    <td class="text-center">Wartość brutto</td>
</tr>
@php
    $totalNetAmount = $totalGrossValue = $totalGrossAmount = 0;
    $showInfo = true;
@endphp
@foreach($grouped_items_after_correction as $i => $item)
    <tr>
        <td colspan="4" style="border: 0; text-align: right;">
            @if($showInfo)
                <strong>Przed korektą</strong>
            @endif
            @php
                $showInfo = false;
            @endphp
        </td>
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
    <td colspan="4" style="border: 0; text-align: right"><strong>Razem przed korektą</strong></td>
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

@php
    $totalNetAmountCorrection = $totalGrossValueCorrection = $totalGrossAmountCorrection = 0;
    $showInfo = true;
@endphp
@foreach($grouped_items as $item)
    <tr>
        <td colspan="4" style="border: 0; text-align: right;">
            @if($showInfo)
                <strong>Po korekcie</strong>
            @endif
            @php
                $showInfo = false;
            @endphp
        </td>
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
        $totalNetAmountCorrection += $item["net_amount"];
        $totalGrossValueCorrection += $item["gross_value"];
        $totalGrossAmountCorrection += $item["gross_amount"];
    @endphp
@endforeach
<tr>
    <td colspan="4" style="border: 0; text-align: right"><strong>Razem po korekcie</strong></td>
    <td class="text-center"></td>
    <td class="text-center">
        <strong>
            {{ Helper::amount($totalNetAmountCorrection) }}
            {{ $invoice->currency }}
        </strong>
    </td>
    <td class="text-center">
        <strong>
            {{ Helper::amount($totalGrossValueCorrection) }}
            {{ $invoice->currency }}
        </strong>
    </td>
    <td class="text-center">
        <strong>
            {{ Helper::amount($totalGrossAmountCorrection) }}
            {{ $invoice->currency }}
        </strong>
    </td>
</tr>

<tr>
    <td colspan="4" style="border: 0; text-align: right"><strong>Różnica</strong></td>
    <td class="text-center"></td>
    <td class="text-center">
        <strong>
            {{ Helper::amount($totalNetAmountCorrection - $totalNetAmount) }}
            {{ $invoice->currency }}
        </strong>
    </td>
    <td class="text-center">
        <strong>
            {{ Helper::amount($totalGrossValueCorrection - $totalGrossValue) }}
            {{ $invoice->currency }}
        </strong>
    </td>
    <td class="text-center">
        <strong>
            {{ Helper::amount($totalGrossAmountCorrection - $totalGrossAmount) }}
            {{ $invoice->currency }}
        </strong>
    </td>
</tr>

<tr>
    <td colspan="2">
        <strong>
            @if($totalGrossAmountCorrection - $totalGrossAmount < 0)
                Łącznie do zwrotu:
            @else
                Łącznie do dopłaty:
            @endif
            {{ Helper::amount(abs($totalGrossAmountCorrection - $totalGrossAmount)) }}
            {{ $invoice->currency }}
        </strong>
    </td>
</tr>

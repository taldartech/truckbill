@php
    $company = auth()->user()->company;
    $totalAmount = 0;
    $lrs = $invoice->custom_invoice ? [] : ($invoice->lr_ids ?? []);
    $customDetails = $customDetails ?? collect();
@endphp
<style>
    td {
      padding-top: 4px!important;
      padding-bottom: 4px!important;
    }
</style>

    <table width="100%" border="1" cellspacing="0" cellpadding="6" style="border-collapse: collapse; font-size: 12px;">
    <tr>
        <td style="font-weight: bold; text-align: center; font-size: 18px!important" colspan="13">{{ $company->name }}</td>
    </tr>
    <tr>
        <td style="font-weight: bold; text-align: center; font-size: 16px!important" colspan="13">{{ $company->address }}</td>
    </tr>
    <tr>
        <td style="text-align: center; font-size: 15px!important" colspan="13">
            {{ optional($company->city)->name . ', ' . optional($company->state)->name . ' - ' . $company->pin_code ?? '' . ' Phone No ' . $company->contact_number }}
        </td>
    </tr>
    <tr>
        <td colspan="13"></td>
    </tr>
    <tr>
        <td style="font-weight: bold; text-align: center; font-size: 15px!important" colspan="13">TRANSPORTATION BILL</td>
    </tr>
    <tr>
        <td style="font-weight: bold;">To,</td>
        <td colspan="12"></td>
    </tr>
    <tr>
        <td style="font-weight: bold;" colspan="3">{{ $invoice->consignee->name }}</td>
        <td colspan="6"></td>
        <td style="font-weight: bold;">PAN No.:</td>
        <td colspan="3">{{ $company->pan_number }}</td>
    </tr>
    <tr>
        <td style="font-weight: bold;" colspan="3">{{ $invoice->consignee->city->name }}</td>
        <td colspan="6"></td>
        <td style="font-weight: bold;">Bill No.:</td>
        <td style="font-weight: bold;" colspan="3">{{ $invoice->manual_invoice_number }}</td>
    </tr>
    <tr>
        <td colspan="3">GSTIN: {{ $invoice->consignee->gst_number }}</td>
        <td colspan="6"></td>
        <td style="font-weight: bold;">Bill Date:</td>
        <td style="font-weight: bold;" colspan="3">{{ $invoice->generated_date }}</td>
    </tr>
    <tr>
        <td colspan="3">PAN No.: {{ $invoice->consignee->pan_number }}</td>
        <td colspan="6"></td>
        <td style="font-weight: bold;">GSTIN:</td>
        <td colspan="3">{{ $company->gst_number }}</td>
    </tr>
    <tr>
        <td colspan="3">State Code: {{ $invoice->consignee->state->code }}</td>
        <td colspan="6"></td>
        <td style="font-weight: bold;">Reverse Charge:</td>
        <td style="font-weight: bold;" colspan="3">{{ $invoice->invoice_type == 'Forward' ? 'No' : 'Yes' }}</td>
    </tr>
    <tr>
        <td colspan="9"></td>
        <td style="font-weight: bold;">SAC Code:</td>
        <td colspan="3">996511</td>
    </tr>
    @php $remark = ''; @endphp
    @if ($invoice->custom_invoice)
        @foreach ($customDetails as $row)
            @php $remark .= ($row->remark ?? '') . ' '; @endphp
        @endforeach
        <tr>
            <td style="font-weight: bold;">Sr.No.</td>
            <td style="font-weight: bold;">Gr.no.</td>
            <td style="font-weight: bold;">Truck No.</td>
            <td style="font-weight: bold;">Destination</td>
            <td style="font-weight: bold;">Invoice No.</td>
            <td style="font-weight: bold;">Invoice date</td>
            <td style="font-weight: bold;">Wt.</td>
            @if ($invoice->custom_invoice === 'rate_difference')
                <td style="font-weight: bold;">Portal Rate</td>
                <td style="font-weight: bold;" colspan="2">Diff</td>
            @else
                <td style="font-weight: bold;">Shortage</td>
                <td style="font-weight: bold;">Damage</td>
                <td style="font-weight: bold;">Damage Rate</td>
            @endif
            <td style="font-weight: bold;" colspan="3">Amount</td>
        </tr>
        @foreach ($customDetails as $index => $row)
            @php $lr = $row->lorryReceipt; @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $lr ? $lr->lr_number : '—' }}</td>
                <td>{{ $lr && $lr->truck ? $lr->truck->truck_number : 'N/A' }}</td>
                <td>{{ $lr ? $lr->destination_city ?? '—' : '—' }}</td>
                <td>{{ $lr ? $lr->invoice_number ?? '—' : '—' }}</td>
                <td>{{ $lr && $lr->gr_date ? \Carbon\Carbon::parse($lr->gr_date)->format('d-m-Y') : '—' }}</td>
                <td>{{ $lr ? $lr->weight ?? '—' : '—' }}</td>
                @if ($invoice->custom_invoice === 'rate_difference')
                    <td>{{ $row->portal_rate !== null ? number_format($row->portal_rate, 2) : '—' }}</td>
                    <td colspan="2">{{ $row->diff !== null ? number_format($row->diff, 2) : '—' }}</td>
                @else
                    <td>{{ $row->shortage_qty !== null ? $row->shortage_qty : '—' }}</td>
                    <td>{{ $row->damage_qty !== null ? $row->damage_qty : '—' }}</td>
                    <td>{{ $row->damage_rate !== null ? number_format($row->damage_rate, 2) : '—' }}</td>
                @endif
                <td colspan="3">{{ number_format($row->amount ?? 0, 2) }}</td>
            </tr>
        @endforeach
        @php
            $totalGst = ($invoice->cgst ?? 0) + ($invoice->sgst ?? 0) + ($invoice->igst ?? 0);
            $totalAmount = $invoice->base_amount ?? (($invoice->invoice_type ?? '') === 'Reverse'
                ? ($invoice->total_amount ?? 0)
                : (($invoice->total_amount ?? 0) - $totalGst));
        @endphp
    @else
    <tr>
        <td style="font-weight: bold;">Sr.No.</td>
        <td style="font-weight: bold;">Gr.no.</td>
        <td style="font-weight: bold;">Truck No.</td>
        <td style="font-weight: bold;">Destination</td>
        <td style="font-weight: bold;">Invoice No.</td>
        <td style="font-weight: bold;">Invoice date</td>
        <td style="font-weight: bold;">Loading Wt.</td>
        <td style="font-weight: bold;">Unloading Wt.</td>
        <td style="font-weight: bold;">Bags</td>
        <td style="font-weight: bold;">Fr rate</td>
        <td style="font-weight: bold;">Shortage</td>
        <td style="font-weight: bold;">Damage</td>
        <td style="font-weight: bold;">Amount</td>
    </tr>
    @foreach ($lrs as $index => $lr_id)
        <tr>
            @php
                $lr = App\Models\LorryReceipt::find($lr_id);
            @endphp
            <td>{{ $index + 1 }}</td>
            <td>{{ $lr->lr_number }}</td>
            <td>{{ $lr->truck ? $lr->truck->truck_number : 'N/A' }}</td>
            <td>{{ $lr->destination_city }}</td>
            <td>{{ $lr->invoice_number }}</td>
            <td>{{ \Carbon\Carbon::parse($lr->gr_date)->format('d-m-Y') }}</td>
            <td>{{ $lr->weight }}</td>
            <td>{{ number_format($lr->unloading_weight ?? $lr->weight, 3) }}</td>
            <td>{{ $lr->no_of_units }}</td>
            <td>{{ number_format($lr->fr_rate, 2) }}</td>
            <td>{{ number_format($lr->shortage_amount ?? 0, 2) }}</td>
            <td>{{ number_format($lr->damage_amount ?? 0, 2) }}</td>
            @php
                $totalShortageDamage = ($lr->shortage_amount ?? 0) + ($lr->damage_amount ?? 0);
                $amount = $lr->actual_amount - $totalShortageDamage;
                $totalAmount += $amount;
            @endphp
            <td>{{ number_format($amount, 2) }}</td>
        </tr>
    @endforeach
    @endif
    @php
        $cgst = $invoice->cgst ?? 0;
        $sgst = $invoice->sgst ?? 0;
        $igst = $invoice->igst ?? 0;
        $cgstpercent = $totalAmount > 0 ? ($cgst * 100) / $totalAmount : 0;
        $sgstpercent = $totalAmount > 0 ? ($sgst * 100) / $totalAmount : 0;
        $igstpercent = $totalAmount > 0 ? ($igst * 100) / $totalAmount : 0;
        $totalgst = $cgst + $sgst + $igst;
    @endphp
    <tr>
        <td style="font-weight: bold;" colspan="9">
            Rs: {{ $totalgst }} GST to be depostied by the party under reverse charge
        </td>
        <td style="font-weight: bold;" colspan="2">Amount</td>
        <td colspan="2">{{ number_format($totalAmount, 2) }}</td>
    </tr>
    <tr>
        <td style="font-weight: bold;" colspan="2">Account No:</td>
        <td colspan="7">{{ $company->account_number ?? '' }}</td>
        @if ($cgst > 0)
            <td style="font-weight: bold;" colspan="2">CGST {{ $cgstpercent }}%</td>
            <td colspan="2">{{ number_format($cgst, 2) }}</td>
        @elseif ($igst > 0)
            <td style="font-weight: bold;" colspan="2">IGST {{ $igstpercent }}%</td>
            <td colspan="2">{{ number_format($igst, 2) }}</td>
        @else
            <td colspan="4"></td>
        @endif
    </tr>
    <tr>
        <td style="font-weight: bold;" colspan="2">IFSC CODE:</td>
        <td colspan="7">{{ $company->ifsc ?? '' }}</td>
        @if ($sgst > 0)
            <td style="font-weight: bold;" colspan="2">SGST {{ $sgstpercent }}%</td>
            <td colspan="2">{{ number_format($sgst, 2) }}</td>
        @else
            <td colspan="4"></td>
        @endif
    </tr>
    <tr>
        <td style="font-weight: bold;" colspan="2">BANK:</td>
        <td colspan="7">{{ $company->bank ?? '' }}</td>
        <td style="font-weight: bold;" colspan="2">Total Amount</td>
        @php
            $grandTotal = $invoice->total_amount ?? ($invoice->invoice_type == 'Reverse' ? $totalAmount : $totalAmount + $totalgst);
        @endphp
        <td colspan="2">{{ number_format($grandTotal, 2) }}</td>
    </tr>
    @if (trim($remark))
        <tr>
            <td style="font-weight: bold;" colspan="9">Remark: {{ trim($remark) }}</td>
            <td colspan="4"></td>
        </tr>
    @endif
    <tr>
        <td style="font-weight: bold;" colspan="9">{{ ucfirst(getIndianCurrency($grandTotal)) }} Only</td>
        <td style="font-weight: bold;" colspan="4">FOR {{ $company->name }}</td>
    </tr>
    <tr>
        <td colspan="9"></td>
        <td colspan="4" rowspan="2"></td>
    </tr>
    <tr>
        <td colspan="13"></td>
    </tr>
    <tr>
        <td style="font-weight: bold;" colspan="9">Note :- GST Liability of Debtor:-  {{ $invoice->consignee->name }}  Under Reverse Charge
            Mechanism of GST Act, 2017</td>
        <td style="font-weight: bold;" colspan="4">Authorized Signature</td>
    </tr>
</table>

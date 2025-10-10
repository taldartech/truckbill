@php
    $company = auth()->user()->company;
    $totalAmount = 0;
    $lrs = $invoice->lr_ids;
@endphp
<style>
    td {
      padding-top: 4px!important;
      padding-bottom: 4px!important;
    }
</style>
<table class="table table-bordered" style="font-size: 13px!important;">
    <tr>
        <td style="font-weight: bold; text-align: center; font-size: 18px!important" colspan="12">{{ $company->name }}</td>
    </tr>
    <tr>
        <td style="font-weight: bold; text-align: center; font-size: 16px!important" colspan="12">{{ $company->address }}</td>
    </tr>
    <tr>
        <td style="text-align: center; font-size: 15px!important" colspan="12">
            {{ optional($company->city)->name . ', ' . optional($company->state)->name . ' - ' . $company->pin_code ?? '' . ' Phone No ' . $company->contact_number }}
        </td>
    </tr>
    <tr>
        <td colspan="12"></td>
    </tr>
    <tr>
        <td style="font-weight: bold; text-align: center; font-size: 15px!important" colspan="12">TRANSPORTATION BILL</td>
    </tr>
    <tr>
        <td style="font-weight: bold;">To,</td>
        <td colspan="11"></td>
    </tr>
    <tr>
        <td style="font-weight: bold;" colspan="3">{{ $invoice->consignee->name }}</td>
        <td colspan="6"></td>
        <td style="font-weight: bold;">PAN No.:</td>
        <td colspan="2">{{ $company->pan_number }}</td>
    </tr>
    <tr>
        <td style="font-weight: bold;" colspan="3">{{ $invoice->consignee->city->name }}</td>
        <td colspan="6"></td>
        <td style="font-weight: bold;">Bill No.:</td>
        <td style="font-weight: bold;" colspan="2">{{ $invoice->manual_invoice_number }}</td>
    </tr>
    <tr>
        <td colspan="3">GSTIN: {{ $invoice->consignee->gst_number }}</td>
        <td colspan="6"></td>
        <td style="font-weight: bold;">Bill Date:</td>
        <td style="font-weight: bold;" colspan="2">{{ $invoice->generated_date }}</td>
    </tr>
    <tr>
        <td colspan="3">PAN No.: {{ $invoice->consignee->pan_number }}</td>
        <td colspan="6"></td>
        <td style="font-weight: bold;">GSTIN:</td>
        <td colspan="2">{{ $company->gst_number }}</td>
    </tr>
    <tr>
        <td colspan="3">State Code: {{ $invoice->consignee->state->code }}</td>
        <td colspan="6"></td>
        <td style="font-weight: bold;">Reverse Charge:</td>
        <td style="font-weight: bold;" colspan="2">{{ $company->gst_scheme == 'forward charge' ? 'No' : 'Yes' }}</td>
    </tr>
    <tr>
        <td colspan="9"></td>
        <td style="font-weight: bold;">SAC Code:</td>
        <td colspan="2"></td>
    </tr>
    <tr>
        <td style="font-weight: bold;">Sr.No.</td>
        <td style="font-weight: bold;">Gr.no.</td>
        <td style="font-weight: bold;">Truck No.</td>
        <td style="font-weight: bold;">Destination</td>
        <td style="font-weight: bold;">Invoice No.</td>
        <td style="font-weight: bold;">Invoice date</td>
        <td style="font-weight: bold;">Wt.</td>
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
            <td>{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d-m-Y') }}</td>
            <td>{{ $lr->weight }}</td>
            <td>{{ $lr->no_of_units }}</td>
            <td>{{ number_format($lr->fr_rate, 2) }}</td>
            <td>{{ number_format($lr->shortage_amount ?? 0, 2) }}</td>
            <td>{{ number_format($lr->damage_amount ?? 0, 2) }}</td>
            @php
                $totalShortageDamage = ($lr->shortage_amount ?? 0) + ($lr->damage_amount ?? 0);
                $amount = $lr->fr_rate * $lr->weight - $totalShortageDamage;
                $totalAmount += $amount;
            @endphp
            <td>{{ number_format($amount, 2) }}</td>
        </tr>
    @endforeach
    @php
        $cgst = $invoice->cgst;
        $sgst = $invoice->sgst;
        $igst = $invoice->igst;
        $totalgst = $cgst + $sgst + $igst;
    @endphp
    <tr>
        <td style="font-weight: bold;" colspan="9">
            Rs: {{ $totalgst }} GST to be depostied by the party under reverse charge
        </td>
        <td style="font-weight: bold;" colspan="2">Amount</td>
        <td>{{ number_format($totalAmount, 2) }}</td>
    </tr>
    @if ($cgst > 0)
        <tr>
            <td colspan="9"></td>
            <td style="font-weight: bold;" colspan="2">CGST 2.5%</td>
            <td>{{ number_format($cgst, 2) }}</td>
        </tr>
    @endif
    @if ($sgst > 0)
        <tr>
            <td colspan="9"></td>
            <td style="font-weight: bold;" colspan="2">SGST 2.5%</td>
            <td>{{ number_format($sgst, 2) }}</td>
        </tr>
    @endif
    @if ($igst > 0)
        <tr>
            <td colspan="9"></td>
            <td style="font-weight: bold;" colspan="2">IGST 5%</td>
            <td>{{ number_format($igst, 2) }}</td>
        </tr>
    @endif
    <tr>
        <td colspan="9"></td>
        <td style="font-weight: bold;" colspan="2">Total Amount</td>
        @php
            $grandTotal = $totalAmount + $totalgst;
        @endphp
        <td>{{ number_format($grandTotal, 2) }}</td>
    </tr>
    <tr>
        <td style="font-weight: bold;" colspan="9">{{ ucfirst(getIndianCurrency($grandTotal)) }} Only</td>
        <td style="font-weight: bold;" colspan="3">FOR {{ $company->name }}</td>
    </tr>
    <tr>
        <td colspan="9"></td>
        <td colspan="3" rowspan="2"></td>
    </tr>
    <tr>
        <td colspan="9"></td>
    </tr>
    <tr>
        <td style="font-weight: bold;" colspan="9">Note :- GST Liability of Debtor:-  {{ $invoice->consignee->name }}  Under Reverse Charge
            Mechanism of GST Act, 2017</td>
        <td style="font-weight: bold;" colspan="3">Authorized Signature</td>
    </tr>
</table>

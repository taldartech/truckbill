@php
    // Flag that tells this view whether it's being rendered for Excel export
    // Default to false when included from a normal Blade view
    $forExcel = $forExcel ?? false;
    
     $balanceAsOnDate = $balanceAsOnDate ?? null;
    $emptyPaymentSummary = ['cash' => 0, 'bank' => 0, 'fuel' => 0, 'advance' => 0, 'total_paid' => 0, 'balance' => 0];
@endphp

<table class="table table-bordered table-striped" style="font-size: 12px !important;">
    <thead>
        <tr>
            <th>Sr No</th>
            <th>GR Date</th>
            <th>LR No</th>
            <th>GR No</th>
            <th>Truck No</th>
            <th>Customer</th>
            <th>Destination</th>
            <th>Invoice No</th>
            <th>Loading Weight</th>
            <th>Unloading Weight</th>
            <th>Bags</th>
            <th>Fr rate</th>
            <th>Amount</th>
            <th>Unloading st</th>
            <th>Distt</th>
            <th>State</th>
            <th>Consignor</th>
            <th>Consignee</th>
            <th>GST Number</th>
            <th>Tr Rate</th>
            <th>Tr Freight</th>
            <th>TDS</th>
            <th>Cash Advance</th>
            <th>Bank</th>
            <th>Adjust In Advance</th>
            <th>Diesel</th>
            <th>Total</th>
            <th>Damage Amount</th>
            <th>Shortage Amount</th>
            <th>Detention</th>
            <th>Deviation</th>
            <th>Misc</th>
            <th>Custom Amount</th>
            <th>Balance</th>
            <th>Negative Adjusted</th>
            <th>Diff</th>
            <th>Memo No</th>
            <th>Bill No</th>
            <th>Driver No</th>
            <th>Transport Name</th>
            <th>Reference By</th>
            <th>Payment Made to</th>
            <th>TDS Deducted</th>
            <th>E-way Bill No</th>
            <th>E-way Bill Expiry Date</th>
            <th>Remarks</th>
            <th>PAN No</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($lrs as $lr)
            <tr>
                @php
                    $freight = $lr->freightBookings->last();
                        $paymentSummary = $freight
                   ? $freight->dispatchPaymentSummary($balanceAsOnDate)
                  : $emptyPaymentSummary;

                    $diff = $lr->fr_rate - $freight?->tr_fr_rate;
                    $invoice = \App\Models\Invoice::whereJsonContains('lr_ids', $lr->id)->first();
                @endphp
                <td>{{ $loop->iteration }}</td>
                <td>
                    @if ($forExcel)
                        {{ \PhpOffice\PhpSpreadsheet\Shared\Date::dateTimeToExcel(\Carbon\Carbon::parse($lr->gr_date)) }}
                    @else
                        {{ \Carbon\Carbon::parse($lr->gr_date)->format('d-m-Y') }}
                    @endif
                </td>
                <td>{{ $lr->lr_number }}</td>
                <td>{{ $lr->gr_number }}</td>
                <td>{{ $lr->truck->truck_number }}</td>
                <td>{{ $lr->customer->name ?? '-' }}</td>
                <td>{{ $lr->destination_city ?? '-' }}</td>
                <td>{{ $lr->invoice_number ?? '-' }}</td>
                <td>{{ $lr->weight ?? 0 }}</td>
                <td>{{ number_format($lr->unloading_weight ?? ($lr->weight ?? 0), 3) }}</td>
                <td>{{ $lr->no_of_units ?? 0 }}</td>
                <td>{{ $lr->fr_rate ?? 0 }}</td>
                <td>{{ $lr->actual_amount ?? 0 }}</td>
                <td>{{ $lr->unloading_station ?? '-' }}</td>
                <td>{{ $lr->destination_city ?? '-' }}</td>
                <td>{{ $lr->destination_state ?? '-' }}</td>
                <td>{{ $lr->consignor->name ?? '-' }}</td>
                <td>{{ $lr->consignee->name ?? '-' }}</td>
                <td>{{ $lr->customer->gst_number ?? '-' }}</td>

                <td>{{ $freight?->tr_fr_rate ?? 0 }}</td>
                <td>{{ $freight?->tr_amount ?? 0 }}</td>
                <td>{{ round($freight?->tds_amount ?? 0) }}</td>

                <td>{{ $paymentSummary['cash'] }}</td>
                <td>{{ $paymentSummary['bank'] }}</td>
                <td>{{ $paymentSummary['advance'] }}</td>
                <td>{{ $paymentSummary['fuel'] }}</td>
                <td>{{ $paymentSummary['total_paid'] }}</td>

                <td>{{ $freight?->damage_amount ?? 0 }}</td>
                <td>{{ $freight?->shortage_amount ?? 0 }}</td>
                <td>{{ $freight?->detention ?? 0 }}</td>
                <td>{{ $freight?->deviation ?? 0 }}</td>
                <td>{{ $freight?->misc_expense ?? 0 }}</td>
                <td>{{ $freight?->customAmountsTotal() ?? 0 }}</td>

                <td>{{ $paymentSummary['balance'] }}</td>
                <td>{{ $freight?->negative_adjusted_amount ?? 0 }}</td>

                <td>{{ $diff ?? 0 }}</td>
                <td>{{ $lr->memo_id ?? '-' }}</td>
                <td>{{ $invoice ? ($invoice->invoice_number ? $invoice->invoice_number . ($invoice->manual_invoice_number ? ' (' . $invoice->manual_invoice_number . ')' : '') : ($invoice->manual_invoice_number ?? $invoice->id)) : '-' }}</td>
                <td>{{ $lr->truck?->driver?->name ?? '-' }}</td>
                <td>{{ optional($lr->transporter)->name ?? '-' }}</td>
                <td>{{ $lr->reference?->name ?? '-' }}</td>
                <td>{{ optional($lr->transporter)->is_owner ? 'owner' : 'agency' }}</td>
                <td>{{ ($freight?->tds_amount ?? 0) > 0 ? 'Yes' : 'No' }}</td>
                <td>
                    @if ($forExcel)
                        {{-- Cast to string and prefix with space so Excel keeps full number as text --}}
                        {{ $freight?->eway_bill_number !== null ? ' ' . (string) $freight->eway_bill_number : '-' }}
                    @else
                        {{ $freight?->eway_bill_number ?? '-' }}
                    @endif
                </td>
                <td>
                    @if ($freight?->eway_bill_expiry)
                        @if ($forExcel)
                            {{ \PhpOffice\PhpSpreadsheet\Shared\Date::dateTimeToExcel(\Carbon\Carbon::parse($freight->eway_bill_expiry)) }}
                        @else
                            {{ \Carbon\Carbon::parse($freight->eway_bill_expiry)->format('d-m-Y') }}
                        @endif
                    @else
                        -
                    @endif
                </td>
                <td>{{ $lr->delivery_status ?? '-' }}</td>
                <td>{{ optional($lr->transporter)->pan_number ?? '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

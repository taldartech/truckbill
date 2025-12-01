<table>
    <thead>
        <tr>
            <th>Sr No</th>
            <th>GR Date</th>
            <th>LR No</th>
            <th>GR No</th>
            <th>Truck No</th>
            <th>Destination</th>
            <th>Invoice No</th>
            <th>Weight</th>
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
            <th>Balance</th>
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
                    $vouchers = $freight?->payments;
                    $total_paid = $vouchers?->sum('amount');
                    $cash = $vouchers?->where('payment_mode', 'cash')->sum('amount');
                    $bank = $vouchers?->where('payment_mode', 'bank transfer')->sum('amount');
                    $fuel = $vouchers?->where('payment_mode', 'fuel(paid to pump)')->sum('amount');
                    $advance = $vouchers?->where('payment_mode', 'adjust in advance')->sum('amount');
                    $diff = $lr->amount - $freight?->tr_amount;
                @endphp
                <td>{{ $loop->iteration }}</td>
                <td>{{ \PhpOffice\PhpSpreadsheet\Shared\Date::dateTimeToExcel(\Carbon\Carbon::parse($lr->gr_date)) }}
                </td>
                <td>{{ $lr->lr_number }}</td>
                <td>{{ $lr->gr_number }}</td>
                <td>{{ $lr->truck->truck_number }}</td>
                <td>{{ $lr->destination_city ?? '-' }}</td>
                <td>{{ $lr->invoice_number ?? '-' }}</td>
                <td>{{ $lr->weight ?? 0 }}</td>
                <td>{{ $lr->no_of_units ?? 0 }}</td>
                <td>{{ $lr->fr_rate ?? 0 }}</td>
                <td>{{ $lr->amount ?? 0 }}</td>
                <td>{{ $lr->unloading_station ?? '-' }}</td>
                <td>{{ $lr->destination_city ?? '-' }}</td>
                <td>{{ $lr->destination_state ?? '-' }}</td>
                <td>{{ $lr->consignor->name ?? '-' }}</td>
                <td>{{ $lr->consignee->name ?? '-' }}</td>
                <td>{{ $lr->customer->gst_number ?? '-' }}</td>

                <td>{{ $freight?->tr_fr_rate ?? 0 }}</td>
                <td>{{ $freight?->tr_amount ?? 0 }}</td>
                <td>{{ $freight?->tds_amount ?? 0 }}</td>

                <td>{{ $cash ?? 0 }}</td>
                <td>{{ $bank ?? 0 }}</td>
                <td>{{ $advance ?? 0 }}</td>
                <td>{{ $fuel ?? 0 }}</td>
                <td>{{ $total_paid ?? 0 }}</td>

                <td>{{ $freight?->damage_amount ?? 0 }}</td>
                <td>{{ $freight?->shortage_amount ?? 0 }}</td>
                <td>{{ $freight?->detention ?? 0 }}</td>
                <td>{{ $freight?->deviation ?? 0 }}</td>
                <td>{{ $freight?->misc_expense ?? 0 }}</td>

                <td>{{ $freight?->balance_amount ?? 0 }}</td>

                <td>{{ $diff ?? 0 }}</td>
                <td>{{ $lr->memo_id ?? '-' }}</td>
                <td>{{ $lr->bill_no ?? '-' }}</td>
                <td>{{ $lr->truck?->driver?->name ?? '-' }}</td>
                <td>{{ $lr->transporter->name ?? '-' }}</td>
                <td>{{ $lr->reference?->name ?? '-' }}</td>
                <td>{{ $lr->transporter->is_owner ? 'owner' : 'agency' }}</td>
                <td>{{ $lr->transporter->tds_percentage ? 'Yes' : 'No' }}</td>
                <td>{{ $freight?->eway_bill_number ?? '-' }}</td>
                <td>{{ $freight?->eway_bill_expiry ? \PhpOffice\PhpSpreadsheet\Shared\Date::dateTimeToExcel(\Carbon\Carbon::parse($freight?->eway_bill_expiry)) : '-' }}
                </td>
                <td>{{ $lr->delivery_status ?? '-' }}</td>
                <td>{{ $lr->transporter->pan_number ?? '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

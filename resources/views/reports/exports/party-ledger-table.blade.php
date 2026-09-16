@php
    $forExcel = $forExcel ?? false;
@endphp

<table class="table table-bordered table-striped table-hover align-middle mb-0 text-nowrap small">
    <thead>
        <tr>
            <th>Sr No</th>
            <th>Date</th>
            <th>LR Number</th>
            <th>Truck No</th>
            <th>Disptach (Destination)</th>
            <th>LR Rate</th>
            <th>LR Amt</th>
            <th>Bill no</th>
            <th>Bill Date</th>
            
            <th>Amount</th>
            <th>Due Amount</th>

            <th>Payment Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($ledgerRows as $row)
            @php
                $lr = $row['lr'];
            @endphp
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td style="white-space: nowrap;">
                    @if ($forExcel)
                        {{ \PhpOffice\PhpSpreadsheet\Shared\Date::dateTimeToExcel(\Carbon\Carbon::parse($lr->gr_date)) }}
                    @else
                        {{ \Carbon\Carbon::parse($lr->gr_date)->format('d-m-Y') }}
                    @endif
                </td>
                <td>{{ $lr->lr_number }}</td>
                <td>{{ $lr->truck?->truck_number ?? 'N/A' }}</td>
                <td>{{ $lr->destination_city . ', ' . $lr->destination_state }}</td>
                <td style="text-align: right;">{{ number_format($lr->fr_rate, 2) }}</td>
                <td style="text-align: right;">{{ number_format($lr->amount, 2) }}</td>
                <td>{{ $row['bill_number'] }}</td>
                <td style="white-space: nowrap;">
                    @if ($row['bill_date'])
                        @if ($forExcel)
                            {{ \PhpOffice\PhpSpreadsheet\Shared\Date::dateTimeToExcel(\Carbon\Carbon::parse($row['bill_date'])) }}
                        @else
                            {{ \Carbon\Carbon::parse($row['bill_date'])->format('d-m-Y') }}
                        @endif
                    @else
                        N/A
                    @endif
                </td>
                <td style="text-align: right;">{{ number_format($row['bill_amount'], 2) }}</td>
                <td style="text-align: right;">
                 {{ number_format($row['due_amount'], 2) }}
                 </td>
                <td>{{ $row['payment_status'] }}</td>
            </tr>
        @empty
            @unless ($forExcel)
                <tr>
                    <td colspan="11" class="text-center">No records found for the selected criteria.</td>
                </tr>
            @endunless
        @endforelse
    </tbody>
</table>

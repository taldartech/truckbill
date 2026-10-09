@php
    $forExcel = $forExcel ?? false;
    $rows = $partyRows instanceof \Illuminate\Pagination\LengthAwarePaginator
        ? collect($partyRows->items())
        : collect($partyRows);
@endphp

<table class="table table-bordered table-striped table-hover align-middle mb-0 text-nowrap small">
    <thead>
        <tr>
            <th>Sr No</th>
            <th>Party</th>
            <th class="text-end">Pending Invoices</th>
            <th class="text-end">Amount to Collect</th>
            <th class="text-end">Paid Amount</th>
            <th class="text-end">Pending Amount</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $row['party_name'] }}</td>
                <td class="text-end">{{ $row['invoice_count'] }}</td>
                <td class="text-end">{{ number_format($row['amount_to_collect'], 2) }}</td>
                <td class="text-end">{{ number_format($row['paid_amount'], 2) }}</td>
                <td class="text-end">{{ number_format($row['pending_amount'], 2) }}</td>
            </tr>
        @empty
            @unless ($forExcel)
                <tr>
                    <td colspan="6" class="text-center">No records found for the selected criteria.</td>
                </tr>
            @endunless
        @endforelse
        @if ($rows->isNotEmpty())
            <tr>
                <td colspan="3" class="text-end fw-bold">Grand Total</td>
                <td class="text-end fw-bold">{{ number_format($grandTotalCollect, 2) }}</td>
                <td class="text-end fw-bold">{{ number_format($grandTotalPaid, 2) }}</td>
                <td class="text-end fw-bold">{{ number_format($grandTotalPending, 2) }}</td>
            </tr>
        @endif
    </tbody>
</table>

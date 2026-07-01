@if ($customer)
    <table>
        <tr>
            <th>Party Name</th>
            <td>{{ $customer->name }}</td>
        </tr>
        <tr>
            <th>GST No</th>
            <td>{{ $customer->gst_number ?? '-' }}</td>
        </tr>
        <tr>
            <th>Location</th>
            <td>{{ ($customer->city?->name ?? '-') . ', ' . ($customer->state?->name ?? '-') }}</td>
        </tr>
        <tr>
            <th>Phone No</th>
            <td>{{ $customer->contact_number ?? '-' }}</td>
        </tr>
    </table>
@endif

@include('reports.exports.party-ledger-table', [
    'ledgerRows' => $ledgerRows,
    'forExcel' => $forExcel ?? false,
])

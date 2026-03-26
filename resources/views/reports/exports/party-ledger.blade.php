<table>
  <tr>
    <th>Party Name</th>
    <td>{{ $customer->name }}</td>
  </tr>
  <tr>
    <th>GST No</th>
    <td>{{ $customer->gst_number }}</td>
  </tr>
  <tr>
    <th>Location</th>
    <td>{{ $customer->city->name . ', ' . $customer->state->name }}</td>
  </tr>
  <tr>
    <th>Phone No</th>
    <td>{{ $customer->contact_number }}</td>
  </tr>
</table>
<table>
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
        <th>Payment Status</th>
    </tr>
    @foreach ($lrs as $index => $lr)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $lr->gr_date }}</td>
            <td>{{ $lr->lr_number }}</td>
            <td>{{ $lr->truck ? $lr->truck->truck_number : 'N/A' }}</td>
            <td>{{ $lr->destination_city . ', ' . $lr->destination_state }}</td>
            <td>{{ $lr->fr_rate }}</td>
            <td>{{ $lr->amount }}</td>
            @php
                $invoice = \App\Models\Invoice::whereJsonContains('lr_ids', $lr->id)->first();
                // Fetch amount and payment status from the bill if exists
                if (($invoice->invoice_type ?? '') === 'Forward') {
                    $gst = 5;
                } else {
                    $gst = 0;
                }
            @endphp
            <td>{{ $invoice->invoice_number ? $invoice->invoice_number . ($invoice->manual_invoice_number ? ' (' . $invoice->manual_invoice_number . ')' : '') : ($invoice->manual_invoice_number ?? $invoice->id) }}</td>
            <td>{{ $invoice->generated_date }}</td>
            <td>{{ $lr->amount + ($lr->amount * $gst) / 100 }}</td>
            <td>{{ $invoice->payment_status }}</td>
        </tr>
    @endforeach

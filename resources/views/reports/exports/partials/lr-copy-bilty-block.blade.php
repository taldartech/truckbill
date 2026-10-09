@php
    $companyLine = ($company->address ?? '') . ', ' . optional($company->country)->name . ', ' . optional($company->state)->name . ', ' . optional($company->city)->name . ' - ' . ($company->pin_code ?? '') . ', Ph. ' . ($company->contact_number ?? '');
@endphp
<div class="lr-sheet__ribbon">Lorry Receipt / Good Receipt / Bilty Copy - <span class="fw-bold">{{ $lr->lr_number }}</span></div>
<div class="lr-sheet__inner">
<table class="lr-bilty-table" style="width:100%; border-collapse:collapse;">
    <tr>
        <td colspan="12" class="text-center fw-bold">
            <p class="fs-5">{{ $company->name }}</p>
            <p class="">{{ $companyLine }}</p>
            <p class="">GST No: {{ $company->gst_number }}, PAN No: {{ $company->pan_number }}</p>
        </td>
    </tr>
    <tr>
        <td colspan="3" class="text-center fw-bold">CAUTION</td>
        <td colspan="3" class="text-center fw-bold">INSURANCE</td>
        <td colspan="6" class="text-center fw-bold">NOTICE</td>
    </tr>
    <tr>
        <td colspan="3" class="values">
            This consignment will not be detained, delivered, re-quoted, or re-booked without consignee
            Bank's written permission.
        </td>
        <td colspan="3" class="values">
            The Consignor has stated that* He has not insured the consignment or* He has insured the
            consignment Policy No. Date Amount Risk.Question is that:
        </td>
        <td colspan="6" class="values">
            The consignment covered by this set of Special Good Receipt (Bilty) form shall be stored under control
            of the Transport Operator and shall be delivered to the consignee Party, whose name as mentioned
            in the Good Receipt (Bilty). It will under no circumstances be delivered to anyone without the written
            authority from the Consignee party or its order recorded on the Consignee copy or on separate
            Letter of Authority.
        </td>
    </tr>
    <tr>
        <td colspan="3">
            <p class="fw-bold">LR No.:</p>
            <p class="values fw-bold">{{ $lr->lr_number }}</p>
        </td>
        <td colspan="3">
            <p class="fw-bold">Truck Owner PAN:</p>
            <p class="values">{{ optional(optional($lr->truck)->transporter)->pan_number ?? '-' }}</p>
        </td>
        <td colspan="2">
            <p class="fw-bold">LR Date: </p>
            <p class="values">{{ \Carbon\Carbon::parse($lr->gr_date)->format('d.m.Y') }}</p>
        </td>
        <td colspan="2">
            <p class="fw-bold">Truck Number: </p>
            <p class="values fw-bold">{{ $lr->truck->truck_number }}</p>
        </td>
        <td colspan="2">
            <p class="fw-bold">Invoice No: </p>
            <p class="values">{{ $lr->invoice_number ?? '' }}</p>
        </td>
    </tr>
    <tr>
        <td colspan="8">
            <p>
                <span class="fw-bold">Consignor's Name:</span>
                <span class="values">{{ $lr->consignor->name }}</span>
                <span class="fw-bold">GST NO:</span>
                <span class="values">{{ $lr->consignor->gst_number }}</span>
            </p>
            <p>
                <span class="fw-bold">Consignee's Name:</span>
                <span class="values">{{ $lr->consignee->name }}</span>
                <span class="fw-bold">GST NO</span>
                <span class="values">{{ $lr->consignee->gst_number }}</span>
            </p>
        </td>
        <td colspan="4" class="fw-bold">
            <p>
                <span class="fw-bold">From:</span>
                <span class="values">{{ $lr->source_city . ', ' . $lr->source_state }}</span>
            </p>
            <p>
                <span class="fw-bold">To:</span>
                <span class="values">{{ $lr->destination_city . ', ' . $lr->destination_state }}</span>
            </p>
            <p>
                <span class="fw-bold">Unloading Station:</span>
                <span class="values">{{ $lr->unloading_station ?? '-' }}</span>
            </p>
        </td>
    </tr>
    <tr class="text-center">
        <td colspan="7" class="text-center fw-bold">Description(said to contain)/ Product Description</td>
        <td colspan="3" class="text-center fw-bold">No Of Units</td>
        <td colspan="2" class="text-center fw-bold">Weight(MT)</td>
    </tr>
    <tr class="text-center">
        <td colspan="7" class="values">{{ $lr->good_type }}</td>
        <td colspan="3" class="values">{{ $lr->no_of_units }}</td>
        <td colspan="2" class="values">{{ $lr->weight }}</td>
    </tr>
    <tr class="text-center">
        <td colspan="7" class="values">Total</td>
        <td colspan="3" class="values">{{ $lr->no_of_units }}</td>
        <td colspan="2" class="values">{{ $lr->weight }}</td>
    </tr>
    <tr>
        <td colspan="9">
            @php
                $fr = $lr->freightBookings->last();
                $ewayBillNumber = $fr ? $fr->eway_bill_number : null;
                $ewayBillExpiry = $fr ? $fr->eway_bill_expiry : null;
            @endphp
            @if ($ewayBillNumber || $ewayBillExpiry)
                <span class="fw-bold">Eway Bill:</span>
                <span class="values">{{ $ewayBillNumber }} ({{ \Carbon\Carbon::parse($ewayBillExpiry)->format('d-m-Y') }})</span>
            @endif
        </td>
        <td colspan="3">
            <span class="fw-bold">TotalFreight:</span>
            <span class="values">
                @if (request()->has('show_freight') && request()->get('show_freight') == 1)
                    {{ number_format($lr->amount, 2) }}
                @endif
            </span>
        </td>
    </tr>
    <tr>
        <td colspan="8">
            <p>AT OWNERS RISK</p>
            <p>Declaration: We hereby declare that we have not availed input credit on capital goods and
                forproviding services.</p>
            @if ($company->gst_scheme == 'reverse charge')
                <p>Under RCM (Reverse Charge Mechanism) GST will be paid by service recipient only.</p>
            @endif
            <p>{{ $copyLabel }}</p>
        </td>
        <td colspan="4" style="vertical-align: bottom; text-align: right;">
            For {{ $company->name }}
            <br>
            @php
                $signatureSrc = $company->signatureFilesystemPathForPdf() ?: $company->signaturePublicUrl();
            @endphp
            @include('reports.exports.partials.pdf-company-signature-slot', [
                'signatureSrc' => $signatureSrc,
                'companyName' => $company->name,
                'slotWidthPx' => 100,
                'slotHeightPx' => 36,
                'align' => 'right',
            ])
            @if (empty($signatureSrc))
                <br><br>
            @endif
            Authorized Signature
        </td>
    </tr>
</table>
<div style="text-align: right; margin-top: 1px; line-height: 1;">
    <span class="fw-bold" style="font-size: 7px;">Digitally Managed by TruckBill (www.truckbill.in)</span>
</div>
</div>

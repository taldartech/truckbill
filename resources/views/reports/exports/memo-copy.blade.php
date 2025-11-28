@php
    $company = auth()->user()->company;
    $totalAmount = 0;
    $damageAmount = 0;
    $shortageAmount = 0;
    $netAmount = 0;

    $customer = $lrs->first() ? $lrs->first()->customer : null;

    // Charge type totals according to lane
    $chargeTypeTotals = [];
    $chargeTypeWeights = [];
    $charge_types = $lrs->groupBy('lane')->keys();
    foreach ($charge_types as $type) {
        $chargeTypeTotals[$type] = $lrs->where('lane', $type)->sum(function ($lr) {
            return $lr->weight * $lr->fr_rate;
        });
        $chargeTypeWeights[$type] = $lrs->where('lane', $type)->sum('weight');
    }
@endphp
<style>
    p {
        margin: 0;
    }
</style>
<table class="table table-bordered" style="font-size: 13px!important;">
    <tr>
        <td style="text-align: center; font-weight: bold; font-size: 18px!important;" colspan="13">{{ $company->name }}
        </td>
    </tr>
    <tr>
        <td style="text-align: center; font-weight: bold; font-size: 16px!important;" colspan="13">
            {{ $company->address }}, {{ optional($company->city)->name }}
        </td>
    </tr>
    <tr>
        <td style="text-align: center; font-weight: bold; font-size: 15px!important;" colspan="13">
            {{ 'Phone No ' . $company->contact_number }}
        </td>
    </tr>
    <tr>
        <td style="text-align: right; font-weight: bold;" colspan="13">Service Tax Regn No :</td>
    </tr>
    <tr>
        <td style="font-weight: bold;" colspan="2">Memo No.: {{ $memo_id }}</td>
        <td style="font-weight: bold;" colspan="8">
            {{ $customer->name }}, {{ $customer->address }}, {{ $customer->city->name }}
        </td>
        <td style="font-weight: bold;" colspan="3">Dt: {{ date('d-m-Y') }}</td>
    </tr>
    <tr>
        <td style="font-weight: bold; text-align: center" colspan="13">Summary</td>
    </tr>
    <tr>
        <td style="font-weight: bold;" colspan="7">Charge Type</td>
        <td style="font-weight: bold;" colspan="3">QTY (MT)</td>
        <td style="font-weight: bold;" colspan="3">Amount</td>
    </tr>
    @foreach ($charge_types as $type)
        <tr>
            <td colspan="7">{{ $type }}</td>
            <td style="text-align: right" colspan="3">{{ number_format($chargeTypeWeights[$type], 2) }}</td>
            <td style="text-align: right" colspan="3">{{ number_format($chargeTypeTotals[$type], 2) }}</td>
        </tr>
    @endforeach
    <tr>
        <td colspan="13"></td>
    </tr>
    <tr>
        <td style="font-weight: bold">Truck No</td>
        <td style="font-weight: bold">From Place</td>
        <td style="font-weight: bold">To Place</td>
        <td style="font-weight: bold">Consignment No</td>
        <td style="font-weight: bold">Date</td>
        <td style="font-weight: bold">Lane</td>
        <td style="font-weight: bold">QTY(MT)</td>
        <td style="font-weight: bold">Rate</td>
        <td style="font-weight: bold">Damage</td>
        <td style="font-weight: bold">Shortage</td>
        <td style="font-weight: bold">Amt.</td>
        <td style="font-weight: bold">S.Tax</td>
        <td style="font-weight: bold">S.B.Cess</td>
    </tr>
    @foreach ($lrs as $lr)
        @php
            $damageAmount += $lr->damage_amount;
            $shortageAmount += $lr->shortage_amount;
            $amount = $lr->weight * $lr->fr_rate;
            $totalAmount += $amount;
        @endphp
        <tr>
            <td>{{ $lr->truck->truck_number }}</td>
            <td>{{ $lr->source_city }}</td>
            <td>{{ $lr->destination_city }}</td>
            <td>{{ $lr->lr_number }}</td>
            <td>{{ date('d-m-Y', strtotime($lr->gr_date)) }}</td>
            <td>{{ $lr->lane }}</td>
            <td style="text-align: right">{{ number_format($lr->weight, 2) }}</td>
            <td style="text-align: right">{{ number_format($lr->fr_rate, 2) }}</td>
            <td style="text-align: right">{{ number_format($lr->damage_amount, 2) }}</td>
            <td style="text-align: right">{{ number_format($lr->shortage_amount, 2) }}</td>
            <td style="text-align: right">{{ number_format($amount, 2) }}</td>
            <td style="text-align: right">0.00</td>
            <td style="text-align: right">0.00</td>
        </tr>
    @endforeach
    <tr>
        <td colspan="8"></td>
        <td style="font-weight: bold; text-align: right;" colspan="4">Gross Total:</td>
        <td>{{ number_format($totalAmount, 2) }}</td>
    </tr>
    <tr>
        <td colspan="8"></td>
        <td style="font-weight: bold; text-align: right;" colspan="4">Less :Shortage</td>
        <td>{{ number_format($shortageAmount, 2) }}</td>
    </tr>
    <tr>
        <td colspan="8"></td>
        <td style="font-weight: bold; text-align: right;" colspan="4">Less: Damage</td>
        <td>{{ number_format($damageAmount, 2) }}</td>
    </tr>
    @php
        $netAmount = $totalAmount - $damageAmount - $shortageAmount;
    @endphp
    <tr>
        <td colspan="8"></td>
        <td style="font-weight: bold; text-align: right;" colspan="4">Net Total</td>
        <td>{{ number_format($netAmount, 2) }}</td>
    </tr>
    <tr>
        <td colspan="8"></td>
        <td style="font-weight: bold; text-align: right;" colspan="4">Service Tax</td>
        <td>0.00</td>
    </tr>
    <tr>
        <td colspan="8"></td>
        <td style="font-weight: bold; text-align: right;" colspan="4">Swachh Bharat cess</td>
        <td>0.00</td>
    </tr>
    <tr>
        <td colspan="8"></td>
        <td style="font-weight: bold; text-align: right;" colspan="4">Total Value :</td>
        <td>{{ number_format($netAmount, 2) }}</td>
    </tr>
    <tr>
        <td colspan="13">
            In words: {{ getIndianCurrency($netAmount) }}
        </td>
    </tr>
    <tr>
        <td style="font-weight: bold; text-align: center;" colspan="13">DECLARATION</td>
    </tr>
    <tr>
        <td colspan="13">
            We hereby declare that we have not availed GST credit paid on input or capital goods used for providing
            services under GST rules 2017. GST service file under RCM &amp; GST liabilities is of service recipient.
        </td>
    </tr>
    <tr>
        <td style="font-weight: bold; text-align: right;" colspan="13">For {{ Auth::user()->company->name }}</td>
    </tr>
    <tr>
      <td colspan="13"></td>
    </tr>
    <tr>
      <td colspan="13"></td>
    </tr>
    <tr>
        <td style="text-align: right;" colspan="13">Authorised Signature</td>
    </tr>
</table>

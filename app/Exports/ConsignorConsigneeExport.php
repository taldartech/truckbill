<?php

namespace App\Exports;

use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ConsignorConsigneeExport implements FromCollection, WithHeadings, ShouldAutoSize
{
  private $startDate, $endDate, $consignorId, $consigneeId;

  public function __construct($startDate, $endDate, $consignorId, $consigneeId)
  {
    $this->startDate = $startDate;
    $this->endDate = $endDate;
    $this->consignorId = $consignorId;
    $this->consigneeId = $consigneeId;
  }

  /**
   * @return \Illuminate\Support\Collection
   */
  public function collection()
  {
    // Fetch data based on the provided parameters
    $company_id = Auth::user()->company_id;
    $query = \App\Models\LorryReceipt::whereBetween('gr_date', [$this->startDate, $this->endDate]);

    if ($company_id) {
      $query->where('company_id', $company_id);
    }

    if ($this->consignorId) {
      $query->where('consignor_id', $this->consignorId);
    }

    if ($this->consigneeId) {
      $query->where('consignee_id', $this->consigneeId);
    }

    $lrs = $query->get();

    // Map data to the desired format
    return $lrs->map(function ($lr, $index) {
      return [
        "Sr No" => $index + 1,
        "Date" => $lr->gr_date,
        "LR No" => $lr->lr_number,
        "Inv No of Factory" => $lr->invoice_number,
        "Truck No" => $lr->truck->truck_number ?? '',
        "Weight" => $lr->weight,
        "LR Rate" => $lr->fr_rate,
        "LR Amt" => $lr->amount,
        "Consignor Name" => optional($lr->consignor)->name,
        "Consignee Name" => optional($lr->consignee)->name,
        "Destination" => $lr->destination_city . ", " . $lr->destination_state,
        "Transport/ Truck Owner Name" => optional($lr->transporter)->name,
      ];
    });
  }

  public function headings(): array
  {
    return [
      "Sr No",
      "Date",
      "LR No",
      "Inv No of Factory",
      "Truck No",
      "Weight",
      "LR Rate",
      "LR Amt",
      "Consignor Name",
      "Consignee Name",
      "Destination",
      "Transport/ Truck Owner Name",
    ];
  }
}

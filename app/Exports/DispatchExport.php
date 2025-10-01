<?php

namespace App\Exports;

use App\Models\FreightBooking;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TDSExport implements FromCollection, WithHeadings, ShouldAutoSize
{
  private $startDate, $endDate;

  public function __construct($startDate, $endDate)
  {
    $this->startDate = $startDate;
    $this->endDate = $endDate;
  }

  /**
   * @return \Illuminate\Support\Collection
   */
  public function collection()
  {
    // Fetch data based on the provided parameters
    $company_id = Auth::user()->company_id;
    $query = FreightBooking::whereHas('lr', function ($query) {
      $query->whereBetween('gr_date', [$this->startDate, $this->endDate]);
    });

    if ($company_id) {
      $query->where('company_id', $company_id);
    }
    $query->where('tds_amount', '>', 0);

    $lrs = $query->get();

    // Map data to the desired format
    return $lrs->map(function ($lr, $index) {
      return [
        "Sr No" => $index + 1,
        "Date" => $lr->lr->gr_date,
        "LR No" => $lr->lr->lr_number,
        "Truck No" => $lr->lr->truck->truck_number ?? 'N/A',
        "Destination" => $lr->lr->destination_city . ', ' . $lr->lr->destination_state,
        "TR Freight" => number_format($lr->tr_fr_rate, 2),
        "TDS" => (string) number_format($lr->tds_amount, 2),
        "Rate" => (string) $lr->transporter_tds_percentage . '%',
        "Transport Name" => $lr->lr->transporter->name ?? 'N/A',
        "PAN No" => $lr->lr->transporter->pan_number ?? 'N/A',
      ];
    });
  }

  public function headings(): array
  {
    return [
      "Sr No",
      "Date",
      "LR No",
      "Truck No",
      "Destination",
      "TR Freight",
      "TDS",
      "Rate",
      "Transport Name",
      "PAN No",
    ];
  }
}

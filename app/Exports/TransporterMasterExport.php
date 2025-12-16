<?php

namespace App\Exports;

use App\Models\Transporter;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class TransporterMasterExport implements FromCollection, WithHeadings, ShouldAutoSize
{
  protected $type;

  public function __construct(string $type = 'transporter')
  {
    $this->type = $type;
  }

  public function collection()
  {
    $query = Transporter::with(['city', 'state', 'country'])
      ->where('company_id', Auth::user()->company_id);

    $normalizedType = strtolower($this->type);
    if ($normalizedType === 'transporter') {
      $query->where('is_owner', 0);
    } elseif ($normalizedType === 'owner' || $normalizedType === 'truck_owner') {
      $query->where('is_owner', 1);
    } // both → no additional filter

    return $query->orderBy('name')->get()->values()->map(function ($t, $index) {
      return [
        'S. No' => $index + 1,
        'Type' => $t->is_owner ? 'Truck Owner' : 'Transporter',
        'Name' => $t->name,
        'Short Name' => $t->short_name,
        'Contact Person Name' => $t->contact_person_name,
        'Mobile Number' => $t->mobile_number,
        'Address' => $t->address,
        'City' => optional($t->city)->name,
        'State' => optional($t->state)->name,
        'Country' => optional($t->country)->name,
        'PAN Number' => $t->pan_number,
        'PAN Active' => $t->pan_active ? 'Yes' : 'No',
        'GST Number' => $t->gst_number,
        'Beneficiary ID' => $t->beneficiary_id,
        'TDS Percentage' => $t->tds_percentage,
        'Declaration Given' => $t->is_owner ? ($t->declaration_given ? 'Yes' : 'No') : '-',
        'Total Trucks' => $t->is_owner ? $t->total_trucks : '-',
        'Advance Amount' => $t->advance_amount,
      ];
    });
  }

  public function headings(): array
  {
    return [
      'S. No',
      'Type',
      'Name',
      'Short Name',
      'Contact Person Name',
      'Mobile Number',
      'Address',
      'City',
      'State',
      'Country',
      'PAN Number',
      'PAN Active',
      'GST Number',
      'Beneficiary ID',
      'TDS Percentage',
      'Declaration Given',
      'Total Trucks',
      'Advance Amount',
    ];
  }
}



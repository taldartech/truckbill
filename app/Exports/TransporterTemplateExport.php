<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TransporterTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'PAN Number',
            'Transport Name',
            'Email',
            'GST Number',
            'PAN Active',
            'Contact Person Name',
            'Mobile Number',
            'Country',
            'State',
            'City',
            'Address',
            'Declaration Given',
            'TDS %',
            'TDS Adjustment Remark',
            'Short Name',
            'Advance Amount',
        ];
    }

    public function array(): array
    {
        return [];
    }
}
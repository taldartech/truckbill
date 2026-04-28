<?php

namespace App\Exports;

use App\Models\Invoice;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class PartyBillExport implements FromView, ShouldAutoSize, WithEvents
{
  protected $id;

  public function __construct($id)
  {
    $this->id = $id;
  }

  /**
   * @return \Illuminate\Contracts\View\View
   */
  public function view(): View
  {
    $invoice = Invoice::find($this->id);
    $customDetails = collect();
    if ($invoice && $invoice->custom_invoice) {
      $invoice->load(['customInvoiceDetails.lorryReceipt.truck']);
      $customDetails = $invoice->customInvoiceDetails->sortBy('sort_order')->values();
    }
    return view('reports.exports.party-bill', [
      'invoice' => $invoice,
      'customDetails' => $customDetails,
      'forExcel' => true,
    ]);
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $sheet = $event->sheet->getDelegate();
        $highestColumn = $sheet->getHighestColumn();
        $highestRow = $sheet->getHighestRow();

        $usedRange = "A1:{$highestColumn}{$highestRow}";
        $sheet->getStyle($usedRange)->applyFromArray([
          'borders' => [
            'allBorders' => [
              'borderStyle' => Border::BORDER_THIN,
              'color' => ['argb' => 'FF000000'],
            ],
          ],
        ]);

        for ($row = 1; $row <= $highestRow; $row++) {
          $label = (string) $sheet->getCell("A{$row}")->getValue();
          if (stripos($label, 'Account No') !== false) {
            $accountCell = "C{$row}";
            $accountValue = (string) $sheet->getCell($accountCell)->getValue();
            $sheet->setCellValueExplicit($accountCell, $accountValue, DataType::TYPE_STRING);
            $sheet->getStyle($accountCell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            break;
          }
        }
      },
    ];
  }
}

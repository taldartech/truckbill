<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Contracts\View\View;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ReportExport implements FromView, ShouldAutoSize, WithTitle, WithEvents
{
    protected $data;
    protected $viewName;
    protected $title;

    public function __construct($data, $viewName, $title = 'Report')
    {
        $this->data = $data;
        $this->viewName = $viewName;
        $this->title = $title;
    }

    public function view(): View
    {
        return view($this->viewName, $this->data);
    }

    public function title(): string
    {
        return $this->title . '_' . now()->format('Y-m-d');
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Set default font
                $event->sheet->getDelegate()->getStyle('A:Z')
                    ->getFont()
                    ->setName('Arial')
                    ->setSize(10);

                // Set title formatting
                $event->sheet->getDelegate()->getStyle('A1:Z1')
                    ->getFont()
                    ->setBold(true)
                    ->setSize(12)
                    ->getColor()->setRGB('FFFFFF');

                // Set header background
                $event->sheet->getDelegate()->getStyle('A1:Z1')
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('4472C4');

                // Set borders for all cells
                $event->sheet->getDelegate()->getStyle(
                    'A1:' . 
                    $event->sheet->getDelegate()->getHighestColumn() . 
                    $event->sheet->getDelegate()->getHighestRow()
                )->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ]);

                // Set number formatting for currency and numbers
                $highestColumn = $event->sheet->getDelegate()->getHighestColumn();
                $highestRow = $event->sheet->getDelegate()->getHighestRow();
                
                // Format currency columns (assuming amount columns contain 'amount' or 'total' in header)
                foreach (range('A', $highestColumn) as $column) {
                    $header = $event->sheet->getDelegate()->getCell($column . '1')->getValue();
                    if (is_string($header) && (stripos($header, 'amount') !== false || stripos($header, 'total') !== false)) {
                        $event->sheet->getDelegate()->getStyle($column . '2:' . $column . $highestRow)
                            ->getNumberFormat()
                            ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);
                    }
                    
                    // Auto-size columns
                    $event->sheet->getDelegate()->getColumnDimension($column)->setAutoSize(true);
                }

                // Freeze the first row
                $event->sheet->getDelegate()->freezePane('A2');

                // Set print settings
                $event->sheet->getDelegate()->getPageSetup()
                    ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
                $event->sheet->getDelegate()->getPageSetup()
                    ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
                $event->sheet->getDelegate()->getPageSetup()
                    ->setFitToWidth(1);
                $event->sheet->getDelegate()->getPageSetup()
                    ->setFitToHeight(0);
                
                // Set print area
                $event->sheet->getDelegate()->getPageSetup()->setPrintArea(
                    'A1:' . $highestColumn . $highestRow
                );
            },
        ];
    }
}

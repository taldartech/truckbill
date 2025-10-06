<?php

namespace App\Exports;

use App\Models\Invoice;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class PartyBillExport implements FromView
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
    return view('reports.exports.party-bill', compact('invoice'));
  }
}

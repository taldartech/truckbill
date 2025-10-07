<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class MemoCopyExport implements FromView
{
  protected $memo_id;

  public function __construct($memo_id)
  {
    $this->memo_id = $memo_id;
  }

  /**
   * @return \Illuminate\Contracts\View\View
   */
  public function view(): View
  {
    $lrs = \App\Models\LorryReceipt::where('memo_id', $this->memo_id)->get();
    return view('reports.exports.memo-copy', [
      'lrs' => $lrs,
      'memo_id' => $this->memo_id,
    ]);
  }
}

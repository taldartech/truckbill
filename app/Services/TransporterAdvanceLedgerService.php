<?php

namespace App\Services;

use App\Models\AdvancePayment;
use App\Models\Transporter;
use App\Models\User;
use App\Services\Notifications\AdvancePaymentNotificationService;
use Illuminate\Validation\ValidationException;

class TransporterAdvanceLedgerService
{
    public function __construct(
        private readonly AdvancePaymentNotificationService $advancePaymentNotificationService
    ) {}

    public function recordAdvancePayment(
        int $companyId,
        int $transporterId,
        float $advanceAmount,
        string $paymentDate,
        ?string $remark = null,
        ?User $actor = null,
        bool $notifyCredit = true
    ): AdvancePayment {
        $transporter = Transporter::findOrFail($transporterId);
        $newBalance = (float) $transporter->advance_amount + $advanceAmount;

        if ($newBalance < 0) {
            throw ValidationException::withMessages([
                'advance_amount' => 'Deduction exceeds available book balance of '.number_format((float) $transporter->advance_amount, 2).'.',
            ]);
        }

        $advancePayment = AdvancePayment::create([
            'company_id' => $companyId,
            'transporter_id' => $transporterId,
            'advance_amount' => $advanceAmount,
            'payment_date' => $paymentDate,
            'remark' => $remark,
        ]);

        $transporter->advance_amount = $newBalance;
        $transporter->save();

        if ($notifyCredit && $advanceAmount > 0 && $actor) {
            $advancePayment->loadMissing(['company', 'transporter.transporterMaster']);
            $this->advancePaymentNotificationService->notifyAdvancePaid($advancePayment, $actor);
        }

        return $advancePayment;
    }
}

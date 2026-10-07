<?php

namespace App\Services;

use App\Models\FreightBooking;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\LorryReceipt;
use App\Models\VoucherPayment;
use Illuminate\Database\Eloquent\Model;

class AuditAttributeLabels
{
    /**
     * Human-readable label for a model attribute.
     */
    public static function labelFor(Model $model, string $attribute): string
    {
        $map = match ($model::class) {
            Invoice::class => [
                'invoice_number' => 'Invoice number',
                'manual_invoice_number' => 'Manual invoice number',
                'invoice_type' => 'Invoice type',
                'total_amount' => 'Total amount',
                'net_amount' => 'Net amount',
                'base_amount' => 'Base amount',
                'payment_status' => 'Payment status',
                'customer_id' => 'Customer',
                'generated_date' => 'Generated date',
                'due_date' => 'Due date',
                'memo_ids' => 'Memos',
                'lr_ids' => 'Good Receipts (Bilty)',
                'sgst' => 'SGST',
                'cgst' => 'CGST',
                'igst' => 'IGST',
            ],
            InvoicePayment::class => [
                'amount' => 'Amount',
                'mode' => 'Payment mode',
                'remark' => 'Remark',
                'payment_date' => 'Payment date',
            ],
            LorryReceipt::class => [
                'lr_number' => 'LR number',
                'delivery_status' => 'Delivery status',
                'amount' => 'Amount',
                'actual_amount' => 'Actual amount',
                'memo_id' => 'Memo ID',
                'weight' => 'Weight',
                'unloading_weight' => 'Unloading weight',
                'consignee_id' => 'Consignee',
                'consignor_id' => 'Consignor',
                'transporter_id' => 'Transporter',
                'gr_date' => 'GR date',
                'goods_value' => 'Goods value',
                'fr_rate' => 'Freight rate',
            ],
            FreightBooking::class => [
                'tr_amount' => 'Transporter amount',
                'advance_amount' => 'Advance amount',
                'balance_amount' => 'Balance amount',
                'tds_amount' => 'TDS amount',
                'is_delivered' => 'Delivered',
                'is_cancelled' => 'Cancelled',
                'voucher_number' => 'Voucher number',
                'remark' => 'Remark',
                'eway_bill_number' => 'E-way bill number',
                'eway_bill_expiry' => 'E-way bill expiry',
                'tr_fr_rate' => 'Transporter freight rate',
                'shortage_amount' => 'Shortage amount',
                'damage_amount' => 'Damage amount',
            ],
            VoucherPayment::class => [
                'amount' => 'Amount',
                'payment_status' => 'Payment status',
                'payment_mode' => 'Payment mode',
                'payment_type' => 'Payment type',
                'remark' => 'Remark',
                'date' => 'Date',
                'voucher_number' => 'Voucher number',
            ],
            default => [],
        };

        return $map[$attribute] ?? ucfirst(str_replace('_', ' ', $attribute));
    }
}

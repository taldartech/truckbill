<?php

namespace App\Services;

use App\Models\FreightBooking;

class FreightBookingCustomAmountService
{
    /**
     * @param  array<int, array{id?: int|null, description: string, amount: float|int|string}>  $rows
     */
    public function sync(FreightBooking $freightBooking, array $rows): float
    {
        $companyId = (int) $freightBooking->company_id;
        $existingIds = $freightBooking->customAmounts()->pluck('id')->all();
        $keptIds = [];

        foreach ($rows as $row) {
            $payload = [
                'description' => (string) $row['description'],
                'amount' => round((float) $row['amount'], 2),
                'company_id' => $companyId,
            ];

            if (! empty($row['id'])) {
                $customAmount = $freightBooking->customAmounts()
                    ->whereKey((int) $row['id'])
                    ->firstOrFail();
                $customAmount->update($payload);
                $keptIds[] = (int) $customAmount->id;
            } else {
                $created = $freightBooking->customAmounts()->create($payload);
                $keptIds[] = (int) $created->id;
            }
        }

        $idsToDelete = array_diff($existingIds, $keptIds);
        if ($idsToDelete !== []) {
            $freightBooking->customAmounts()->whereIn('id', $idsToDelete)->delete();
        }

        $freightBooking->unsetRelation('customAmounts');

        return $freightBooking->customAmountsTotal();
    }
}

<?php

namespace App\Helpers;

class TdsHelper
{
    public static function getTdsPercentage($isOwner, $panVerified)
    {
        $defaultPercentage = env('TDS_PERCENTAGE_DEFAULT', 20) / 100;
        $agencyPercentage = env('TDS_PERCENTAGE_AGENCY', 2) / 100;
        $ownerPercentage = env('TDS_PERCENTAGE_OWNER', 1) / 100;

        if ($panVerified) {
            return $isOwner ? $ownerPercentage : $agencyPercentage;
        }

        return $defaultPercentage;
    }
}

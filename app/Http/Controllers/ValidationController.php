<?php

namespace App\Http\Controllers;

use App\Models\Consignee;
use Illuminate\Http\Request;

class ValidationController extends Controller
{
    /**
     * Check if GST number is available
     */
    public function checkGstNumber(Request $request)
    {
        $gstNumber = strtoupper($request->input('gst_number'));
        $excludeId = $request->input('exclude_id');
        $companyId = auth()->user()->company_id;

        $query = Consignee::where('gst_number', $gstNumber)
                          ->where('company_id', $companyId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $exists = $query->exists();

        return response()->json([
            'available' => !$exists,
            'message' => $exists ? 'GST Number already in use' : 'GST Number is available'
        ]);
    }

    /**
     * Check if PAN number is available
     */
    public function checkPanNumber(Request $request)
    {
        $panNumber = strtoupper($request->input('pan_number'));
        $excludeId = $request->input('exclude_id');
        $companyId = auth()->user()->company_id;

        $query = Consignee::where('pan_number', $panNumber)
                          ->where('company_id', $companyId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $exists = $query->exists();

        return response()->json([
            'available' => !$exists,
            'message' => $exists ? 'PAN Number already in use' : 'PAN Number is available'
        ]);
    }
}

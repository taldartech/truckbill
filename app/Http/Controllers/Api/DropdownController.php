<?php

namespace App\Http\Controllers\Api;

use App\Models\State;
use App\Models\City;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DropdownController extends Controller
{
    //         return response()->json($cities);
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => 'Failed to fetch cities'], 500);
    //     }
    // }
    public function states($countryId)
{
    try {
        $states = State::where('country_id', $countryId)
            ->select('id', 'name') // Only select needed fields
            ->distinct()
            ->orderBy('name')
            ->get();
        return response()->json($states);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to fetch states'], 500);
    }
}

public function cities($stateId)
{
    try {
        $cities = City::where('state_id', $stateId)
            ->select('id', 'name')
            ->distinct()
            ->orderBy('name')
            ->get();
        return response()->json($cities);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to fetch cities'], 500);
    }
}

}

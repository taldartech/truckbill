<?php

namespace App\Http\Controllers\Api;

use App\Models\State;
use App\Models\City;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DropdownController extends Controller
{
    public function states($countryId)
    {
        try {
            $states = State::where('country_id', $countryId)->get();
            return response()->json($states);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch states'], 500);
        }
    }

    public function cities($stateId)
    {
        try {
            $cities = City::where('state_id', $stateId)->get();
            return response()->json($cities);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch cities'], 500);
        }
    }
}

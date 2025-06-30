<?php
namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function show($id)
    {
        $city = City::find($id);
        if (!$city) {
            return response()->json(['error' => 'City not found'], 404);
        }
        return response()->json($city);
    }
}

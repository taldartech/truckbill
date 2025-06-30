<?php
namespace App\Http\Controllers;

use App\Models\State;
use Illuminate\Http\Request;

class StateController extends Controller
{
    public function show($id)
    {
        $state = State::find($id);
        if (!$state) {
            return response()->json(['error' => 'State not found'], 404);
        }
        return response()->json($state);
    }
}

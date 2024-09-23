<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriverController extends Controller
{
    public function updateLocation(Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        
        $driver = Auth::user();

        // Update the driver's location
        $driver->latitude = $validated['latitude'];
        $driver->longitude = $validated['longitude'];
        $driver->save();

        return response()->json([
            'message' => 'Location updated successfully',
            'latitude' => $driver->latitude,
            'longitude' => $driver->longitude,
        ], 200);
    }
}

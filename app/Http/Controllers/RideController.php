<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\RideRequest;

class RideController extends Controller
{
    // Match a ride for the driver based on their location
    public function match(Request $request)
    {
        // Get the authenticated driver (current user)
        $driver = Auth::user();

        // Ensure the driver has a current location
        if (is_null($driver->latitude) || is_null($driver->longitude)) {
            return response()->json(['error' => 'Location not available'], 400);
        }

        // Find nearby ride requests (within 5 km radius)
        $nearbyRides = RideRequest::where('status', 'pending')
            ->whereRaw('ST_Distance_Sphere(
                point(longitude, latitude),
                point(?, ?)
            ) <= ?', [
                $driver->longitude, $driver->latitude, 5000 // 5 km radius
            ])->get();

        // If there are no nearby rides
        if ($nearbyRides->isEmpty()) {
            return response()->json(['message' => 'No nearby rides found'], 404);
        }

        // For simplicity, we'll return the first available ride request
        $ride = $nearbyRides->first();

        return response()->json([
            'message' => 'Ride matched',
            'ride' => $ride
        ], 200);
    }

    // Accept a ride
    public function accept(Request $request)
    {
        $request->validate([
            'ride_id' => 'required|exists:ride_requests,id',
        ]);

        $driver = Auth::user();

        // Fetch the ride request
        $ride = RideRequest::where('id', $request->ride_id)
            ->where('status', 'pending') // Only allow accepting pending rides
            ->first();

        if (!$ride) {
            return response()->json(['error' => 'Ride not found or already assigned'], 404);
        }

        // Assign ride to the driver
        $ride->driver_id = $driver->id; // Assuming `driver_id` exists in ride_requests table
        $ride->status = 'assigned';
        $ride->save();

        return response()->json([
            'message' => 'Ride accepted successfully',
            'ride' => $ride
        ], 200);
    }

    // Reject a ride
    public function reject(Request $request)
    {
        $request->validate([
            'ride_id' => 'required|exists:ride_requests,id',
        ]);

        $ride = RideRequest::where('id', $request->ride_id)
            ->where('status', 'pending') // Only reject pending rides
            ->first();

        if (!$ride) {
            return response()->json(['error' => 'Ride not found or already assigned'], 404);
        }

        // Update the status to rejected
        $ride->status = 'cancelled';
        $ride->save();

        return response()->json([
            'message' => 'Ride rejected successfully',
        ], 200);
    }

    // Start a ride
    public function start(Request $request)
    {
        $request->validate([
            'ride_id' => 'required|exists:ride_requests,id',
            'driver_id' => 'required|exists:users,id', // Optional, if you want to validate
            'status' => 'required|in:started',
        ]);

        $driver = Auth::user();
        
        // Fetch the ride request
        $ride = RideRequest::where('id', $request->ride_id)
            ->where('driver_id', $driver->id) // Ensure the ride is assigned to the driver
            ->first();

        if (!$ride) {
            return response()->json(['error' => 'Ride not found or not assigned to this driver'], 404);
        }

        // Update the ride status to started
        $ride->status = 'started';
        $ride->save();

        return response()->json([
            'message' => 'Ride started successfully',
            'ride' => $ride
        ], 200);
    }

    // Complete a ride
    public function complete(Request $request)
    {
        $request->validate([
            'ride_id' => 'required|exists:ride_requests,id',
            'driver_id' => 'required|exists:users,id', // Optional, if you want to validate
            'status' => 'required|in:completed',
        ]);

        $driver = Auth::user();
        
        // Fetch the ride request
        $ride = RideRequest::where('id', $request->ride_id)
            ->where('driver_id', $driver->id) // Ensure the ride is assigned to the driver
            ->first();

        if (!$ride) {
            return response()->json(['error' => 'Ride not found or not assigned to this driver'], 404);
        }

        // Update the ride status to completed
        $ride->status = 'completed';
        $ride->save();

        return response()->json([
            'message' => 'Ride completed successfully',
            'ride' => $ride
        ], 200);
    }

     // Calculate fare
     public function calculateFare(Request $request)
     {
         $request->validate([
             'ride_id' => 'required|exists:ride_requests,id',
         ]);
 
         // Fetch the ride request
         $ride = RideRequest::find($request->ride_id);
 
         if (!$ride || $ride->status !== 'completed') {
             return response()->json(['error' => 'Ride not found or not completed'], 404);
         }
 
         // Here you can define your fare calculation logic
         $distance = $this->calculateDistance($ride->latitude, $ride->longitude); // Placeholder for actual distance calculation
         $time = $this->estimateTime($ride->latitude, $ride->longitude); // Placeholder for actual time estimation
 
         // Example fare calculation
         $baseFare = 5.00; // Base fare
         $perMileRate = 1.50; // Rate per mile
         $perMinuteRate = 0.50; // Rate per minute
 
         $fare = $baseFare + ($distance * $perMileRate) + ($time * $perMinuteRate);
 
         return response()->json([
             'ride_id' => $ride->id,
             'fare' => round($fare, 2), // Round to two decimal places
         ], 200);
     }
 
     private function calculateDistance($latitude, $longitude)
     {
         // Implement your distance calculation logic here
         // For example, use an external API or a simple formula
         return 10; // Placeholder value (e.g., in miles)
     }
 
     private function estimateTime($latitude, $longitude)
     {
         // Implement your time estimation logic here
         // This could involve traffic data or simply a static calculation
         return 15; // Placeholder value (e.g., in minutes)
     }

     public function updateLocation(Request $request)
    {
        $request->validate([
            'ride_id' => 'required|exists:ride_requests,id',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        // Fetch the ride request
        $ride = RideRequest::find($request->ride_id);

        if (!$ride || $ride->status !== 'started') {
            return response()->json(['error' => 'Ride not found or not started'], 404);
        }

        // Update the location
        $ride->latitude = $request->latitude;
        $ride->longitude = $request->longitude;
        $ride->save();

        // Optionally, broadcast the new location to the rider and admin
        // You could use Laravel broadcasting for this
        // event(new LocationUpdated($ride));

        return response()->json([
            'message' => 'Location updated successfully',
            'ride' => $ride
        ], 200);
    }
}

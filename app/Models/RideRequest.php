<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RideRequest extends Model
{
    use HasFactory;

    // Fields that can be mass-assigned
    protected $fillable = ['user_id', 'latitude', 'longitude', 'status', 'driver_id'];

    // Relationship to the user who requested the ride from the separate user app
    public function user()
    {
        return $this->belongsTo(User::class); // You can adjust this if users are managed externally
    }

    // Relationship to the driver assigned to the ride from the driver app
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}



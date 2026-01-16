<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DoctorAvailability;
use App\Http\Resources\DoctorAvailabilityResource;

class DoctorAvailabilityController extends Controller
{
    public function index($id)
    {
        $availabilities = DoctorAvailability::where('doctor_id', $id)->get();
        return DoctorAvailabilityResource::collection($availabilities);
    }
    
}

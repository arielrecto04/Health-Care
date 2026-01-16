<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\DoctorAvailability;
use App\Http\Resources\DoctorAvailabilityResource;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    public function mySchedule()
    {
        $doctor = Auth::user()->profile->doctor;

        if (!$doctor) {
            return response()->json(['message' => 'Doctor profile not found.'], 404);
        }

        $availability = $doctor->availabilities()
        ->orderByRaw("CASE day_of_week
            WHEN 'monday' THEN 1
            WHEN 'tuesday' THEN 2
            WHEN 'wednesday' THEN 3
            WHEN 'thursday' THEN 4
            WHEN 'friday' THEN 5
            WHEN 'saturday' THEN 6
            WHEN 'sunday' THEN 7
        END")
        ->orderBy('start_time')
        ->get();

        return DoctorAvailabilityResource::collection($availability);
    }

    public function store(Request $request)
{
    if ($request->boolean('clear')) {
    $doctor = Auth::user()->profile->doctor;

    if (!$doctor) {
        return response()->json(['message' => 'Doctor profile not found'], 404);
    }

    $doctor->availabilities()->delete();

    return response()->json([
        'message' => 'Schedule cleared successfully',
    ]);
}

    $request->validate([
    'schedules' => 'required|array|min:1',
    'schedules.*.selectedDays' => 'required|array|min:1',
    'schedules.*.selectedDays.*.name' => 'required|string',
    'schedules.*.timeRanges' => 'required|array|min:1',
    'schedules.*.timeRanges.*.start_time' => 'required|date_format:H:i',
    'schedules.*.timeRanges.*.end_time' => 'required|date_format:H:i|after:schedules.*.timeRanges.*.start_time',
]);


    $doctor = Auth::user()->profile->doctor;

    if (!$doctor) {
        return response()->json(['message' => 'Doctor profile not found'], 404);
    }

    // Clear old schedule
    $doctor->availabilities()->delete();

    foreach ($request->schedules as $schedule) {
        foreach ($schedule['selectedDays'] as $dayObj) {
            $day = strtolower($dayObj['name']);

            foreach ($schedule['timeRanges'] as $range) {

                DoctorAvailability::create([
                    'doctor_id'   => $doctor->id,
                    'day_of_week' => strtolower($day),
                    'start_time'  => $range['start_time'] . ':00',
                    'end_time'    => $range['end_time'] . ':00',
                ]);

            }
        }
    }

    return response()->json(['message' => 'Schedule saved successfully']);
}
}
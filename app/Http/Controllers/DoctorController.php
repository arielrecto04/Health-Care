<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Doctor;
use App\Http\Resources\DoctorResource;
use App\Models\DoctorSpecialty;
use App\Models\Hmo;

class DoctorController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->input('limit', 10);
        $doctors = Doctor::with(['profile', 'specialty'])->paginate($limit);
        return DoctorResource::collection($doctors);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'doctor_specialty_id' => 'nullable|integer|exists:doctor_specialties,id',
            'license_number' => 'nullable|string|max:50',
            'room_number' => 'nullable|string|max:50',
            'clinic_phone_number' => 'nullable|string|max:50',
            'doctor_note' => 'nullable|string',
        ]);

        $user->update([
            'email' => $validated['email'],
        ]);
        
        $user->profile->update([
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'],
            'last_name' => $validated['last_name'],
            'contact_email' => $validated['email'],
        ]);

        $user->profile->doctor->update([
            'doctor_specialty_id' => $validated['doctor_specialty_id'],
            'license_number' => $validated['license_number'],
            'room_number' => $validated['room_number'],
            'clinic_phone_number' => $validated['clinic_phone_number'],
            'doctor_note' => $validated['doctor_note'],
        ]);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user->load('profile.doctor.specialty'),
        ]);
    }

    public function getSpecialties()
    {
        $specialties = DoctorSpecialty::all(['id', 'name']);
        return response()->json($specialties);
    }

    public function getHmos()
    {
        $hmos = Hmo::all(['id', 'name']); // select id and name
        return response()->json($hmos);
    }

}

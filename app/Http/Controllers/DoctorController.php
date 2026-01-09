<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Doctor;
use App\Http\Resources\DoctorResource;
use App\Models\DoctorSpecialty;
use App\Models\Hmo;
use Illuminate\Support\Facades\DB;

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

    public function search(Request $request)
    {
        try {
            $name = trim((string) $request->input('name', ''));
            $specialty = $request->input('specialty');
            $hmo = $request->input('hmo');
            // normalize incoming days to lowercase to match stored values like 'monday'
            $days = array_map('strtolower', (array) $request->input('days', []));
            $time = $request->input('time'); // 'AM' or 'PM'

            $query = Doctor::with(['profile', 'specialty', 'availabilities', 'hmos']);

            if ($name) {
                // prepare escaped, lowercase needle to avoid wildcard injection and ensure case-insensitive matching
                $needle = strtolower($name);
                $needle = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $needle);
                $parts = preg_split('/\s+/', $needle, -1, PREG_SPLIT_NO_EMPTY);

                $query->whereHas('profile', function ($q) use ($needle, $parts) {
                    // If there are at least two parts, attempt to match first/last combinations
                    if (count($parts) >= 2) {
                        $first = $parts[0];
                        $last = $parts[1];

                        $q->where(function($qq) use ($first, $last) {
                            $qq->whereRaw('LOWER(first_name) LIKE ?', ["%{$first}%"])
                               ->whereRaw('LOWER(last_name) LIKE ?', ["%{$last}%"]);
                        })->orWhere(function($qq) use ($first, $last) {
                            $qq->whereRaw('LOWER(first_name) LIKE ?', ["%{$last}%"])
                               ->whereRaw('LOWER(last_name) LIKE ?', ["%{$first}%"]);
                        });
                    }

                    // fallback: match either first or last by the full needle and combinations
                    $q->orWhereRaw('LOWER(first_name) LIKE ?', ["%{$needle}%"])
                      ->orWhereRaw('LOWER(last_name) LIKE ?', ["%{$needle}%"])
                      ->orWhereRaw("LOWER(first_name || ' ' || last_name) LIKE ?", ["%{$needle}%"]) 
                      ->orWhereRaw("LOWER(last_name || ' ' || first_name) LIKE ?", ["%{$needle}%"]);
                });
            }

            if ($specialty) {
                $query->where('doctor_specialty_id', $specialty);
            }

            if ($hmo) {
                $query->whereHas('hmos', function ($q) use ($hmo) {
                    $q->where('hmos.id', $hmo);
                });
            }

            if (!empty($days)) {
                $query->whereHas('availabilities', function ($q) use ($days) {
                    $q->whereIn('day_of_week', $days);
                });
            }

            if ($time) {
                if (strtoupper($time) === 'AM') {
                    $query->whereHas('availabilities', function ($q) {
                        $q->whereBetween('start_time', ['00:00:00', '11:59:59']);
                    });
                } else {
                    $query->whereHas('availabilities', function ($q) {
                        $q->whereBetween('start_time', ['12:00:00', '23:59:59']);
                    });
                }
            }

            $doctors = $query->get();

            // If debug flag is present, return structured JSON for inspection
            if ($request->boolean('debug')) {
                return response()->json([
                    'count' => $doctors->count(),
                    'doctors' => $doctors->map(function($d){
                        return [
                            'id' => $d->id,
                            'name' => ($d->profile->first_name ?? '') . ' ' . ($d->profile->last_name ?? ''),
                            'specialty' => $d->specialty->name ?? null,
                            'hmos' => $d->hmos->pluck('name'),
                            'availabilities' => $d->availabilities->map(function($a){ return ['day' => $a->day_of_week, 'start' => $a->start_time, 'end' => $a->end_time]; }),
                        ];
                    })
                ]);
            }

            $html = '';
            foreach ($doctors as $doctor) {
                $html .= view('components.card.doctor-2', ['doctor' => $doctor])->render();
            }

            return response()->json([
                'html' => $html,
                'count' => $doctors->count(),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Doctor search error', ['message' => $e->getMessage(), 'exception' => (string) $e]);
            return response()->json(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
        }
    }

}

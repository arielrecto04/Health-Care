<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => ucfirst($this->role),
        ];

        if ($this->relationLoaded('profile') && $this->profile) {
            $data['first_name'] = $this->profile->first_name;
            $data['middle_name'] = $this->profile->middle_name;
            $data['last_name'] = $this->profile->last_name;
            $data['date_of_birth'] = $this->profile->date_of_birth;
            $data['gender'] = $this->profile->gender;
            $data['phone'] = $this->profile->phone;
            $data['contact_email'] = $this->profile->contact_email;
            $data['profile_picture_url'] = $this->profile->profile_picture_url;


            if ($this->profile->relationLoaded('doctor') && $this->profile->doctor) {
                $data['doctor'] = [
                    'id' => $this->profile->doctor->id,
                    'specialty' => [
                        'id' => $this->profile->doctor->specialty?->id,
                        'name' => $this->profile->doctor->specialty?->name,
                    ],
                    'license_number' => $this->profile->doctor->license_number,
                    'room_number' => $this->profile->doctor->room_number,
                    'clinic_phone_number' => $this->profile->doctor->clinic_phone_number,
                    'doctor_note' => $this->profile->doctor->doctor_note,
                ];
            }

            if ($this->profile->relationLoaded('staff') && $this->profile->staff) {
                $data['staff'] = [
                    'id' => $this->profile->staff->id,
                ];
            }

            if ($this->profile->relationLoaded('patient') && $this->profile->patient) {
                $data['patient'] = [
                    'id' => $this->profile->patient->id,
                    'medical_history' => $this->profile->patient->medical_history,
                ];
            }
        }
        return $data;
    }
}

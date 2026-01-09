<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
             [
                 'email' => 'admin@admin.com',
                 'password' => bcrypt('testtest'),
                 'role' => 'admin',
                 'profile' => [
                     'first_name' => 'Admin',
                     'middle_name' => 'Admin',
                     'last_name' => 'One',
                     'date_of_birth' => '2000-01-01',
                     'gender' => 'male',
                     'phone_number' => '(+63) 123 123 1231',
                     'contact_email' => 'admin@admin.com',
                 ],
             ],
             [
                 'email' => 'doctor@doctor.com',
                 'password' => bcrypt('testtest'),
                 'role' => 'doctor',
                 'profile' => [
                     'first_name' => 'Doctor',
                     'middle_name' => 'Doctor',
                     'last_name' => 'One',
                     'date_of_birth' => '2001-05-10',
                     'gender' => 'male',
                     'phone_number' => '(+63) 987 654 3210',
                     'contact_email' => 'doctor@doctor.com',
                 ],
             ],
             [
                 'email' => 'patient@patient.com',
                 'password' => bcrypt('testtest'),
                 'role' => 'patient',
                 'profile' => [
                     'first_name' => 'Patient',
                     'middle_name' => 'Patient',
                     'last_name' => 'One',
                     'date_of_birth' => '1999-09-20',
                     'gender' => 'female',
                     'phone_number' => '(+63) 912 345 6789',
                     'contact_email' => 'patient@patient.com',
                 ],
             ],
            [
             'email' => 'staff@staff.com',
                 'password' => bcrypt('testtest'),
                 'role' => 'staff',
                 'profile' => [
                     'first_name' => 'Staff',
                     'middle_name' => 'Staff',
                     'last_name' => 'One',
                     'date_of_birth' => '1999-09-20',
                     'gender' => 'female',
                     'phone_number' => '(+63) 912 345 6789',
                     'contact_email' => 'staff@staff.com',
                 ],
             ],
         ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'password' => $data['password'],
                    'role' => $data['role'],
                ]
            );

            $profile = $user->profile()->firstOrCreate(
                ['user_id' => $user->id],
                $data['profile']
            );

            if ($user->role === 'doctor') {
                $profile->doctor()->firstOrCreate([
                    'profile_id' => $profile->id,
                ]);
            }

            if ($user->role === 'patient') {
                $profile->patient()->firstOrCreate([
                    'profile_id' => $profile->id,
                ]);
            }

            if ($user->role === 'staff') {
                $profile->staff()->firstOrCreate([
                    'profile_id' => $profile->id,
                ]);
            }
        }

    }
}

<?php

use Illuminate\Http\Request;
use App\Models\DoctorAvailability;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HmoController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\InsuranceController;
use App\Http\Controllers\DoctorSpecialtyController;
use App\Http\Controllers\DoctorAvailabilityController;

Route::get('/services', [ServiceController::class, 'index']);
Route::get('/hmos', [HmoController::class, 'index']);
Route::get('/insurances', [InsuranceController::class, 'index']);
Route::get('/doctors', [DoctorController::class, 'index']);
Route::get('/doctor/specialties', [DoctorSpecialtyController::class, 'index']);
Route::get('doctor/{id}/availabilities', [DoctorAvailabilityController::class, 'index']);
Route::get('/staffs', [StaffController::class, 'index']);
Route::get('/services', [ServiceController::class, 'apiIndex']);


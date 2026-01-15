<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ServiceController;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/about', function () {
    return view('about');
})->name('about');

Route::get('/services', [ServiceController::class, 'index'])->name('services');

Route::get('/doctor', function () {
    return view('doctor');
})->name('doctor');

Route::get('/doctor/specialties', [DoctorController::class, 'getSpecialties']);
Route::get('/doctor/hmos', [DoctorController::class, 'getHmos']);
Route::post('/doctor/search', [DoctorController::class, 'search']);
Route::get('/doctors/specialties', [DoctorController::class, 'getSpecialties']);
Route::get('/doctors/hmos', [DoctorController::class, 'getHmos']);
Route::post('/doctors/search-json', [DoctorController::class, 'searchJson']); 


Route::get('/contact', function () {
    return view('contact');
})->name('contact');

Route::get('/login', function () {
    if (Auth::check()) {
        return redirect()->route('portal');
    }
    return view('login');
})->name('login');

Route::get('/register', function () {
    if (Auth::check()) {
        return redirect()->route('portal');
    }
    return view('register');
})->name('register');

Route::middleware(['auth'])->prefix('portal')->group(function () {
    Route::get('/{any?}', function () {
        return view('app');
    })->where('any', '.*')->name('portal');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/user', [UserController::class, 'currentUser']);
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
    Route::post('/doctor/schedule', [ScheduleController::class, 'store']);
    Route::get('/doctor/schedule', [ScheduleController::class, 'mySchedule']);
    Route::put('/doctor/profile', [DoctorController::class, 'update']);
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::get('/appointments', [AppointmentController::class, 'myAppointments']);
    Route::get('/available-time-slots', [AppointmentController::class, 'getAvailableTimes']);
    Route::get('/conversation', [ConversationController::class, 'findConversation']);
    Route::post('/conversation', [ConversationController::class, 'store']);
    Route::get('/my-conversation', [ConversationController::class, 'myConversation']);
    Route::get('/conversations', [ConversationController::class, 'myConversations']);
    Route::get('/patients/search', [PatientController::class, 'search']);
    Route::post('/prescriptions', [PrescriptionController::class, 'store']);
    Route::get('/my-prescriptions', [PrescriptionController::class, 'myPrescriptions']);
    Route::get('/prescriptions/{prescription}/pdf', [PrescriptionController::class, 'pdf']);
});

Route::get('/force-logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
});

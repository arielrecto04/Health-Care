<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServiceCategory;
use App\Models\User;

class HomeController extends Controller
{
 public function index()
    {
        // Get all service categories
        $service_categories = ServiceCategory::all();

        // Get all doctors (including profile and specialty)
        $doctors = User::with('profile', 'doctor.specialty')->where('role', 'doctor')->take(4)->get();

        // Return the home view and pass the data
        return view('home', compact('service_categories', 'doctors'));
    }
}

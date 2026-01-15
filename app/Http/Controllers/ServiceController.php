<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServiceCategory;
use App\Http\Resources\ServiceCategoryResource;

class ServiceController extends Controller
{
    public function index()
    {
        $categories = ServiceCategory::with('services')->get();

        return view('services', compact('categories'));

    }

    public function apiIndex()
    {
        $categories = ServiceCategory::with('services')->get();
        return response()->json(['data' => $categories]);
    }

}

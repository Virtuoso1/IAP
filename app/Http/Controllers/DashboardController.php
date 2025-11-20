<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        // For now, we will just pass empty arrays to avoid null errors
        $helpers = [];
        $overwhelmed = [];

        return view('dashboard.index', compact('helpers', 'overwhelmed'));
    }
}

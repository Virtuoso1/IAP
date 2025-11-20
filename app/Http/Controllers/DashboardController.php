<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Fake logged-in user for testing
        $user = (object)[
            'name' => 'John Doe',
            'student_type' => 'overwhelmed', // change to 'helper' to test the other view
        ];

        // Fake users data
        $helpers = [
            (object)['name' => 'Alice Helper'],
            (object)['name' => 'Bob Helper'],
        ];

        $overwhelmed = [
            (object)['name' => 'Charlie Overwhelmed'],
            (object)['name' => 'Diana Overwhelmed'],
        ];

        return view('dashboard.index', compact('user', 'helpers', 'overwhelmed'));
    }
}

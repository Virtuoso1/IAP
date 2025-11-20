<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        // Dummy list of users
        $users = [
            (object)['name' => 'John Doe', 'student_type' => 'overwhelmed'],
            (object)['name' => 'Alice Helper', 'student_type' => 'helper'],
            (object)['name' => 'Charlie Overwhelmed', 'student_type' => 'overwhelmed'],
        ];

        return view('admin.index', compact('users'));
    }
}

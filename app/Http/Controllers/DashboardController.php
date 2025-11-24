<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $groups = \App\Models\Group::with('members')->get();
        $myGroups = $groups->filter(fn($group) => $group->members->contains(auth()->id()));
        $joinableGroups = $groups->filter(fn($group) => !$group->members->contains(auth()->id()));
        return view('dashboard', compact('groups', 'myGroups', 'joinableGroups'));
    }
}

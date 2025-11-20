@extends('layouts.app')

@section('content')
<div class="container mx-auto mt-10">
    <h2 class="text-2xl font-bold mb-6">Admin Panel</h2>

    <p class="mb-4 font-semibold">List of all students:</p>

    <table class="min-w-full bg-white border">
        <thead>
            <tr class="bg-gray-200">
                <th class="py-2 px-4 border">Name</th>
                <th class="py-2 px-4 border">Type</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
                <tr>
                    <td class="py-2 px-4 border">{{ $user->name }}</td>
                    <td class="py-2 px-4 border">{{ $user->student_type }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
